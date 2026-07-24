<?php

namespace App\Rules;

use App\Support\DemoTenant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotReservedSubdomain implements ValidationRule
{
    /**
     * @var list<string>
     */
    private const RESERVED = [
        'www', 'api', 'admin', 'app', 'mail', 'webhook', 'assets', 'static',
        'cdn', 'ftp', 'localhost', 'staging', 'dashboard', 'login', 'logout',
        'register', 'support', 'help', 'docs', 'status', 'blog', 'santriq',
        DemoTenant::SUBDOMAIN,
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (in_array(strtolower((string) $value), self::RESERVED, true)) {
            $fail('Subdomain tersebut tidak dapat digunakan.');
        }
    }
}
