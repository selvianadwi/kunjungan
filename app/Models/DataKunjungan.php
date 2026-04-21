<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataKunjungan extends Model
{
    protected $table = 'data_kunjungan';

    protected $fillable = [
        'no',
        'wbp',
        'nomor_registrasi',
        'no_kunjungan',
        'pengunjung',
        'jenis_kelamin',
        'hubungan',
        'sub_hubungan',
        'alamat_pengunjung',
        'no_identitas',
        'waktu_kunjungan',
        'no_kamar',
        'catatan',
    ];

    /**
     * Ekstrak No HP dari kolom catatan
     * Format catatan biasanya mengandung "No HP: 08xxx" atau serupa
     */
    public function getNoHpAttribute(): string
    {
        if (!$this->catatan) return '-';

        // Coba berbagai pattern umum untuk no HP di kolom catatan
        $patterns = [
            '/(?:no\.?\s*hp|no\.?\s*telp|telpon|telepon|hp|handphone)\s*[:\-]?\s*([\d\s\+\-]{8,15})/i',
            '/\b((?:\+62|62|0)[\d\s\-]{8,14})\b/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $this->catatan, $matches)) {
                return trim(preg_replace('/\s+/', '', $matches[1]));
            }
        }

        return '-';
    }

    /**
     * Format tanggal dari waktu_kunjungan
     */
    public function getTanggalAttribute(): string
    {
        if (!$this->waktu_kunjungan) return '-';

        try {
            $date = \Carbon\Carbon::parse($this->waktu_kunjungan);
            return $date->format('d/m/Y');
        } catch (\Exception $e) {
            // Jika format tidak standar, ambil bagian tanggal saja
            $parts = preg_split('/[\s\T]/', $this->waktu_kunjungan);
            return $parts[0] ?? $this->waktu_kunjungan;
        }
    }
}
