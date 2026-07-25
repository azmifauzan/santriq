<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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

/**
 * Build a real .xlsx file on disk and wrap it as an UploadedFile, for
 * posting to import endpoints in feature tests. $rows is a list of
 * arrays, one per data row (no need to pass headings separately if
 * $headings already leads the sheet).
 *
 * @param  array<int, string>  $headings
 * @param  array<int, array<int, mixed>>  $rows
 */
function makeXlsxUploadedFile(array $headings, array $rows, string $filename = 'import.xlsx'): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($headings, null, 'A1');
    $sheet->fromArray($rows, null, 'A2');

    $path = sys_get_temp_dir().'/'.uniqid('test-import-', true).'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}
