<?php

namespace App\Models;

use App\Models\Concerns\RecordsAuthor;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A kind of rentable equipment (HT, mic, TOA…). Every unit belongs to one.
 */
#[Fillable(['name', 'daily_rate', 'created_by'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, RecordsAuthor, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'daily_rate' => 'integer',
        ];
    }

    /**
     * @return HasMany<Unit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * Units must be moved to another category or deleted first.
     */
    public function isDeletable(): bool
    {
        return $this->units()->doesntExist();
    }

    /**
     * Remove the category. It is soft-deleted while deleted units still point at it (their rental
     * history shows it); otherwise it is deleted for good, which frees its name for reuse.
     */
    public function remove(): void
    {
        if ($this->units()->withTrashed()->exists()) {
            $this->delete();
        } else {
            $this->forceDelete();
        }
    }
}
