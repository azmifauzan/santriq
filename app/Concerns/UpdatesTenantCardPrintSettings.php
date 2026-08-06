<?php

namespace App\Concerns;

use App\Http\Requests\Settings\UpdateCardPrintSettingsRequest;
use App\Models\Tenant;

trait UpdatesTenantCardPrintSettings
{
    /**
     * @return array{columns_per_print_row: int, accent_color: string, show_nis: bool, show_classroom: bool, show_gender: bool, show_logo: bool}
     */
    private function applyCardPrintUpdate(UpdateCardPrintSettingsRequest $request, Tenant $tenant): array
    {
        $cardPrint = [
            'columns_per_print_row' => $request->integer('columns_per_print_row'),
            'accent_color' => $request->string('accent_color')->toString(),
            'show_nis' => $request->boolean('show_nis'),
            'show_classroom' => $request->boolean('show_classroom'),
            'show_gender' => $request->boolean('show_gender'),
            'show_logo' => $request->boolean('show_logo'),
        ];

        $tenant->update([
            'settings' => [...$tenant->settings ?? [], 'card_print' => $cardPrint],
        ]);

        return $cardPrint;
    }
}
