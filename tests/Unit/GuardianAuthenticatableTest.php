<?php

use App\Models\Guardian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('a guardian can be logged into the guardian guard', function () {
    $guardian = Guardian::factory()->create();

    Auth::guard('guardian')->login($guardian);

    expect(Auth::guard('guardian')->id())->toBe($guardian->id);
    expect(Auth::guard('guardian')->check())->toBeTrue();
});
