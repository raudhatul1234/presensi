<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Student extends Model
{
    public const DEFAULT_CLASSES = [
        'TI-1A',
        'TI-1B',
        'TI-2A',
        'TI-2B',
        'TI-3A',
        'TI-3B',
        'TI-4A',
        'TI-4B',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'npm',
        'name',
        'class_name',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class);
    }

    protected function npm(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value): string => strtoupper(trim((string) $value)),
        );
    }

    protected function className(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value): string => strtoupper(trim((string) $value)),
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return Collection<int, string>
     */
    public static function classOptions(): Collection
    {
        $storedClasses = static::query()
            ->active()
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name');

        return collect(self::DEFAULT_CLASSES)
            ->merge($storedClasses)
            ->unique()
            ->sort()
            ->values();
    }
}
