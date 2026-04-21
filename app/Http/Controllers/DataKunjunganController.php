<?php

namespace App\Http\Controllers;

use App\Models\DataKunjungan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

class DataKunjunganController extends Controller
{
    /**
     * Tampilkan halaman utama
     */
    public function index(Request $request): View
    {
        $query = DataKunjungan::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('wbp', 'like', "%{$search}%")
                    ->orWhere('pengunjung', 'like', "%{$search}%")
                    ->orWhere('no_identitas', 'like', "%{$search}%")
                    ->orWhere('hubungan', 'like', "%{$search}%")
                    ->orWhere('no_kunjungan', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tanggal_dari')) {
            $query->whereDate('waktu_kunjungan', '>=', $request->tanggal_dari);
        }
        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('waktu_kunjungan', '<=', $request->tanggal_sampai);
        }

        $data = $query->orderBy('waktu_kunjungan', 'desc')->paginate(20)->withQueryString();

        return view('index', compact('data'));
    }

    /**
     * Handle upload dan import file CSV/Excel
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ], [
            'file.required' => 'File harus dipilih.',
            'file.mimes'    => 'Format file harus CSV, XLS, atau XLSX.',
            'file.max'      => 'Ukuran file maksimal 20MB.',
        ]);

        try {
            $file      = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());
            $filePath  = $file->getPathname();

            // Debug: log nama file
            Log::info('Import file: ' . $file->getClientOriginalName());

            if ($extension === 'csv') {
                return $this->importCsv($filePath);
            } else {
                $rows = $this->readExcel($filePath);
                return $this->processRows($rows);
            }
        } catch (\Exception $e) {
            Log::error('Import error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import CSV dengan pemrosesan bertahap untuk menghindari memory leak
     */
    private function importCsv(string $filePath): JsonResponse
    {
        $handle = fopen($filePath, 'r');

        // Buang BOM jika ada
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Deteksi delimiter
        $firstLine = fgets($handle);
        rewind($handle);
        if ($bom === "\xEF\xBB\xBF") {
            fread($handle, 3);
        }
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        // Baca heading
        $rawHeadings = fgetcsv($handle, 0, $delimiter, '"');
        if (!$rawHeadings) {
            fclose($handle);
            return response()->json(['success' => false, 'message' => 'File kosong atau tidak ada data.'], 422);
        }

        // Normalisasi heading
        $headings = array_map(function ($h) {
            $h = trim((string) $h);
            $h = mb_strtolower($h);
            $h = preg_replace('/[\s\.\-\/\\\\]+/', '_', $h);
            $h = preg_replace('/[^a-z0-9_]/', '', $h);
            $h = trim($h, '_');
            return $h;
        }, $rawHeadings);

        if (empty(array_filter($headings))) {
            fclose($handle);
            return response()->json([
                'success' => false,
                'message' => 'Heading kolom tidak terbaca. Pastikan baris pertama file berisi nama kolom.'
            ], 422);
        }

        $imported = 0;
        $skipped  = 0;
        $batch    = [];

        while (($row = fgetcsv($handle, 0, $delimiter, '"')) !== false) {
            $row = array_map(function ($v) {
                if ($v === null) return '';
                if (!mb_check_encoding($v, 'UTF-8')) {
                    $v = mb_convert_encoding($v, 'UTF-8', 'Windows-1252');
                }
                return $v;
            }, $row);

            // Pastikan jumlah kolom row sama dengan heading
            $row = array_pad((array) $row, count($headings), null);
            $row = array_slice($row, 0, count($headings));

            // Gabungkan heading dengan nilai baris
            $data = array_combine($headings, $row);

            // Skip baris yang benar-benar kosong
            $allEmpty = true;
            foreach ($data as $val) {
                if ($val !== null && trim((string) $val) !== '') {
                    $allEmpty = false;
                    break;
                }
            }
            if ($allEmpty) {
                $skipped++;
                continue;
            }

            $batch[] = [
                'no'                => $this->getValue($data, ['no', 'nomor', 'no_']) ?: ($imported + 1),
                'wbp'               => $this->getValue($data, ['wbp', 'nama_wbp', 'namawbp', 'nama_warga', 'warga']),
                'nomor_registrasi'  => $this->getValue($data, ['nomor_registrasi', 'no_registrasi', 'registrasi', 'no_reg', 'nomer_registrasi']),
                'no_kunjungan'      => $this->getValue($data, ['no_kunjungan', 'nomor_kunjungan', 'kunjungan', 'no_kun', 'nomer_kunjungan']),
                'pengunjung'        => $this->getValue($data, ['pengunjung', 'nama_pengunjung', 'nama_penjung', 'nama']),
                'jenis_kelamin'     => $this->getValue($data, ['jenis_kelamin', 'jeniskelamin', 'jenis', 'kelamin', 'gender', 'sex']),
                'hubungan'          => $this->getValue($data, ['hubungan', 'hub']),
                'sub_hubungan'      => $this->getValue($data, ['sub_hubungan', 'subhubungan', 'sub', 'sub_hub']),
                'alamat_pengunjung' => $this->getValue($data, ['alamat_pengunjung', 'alamat_penunjung', 'alamat', 'address']),
                'no_identitas'      => $this->normalizeNik(
                    $this->getValue($data, ['No_Identitas', 'no_identitas', 'no identitas', 'noidentitas', 'nik', 'ktp', 'identitas', 'no_ktp', 'no_nik'])
                ),
                'waktu_kunjungan'   => $this->normalizeDate(
                    $this->getValue($data, ['waktu_kunjungan', 'waktu', 'tanggal', 'tanggal_kunjungan', 'tgl', 'tgl_kunjungan', 'datetime'])
                ),
                'no_kamar'          => $this->getValue($data, ['no_kamar', 'nokamar', 'kamar', 'blok', 'no_blok', 'blok_kamar']),
                'catatan'           => $this->getValue($data, ['catatan', 'notes', 'keterangan', 'ket']),
            ];

            $imported++;

            // Insert per 500 baris
            if (count($batch) >= 500) {
                DataKunjungan::insert($batch);
                $batch = [];
            }
        }

        // Insert sisa data
        if (!empty($batch)) {
            DataKunjungan::insert($batch);
        }

        fclose($handle);

        Log::info("Import selesai: {$imported} data masuk, {$skipped} baris dilewati.");

        return response()->json([
            'success' => true,
            'message' => "Berhasil mengimport {$imported} data kunjungan.",
        ]);
    }

    /**
     * Proses baris untuk Excel (tetap seperti sebelumnya, tapi dipisah)
     */
    private function processRows(array $rows): JsonResponse
    {
        // Debug: log jumlah baris yang terbaca
        Log::info('Total baris terbaca (termasuk header): ' . count($rows));

        if (empty($rows)) {
            return response()->json(['success' => false, 'message' => 'File kosong atau tidak ada data.'], 422);
        }

        // Ambil baris pertama sebagai heading
        $rawHeadings = array_shift($rows);

        // Debug: log heading yang terbaca dari file
        Log::info('Heading asli dari file: ' . json_encode($rawHeadings));

        // Normalisasi heading: lowercase, trim, ganti spasi/titik/strip dengan underscore
        $headings = array_map(function ($h) {
            $h = trim((string) $h);
            $h = mb_strtolower($h);
            $h = preg_replace('/[\s\.\-\/\\\\]+/', '_', $h);
            $h = preg_replace('/[^a-z0-9_]/', '', $h);
            $h = trim($h, '_');
            return $h;
        }, $rawHeadings);

        // Debug: log heading setelah normalisasi
        Log::info('Heading setelah normalisasi: ' . json_encode($headings));

        if (empty(array_filter($headings))) {
            return response()->json([
                'success' => false,
                'message' => 'Heading kolom tidak terbaca. Pastikan baris pertama file berisi nama kolom.'
            ], 422);
        }

        $imported = 0;
        $skipped  = 0;
        $batch    = [];

        foreach ($rows as $row) {
            // Pastikan jumlah kolom row sama dengan heading
            $row = array_pad((array) $row, count($headings), null);
            $row = array_slice($row, 0, count($headings));

            // Gabungkan heading dengan nilai baris
            $data = array_combine($headings, $row);

            // Skip baris yang benar-benar kosong
            $allEmpty = true;
            foreach ($data as $val) {
                if ($val !== null && trim((string) $val) !== '') {
                    $allEmpty = false;
                    break;
                }
            }
            if ($allEmpty) {
                $skipped++;
                continue;
            }

            $batch[] = [
                'no'                => $this->getValue($data, ['no', 'nomor', 'no_']) ?: ($imported + 1),
                'wbp'               => $this->getValue($data, ['wbp', 'nama_wbp', 'namawbp', 'nama_warga', 'warga']),
                'nomor_registrasi'  => $this->getValue($data, ['nomor_registrasi', 'no_registrasi', 'registrasi', 'no_reg', 'nomer_registrasi']),
                'no_kunjungan'      => $this->getValue($data, ['no_kunjungan', 'nomor_kunjungan', 'kunjungan', 'no_kun', 'nomer_kunjungan']),
                'pengunjung'        => $this->getValue($data, ['pengunjung', 'nama_pengunjung', 'nama_penjung', 'nama']),
                'jenis_kelamin'     => $this->getValue($data, ['jenis_kelamin', 'jeniskelamin', 'jenis', 'kelamin', 'gender', 'sex']),
                'hubungan'          => $this->getValue($data, ['hubungan', 'hub']),
                'sub_hubungan'      => $this->getValue($data, ['sub_hubungan', 'subhubungan', 'sub', 'sub_hub']),
                'alamat_pengunjung' => $this->getValue($data, ['alamat_pengunjung', 'alamat_penunjung', 'alamat', 'address']),
                'no_identitas'      => $this->normalizeNik(
                    $this->getValue($data, ['No_Identitas', 'no_identitas', 'no identitas', 'noidentitas', 'nik', 'ktp', 'identitas', 'no_ktp', 'no_nik'])
                ),
                'waktu_kunjungan'   => $this->normalizeDate(
                    $this->getValue($data, ['waktu_kunjungan', 'waktu', 'tanggal', 'tanggal_kunjungan', 'tgl', 'tgl_kunjungan', 'datetime'])
                ),
                'no_kamar'          => $this->getValue($data, ['no_kamar', 'nokamar', 'kamar', 'blok', 'no_blok', 'blok_kamar']),
                'catatan'           => $this->getValue($data, ['catatan', 'notes', 'keterangan', 'ket']),
                // 'created_at'        => now(),
                // 'updated_at'        => now(),
            ];

            $imported++;

            // Insert per 500 baris agar tidak memory leak
            if (count($batch) >= 500) {
                DataKunjungan::insert($batch);
                $batch = [];
            }
        }

        // Insert sisa data
        if (!empty($batch)) {
            DataKunjungan::insert($batch);
        }

        Log::info("Import selesai: {$imported} data masuk, {$skipped} baris dilewati.");

        return response()->json([
            'success' => true,
            'message' => "Berhasil mengimport {$imported} data kunjungan.",
        ]);
    }

    /**
     * Baca file CSV - semua kolom otomatis terbaca sebagai string
     */
    private function readCsv(string $filePath): array
    {
        $rows   = [];
        $handle = fopen($filePath, 'r');

        // Buang BOM jika ada (file Excel CSV sering ada BOM di awal)
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Deteksi delimiter otomatis: koma atau titik koma
        $firstLine = fgets($handle);
        rewind($handle);
        if ($bom === "\xEF\xBB\xBF") {
            fread($handle, 3); // skip BOM lagi
        }
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        while (($row = fgetcsv($handle, 0, $delimiter, '"')) !== false) {
            $row = array_map(function ($v) {
                if ($v === null) return '';
                if (!mb_check_encoding($v, 'UTF-8')) {
                    $v = mb_convert_encoding($v, 'UTF-8', 'Windows-1252');
                }
                return $v;
            }, $row);

            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Baca file Excel dengan StringValueBinder
     * KUNCI: agar NIK tidak berubah jadi Scientific Notation
     */
    private function readExcel(string $filePath): array
    {
        // Set StringValueBinder agar semua sel dibaca sebagai string
        Cell::setValueBinder(new StringValueBinder());

        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = [];

        $highestRow    = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();

        for ($rowNum = 1; $rowNum <= $highestRow; $rowNum++) {
            $cellRange = $sheet->rangeToArray(
                'A' . $rowNum . ':' . $highestColumn . $rowNum,
                null,   // nullValue
                true,   // calculateFormulas
                false,  // formatData -> FALSE agar angka tidak diformat
                false   // returnCellRef
            );

            $rowData = array_map(function ($val) {
                if ($val === null) return '';
                if ($val instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                    return $val->getPlainText();
                }
                return (string) $val;
            }, $cellRange[0] ?? []);

            $rows[] = $rowData;
        }

        return $rows;
    }

    /**
     * Ambil nilai dari array berdasarkan beberapa kemungkinan key
     */
    private function getValue(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            // Exact match
            if (array_key_exists($key, $data) && trim((string)($data[$key] ?? '')) !== '') {
                return trim((string) $data[$key]);
            }
            // Partial match: cek apakah key ada sebagai bagian dari key di data
            foreach ($data as $dataKey => $dataVal) {
                if (str_contains($dataKey, $key) && trim((string)($dataVal ?? '')) !== '') {
                    return trim((string) $dataVal);
                }
            }
        }
        return null;
    }

    /**
     * Normalisasi tanggal dari berbagai format ke format Y-m-d
     * Menangani:
     * - Excel Serial Number : 45306       → 2024-01-15
     * - Format Indonesia    : 15/01/2024  → 2024-01-15
     * - Format dengan jam   : 2024-01-15 09:00:00 → 2024-01-15
     * - Format standar      : 2024-01-15  → 2024-01-15
     */
    private function normalizeDate(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        // Angka murni = Excel Date Serial Number
        // Contoh: 45306 = 15 Januari 2024
        if (is_numeric($value) && !str_contains($value, '.')) {
            try {
                $unixTimestamp = ((int) $value - 25569) * 86400;
                $date = \DateTime::createFromFormat('U', (string) $unixTimestamp);
                return $date ? $date->format('Y-m-d') : null;
            } catch (\Exception $e) {
                return null;
            }
        }

        // Format Indonesia: dd/mm/yyyy atau dd-mm-yyyy
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $value, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        // Format dengan jam, ambil tanggal saja: 2024-01-15 09:00:00
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $value, $m)) {
            return $m[1];
        }

        // Fallback: coba parse dengan strtotime
        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    /**
     * Normalisasi NIK dari Scientific Notation ke angka penuh
     * Input:  "3.57123E+15" atau "3,57123E+15"
     * Output: "3571230000000000"
     */
    private function normalizeNik(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        // Deteksi Scientific Notation
        if (preg_match('/^[\d,\.]+[eE][+\-]?\d+$/', $value)) {
            $value  = str_replace(',', '.', $value);
            $result = sprintf('%.0f', (float) $value);
            return $result;
        }

        // Bersihkan karakter non-digit
        $cleaned = preg_replace('/[^\d]/', '', $value);

        if (strlen($cleaned) >= 10 && strlen($cleaned) <= 20) {
            return $cleaned;
        }

        return $value;
    }

    /**
     * Hapus semua data
     */
    public function truncate(): JsonResponse
    {
        try {
            DataKunjungan::truncate();
            return response()->json(['success' => true, 'message' => 'Semua data berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Hapus satu record
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            DataKunjungan::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
