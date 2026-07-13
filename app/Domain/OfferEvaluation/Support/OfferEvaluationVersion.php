<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\OfferEvaluation\Support;

final class OfferEvaluationVersion
{
    public const CURRENT = '3.9.0';

    public const REC_ACCEPT = 'accept';
    public const REC_NEGOTIATE = 'negotiate';
    public const REC_DECLINE = 'decline';
}
