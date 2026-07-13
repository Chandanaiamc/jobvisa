<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\SkillGap\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

final class SkillGapException extends DomainException
{
    public static function forbidden(): self
    {
        return new self('You are not allowed to use the skill gap analyzer.');
    }

    public static function resumeNotFound(): self
    {
        return new self('Resume not found.');
    }

    public static function jobNotFound(): self
    {
        return new self('Target job not found or not available.');
    }

    public static function invalidResume(): self
    {
        return new self('Invalid resume identifier.');
    }

    public static function invalidJob(): self
    {
        return new self('Select a valid target job for skill gap analysis.');
    }

    public static function analysisNotFound(): self
    {
        return new self('Skill gap analysis not found.');
    }

    public static function historyNotFound(): self
    {
        return new self('Skill gap history entry not found.');
    }
}
