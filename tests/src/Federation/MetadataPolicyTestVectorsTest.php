<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\EntityTypesEnum;
use SimpleSAML\OpenID\Codebooks\MetadataPolicyOperatorsEnum;
use SimpleSAML\OpenID\Federation\MetadataPolicyApplicator;
use SimpleSAML\OpenID\Federation\MetadataPolicyResolver;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\Test\OpenID\Help;

/**
 * Test policy and metadata resolving using test vectors JSON from
 * https://connect2id.com/blog/metadata-policy-test-vectors-openid-federation
 */
#[CoversClass(MetadataPolicyResolver::class)]
#[CoversClass(MetadataPolicyApplicator::class)]
#[UsesClass(MetadataPolicyOperatorsEnum::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Arr::class)]
final class MetadataPolicyTestVectorsTest extends TestCase
{
    protected static array $testVectors;

    protected static MetadataPolicyResolver $metadataPolicyResolver;

    protected static MetadataPolicyApplicator $metadataPolicyApplicator;


    public static function setUpBeforeClass(): void
    {
        self::$testVectors = json_decode(
            file_get_contents(
                (new Help())->getTestDataDir('metadata-policy-test-vectors-2025-02-13.json'),
            ),
            true,
        );

        $helpers = new Helpers();
        self::$metadataPolicyResolver = new MetadataPolicyResolver($helpers);
        self::$metadataPolicyApplicator = new MetadataPolicyApplicator($helpers);
    }


    public function testCanLoadTestVectors(): void
    {
        $this->assertNotEmpty(self::$testVectors);
    }


    public function testConformsToAllTestVectors(): void
    {
        // Just so we can act as if we are resolving metadata for specific entity type. Not really important.
        $sampleEntityType = EntityTypesEnum::FederationEntity;
        foreach (self::$testVectors as $testVector) {
            //if ($testVector['n'] !== 13) { continue;}


            $trustAnchorPolicy = $testVector['TA'] ?? throw new \Exception('No Trust Anchor policy.');
            $intermediatePolicy = $testVector['INT'] ?? throw new \Exception('No intermediate policy.');

            $trustAnchorPolicy = [$sampleEntityType->value => $trustAnchorPolicy];
            $intermediatePolicy = [$sampleEntityType->value => $intermediatePolicy];

            self::$metadataPolicyResolver->ensureFormat($trustAnchorPolicy);
            self::$metadataPolicyResolver->ensureFormat($intermediatePolicy);

            $policies = [
                $trustAnchorPolicy,
                $intermediatePolicy,
            ];

            // Errors can be invalid_policy or invalid_metadata. We throw MetadataPolicyException in any case.
            $error = $testVector['error'] ?? null;

            // Handle policy resolving
            try {
                $expectInvalidPolicyError = $error === 'invalid_policy';
                $resolvedPolicy = self::$metadataPolicyResolver->for($sampleEntityType, $policies);
            } catch (\Throwable $exception) {
                if ($expectInvalidPolicyError) {
                    // Error is expected, so we can move on.
                    continue;
                }

                throw new \Exception(
                    'Unexpected policy resolving error: ' . $exception->getMessage(),
                    $exception->getCode(),
                    $exception,
                );
            }

            $expectedMergedPolicy = $testVector['merged'] ?? null;

            if (is_null($expectedMergedPolicy)) {
                throw new \Exception(sprintf(
                    'Policy was not expected to be resolved for test: %s. Resolved policy: %s',
                    var_export($testVector, true),
                    var_export($resolvedPolicy, true),
                ));
            }

            $this->assertEquals($expectedMergedPolicy, $resolvedPolicy);

            // Handle metadata resolving.
            $inputMetadata = $testVector['metadata'] ?? throw new \Exception('No input metadata.');
            try {
                $expectInvalidMetadataError = $error === 'invalid_metadata';
                $resolvedMetadata = self::$metadataPolicyApplicator->for(
                    $resolvedPolicy,
                    $inputMetadata,
                );
            } catch (\Throwable $exception) {
                if ($expectInvalidMetadataError) {
                    // Error is expected, so we can move on.
                    continue;
                }

                throw new \Exception(
                    'Unexpected metadata resolving error: ' . $exception->getMessage(),
                    $exception->getCode(),
                    $exception,
                );
            }

            $expectedResolvedMetadata = $testVector['resolved'] ?? null;

            if (is_null($expectedResolvedMetadata)) {
                throw new \Exception(sprintf(
                    'Metadata was not expected to be resolved for test: %s. Resolved metadata: %s',
                    var_export($testVector, true),
                    var_export($resolvedMetadata, true),
                ));
            }

            $this->assertEquals(
                $expectedResolvedMetadata,
                $resolvedMetadata,
                var_export($resolvedPolicy, true) .
                var_export($resolvedMetadata, true) .
                var_export($testVector, true),
            );
        }
    }
}
