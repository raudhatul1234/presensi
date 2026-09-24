<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Schedule extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'course_id',
        'class_name',
        'schedule_date',
        'start_time',
        'end_time',
        'room',
        'qr_token',
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

    protected function scheduleDate(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?Carbon => $value === null ? null : Carbon::parse($value),
            set: fn (mixed $value): string => Carbon::parse($value)->toDateString(),
        );
    }

    protected function className(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value): string => strtoupper(trim((string) $value)),
        );
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('schedule_date', $date);
    }

    public function isOpenForAttendance(?Carbon $at = null): bool
    {
        if (! $this->is_active || $this->schedule_date === null) {
            return false;
        }

        $current = ($at ?? now())->copy();
        $date = $this->schedule_date->toDateString();

        if ($current->toDateString() !== $date) {
            return false;
        }

        $start = Carbon::parse("{$date} {$this->start_time}", config('app.timezone'));
        $end = Carbon::parse("{$date} {$this->end_time}", config('app.timezone'));

        return $current->greaterThanOrEqualTo($start)
            && $current->lessThanOrEqualTo($end);
    }

    public function getTimeRangeAttribute(): string
    {
        return substr((string) $this->start_time, 0, 5).' - '.substr((string) $this->end_time, 0, 5);
    }

    public function getDateLabelAttribute(): string
    {
        return $this->schedule_date?->translatedFormat('d M Y') ?? '-';
    }
}
