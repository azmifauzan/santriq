<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ], [], [
            'institution_name' => 'Nama Lembaga',
        ])->validate();

        return DB::transaction(function () use ($input) {
            $slugBase = Str::slug($input['institution_name']);
            $slug = $slugBase ?: 'lembaga';
            $uniqueSlug = $slug;
            $count = 1;
            while (Tenant::where('slug', $uniqueSlug)->exists()) {
                $uniqueSlug = "{$slug}-{$count}";
                $count++;
            }

            $tenant = Tenant::create([
                'name' => $input['institution_name'],
                'slug' => $uniqueSlug,
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
