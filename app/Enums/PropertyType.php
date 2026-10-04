<?php

namespace App\Enums;

enum PropertyType: string
{
    case Apartment = 'apartment';
    case BoardingHouse = 'boarding_house';
    case Condo = 'condo';
    case House = 'house';
    case Room = 'room';
    case Bedspace = 'bedspace';

    public function label(): string
    {
        return match ($this) {
            self::Apartment => 'Apartment',
            self::BoardingHouse => 'Boarding house',
            self::Condo => 'Condo',
            self::House => 'House',
            self::Room => 'Room',
            self::Bedspace => 'Bedspace',
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $type) => ['value' => $type->value, 'label' => $type->label()], self::cases());
    }
}
