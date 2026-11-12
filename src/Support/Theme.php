<?php

declare(strict_types=1);

namespace DataForge\Support;

/**
 * Temas visuales disponibles.
 *
 * Los identificadores se conservan del proyecto original para no invalidar la
 * preferencia guardada de los usuarios existentes.
 */
enum Theme: string
{
    case Neutral   = 'neutral';
    case MediCare  = 'medico';
    case FoodPro   = 'alimentos';
    case IronForge = 'ferreteria';
    case LexDesk   = 'legal';
    case EduBase   = 'educacion';
    case ShopFlow  = 'retail';

    public function label(): string
    {
        return match ($this) {
            self::Neutral   => 'Grafito',
            self::MediCare  => 'MediCare',
            self::FoodPro   => 'FoodPro',
            self::IronForge => 'IronForge',
            self::LexDesk   => 'LexDesk',
            self::EduBase   => 'EduBase',
            self::ShopFlow  => 'ShopFlow',
        };
    }

    public function industry(): string
    {
        return match ($this) {
            self::Neutral   => 'Tecnologia y desarrollo',
            self::MediCare  => 'Salud y medicina',
            self::FoodPro   => 'Alimentos y restauracion',
            self::IronForge => 'Ferreteria y construccion',
            self::LexDesk   => 'Servicios legales',
            self::EduBase   => 'Educacion',
            self::ShopFlow  => 'Comercio y retail',
        };
    }

    public static function tryFromName(string $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        return self::cases();
    }
}
