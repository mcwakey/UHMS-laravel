<?php

namespace App\Enums;

enum ProductType: string
{
    case DRUG         = 'drug';
    case CONSUMABLE   = 'consumable';
    case REAGENT      = 'reagent';
    case SUPPLY       = 'supply';
    case EQUIPMENT    = 'equipment';
    case GENERAL_ITEM = 'general_item';

    public function label(): string
    {
        return match ($this) {
            self::DRUG         => 'Drug',
            self::CONSUMABLE   => 'Consumable',
            self::REAGENT      => 'Reagent',
            self::SUPPLY       => 'Supply',
            self::EQUIPMENT    => 'Equipment',
            self::GENERAL_ITEM => 'General Item',
        };
    }

    public static function options(): array
    {
        return array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], self::cases());
    }
}
