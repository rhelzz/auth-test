<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * Strong password requirements:
     * - Minimum 8 characters
     * - At least 1 uppercase letter
     * - At least 1 lowercase letter  
     * - At least 1 number
     * - At least 1 special character
     * - Not in common password lists
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return [
            'required',
            'string',
            Password::min(8)
                ->mixedCase()      // Requires uppercase & lowercase
                ->numbers()        // Requires at least 1 number
                ->symbols()        // Requires at least 1 special character
                ->uncompromised(), // Check against breached password databases
            'confirmed',
        ];
    }
}

