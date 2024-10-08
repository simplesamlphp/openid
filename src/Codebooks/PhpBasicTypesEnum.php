<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

/**
 * Primitive types with values as returned from PHP function gettype($someValue)
 */
enum PhpBasicTypesEnum: string
{
    case Boolean = 'boolean';
    case Integer = 'integer';
    case Double = 'double';
    case String = 'string';
    case Array = 'array';
    case Object = 'object';
    case Resource = 'resource';
    case Null = 'NULL';
    case UnknownType = 'unknown type';
    case ResourceClosed = 'resource (closed)';
}
