<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataKunjungan extends Model
{
    public $timestamps = false;

    protected $table = 'data_kunjungan';

    protected $fillable = [
        'no',
        'wbp',
        'pengunjung',
        'jenis_kelamin',
        'hubungan',
        'sub_hubungan',
        'alamat_pengunjung',
        'no_identitas',
        'waktu_kunjungan',
        'foto_ktp',
        'foto_diri',
        'catatan',
    ];


    public function getTanggalAttribute(): string
    {

        $raw = $this->attributes['waktu_kunjungan'] ?? null;

        if ($raw === null || trim((string)$raw) === '') {
            $raw = $this->attributes['jadwal_kunjungan'] ?? null;
        }

        if ($raw === null || trim((string)$raw) === '') {
            return '-';
        }

        $raw = trim((string)$raw);

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $raw, $m)) {
            return sprintf(
                '%02d/%02d/%04d',
                (int)$m[3],
                (int)$m[2],
                (int)$m[1]
            );
        }

        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $raw, $m)) {
            return sprintf(
                '%02d/%02d/%04d',
                (int)$m[1],
                (int)$m[2],
                (int)$m[3]
            );
        }

        // Excel serial number
        if (ctype_digit($raw)) {

            $serial = (int)$raw;

            if ($serial > 1 && $serial < 73000) {

                $unix = ($serial - 25569) * 86400;

                if ($unix > 0) {
                    return date('d/m/Y', $unix);
                }
            }
        }

        // Fallback strtotime
        $ts = strtotime($raw);

        if ($ts !== false && $ts > 0) {
            return date('d/m/Y', $ts);
        }

        return '-';
    }

    
    public function getNoHpAttribute(): string
    {
        $catatan = trim((string)($this->attributes['catatan'] ?? ''));

        if ($catatan === '') return '-';

        $stripped = preg_replace('/[\s\-\.\(\)]/', '', $catatan);

        if (preg_match('/^(\+62|0)[0-9]{7,13}$/', $stripped)) {
            return $stripped;
        }

        if (preg_match('/^[0-9]{9,13}$/', $stripped)) {
            return $stripped;
        }

        $patterns = [
            '/(?:no\.?\s*hp|nomor\s*hp|no\.?\s*telp|telepon|telp|phone|hp|handphone|wa|whatsapp)\s*[:\-]?\s*((?:\+62|0)[0-9\s\-\.]{7,14})/i',
            '/((?:\+62|0)[0-9\s\-\.]{9,14})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $catatan, $matches)) {
                return preg_replace('/[\s\-\.\(\)]/', '', $matches[1]);
            }
        }
        return $catatan;
    }

    public function getWbpAttribute(?string $value): ?string
    {
        return $this->cleanNamaWbp($value);
    }


    public static function cleanNamaWbp(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return implode(', ', array_filter(array_map('trim', $decoded)));
        }

        $clean = str_replace(['["', '"]', '[', ']', '"', "'"], '', $value);

        return trim(preg_replace('/\s+/', ' ', $clean));
    }
    
    
}
