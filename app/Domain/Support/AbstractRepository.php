<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Support;

use JobVisa\App\Domain\Contracts\RepositoryInterface;

/**
 * Repository base — no SQL or PDO usage in the foundation.
 *
 * Concrete infrastructure repositories will extend domain interfaces,
 * not necessarily this class.
 *
 * @template T of object
 * @implements RepositoryInterface<T>
 */
abstract class AbstractRepository implements RepositoryInterface
{
    abstract public function findById(int|string $id): ?object;
}
