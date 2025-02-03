<?php

declare(strict_types=1);

namespace App\Validation;

class TimeRules
{
    public function time_greater_than_field(string $value, string $field, array $data): bool
    {
        return strtotime($value) > strtotime($data[$field]);
    }
}