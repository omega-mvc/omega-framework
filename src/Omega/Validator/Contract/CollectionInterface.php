<?php

declare(strict_types=1);

namespace Omega\Validator\Contract;

use ArrayAccess;
use Countable;
use IteratorAggregate;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends ArrayAccess<TKey, TValue>
 * @extends IteratorAggregate<TKey, TValue>
 */
interface CollectionInterface extends ArrayAccess, IteratorAggregate, Countable
{
    /** @return array<TKey, TValue> */
    public function toArray(): array;
}
