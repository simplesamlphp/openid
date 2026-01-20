<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\ValueAbstracts;

/**
 * @implements \IteratorAggregate<int, string>
 */
class UniqueStringBag implements \JsonSerializable, \IteratorAggregate
{
    /**
     * @var string[]
     */
    protected array $values = [];


    public function __construct(
        string ...$values,
    ) {
        $this->add(...$values);
    }


    /**
     * @return \ArrayIterator<int, string>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->values);
    }


    /**
     * @return string[]
     */
    public function getAll(): array
    {
        return $this->values;
    }


    public function has(string $value): bool
    {
        return in_array($value, $this->values, true);
    }


    public function add(string ...$values): void
    {
        foreach ($values as $value) {
            if (!in_array($value, $this->values, true)) {
                $this->values[] = $value;
            }
        }
    }


    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return $this->values;
    }
}
