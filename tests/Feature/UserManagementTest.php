<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Livewire\Users\Form;
use App\Livewire\Users\Index;
use App\Models\User;
use Database\Seeders\SuperadminSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    $this->owner = User::factory()->owner()->create(['name' => 'Rina Owner']);
});

/**
 * A user whose custom role holds exactly the given permissions.
 *
 * @param  list<Permission>  $permissions
 */
function managerWith(array $permissions): User
{
    $role = RoleModel::create(['name' => 'Manajer '.uniqid(), 'guard_name' => 'web']);
    $role->syncPermissions(array_column($permissions, 'value'));

    return tap(User::factory()->create())->syncRoles($role);
}

test('the superadmin seeder creates the requested account and is safe to re-run', function () {
    $this->seed(SuperadminSeeder::class);
    $this->seed(SuperadminSeeder::class);

    $superadmin = User::query()->where('email', 'superadmin@gmail.com')->sole();

    expect($superadmin->isSuperadmin())->toBeTrue()
        ->and(Hash::check('password', $superadmin->password))->toBeTrue();
});

test('only users with permission can open the users pages', function () {
    $staff = User::factory()->create();

    $this->actingAs($staff)->get(route('users.index'))->assertForbidden();
    $this->actingAs($staff)->get(route('users.create'))->assertForbidden();
    $this->actingAs($staff)->get(route('users.edit', $this->owner))->assertForbidden();

    $this->actingAs($this->owner)->get(route('users.index'))
        ->assertOk()
        ->assertSee('Rina Owner')
        ->assertSee('href="'.route('users.create').'"', false)
        ->assertSee('href="'.route('users.edit', $staff).'"', false);
    $this->actingAs($this->owner)->get(route('users.create'))->assertOk()->assertSee('Tambah pengguna');
    $this->actingAs($this->owner)->get(route('users.edit', $staff))->assertOk()->assertSee('Ubah pengguna')->assertSee($staff->email);
});

test('owners can add a user with a role', function () {
    $this->actingAs($this->owner);

    Livewire::test(Form::class)
        ->assertSet('role', Role::Staff->value)
        ->set('name', 'Doni')
        ->set('email', 'doni@example.com')
        ->set('password', 'rahasia123')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('users.index'));

    $user = User::query()->where('email', 'doni@example.com')->sole();
    expect($user->hasRole(Role::Staff))->toBeTrue()
        ->and(Hash::check('rahasia123', $user->password))->toBeTrue();
});

test('editing a user keeps the password when left blank and can change the role', function () {
    $this->actingAs($this->owner);
    RoleModel::create(['name' => 'Kasir', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $originalHash = $user->password;

    Livewire::test(Form::class, ['user' => $user])
        ->assertSet('email', $user->email)
        ->set('name', 'Nama Baru')
        ->set('role', 'Kasir')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('users.index'));

    $user->refresh();
    expect($user->name)->toBe('Nama Baru')
        ->and($user->password)->toBe($originalHash)
        ->and($user->roleName())->toBe('Kasir');
});

test('emails must be unique and passwords are required for new users', function () {
    $this->actingAs($this->owner);

    Livewire::test(Form::class)
        ->set('name', 'X')
        ->set('email', $this->owner->email)
        ->call('save')
        ->assertHasErrors(['email' => 'unique', 'password' => 'required']);
});

test('only superadmins can see, give or touch the superadmin role', function () {
    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($this->owner);

    expect(Livewire::test(Form::class)->instance()->roleOptions)->not->toHaveKey(Role::Superadmin->value);

    Livewire::test(Form::class, ['user' => $this->owner])->set('role', Role::Superadmin->value)->call('save')->assertHasErrors(['role']);
    Livewire::test(Form::class, ['user' => $superadmin])->assertForbidden();
    Livewire::test(Index::class)->call('delete', $superadmin->id)->assertForbidden();
    $this->get(route('users.edit', $superadmin))->assertForbidden();

    expect($this->owner->fresh()->isSuperadmin())->toBeFalse();

    $this->actingAs($superadmin);
    expect(Livewire::test(Form::class)->instance()->roleOptions)->toHaveKey(Role::Superadmin->value);
});

test('the last superadmin cannot be demoted or deleted', function () {
    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);

    Livewire::test(Form::class, ['user' => $superadmin])
        ->set('role', Role::Owner->value)
        ->call('save')
        ->assertHasErrors(['role']);

    expect($superadmin->fresh()->isSuperadmin())->toBeTrue();

    $second = User::factory()->superadmin()->create();
    $this->actingAs($second);

    Livewire::test(Index::class)->call('delete', $superadmin->id)->assertSet('notice', "Pengguna {$superadmin->name} dihapus.");
    Livewire::test(Form::class, ['user' => $second])->set('role', Role::Owner->value)->call('save')->assertHasErrors(['role']);
});

test('a second owner can be removed by an owner', function () {
    $this->actingAs($this->owner);
    $second = User::factory()->owner()->create();

    Livewire::test(Index::class)->call('delete', $second->id)->assertSet('notice', "Pengguna {$second->name} dihapus.");

    expect(User::query()->find($second->id))->toBeNull();
});

test('users cannot delete themselves', function () {
    $this->actingAs($this->owner);

    Livewire::test(Index::class)
        ->call('delete', $this->owner->id)
        ->assertSet('notice', 'Anda tidak bisa menghapus akun Anda sendiri.');

    expect($this->owner->fresh())->not->toBeNull();
});

test('anyone who can manage users can manage every non-superadmin account and role', function () {
    $this->actingAs(managerWith([Permission::ManageUsers]));

    expect(Livewire::test(Form::class)->instance()->roleOptions)->toHaveKeys([Role::Owner->value, Role::Staff->value])
        ->not->toHaveKey(Role::Superadmin->value);

    $staff = User::factory()->create();
    Livewire::test(Form::class, ['user' => $staff])->set('role', Role::Owner->value)->call('save')->assertHasNoErrors();
    expect($staff->fresh()->isOwner())->toBeTrue();

    Livewire::test(Form::class, ['user' => $this->owner])->assertOk()->assertSet('name', 'Rina Owner');
    Livewire::test(Index::class)->call('delete', $this->owner->id)->assertSet('notice', "Pengguna {$this->owner->name} dihapus.");
});

test('staff cannot call user management actions directly', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Form::class)->assertForbidden();
    Livewire::test(Index::class)->call('delete', $this->owner->id)->assertForbidden();
});

test('the users table can be sorted by email', function () {
    User::factory()->create(['name' => 'Zed', 'email' => 'a@example.com']);
    $this->actingAs($this->owner);

    $component = Livewire::test(Index::class);
    expect($component->viewData('users')->pluck('name')->first())->toBe('Rina Owner');

    $component->call('sort', 'email');
    expect($component->viewData('users')->pluck('name')->first())->toBe('Zed');
});
