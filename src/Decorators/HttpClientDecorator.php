<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Decorators;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Exceptions\HttpException;
use SimpleSAML\OpenID\Utils\SizeLimitedStream;
use Throwable;

/**
 * @see \SimpleSAML\Test\OpenID\Decorators\HttpClientDecoratorTest
 */
class HttpClientDecorator
{
    public const DEFAULT_HTTP_CLIENT_CONFIG = [
        RequestOptions::ALLOW_REDIRECTS => true,
        RequestOptions::CONNECT_TIMEOUT => 3,
        RequestOptions::TIMEOUT => 10,
        RequestOptions::HTTP_ERRORS => true,
    ];

    public const DEFAULT_MAX_FETCH_SIZE_BYTES = 102400;

    protected const BODY_READ_CHUNK_SIZE_BYTES = 8192;


    protected int $maxFetchSizeBytes;


    public function __construct(
        public readonly Client $client = new Client(self::DEFAULT_HTTP_CLIENT_CONFIG),
        int $maxFetchSizeBytes = self::DEFAULT_MAX_FETCH_SIZE_BYTES,
    ) {
        $this->maxFetchSizeBytes = max(1, $maxFetchSizeBytes);
    }


    public function getMaxFetchSizeBytes(): int
    {
        return $this->maxFetchSizeBytes;
    }


    /**
     * One sink is reused for every hop of a redirect chain, while on_headers fires once per response. It is
     * therefore the handler independent signal for where one response body ends and the next one begins.
     *
     * @param ?callable $callerOnHeaders Callback supplied by the caller, which still has to run.
     */
    protected function buildOnHeadersCallback(SizeLimitedStream $sink, ?callable $callerOnHeaders): callable
    {
        // Variadic on purpose: the handlers call this with ($response) or ($response, $request) depending on
        // version and handler, and the caller's own callback has to receive whatever it declared.
        return function (mixed ...$arguments) use ($sink, $callerOnHeaders): void {
            $sink->startNewResponse();

            if (!is_null($callerOnHeaders)) {
                $callerOnHeaders(...$arguments);
            }
        };
    }


    /**
     * @param array<string, mixed> $options See https://docs.guzzlephp.org/en/stable/request-options.html
     * @param ?int $maxSizeBytes Overrides the configured maximum response body size for this single request.
     * @throws \SimpleSAML\OpenID\Exceptions\HttpException
     */
    public function request(
        HttpMethodsEnum $httpMethodsEnum,
        string $uri,
        array $options = [],
        ?int $maxSizeBytes = null,
    ): ResponseInterface {
        $maxSizeBytes = is_null($maxSizeBytes) ? $this->maxFetchSizeBytes : max(1, $maxSizeBytes);

        // Bound the response body by giving the client a sink that stops accepting data once the limit is
        // crossed, which aborts the transfer instead of letting the whole body arrive first. A caller supplying
        // its own sink is left alone, since it has taken over responsibility for where the body goes.
        $sizeLimitedSink = isset($options[RequestOptions::SINK]) ?
        null :
        new SizeLimitedStream($maxSizeBytes);

        if ($sizeLimitedSink instanceof SizeLimitedStream) {
            /** @var ?callable $callerOnHeaders */
            $callerOnHeaders = $options[RequestOptions::ON_HEADERS] ?? null;

            $options = array_merge($options, [
                RequestOptions::SINK => $sizeLimitedSink,
                // Streaming mode bypasses the sink entirely, which would leave the body unbounded. The body is
                // read into a string further down regardless, so there is nothing to gain from streaming here.
                RequestOptions::STREAM => false,
                RequestOptions::ON_HEADERS => $this->buildOnHeadersCallback($sizeLimitedSink, $callerOnHeaders),
            ]);
        }

        try {
            /** @phpstan-ignore argument.type */
            $response = $this->client->request($httpMethodsEnum->value, $uri, $options);
        } catch (Throwable $throwable) {
            // The handlers report an aborted transfer as a generic failure, so name the actual cause.
            if ($sizeLimitedSink instanceof SizeLimitedStream && $sizeLimitedSink->hasExceededLimit()) {
                throw new HttpException(
                    sprintf(
                        'Response body from %s exceeded the limit of %s bytes.',
                        $uri,
                        $maxSizeBytes,
                    ),
                    (int)$throwable->getCode(),
                    $throwable,
                );
            }

            $message = sprintf(
                'Error sending HTTP request to %s. Error was: %s',
                $uri,
                $throwable->getMessage(),
            );
            throw new HttpException($message, (int)$throwable->getCode(), $throwable);
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
            $message = sprintf(
                'Unexpected HTTP response for URI %s. Status code: %s, reason: %s.',
                $uri,
                $response->getStatusCode(),
                $response->getReasonPhrase(),
            );
            throw new HttpException($message);
        }

        return $response;
    }


    /**
     * Read a response body into a string, stopping as soon as the maximum allowed size is exceeded.
     *
     * The transfer itself is already bounded by the sink installed in request(). This is the backstop for
     * responses that did not come from there, such as a body produced by a caller supplied sink or by a
     * pre-built client used directly.
     *
     * @param ?int $maxSizeBytes Overrides the configured maximum for this single read.
     * @throws \SimpleSAML\OpenID\Exceptions\HttpException
     */
    public function readResponseBodyAsString(ResponseInterface $response, ?int $maxSizeBytes = null): string
    {
        $maxSizeBytes = is_null($maxSizeBytes) ? $this->maxFetchSizeBytes : max(1, $maxSizeBytes);

        $body = $response->getBody();
        $content = '';

        try {
            // Some handlers hand back a body whose cursor has already been moved to the end (Guzzle's own
            // MockHandler does this when a sink is set), which would otherwise read as an empty body.
            if ($body->isSeekable()) {
                $body->rewind();
            }

            while (!$body->eof()) {
                $chunk = $body->read(self::BODY_READ_CHUNK_SIZE_BYTES);

                // A blocking stream only returns an empty chunk once it is spent, so stop instead of spinning.
                if ($chunk === '') {
                    break;
                }

                $content .= $chunk;

                if (strlen($content) > $maxSizeBytes) {
                    throw new HttpException(
                        sprintf('Response body size exceeded the limit of %s bytes.', $maxSizeBytes),
                    );
                }
            }
        } catch (HttpException $httpException) {
            throw $httpException;
        } catch (Throwable $throwable) {
            // A body that fails mid read (a reset or timed out connection) has to stay within the error
            // contract of this class, so that callers catching HttpException keep working.
            throw new HttpException(
                'Error reading HTTP response body. Error was: ' . $throwable->getMessage(),
                (int)$throwable->getCode(),
                $throwable,
            );
        }

        return $content;
    }
}
