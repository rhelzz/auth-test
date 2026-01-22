<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Regex patterns for validation
     */
    protected const NAME_REGEX = '/^[a-zA-Z\s\-\'\.]+$/u';
    protected const NAME_NO_CONSECUTIVE_SPACES = '/^(?!.*\s{2})/';

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:' . self::NAME_REGEX, // Only letters, spaces, hyphens, apostrophes, dots
                'regex:' . self::NAME_NO_CONSECUTIVE_SPACES, // No consecutive spaces
            ],
            'email' => [
                'required',
                'string',
                'email:rfc,dns', // Strict email validation with DNS check
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ], [
            'name.regex' => 'Name can only contain letters, spaces, hyphens, apostrophes, and dots.',
            'email.email' => 'Please provide a valid email address.',
        ])->validate();

        return User::create([
            'name' => $this->sanitizeName($input['name']),
            'email' => $this->normalizeEmail($input['email']),
            'password' => Hash::make($input['password']),
        ]);
    }

    /**
     * Sanitize the name input
     */
    protected function sanitizeName(string $name): string
    {
        // Trim whitespace and normalize multiple spaces to single space
        $name = trim(preg_replace('/\s+/', ' ', $name));
        
        // Convert to title case
        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Normalize email to lowercase
     */
    protected function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}

