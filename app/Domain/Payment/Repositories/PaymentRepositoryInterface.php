<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Payment\Repositories;

use JobVisa\App\Domain\Contracts\RepositoryInterface;
use JobVisa\App\Domain\Payment\Entities\Payment;

/**
 * Persistence contract for the Payment aggregate.
 *
 * @extends RepositoryInterface<Payment>
 */
interface PaymentRepositoryInterface extends RepositoryInterface
{
    public function findById(int|string $id): ?Payment;
}
