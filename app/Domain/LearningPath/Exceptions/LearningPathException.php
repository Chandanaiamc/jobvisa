<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\LearningPath\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

final class LearningPathException extends DomainException
{
    public static function forbidden(): self
    {
        return new self('You are not allowed to use the learning path generator.');
    }

    public static function resumeNotFound(): self
    {
        return new self('Resume not found.');
    }

    public static function invalidResume(): self
    {
        return new self('Invalid resume identifier.');
    }

    public static function pathNotFound(): self
    {
        return new self('Learning path not found.');
    }

    public static function historyNotFound(): self
    {
        return new self('Learning path history entry not found.');
    }

    public static function milestoneNotFound(): self
    {
        return new self('Milestone not found on this learning path.');
    }
}
