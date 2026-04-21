<?php

namespace App\Imports;

use App\Models\DataKunjungan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class DataKunjunganImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    private int $importedCount = 0;

    /**
     * Konversi Scientific Notation NIK ke format angka penuh
     * Contoh: "3.57123E+15" -> "3571230000000000" (tidak akurat)
     * Solusi terbaik: baca sebagai string dari awal
     */
    private function normalizeNik(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $strValue = (string) $value;

        // Deteksi scientific notation (e.g., 3.57123E+15 atau 3,57123E+15)
        if (preg_match('/^[\d,\.]+[eE][+\-]?\d+$/', trim($strValue))) {
            // Ganti koma desimal (format Indonesia) dengan titik
            $strValue = str_replace(',', '.', $strValue);

            // Konversi scientific notation ke integer penuh
            // Gunakan bcmath untuk presisi tinggi
            $number = (float) $strValue;

            // Format tanpa scientific notation, tanpa desimal
            $formatted = number_format($number, 0, '.', '');

            return $formatted;
        }

        // Hapus karakter non-digit kecuali jika bukan NIK
        // NIK hanya angka
        $cleaned = preg_replace('/[^\d]/', '', $strValue);

        return $cleaned ?: $strValue;
    }

    /**
     * Normalisasi nilai waktu/tanggal dari Excel
     */
    private function normalizeDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Jika numeric (Excel date serial number)
        if (is_numeric($value)) {
            try {
                $date = ExcelDate::excelToDateTimeObject($value);
                return $date->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return (string) $value;
            }
        }

        return (string) $value;
    }

    /**
     * Normalisasi heading key (hapus spasi, lowercase, ganti spasi dengan underscore)
     */
    private function getValueByPossibleKeys(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            // Coba key langsung
            if (array_key_exists($key, $row) && $row[$key] !== null) {
                return $row[$key];
            }
            // Coba lowercase
            $lower = strtolower($key);
            if (array_key_exists($lower, $row) && $row[$lower] !== null) {
                return $row[$lower];
            }
        }
        return null;
    }

    public function model(array $row): ?DataKunjungan
    {
        // Skip baris kosong
        $allEmpty = true;
        foreach ($row as $val) {
            if ($val !== null && $val !== '') {
                $allEmpty = false;
                break;
            }
        }
        if ($allEmpty) return null;

        // Map kolom dengan berbagai kemungkinan nama heading
        $no              = $this->getValueByPossibleKeys($row, ['no', 'no_', 'nomor']);
        $wbp             = $this->getValueByPossibleKeys($row, ['wbp', 'nama_wbp', 'namawbp']);
        $noReg           = $this->getValueByPossibleKeys($row, ['nomor_registrasi', 'no_registrasi', 'registrasi']);
        $noKunjungan     = $this->getValueByPossibleKeys($row, ['no_kunjungan', 'nomor_kunjungan', 'kunjungan']);
        $pengunjung      = $this->getValueByPossibleKeys($row, ['pengunjung', 'nama_pengunjung']);
        $jenisKelamin    = $this->getValueByPossibleKeys($row, ['jenis_kelamin', 'jeniskelamin', 'kelamin', 'gender']);
        $hubungan        = $this->getValueByPossibleKeys($row, ['hubungan']);
        $subHubungan     = $this->getValueByPossibleKeys($row, ['sub_hubungan', 'subhubungan', 'sub']);
        $alamat          = $this->getValueByPossibleKeys($row, ['alamat_pengunjung', 'alamat_penunjung', 'alamat']);
        $noIdentitas     = $this->getValueByPossibleKeys($row, ['no_identitas', 'noidentitas', 'nik', 'ktp', 'identitas']);
        $waktuKunjungan  = $this->getValueByPossibleKeys($row, ['waktu_kunjungan', 'waktu', 'tanggal', 'tanggal_kunjungan']);
        $noKamar         = $this->getValueByPossibleKeys($row, ['no_kamar', 'nokamar', 'kamar', 'blok']);
        $catatan         = $this->getValueByPossibleKeys($row, ['catatan', 'notes', 'keterangan']);

        // Normalisasi NIK - ini bagian terpenting
        $nikNormalized = $this->normalizeNik($noIdentitas);

        $this->importedCount++;

        return new DataKunjungan([
            'no'              => $no ? (int) $no : $this->importedCount,
            'wbp'             => $wbp,
            'nomor_registrasi'=> $noReg,
            'no_kunjungan'    => $noKunjungan,
            'pengunjung'      => $pengunjung,
            'jenis_kelamin'   => $jenisKelamin,
            'hubungan'        => $hubungan,
            'sub_hubungan'    => $subHubungan,
            'alamat_pengunjung'=> $alamat,
            'no_identitas'    => $nikNormalized,
            'waktu_kunjungan' => $this->normalizeDate($waktuKunjungan),
            'no_kamar'        => $noKamar,
            'catatan'         => $catatan,
        ]);
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }
}
