<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials;

use SimpleSAML\OpenID\Exceptions\ClaimsPathPointerException;
use SimpleSAML\OpenID\Helpers;

/**
 * @see https://openid.net/specs/openid-4-verifiable-credential-issuance-1_0.html#claims_path_pointer
 */
class ClaimsPathPointerResolver
{
    public function __construct(
        protected readonly Helpers $helpers,
    ) {
    }


    /**
     * @see https://openid.net/specs/openid-4-verifiable-credential-issuance-1_0.html#name-processing
     *
     * @param mixed[] $data
     * @param non-empty-array<mixed> $path
     * @return mixed[]
     * @throws \SimpleSAML\OpenID\Exceptions\ClaimsPathPointerException
     */
    public function forJsonBased(array $data, array $path): array
    {
        // Start with the root element as the only “selected” element
        $selected = [$data];

        foreach ($path as $pathComponent) {
            $nextSelected = [];

            // Process each currently selected element
            foreach ($selected as $selectedElement) {
                if (is_string($pathComponent)) {
                    // String -> object key
                    if (
                        (!is_array($selectedElement)) ||
                        (!$this->helpers->arr()->isAssociative($selectedElement))
                    ) {
                        throw new ClaimsPathPointerException('Expected object for string path component.');
                    }

                    if (array_key_exists($pathComponent, $selectedElement)) {
                        $nextSelected[] = $selectedElement[$pathComponent];
                    }

                    // else: drop this element
                } elseif (is_null($pathComponent)) {
                    // Null -> all elements of an array
                    if (
                        (!is_array($selectedElement)) ||
                        $this->helpers->arr()->isAssociative($selectedElement)
                    ) {
                        throw new ClaimsPathPointerException('Expected array for null path component.');
                    }

                    foreach ($selectedElement as $item) {
                        $nextSelected[] = $item;
                    }
                } elseif (is_int($pathComponent) && $pathComponent >= 0) {
                    // Integer → array index
                    if (
                        (!is_array($selectedElement)) ||
                        $this->helpers->arr()->isAssociative($selectedElement)
                    ) {
                        throw new ClaimsPathPointerException('Expected array for integer path component.');
                    }

                    if (array_key_exists($pathComponent, $selectedElement)) {
                        $nextSelected[] = $selectedElement[$pathComponent];
                    }

                    // else: drop this element
                } else {
                    throw new ClaimsPathPointerException(
                        'Path component must be string, null, or non-negative integer.',
                    );
                }
            }

            // If nothing remains, error out
            if ($nextSelected === []) {
                throw new ClaimsPathPointerException('No elements selected at path component');
            }

            $selected = $nextSelected;
        }

        // The final result is the set of selected JSON elements.
        return $selected;
    }
}
