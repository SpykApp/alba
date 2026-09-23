<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Support;

/** Tiny pipe-syntax validator: required|email|url|numeric|min:n|max:n|same:field. */
final class Validator
{
    /**
     * @param  array<string, array{label: string, rules: string}>  $fields
     * @param  array<string, mixed>  $input
     * @return array<string, string> first error per field
     */
    public static function validate(array $fields, array $input): array
    {
        $errors = [];

        foreach ($fields as $name => $field) {
            $value = trim((string) ($input[$name] ?? ''));
            $label = $field['label'];

            foreach (array_filter(explode('|', $field['rules'])) as $rule) {
                [$rule, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $error = match ($rule) {
                    'required' => $value === '' ? "$label is required." : null,
                    'email' => $value !== '' && ! filter_var($value, FILTER_VALIDATE_EMAIL) ? "$label must be a valid email address." : null,
                    'url' => $value !== '' && ! filter_var($value, FILTER_VALIDATE_URL) ? "$label must be a valid URL." : null,
                    'numeric' => $value !== '' && ! is_numeric($value) ? "$label must be a number." : null,
                    'min' => $value !== '' && mb_strlen($value) < (int) $arg ? "$label must be at least $arg characters." : null,
                    'max' => mb_strlen($value) > (int) $arg ? "$label may not be longer than $arg characters." : null,
                    'same' => $value !== trim((string) ($input[$arg] ?? '')) ? "$label does not match." : null,
                    default => null,
                };
                if ($error) {
                    $errors[$name] = $error;
                    break;
                }
            }
        }

        return $errors;
    }
}
