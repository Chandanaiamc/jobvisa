<?php

declare(strict_types=1);

namespace JobVisa\App\Security;

/**
 * Lightweight input validator (no database checks).
 */
final class Validator
{
    /** @var array<string, list<string>> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $data;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function make(array $data): self
    {
        return new self($data);
    }

    public function required(string $field, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;

        if ($value === null || (is_string($value) && trim($value) === '') || $value === []) {
            $this->addError($field, $message ?? 'The ' . $field . ' field is required.');
        }

        return $this;
    }

    public function email(string $field, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;

        if ($value === null || $value === '') {
            return $this;
        }

        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->addError($field, $message ?? 'The ' . $field . ' field must be a valid email address.');
        }

        return $this;
    }

    public function min(string $field, int $min, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;

        if ($value === null || $value === '') {
            return $this;
        }

        $length = is_string($value) ? mb_strlen($value) : 0;

        if ($length < $min) {
            $this->addError($field, $message ?? 'The ' . $field . ' field must be at least ' . $min . ' characters.');
        }

        return $this;
    }

    public function max(string $field, int $max, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;

        if ($value === null || $value === '') {
            return $this;
        }

        $length = is_string($value) ? mb_strlen($value) : 0;

        if ($length > $max) {
            $this->addError($field, $message ?? 'The ' . $field . ' field must not exceed ' . $max . ' characters.');
        }

        return $this;
    }

    /**
     * Ensure field matches "{field}_confirmation" (or a custom confirmation field).
     */
    public function confirmed(string $field, ?string $confirmationField = null, ?string $message = null): self
    {
        $confirmationField ??= $field . '_confirmation';
        $value = $this->data[$field] ?? null;
        $confirmation = $this->data[$confirmationField] ?? null;

        if ((string) $value !== (string) $confirmation) {
            $this->addError($field, $message ?? 'The ' . $field . ' confirmation does not match.');
        }

        return $this;
    }

    /**
     * @param  list<mixed>  $allowed
     */
    public function in(string $field, array $allowed, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;

        if ($value === null || $value === '') {
            return $this;
        }

        if (!in_array($value, $allowed, true)) {
            $this->addError($field, $message ?? 'The selected ' . $field . ' is invalid.');
        }

        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Flat list of all error messages.
     *
     * @return list<string>
     */
    public function allErrors(): array
    {
        $all = [];

        foreach ($this->errors as $messages) {
            foreach ($messages as $message) {
                $all[] = $message;
            }
        }

        return $all;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
