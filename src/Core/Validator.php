<?php

declare(strict_types=1);

namespace Craw\Core;

/**
 * Validation simple et lisible, avec des messages en francais destines a etre
 * affiches directement a l'utilisateur (et annonces via aria-live).
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $validated = [];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(private readonly array $data)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function make(array $data): self
    {
        return new self($data);
    }

    private function value(string $field): ?string
    {
        $value = $this->data[$field] ?? null;

        if (is_array($value) || $value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public function required(string $field, string $label): self
    {
        if ($this->value($field) === null) {
            $this->fail($field, t('validation.required', ['label' => $label]));
        } else {
            $this->validated[$field] = $this->value($field);
        }

        return $this;
    }

    public function optional(string $field): self
    {
        $this->validated[$field] = $this->value($field);

        return $this;
    }

    public function minLength(string $field, string $label, int $min): self
    {
        $value = $this->value($field);

        if ($value !== null && mb_strlen($value) < $min) {
            $this->fail($field, t('validation.min_length', ['label' => $label, 'min' => $min]));
        }

        return $this;
    }

    public function maxLength(string $field, string $label, int $max): self
    {
        $value = $this->value($field);

        if ($value !== null && mb_strlen($value) > $max) {
            $this->fail($field, t('validation.max_length', ['label' => $label, 'max' => $max]));
        }

        return $this;
    }

    public function email(string $field, string $label): self
    {
        $value = $this->value($field);

        if ($value === null) {
            return $this;
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false || mb_strlen($value) > 190) {
            $this->fail($field, t('validation.email', ['label' => $label]));

            return $this;
        }

        $this->validated[$field] = mb_strtolower($value);

        return $this;
    }

    public function phone(string $field, string $label): self
    {
        $value = $this->value($field);

        if ($value === null) {
            return $this;
        }

        $digits = preg_replace('/[^0-9]/', '', $value) ?? '';

        if (mb_strlen($digits) < 8 || mb_strlen($digits) > 15) {
            $this->fail($field, t('validation.phone', ['label' => $label]));
        }

        return $this;
    }

    /**
     * @param array<int, string> $allowed
     */
    public function in(string $field, string $label, array $allowed): self
    {
        $value = $this->value($field);

        if ($value !== null && !in_array($value, $allowed, true)) {
            $this->fail($field, t('validation.in', ['label' => $label]));
        }

        return $this;
    }

    public function date(string $field, string $label): self
    {
        $value = $this->value($field);

        if ($value === null) {
            return $this;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            $this->fail($field, t('validation.date', ['label' => $label]));
        }

        return $this;
    }

    public function accepted(string $field, string $message): self
    {
        $value = $this->value($field);

        if ($value === null || !in_array(strtolower($value), ['1', 'on', 'true', 'yes', 'oui'], true)) {
            $this->fail($field, $message);
        }

        return $this;
    }

    /** Numero de TVA belge ou europeen, verifie de facon souple. */
    public function vatNumber(string $field, string $label): self
    {
        $value = $this->value($field);

        if ($value === null) {
            return $this;
        }

        $normalised = strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', $value));

        if (preg_match('/^[A-Z]{2}[A-Z0-9]{8,12}$/', $normalised) !== 1) {
            $this->fail($field, t('validation.vat_number', ['label' => $label]));

            return $this;
        }

        $this->validated[$field] = $normalised;

        return $this;
    }

    public function requiredIf(string $field, string $label, bool $condition): self
    {
        if ($condition && $this->value($field) === null) {
            $this->fail($field, t('validation.required', ['label' => $label]));
        }

        return $this;
    }

    public function custom(string $field, bool $passes, string $message): self
    {
        if (!$passes) {
            $this->fail($field, $message);
        }

        return $this;
    }

    private function fail(string $field, string $message): void
    {
        // On conserve la premiere erreur par champ : plus lisible qu'une liste.
        $this->errors[$field] ??= $message;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors === [] ? null : (string) reset($this->errors);
    }
}
