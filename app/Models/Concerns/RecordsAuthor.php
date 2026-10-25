<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * Fills the created_by / updated_by / deleted_by audit columns from the signed-in user.
 *
 * Values set explicitly (e.g. by seeders) are left untouched. deleted_by is only written for
 * soft deletes; a force-deleted row is gone, so there is nothing to record it on.
 */
trait RecordsAuthor
{
    public static function bootRecordsAuthor(): void
    {
        static::creating(function (self $model): void {
            $model->created_by ??= Auth::id();
        });

        static::updating(function (self $model): void {
            if (Auth::check() && ! $model->isDirty('updated_by')) {
                $model->updated_by = Auth::id();
            }
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::softDeleted(function (self $model): void {
                if (Auth::check()) {
                    $model->forceFill(['deleted_by' => Auth::id()])->saveQuietly();
                }
            });
        }
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
