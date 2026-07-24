<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Tenant;
use App\Models\User;
use App\Rules\NotReservedSubdomain;
use App\Support\GoogleOAuthToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
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
        // A "Daftar dengan Google" signup skips the password fields entirely —
        // see GoogleAuthController::handleRegister, which mints this token right
        // after Google confirms the user's identity.
        $google = isset($input['google_token']) && $input['google_token'] !== ''
            ? GoogleOAuthToken::decode((string) $input['google_token'])
            : null;

        if (isset($input['google_token']) && $input['google_token'] !== '' && $google === null) {
            throw ValidationException::withMessages([
                'email' => 'Sesi Google sudah kedaluwarsa. Silakan ulangi pendaftaran.',
            ]);
        }

        // The client can submit whatever it wants for `email` — never trust that
        // field over the signed token once Google has attested an identity. The
        // name stays user-editable; it's just a display label, not an identity claim.
        if ($google !== null) {
            $input['email'] = $google['email'];
        }

        Validator::make($input, [
            'institution_name' => ['required', 'string', 'max:255'],
            'subdomain' => [
                'required', 'string', 'min:3', 'max:63',
                'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                'unique:tenants,subdomain',
                new NotReservedSubdomain,
            ],
            ...$this->profileRules(),
            'password' => $google ? ['nullable'] : $this->passwordRules(),
        ], [], [
            'institution_name' => 'Nama Lembaga',
            'subdomain' => 'Subdomain',
        ])->validate();

        return DB::transaction(function () use ($input, $google) {
            $tenant = Tenant::create([
                'name' => $input['institution_name'],
                'subdomain' => strtolower($input['subdomain']),
            ]);

            return User::create([
                'tenant_id' => $tenant->id,
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $google ? null : $input['password'],
                'google_id' => $google['sub'] ?? null,
                'email_verified_at' => $google ? now() : null,
                'role' => 'admin',
                'onboarded_at' => null,
            ]);
        });
    }
}
