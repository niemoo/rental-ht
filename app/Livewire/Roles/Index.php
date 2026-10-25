<?php

namespace App\Livewire\Roles;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role as RoleModel;

/**
 * Roles: create custom roles and choose which permissions each role has.
 *
 * Built-in roles (superadmin, owner, staff) can't be renamed or deleted. The superadmin always keeps
 * every permission and can't be edited at all; anyone with "Kelola role & izin" can edit the rest.
 */
#[Title('Roles')]
class Index extends Component
{
    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $name = '';

    /** @var list<string> */
    public array $permissions = [];

    /** Feedback shown above the list after a delete, e.g. why it was refused. */
    public ?string $notice = null;

    public function create(): void
    {
        $this->authorize('create', RoleModel::class);

        $this->reset('editingId', 'name', 'permissions', 'notice');
        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function edit(int $roleId): void
    {
        $role = RoleModel::query()->with('permissions')->findOrFail($roleId);

        $this->authorize('update', $role);

        $this->editingId = $role->id;
        $this->name = $role->name;
        $this->permissions = $role->permissions->pluck('name')->all();
        $this->notice = null;
        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $role = $this->editingId ? RoleModel::query()->with('permissions')->findOrFail($this->editingId) : null;
        $role ? $this->authorize('update', $role) : $this->authorize('create', RoleModel::class);
        $builtIn = $role ? Role::tryFrom($role->name) : null;

        $validated = $this->validate([
            'name' => $builtIn ? [] : ['required', 'string', 'max:50', Rule::unique(RoleModel::class, 'name')->where('guard_name', 'web')->ignore($this->editingId)],
            'permissions' => ['array'],
            'permissions.*' => [Rule::enum(Permission::class)],
        ], [
            'name.required' => 'Nama role wajib diisi',
            'name.unique' => 'Sudah ada role dengan nama ini.',
        ]);

        $role ??= RoleModel::create(['name' => trim($validated['name']), 'guard_name' => 'web']);

        if ($builtIn === null) {
            $role->update(['name' => trim($validated['name'])]);
        }

        $role->syncPermissions($validated['permissions']);

        $this->showFormModal = false;
        unset($this->roles);
    }

    public function delete(int $roleId): void
    {
        $this->authorize(Permission::ManageRoles);

        $role = RoleModel::query()->with('permissions')->withCount('users')->findOrFail($roleId);

        if (Role::tryFrom($role->name) !== null) {
            $this->notice = 'Role bawaan tidak bisa dihapus.';

            return;
        }

        $this->authorize('delete', $role);

        if ($role->users_count > 0) {
            $this->notice = "Role {$role->name} masih dipakai oleh {$role->users_count} pengguna. Pindahkan mereka ke role lain dulu.";

            return;
        }

        $role->delete();

        $this->notice = "Role {$role->name} dihapus.";
        unset($this->roles);
    }

    /**
     * @return Collection<int, RoleModel>
     */
    #[Computed]
    public function roles(): Collection
    {
        $builtInOrder = array_flip(array_column(Role::cases(), 'value'));

        return RoleModel::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->withCount('users')
            ->orderBy('id')
            ->get()
            ->sortBy(fn (RoleModel $role): int => $builtInOrder[$role->name] ?? PHP_INT_MAX, SORT_NUMERIC)
            ->values();
    }

    #[Computed]
    public function editingBuiltIn(): ?Role
    {
        return $this->editingId ? Role::tryFrom((string) RoleModel::query()->whereKey($this->editingId)->value('name')) : null;
    }

    public function render(): View
    {
        return view('livewire.roles.index');
    }
}
