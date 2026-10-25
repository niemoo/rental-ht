<?php

namespace App\Livewire\Users;

use App\Enums\Permission;
use App\Livewire\Concerns\SortsTable;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Pengguna: list and delete users. Adding and editing happen on {@see Form}.
 *
 * Safety rules (see UserPolicy): only superadmins touch superadmin accounts, there is always
 * at least one superadmin, and nobody can delete their own account.
 */
#[Title('Pengguna')]
class Index extends Component
{
    use SortsTable;

    #[Url(except: '')]
    public string $search = '';

    #[Url(as: 'sort', except: 'name')]
    public string $sortBy = 'name';

    #[Url(as: 'dir', except: 'asc')]
    public string $sortDirection = 'asc';

    /** Feedback shown above the list after a delete, e.g. why it was refused. */
    public ?string $notice = null;

    public function delete(int $userId): void
    {
        $this->authorize(Permission::ManageUsers);

        $user = User::query()->with('roles')->findOrFail($userId);

        if ($user->is(Auth::user())) {
            $this->notice = 'Anda tidak bisa menghapus akun Anda sendiri.';

            return;
        }

        $this->authorize('delete', $user);

        if ($user->isSuperadmin() && User::superadminCount() <= 1) {
            $this->notice = 'Superadmin terakhir tidak bisa dihapus.';

            return;
        }

        $user->delete();

        $this->notice = "Pengguna {$user->name} dihapus.";
    }

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'name' => 'name',
            'email' => 'email',
            'joined' => 'created_at',
        ];
    }

    public function render(): View
    {
        $search = trim($this->search);

        return view('livewire.users.index', [
            'users' => $this->paginateSorted(
                User::query()
                    ->with('roles')
                    ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                        ->whereLike('name', "%{$search}%")
                        ->orWhereLike('email', "%{$search}%")))
            ),
            'totalCount' => User::query()->count(),
        ]);
    }
}
