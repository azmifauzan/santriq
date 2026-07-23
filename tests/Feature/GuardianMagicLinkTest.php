<?php

use App\Jobs\SendTelegramMessage;
use App\Models\Guardian;
use App\Models\Tenant;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

test('requesting a link dispatches a telegram message when the guardian is linked', function () {
    Queue::fake();

    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-wali']);
    $guardian = Guardian::factory()->create([
        'tenant_id' => $tenant->id,
        'phone' => '081234567890',
        'telegram_chat_id' => '999',
    ]);

    $this->post("http://{$tenant->subdomain}.santriq.test/wali/masuk", [
        'phone' => '081234567890',
    ])->assertRedirect();

    Queue::assertPushed(SendTelegramMessage::class, fn ($job) => $job->guardian->id === $guardian->id);
});

test('requesting a link fails silently useful when the guardian has no telegram link', function () {
    Queue::fake();

    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-wali2']);
    Guardian::factory()->create([
        'tenant_id' => $tenant->id,
        'phone' => '081111111111',
        'telegram_chat_id' => null,
    ]);

    $this->post("http://{$tenant->subdomain}.santriq.test/wali/masuk", [
        'phone' => '081111111111',
    ])->assertSessionHasErrors('phone');

    Queue::assertNothingPushed();
});

test('a phone number belonging to another tenant is rejected', function () {
    Queue::fake();

    $tenantA = Tenant::factory()->create(['subdomain' => 'tpq-a2']);
    $tenantB = Tenant::factory()->create(['subdomain' => 'tpq-b2']);
    Guardian::factory()->create([
        'tenant_id' => $tenantB->id,
        'phone' => '082222222222',
        'telegram_chat_id' => '888',
    ]);

    $this->post("http://{$tenantA->subdomain}.santriq.test/wali/masuk", [
        'phone' => '082222222222',
    ])->assertSessionHasErrors('phone');

    Queue::assertNothingPushed();
});

test('a valid signed link logs the guardian in and redirects to the portal', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-verify']);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id]);

    $link = URL::temporarySignedRoute(
        'guardian.login.verify',
        now()->addMinutes(15),
        ['guardian' => $guardian->id, 'subdomain' => $tenant->subdomain]
    );

    $this->get($link)->assertRedirect(route('guardian.portal.index'));

    $this->assertAuthenticatedAs($guardian, 'guardian');
});

test('an expired or tampered link is rejected', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-expired']);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id]);

    $link = URL::temporarySignedRoute(
        'guardian.login.verify',
        now()->subMinute(),
        ['guardian' => $guardian->id, 'subdomain' => $tenant->subdomain]
    );

    $this->get($link)->assertForbidden();
    $this->assertGuest('guardian');
});

test('a guardian id from another tenant cannot be verified through this subdomain', function () {
    $tenantA = Tenant::factory()->create(['subdomain' => 'tpq-a3']);
    $tenantB = Tenant::factory()->create(['subdomain' => 'tpq-b3']);
    $guardianB = Guardian::factory()->create(['tenant_id' => $tenantB->id]);

    $link = str_replace(
        "{$tenantB->subdomain}.santriq.test",
        "{$tenantA->subdomain}.santriq.test",
        URL::temporarySignedRoute(
            'guardian.login.verify',
            now()->addMinutes(15),
            ['guardian' => $guardianB->id, 'subdomain' => $tenantB->subdomain]
        )
    );

    $this->get($link)->assertForbidden();
    $this->assertGuest('guardian');
});

test('a logged-in guardian can log out', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-out']);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($guardian, 'guardian')
        ->post("http://{$tenant->subdomain}.santriq.test/wali/keluar")
        ->assertRedirect(route('guardian.login'));

    $this->assertGuest('guardian');
});
