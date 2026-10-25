<?php

namespace App\Livewire\Users;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role as RoleModel;

/**
 * Add a user (users.create) or edit one (users.edit), including their role.
 *
 * Safety rules (see UserPolicy and RolePolicy): only superadmins touch superadmin accounts or hand
 * out the superadmin role, and there is always at least one superadmin.
 */
class Form extends Component
{
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = '';

    public function mount(?User $user = null): void
    {
        if ($user?->exists) {
            $this->authorize('update', $user);

            $this->user = $user;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->role = $user->roleName() ?? Role::Staff->value;

            return;
        }

        $this->authorize('create', User::class);

        $this->role = array_key_exists(Role::Staff->value, $this->roleOptions) ? Role::Staff->value : (string) array_key_first($this->roleOptions);
    }

    public function save(): void
    {
        $user = $this->user?->fresh('roles') ?? new User;

        $this->authorize($user->exists ? 'update' : 'create', $user->exists ? $user : User::class);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'password' => [$user->exists ? 'nullable' : 'required', 'string', Password::defaults()],
            'role' => ['required', Rule::exists(RoleModel::class, 'name')->where('guard_name', 'web')],
        ], [
            'name.required' => 'Nama wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email ini sudah dipakai pengguna lain.',
            'password.required' => 'Kata sandi wajib diisi',
            'role.required' => 'Pilih role',
        ]);

        $this->guardRoleChange($user, $validated['role']);

        $isNew = ! $user->exists;
        $user->fill(['name' => $validated['name'], 'email' => $validated['email']]);

        if (filled($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();
        $user->syncRoles($validated['role']);

        session()->flash('status', $isNew ? "Pengguna {$user->name} ditambahkan." : "Pengguna {$user->name} disimpan.");
        $this->redirectRoute('users.index');
    }

    /**
     * Roles the current user may assign, as [name => label]: built-in roles first, then custom ones.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function roleOptions(): array
    {
        $builtInOrder = array_flip(array_column(Role::cases(), 'value'));

        return RoleModel::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->orderBy('id')
            ->get()
            ->filter(fn (RoleModel $role): bool => Gate::allows('assign', $role))
            ->sortBy(fn (RoleModel $role): int => $builtInOrder[$role->name] ?? PHP_INT_MAX, SORT_NUMERIC)
            ->mapWithKeys(fn (RoleModel $role): array => [$role->name => Role::labelFor($role->name)])
            ->all();
    }

    public function render(): View
    {
        return view('livewire.users.form')->title($this->user ? 'Ubah pengguna' : 'Tambah pengguna');
    }

    /**
     * A changed role must be one the current user is allowed to hand out, and the last superadmin
     * can never lose the role.
     *
     * @throws ValidationException
     */
    private function guardRoleChange(User $user, string $newRole): void
    {
        if ($user->exists && $user->roleName() === $newRole) {
            return;
        }

        $role = RoleModel::query()->where(['name' => $newRole, 'guard_name' => 'web'])->with('permissions')->firstOrFail();

        if (Gate::denies('assign', $role)) {
            throw ValidationException::withMessages(['role' => 'Hanya superadmin yang bisa memberi role Superadmin.']);
        }

        if ($user->exists && $user->isSuperadmin() && User::superadminCount() <= 1) {
            throw ValidationException::withMessages(['role' => 'Harus ada minimal satu superadmin.']);
        }
    }
}
