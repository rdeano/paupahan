<?php

namespace App\Enums;

enum PropertyStatus: string
{
    case Draft = 'draft';
    case Available = 'available';
    case Reserved = 'reserved';
    case Occupied = 'occupied';
    case Archived = 'archived';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Statuses that appear on the public site. */
    public static function publicValues(): array
    {
        return [self::Available->value, self::Reserved->value];
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $status) => ['value' => $status->value, 'label' => $status->label()], self::cases());
    }
}
