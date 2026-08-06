<?php

namespace App\Support;

use App\Models\Tenant;

class CardPrintSettings
{
    /**
     * @return array{columns_per_print_row: int, accent_color: string, show_nis: bool, show_classroom: bool, show_gender: bool, show_logo: bool}
     */
    public static function defaults(): array
    {
        return [
            'columns_per_print_row' => 2,
            'accent_color' => '#1e293b',
            'show_nis' => true,
            'show_classroom' => true,
            'show_gender' => false,
            'show_logo' => false,
        ];
    }

    /**
     * @return array{columns_per_print_row: int, accent_color: string, show_nis: bool, show_classroom: bool, show_gender: bool, show_logo: bool}
     */
    public static function resolve(Tenant $tenant): array
    {
        return [...self::defaults(), ...($tenant->settings['card_print'] ?? [])];
    }
}
