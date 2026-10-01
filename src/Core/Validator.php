<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Core;

/**
 * Server-side validator. Rules mirror public/assets/js/validator.js exactly
 * (same rule names, same messages) so client and server always agree.
 *
 * Rules: required, name, email, phone, password, digits, letters, min:n, max:n,
 *        same:field, in:a,b,c, alnum_code
 */
final class Validator
{
    public const NAME_REGEX  = '/^[\p{L}\p{M}]+(?: [\p{L}\p{M}]+)*$/u';
    public const PHONE_REGEX = '/^\d{10,15}$/';

    private array $errors = [];

    private function __construct(private array $data, private array $rules, private array $labels)
    {
    }

    public static function make(array $data, array $rules, array $labels = []): self
    {
        return new self($data, $rules, $labels);
    }

    public function fails(): bool
    {
        $this->run();
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function label(string $field): string
    {
        return $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    private function run(): void
    {
        $this->errors = [];
        foreach ($this->rules as $field => $rules) {
            $raw = $this->data[$field] ?? '';
            $value = is_scalar($raw) ? trim((string)$raw) : '';
            $label = $this->label($field);
            $rules = is_array($rules) ? $rules : explode('|', (string)$rules);
            $required = in_array('required', $rules, true);

            if ($value === '') {
                if ($required) {
                    $this->errors[$field] = "{$label} is required.";
                }
                continue;
            }

            foreach ($rules as $rule) {
                if ($rule === 'required') {
                    continue;
                }
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $msg = $this->check($name, $arg, $value, $label);
                if ($msg !== null) {
                    $this->errors[$field] = $msg;
                    break;
                }
            }
        }
    }

    private function check(string $rule, ?string $arg, string $value, string $label): ?string
    {
        switch ($rule) {
            case 'name':
                if (!preg_match(self::NAME_REGEX, $value)) {
                    return "{$label} can contain letters and spaces only.";
                }
                return null;
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL) || mb_strlen($value) > 120) {
                    return 'Enter a valid email address.';
                }
                return null;
            case 'phone':
                if (!preg_match(self::PHONE_REGEX, $value)) {
                    return "{$label} must contain digits only (10 to 15 digits).";
                }
                return null;
            case 'digits':
                return ctype_digit($value) ? null : "{$label} can contain digits only.";
            case 'letters':
                return preg_match('/^[\p{L}\p{M} ]+$/u', $value) ? null : "{$label} can contain letters only.";
            case 'password':
                if (
                    mb_strlen($value) < 8
                    || !preg_match('/[a-z]/', $value)
                    || !preg_match('/[A-Z]/', $value)
                    || !preg_match('/\d/', $value)
                    || !preg_match('/[^A-Za-z0-9]/', $value)
                ) {
                    return 'Password needs 8+ characters with upper case, lower case, a digit and a symbol.';
                }
                return null;
            case 'min':
                return mb_strlen($value) >= (int)$arg ? null : "{$label} must be at least {$arg} characters.";
            case 'max':
                return mb_strlen($value) <= (int)$arg ? null : "{$label} must be at most {$arg} characters.";
            case 'same':
                $other = $this->data[$arg] ?? '';
                $other = is_scalar($other) ? trim((string)$other) : '';
                return hash_equals($other, $value) ? null : "{$label} does not match.";
            case 'in':
                return in_array($value, explode(',', (string)$arg), true) ? null : "{$label} is invalid.";
            case 'alnum_code':
                return preg_match('/^[A-Za-z0-9_-]+$/', $value) ? null : "{$label} can contain letters, digits, - and _ only.";
        }
        return null;
    }
}
