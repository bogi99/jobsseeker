<?php

namespace App\Models\Enums;

enum SalaryPeriod: string
{
    case Hour = 'hour';
    case Year = 'year';
    case Budget = 'budget';

    public function label(): string
    {
        return match ($this) {
            self::Hour => 'Per hour',
            self::Year => 'Per year',
            self::Budget => 'Project budget',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $period): array => [$period->value => $period->label()])
            ->all();
    }
}
