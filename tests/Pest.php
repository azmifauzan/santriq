<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\PathFallbackTestCase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// routes/tenant.php's domain-vs-path shape is picked once at boot from
// config('tenancy.subdomain_active'), so exercising the path-fallback shape needs
// the app to boot with that config already flipped — see PathFallbackTestCase.
pest()->extend(PathFallbackTestCase::class)
    ->use(RefreshDatabase::class)
    ->in('PathFallback');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Follow the signed handoff link that carries an identity from the apex domain
 * onto the tenant subdomain (see App\Support\TenantSessionHandoff), and return
 * the response of the subdomain that consumed it.
 */
function followTenantHandoff(TestResponse $response): TestResponse
{
    $location = $response->headers->get('Location');

    expect($location)->toContain('/auth/verify-session/');

    return test()->get($location);
}
