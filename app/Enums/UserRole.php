<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Manajer = 'manajer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Manajer => 'Manajer outlet',
        };
    }

    public function isOwner(): bool
    {
        return $this === self::Owner;
    }
}
