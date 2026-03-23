<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\ValueAbstracts;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SimpleSAML\OpenID\ValueAbstracts\KeyPairFilenameConfig;

#[CoversClass(KeyPairFilenameConfig::class)]
final class KeyPairFilenameConfigTest extends TestCase
{
    private string $tempFile1;

    private string $tempFile2;

    private string $emptyFile;


    protected function setUp(): void
    {
        $this->tempFile1 = tempnam(sys_get_temp_dir(), 'test_key1_');
        $this->tempFile2 = tempnam(sys_get_temp_dir(), 'test_key2_');
        $this->emptyFile = tempnam(sys_get_temp_dir(), 'test_empty_');

        file_put_contents($this->tempFile1, 'private-key-content');
        file_put_contents($this->tempFile2, 'public-key-content');
        file_put_contents($this->emptyFile, '');
    }


    protected function tearDown(): void
    {
        if (file_exists($this->tempFile1)) {
            unlink($this->tempFile1);
        }

        if (file_exists($this->tempFile2)) {
            unlink($this->tempFile2);
        }

        if (file_exists($this->emptyFile)) {
            unlink($this->emptyFile);
        }
    }


    public function testGettersReturnInjectedValues(): void
    {
        $config = new KeyPairFilenameConfig(
            $this->tempFile1,
            $this->tempFile2,
            'password123',
            'kid456',
        );

        $this->assertSame($this->tempFile1, $config->getPrivateKeyFilename());
        $this->assertSame($this->tempFile2, $config->getPublicKeyFilename());
        $this->assertSame('password123', $config->getPrivateKeyPassword());
        $this->assertSame('kid456', $config->getKeyId());
    }


    public function testGettersReturnNullWhenNotProvided(): void
    {
        $config = new KeyPairFilenameConfig(
            $this->tempFile1,
            $this->tempFile2,
        );

        $this->assertNull($config->getPrivateKeyPassword());
        $this->assertNull($config->getKeyId());
    }


    public function testGetPrivateKeyStringReturnsFileContent(): void
    {
        $config = new KeyPairFilenameConfig($this->tempFile1, $this->tempFile2);
        $this->assertSame('private-key-content', $config->getPrivateKeyString());
    }


    public function testGetPublicKeyStringReturnsFileContent(): void
    {
        $config = new KeyPairFilenameConfig($this->tempFile1, $this->tempFile2);
        $this->assertSame('public-key-content', $config->getPublicKeyString());
    }


    public function testGetPrivateKeyStringThrowsExceptionWhenFileDoesNotExist(): void
    {
        $nonExistentFile = sys_get_temp_dir() . '/non_existent_file_' . uniqid();
        $config = new KeyPairFilenameConfig($nonExistentFile, $this->tempFile2);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('File %s does not exist.', $nonExistentFile));
        $config->getPrivateKeyString();
    }


    public function testGetPublicKeyStringThrowsExceptionWhenFileDoesNotExist(): void
    {
        $nonExistentFile = sys_get_temp_dir() . '/non_existent_file_' . uniqid();
        $config = new KeyPairFilenameConfig($this->tempFile1, $nonExistentFile);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('File %s does not exist.', $nonExistentFile));
        $config->getPublicKeyString();
    }


    public function testGetPrivateKeyStringThrowsExceptionWhenFileIsEmpty(): void
    {
        $config = new KeyPairFilenameConfig($this->emptyFile, $this->tempFile2);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('File %s is empty.', $this->emptyFile));
        $config->getPrivateKeyString();
    }


    public function testGetPublicKeyStringThrowsExceptionWhenFileIsEmpty(): void
    {
        $config = new KeyPairFilenameConfig($this->tempFile1, $this->emptyFile);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('File %s is empty.', $this->emptyFile));
        $config->getPublicKeyString();
    }


    public function testGetPrivateKeyStringThrowsExceptionWhenFileCannotBeRead(): void
    {
        $unreadableFile = sys_get_temp_dir() . '/test_unreadable_' . uniqid();
        touch($unreadableFile);
        chmod($unreadableFile, 0000);

        $config = new KeyPairFilenameConfig($unreadableFile, $this->tempFile2);

        try {
            $this->expectException(RuntimeException::class);
            $config->getPrivateKeyString();
        } finally {
            chmod($unreadableFile, 0666);
            unlink($unreadableFile);
        }
    }
}
