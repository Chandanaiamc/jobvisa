<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Notification\Repositories;

use JobVisa\App\Domain\Contracts\RepositoryInterface;
use JobVisa\App\Domain\Notification\Entities\Notification;

/**
 * Persistence contract for the Notification aggregate.
 *
 * @extends RepositoryInterface<Notification>
 */
interface NotificationRepositoryInterface extends RepositoryInterface
{
    public function findById(int|string $id): ?Notification;
}
