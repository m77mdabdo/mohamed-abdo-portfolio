<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Creates (or promotes) the owner account interactively. The password is typed by the owner in the
 * terminal and stored only as a hash — it never appears in code, seeders, logs or Git.
 */
class CreateOwner extends Command
{
    protected $signature = 'portfolio:create-owner {--email= : E-mail address used to sign in} {--name= : Display name}';

    protected $description = 'Create the owner account of the dashboard (asks for the password)';

    public function handle(): int
    {
        $email = $this->option('email') ?: text('E-mail address', required: true, validate: fn ($v) => filter_var($v, FILTER_VALIDATE_EMAIL) ? null : 'Enter a valid e-mail address.');
        $name = $this->option('name') ?: text('Name', default: 'Mohamed Abdo', required: true);
        $password = password('Password (at least 12 characters)', required: true);
        $confirm = password('Repeat the password', required: true);

        $validator = Validator::make(compact('email', 'name', 'password') + ['password_confirmation' => $confirm], [
            'email' => ['required', 'email', 'max:190'],
            'name' => ['required', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => strtolower($email)]);
        $user->name = $name;
        $user->password = $password;
        $user->forceFill(['role' => Role::Owner, 'is_active' => true, 'email_verified_at' => now()])->save();

        $this->info("Owner account ready: {$user->email}. Sign in at ".url('/admin').' — then enable two-factor sign-in from your profile.');

        return self::SUCCESS;
    }
}
