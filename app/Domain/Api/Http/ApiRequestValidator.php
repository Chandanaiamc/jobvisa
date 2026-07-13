<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Api\Http;

/**
 * Lightweight API request validation returning structured details.
 */
final class ApiRequestValidator
{
    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, string>  $rules  field => rule string (required|integer|min:1|max:100|in:a,b)
     * @return array<string, mixed>
     */
    public function validate(array $input, array $rules): array
    {
        $errors = [];
        $out = [];

        foreach ($rules as $field => $ruleStr) {
            $parts = array_filter(explode('|', $ruleStr));
            $value = $input[$field] ?? null;
            $required = in_array('required', $parts, true);

            if (($value === null || $value === '') && !$required) {
                continue;
            }

            if (($value === null || $value === '') && $required) {
                $errors[$field][] = 'The ' . $field . ' field is required.';
                continue;
            }

            foreach ($parts as $rule) {
                if ($rule === 'required') {
                    continue;
                }
                if ($rule === 'integer') {
                    if (filter_var($value, FILTER_VALIDATE_INT) === false && !is_int($value)) {
                        $errors[$field][] = 'The ' . $field . ' field must be an integer.';
                    } else {
                        $value = (int) $value;
                    }
                    continue;
                }
                if (str_starts_with($rule, 'min:')) {
                    $min = (int) substr($rule, 4);
                    if (is_numeric($value) && (float) $value < $min) {
                        $errors[$field][] = 'The ' . $field . ' field must be at least ' . $min . '.';
                    }
                    continue;
                }
                if (str_starts_with($rule, 'max:')) {
                    $max = (int) substr($rule, 4);
                    if (is_numeric($value) && (float) $value > $max) {
                        $errors[$field][] = 'The ' . $field . ' field must not exceed ' . $max . '.';
                    } elseif (is_string($value) && mb_strlen($value) > $max) {
                        $errors[$field][] = 'The ' . $field . ' field must not exceed ' . $max . ' characters.';
                    }
                    continue;
                }
                if (str_starts_with($rule, 'in:')) {
                    $allowed = explode(',', substr($rule, 3));
                    if (!in_array((string) $value, $allowed, true)) {
                        $errors[$field][] = 'The selected ' . $field . ' is invalid.';
                    }
                }
            }

            $out[$field] = $value;
        }

        if ($errors !== []) {
            throw ApiException::validation('Validation failed.', $errors);
        }

        return $out;
    }
}
