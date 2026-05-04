<?php

namespace App\Imports;

use App\Models\DataKunjungan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

class DataKunjunganImport extends DefaultValueBinder implements ToModel, WithHeadingRow, SkipsEmptyRows, WithCustomValueBinder
{
    private int $importedCount = 0;

    private function normalizeNik($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string)$value);

        // Jika scientific notation (misal: 3.317E+15)
        if (preg_match('/^[0-9\.]+E\+\d+$/i', $value)) {
            $value = sprintf('%.0f', $value);
        }

        // Ambil hanya angka
        $value = preg_replace('/[^0-9]/', '', $value);

        return $value ?: null;
    }

    private function normalizeDate($value): ?string
    {
        if (!$value) return null;

        try {
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getValue(array $row, array $keys)
    {
        foreach ($keys as $key) {
            $key = strtolower($key);
            foreach ($row as $k => $v) {
                if (strtolower(trim($k)) === $key) {
                    return $v;
                }
            }
        }
        return null;
    }

    public function model(array $row)
    {
        // Skip jika baris benar-benar kosong
        if (count(array_filter($row)) === 0) {
            return null;
        }

        $nik = $this->normalizeNik(
            $this->getValue($row, ['no_identitas', 'nik', 'ktp'])
        );

        $this->importedCount++;

        return new DataKunjungan([
            'no'               => $this->importedCount,
            'wbp'              => $this->getValue($row, ['wbp', 'nama_wbp']),
            'nomor_registrasi' => $this->getValue($row, ['nomor_registrasi', 'no_reg']),
            'no_kunjungan'     => $this->getValue($row, ['no_kunjungan']),
            'pengunjung'       => $this->getValue($row, ['pengunjung', 'nama_pengunjung']),
            'jenis_kelamin'    => $this->mapGender(
                $this->getValue($row, ['jenis_kelamin', 'gender', 'jk'])
            ),
            'hubungan'         => $this->getValue($row, ['hubungan']),
            'sub_hubungan'     => $this->getValue($row, ['sub_hubungan']),
            'alamat_pengunjung' => $this->getValue($row, ['alamat_pengunjung', 'alamat']),
            'no_identitas'     => $nik,
            'no_hp'            => $this->getValue($row, ['no_hp', 'telepon', 'hp']), // Tambahan sesuai UI
            'waktu_kunjungan'  => $this->normalizeDate(
                $this->getValue($row, ['waktu_kunjungan', 'tanggal', 'tgl'])
            ),
            'no_kamar'         => $this->getValue($row, ['no_kamar', 'kamar']),
            'catatan'          => $this->getValue($row, ['catatan']),

            // Set null secara eksplisit agar tidak error saat import meskipun kolom ada di DB
            'foto_ktp'         => null,
            'foto_diri'        => null,
        ]);
    }

    private function mapGender($value)
    {
        if (!$value) return null;

        $v = strtolower(trim($value));

        if (in_array($v, ['l', 'laki', 'laki-laki', 'pria', 'male'])) return 'Laki-laki';
        if (in_array($v, ['p', 'perempuan', 'wanita', 'female'])) return 'Perempuan';

        return null;
    }

    public function bindValue(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell, $value)
    {
        // Mencegah NIK/No HP berubah jadi format scientific di tingkat Spreadsheet
        if (is_numeric($value) && strlen((string)$value) > 10) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }
}
