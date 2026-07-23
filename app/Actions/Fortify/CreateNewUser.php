<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Tenant;
use App\Models\User;
use App\Rules\NotReservedSubdomain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'institution_name' => ['required', 'string', 'max:255'],
            'subdomain' => [
                'required', 'string', 'min:3', 'max:63',
                'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                'unique:tenants,subdomain',
                new NotReservedSubdomain,
            ],
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ], [], [
            'institution_name' => 'Nama Lembaga',
            'subdomain' => 'Subdomain',
        ])->validate();

        return DB::transaction(function () use ($input) {
            $tenant = Tenant::create([
                'name' => $input['institution_name'],
                'subdomain' => strtolower($input['subdomain']),
            ]);

            return User::create([
                'tenant_id' => $tenant->id,
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'role' => 'admin',
            ]);
        });
    }
}
