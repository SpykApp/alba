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
            $label = Lang::text($field['label']);

            foreach (array_filter(explode('|', $field['rules'])) as $rule) {
                [$rule, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $error = match ($rule) {
                    'required' => $value === '' ? Lang::t('val.required', ['label' => $label, 'min' => $arg, 'max' => $arg]) : null,
                    'email' => $value !== '' && ! filter_var($value, FILTER_VALIDATE_EMAIL) ? Lang::t('val.email', ['label' => $label, 'min' => $arg, 'max' => $arg]) : null,
                    'url' => $value !== '' && ! filter_var($value, FILTER_VALIDATE_URL) ? Lang::t('val.url', ['label' => $label, 'min' => $arg, 'max' => $arg]) : null,
                    'numeric' => $value !== '' && ! is_numeric($value) ? Lang::t('val.numeric', ['label' => $label, 'min' => $arg, 'max' => $arg]) : null,
                    'min' => $value !== '' && mb_strlen($value) < (int) $arg ? Lang::t('val.min', ['label' => $label, 'min' => $arg, 'max' => $arg]) : null,
                    'max' => mb_strlen($value) > (int) $arg ? Lang::t('val.max', ['label' => $label, 'min' => $arg, 'max' => $arg]) : null,
                    'same' => $value !== trim((string) ($input[$arg] ?? '')) ? Lang::t('val.same', ['label' => $label, 'min' => $arg, 'max' => $arg]) : null,
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
