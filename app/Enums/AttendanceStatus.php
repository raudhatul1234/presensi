<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Hadir = 'Hadir';
    case Izin = 'Izin';
    case Sakit = 'Sakit';
    case Alpa = 'Alpa';

    public function description(): string
    {
        return match ($this) {
            self::Hadir => 'Hadir tepat waktu',
            self::Izin => 'Tidak hadir dengan izin',
            self::Sakit => 'Beristirahat karena sakit',
            self::Alpa => 'Tidak hadir tanpa keterangan',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Hadir => 'fa-solid fa-circle-check',
            self::Izin => 'fa-solid fa-file-signature',
            self::Sakit => 'fa-solid fa-heart-pulse',
            self::Alpa => 'fa-solid fa-user-clock',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(self::values(), self::values());
    }
}
