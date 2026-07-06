<?php

namespace App\Models\Enums;

enum SalaryCurrency: string
{
    case CAD = 'CAD';
    case USD = 'USD';
    case EUR = 'EUR';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $currency): array => [$currency->value => $currency->value])
            ->all();
    }
}
