<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'amount' => 50000,
            'method' => 'cash',
            'paid_at' => now(),
            'verified_by' => User::factory(),
            'note' => 'Pembayaran tunai SPP',
        ];
    }
}
