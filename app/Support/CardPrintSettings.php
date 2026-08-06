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
        /** @var array<string, mixed> $saved */
        $saved = $tenant->settings['card_print'] ?? [];
        $defaults = self::defaults();

        return [
            'columns_per_print_row' => isset($saved['columns_per_print_row']) && is_int($saved['columns_per_print_row'])
                ? $saved['columns_per_print_row']
                : $defaults['columns_per_print_row'],
            'accent_color' => isset($saved['accent_color']) && is_string($saved['accent_color'])
                ? $saved['accent_color']
                : $defaults['accent_color'],
            'show_nis' => isset($saved['show_nis']) && is_bool($saved['show_nis'])
                ? $saved['show_nis']
                : $defaults['show_nis'],
            'show_classroom' => isset($saved['show_classroom']) && is_bool($saved['show_classroom'])
                ? $saved['show_classroom']
                : $defaults['show_classroom'],
            'show_gender' => isset($saved['show_gender']) && is_bool($saved['show_gender'])
                ? $saved['show_gender']
                : $defaults['show_gender'],
            'show_logo' => isset($saved['show_logo']) && is_bool($saved['show_logo'])
                ? $saved['show_logo']
                : $defaults['show_logo'],
        ];
    }
}
