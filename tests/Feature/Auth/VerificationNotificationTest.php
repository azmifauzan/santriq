<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::emailVerification());
});

test('sends verification notification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAsStaff($user)
        ->post(route('verification.send'))
        ->assertRedirect('/');

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('verification email is sent in indonesian with a santriq-branded subject', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAsStaff($user)->post(route('verification.send'));

    Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user) {
        $mail = $notification->toMail($user);

        expect($mail->subject)->toBe('Verifikasi Alamat Email - SantriQ')
            ->and(implode(' ', $mail->introLines))->toContain('memverifikasi');

        return true;
    });
});

test('does not send verification notification if email is verified', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAsStaff($user)
        ->post(route('verification.send'))
        ->assertRedirect('/dashboard');

    Notification::assertNothingSent();
});
