<?php

namespace App\Enums;

enum ProductType: string
{
    case DRUG            = 'drug';
    case CONSUMABLE      = 'consumable';
    case REAGENT         = 'reagent';
    case SURGICAL_SUPPLY = 'surgical_supply';
    case MEDICAL_SUPPLY  = 'medical_supply';
    case SUPPLY          = 'supply';
    case EQUIPMENT       = 'equipment';
    case GENERAL_ITEM    = 'general_item';

    public function label(): string
    {
        return match ($this) {
            self::DRUG            => 'Drug',
            self::CONSUMABLE      => 'Consumable',
            self::REAGENT         => 'Reagent',
            self::SURGICAL_SUPPLY => 'Surgical Supply',
            self::MEDICAL_SUPPLY  => 'Medical Supply',
            self::SUPPLY          => 'Supply (legacy)',
            self::EQUIPMENT       => 'Equipment',
            self::GENERAL_ITEM    => 'General Item',
        };
    }

    public static function options(): array
    {
        return array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], self::cases());
    }
}
