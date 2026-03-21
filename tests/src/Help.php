<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID;

class Help
{
    public const DIR_DATA = 'data';


    public function getTestRootDir(string ...$pathElements): string
    {
        $testRootDir = dirname(__DIR__);
        return $pathElements === [] ?
        $testRootDir :
        $testRootDir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $pathElements);
    }


    public function getTestDataDir(string ...$pathElements): string
    {
        return $this->getTestRootDir(self::DIR_DATA, ...$pathElements);
    }
}
