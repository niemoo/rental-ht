<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isSuperadmin(): bool
    {
        return $this->hasRole(Role::Superadmin);
    }

    public function isOwner(): bool
    {
        return $this->hasRole(Role::Owner);
    }

    /**
     * How many superadmins exist. The app never lets this drop to zero.
     */
    public static function superadminCount(): int
    {
        return static::role(Role::Superadmin->value)->count();
    }

    /**
     * Name of the user's role. Users hold exactly one role in this app.
     */
    public function roleName(): ?string
    {
        return $this->roles->first()?->name;
    }

    /**
     * Display label of the user's role, e.g. "Pemilik" or a custom role's name.
     */
    public function roleLabel(): string
    {
        $name = $this->roleName();

        return $name === null ? 'Tanpa role' : Role::labelFor($name);
    }

    /**
     * Up to two initials, e.g. "Rina (Owner)" → "RO".
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->replaceMatches('/[^\pL ]/u', '')
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');
    }
}
