<?php

namespace App\Http\Controllers;

use App\Models\DataKunjungan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class DataKunjunganController extends Controller
{
    // =========================================================================
    // AUTH CHECK
    // =========================================================================

    private function checkAuth()
    {
        if (!session('logged_in')) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }
        return null;
    }

    private function checkAuthJson(): ?JsonResponse
    {
        if (!session('logged_in')) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi habis. Silakan login kembali.',
            ], 401);
        }
        return null;
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        if ($redirect = $this->checkAuth()) return $redirect;

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

    // =========================================================================
    // IMPORT EXCEL
    // =========================================================================

    public function import(Request $request): JsonResponse
    {
        if ($error = $this->checkAuthJson()) return $error;

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ], [
            'file.required' => 'File harus dipilih.',
            'file.mimes'    => 'Format file harus XLS atau XLSX.',
            'file.max'      => 'Ukuran file maksimal 10MB.',
        ]);

        try {
            $file     = $request->file('file');
            $filePath = $file->getPathname();
            $rows     = $this->readExcel($filePath);

            Log::info('Import file: ' . $file->getClientOriginalName());
            Log::info('Total baris terbaca (termasuk header): ' . count($rows));

            if (empty($rows)) {
                return response()->json(['success' => false, 'message' => 'File kosong atau tidak ada data.'], 422);
            }

            // Baris pertama: judul/metadata → dilewati
            array_shift($rows);

            // Baris kedua: heading kolom
            $rawHeadings = array_shift($rows);

            Log::info('Heading asli: ' . json_encode($rawHeadings));

            $headings = array_map(function ($h) {
                $h = trim((string) $h);
                $h = mb_strtolower($h);
                $h = preg_replace('/[\s\.\-\/\\\\]+/', '_', $h);
                $h = preg_replace('/[^a-z0-9_]/', '', $h);
                return trim($h, '_');
            }, $rawHeadings);

            Log::info('Heading normalisasi: ' . json_encode($headings));

            if (empty(array_filter($headings))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Heading kolom tidak terbaca. Pastikan baris kedua berisi nama kolom.',
                ], 422);
            }

            $imported = 0;
            $skipped  = 0;
            $batch    = [];

            foreach ($rows as $row) {
                $row  = array_pad((array) $row, count($headings), null);
                $row  = array_slice($row, 0, count($headings));
                $data = array_combine($headings, $row);

                if (empty(array_filter($data, fn($v) => $v !== null && trim((string) $v) !== ''))) {
                    $skipped++;
                    continue;
                }

                $batch[] = [
                    'no'                => $this->getExact($data, ['no', 'nomor']),
                    'wbp'               => $this->getExact($data, ['wbp', 'nama_wbp', 'namawbp', 'warga']),
                    'nomor_registrasi'  => $this->getExact($data, ['nomor_registrasi', 'no_registrasi', 'nomer_registrasi']),
                    'no_kunjungan'      => $this->getExact($data, ['no_kunjungan', 'nomor_kunjungan', 'nomer_kunjungan']),
                    'pengunjung'        => $this->getExact($data, ['pengunjung', 'nama_pengunjung']),
                    'jenis_kelamin'     => $this->getExact($data, ['jenis_kelamin', 'jeniskelamin', 'gender']),
                    'hubungan'          => $this->getExact($data, ['hubungan', 'hub']),
                    'sub_hubungan'      => $this->getExact($data, ['sub_hubungan', 'subhubungan', 'sub_hub']),
                    'alamat_pengunjung' => $this->getExact($data, ['alamat_pengunjung', 'alamat_penunjung', 'alamat']),
                    'no_identitas'      => $this->normalizeNik(
                        $this->getExact($data, ['no_identitas', 'noidentitas', 'nik', 'ktp', 'no_ktp', 'nomor_identitas', 'no_identias'])
                    ),
                    'waktu_kunjungan'   => $this->normalizeDate(
                        $this->getExact($data, ['waktu_kunjungan', 'tanggal', 'tanggal_kunjungan', 'tgl', 'tgl_kunjungan', 'waktu'])
                    ),
                    'no_kamar'          => $this->getExact($data, ['no_kamar', 'nokamar', 'kamar', 'blok', 'no_blok']),
                    'catatan'           => $this->getExact($data, ['catatan', 'notes', 'keterangan', 'ket']),
                    'foto_ktp'          => null,
                    'foto_diri'         => null,
                ];

                $imported++;

                if (count($batch) >= 500) {
                    DataKunjungan::insert($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                DataKunjungan::insert($batch);
            }

            Log::info("Import selesai: {$imported} masuk, {$skipped} dilewati.");

            return response()->json([
                'success' => true,
                'message' => "Berhasil mengimport {$imported} data kunjungan.",
            ]);
        } catch (\Exception $e) {
            Log::error('Import error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update(Request $request, int $id): JsonResponse
    {
        if ($error = $this->checkAuthJson()) return $error;

        try {
            $kunjungan = DataKunjungan::findOrFail($id);

            $payload = [
                'no'                => $request->no,
                'wbp'               => $request->wbp,
                'nomor_registrasi'  => $request->nomor_registrasi,
                'no_kunjungan'      => $request->no_kunjungan,
                'pengunjung'        => $request->pengunjung,
                'jenis_kelamin'     => $request->jenis_kelamin,
                'hubungan'          => $request->hubungan,
                'sub_hubungan'      => $request->sub_hubungan,
                'alamat_pengunjung' => $request->alamat_pengunjung,
                'no_identitas'      => $request->no_identitas,
                'waktu_kunjungan'   => $request->waktu_kunjungan ?: null,
                'no_kamar'          => $request->no_kamar,
                'catatan'           => $request->catatan,
            ];

            // ── Foto KTP ──────────────────────────────────────────────────────
            if ($request->hasFile('foto_ktp')) {
                // Hapus file lama dari storage jika ada
                if ($kunjungan->foto_ktp && Storage::disk('public')->exists($kunjungan->foto_ktp)) {
                    Storage::disk('public')->delete($kunjungan->foto_ktp);
                }

                // Simpan file baru ke storage/app/public/ktp/
                $path = $request->file('foto_ktp')->store('ktp', 'public');
                $payload['foto_ktp'] = $path;
            }

            // ── Foto Diri ─────────────────────────────────────────────────────
            if ($request->hasFile('foto_diri')) {
                // Hapus file lama dari storage jika ada
                if ($kunjungan->foto_diri && Storage::disk('public')->exists($kunjungan->foto_diri)) {
                    Storage::disk('public')->delete($kunjungan->foto_diri);
                }

                // Simpan file baru ke storage/app/public/diri/
                $path = $request->file('foto_diri')->store('diri', 'public');
                $payload['foto_diri'] = $path;
            }

            $kunjungan->update($payload);

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diperbarui.',
            ]);
        } catch (\Exception $e) {
            Log::error("Update Error ID {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function destroy(int $id): JsonResponse
    {
        if ($error = $this->checkAuthJson()) return $error;

        try {
            $kunjungan = DataKunjungan::findOrFail($id);

            // Hapus foto dari storage jika ada
            if ($kunjungan->foto_ktp && Storage::disk('public')->exists($kunjungan->foto_ktp)) {
                Storage::disk('public')->delete($kunjungan->foto_ktp);
            }
            if ($kunjungan->foto_diri && Storage::disk('public')->exists($kunjungan->foto_diri)) {
                Storage::disk('public')->delete($kunjungan->foto_diri);
            }

            $kunjungan->delete();

            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // TRUNCATE
    // =========================================================================

    public function truncate(): JsonResponse
    {
        if ($error = $this->checkAuthJson()) return $error;

        try {
            DataKunjungan::truncate();
            return response()->json(['success' => true, 'message' => 'Semua data berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PRIVATE: BACA EXCEL
    // =========================================================================

    private function readExcel(string $filePath): array
    {
        Cell::setValueBinder(new StringValueBinder());

        $spreadsheet   = IOFactory::load($filePath);
        $sheet         = $spreadsheet->getActiveSheet();
        $rows          = [];
        $highestRow    = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();

        for ($rowNum = 1; $rowNum <= $highestRow; $rowNum++) {
            $rowData = [];
            foreach ($sheet->getRowIterator($rowNum, $rowNum)->current()->getCellIterator('A', $highestColumn) as $cell) {
                $rowData[] = $this->readCellValue($cell);
            }
            $rows[] = $rowData;
        }

        return $rows;
    }

    private function readCellValue(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell): string
    {
        $rawValue   = $cell->getValue();
        $dataType   = $cell->getDataType();
        $formatCode = $cell->getStyle()->getNumberFormat()->getFormatCode();

        if ($rawValue === null || $rawValue === '') return '';

        if ($rawValue instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            return $rawValue->getPlainText();
        }

        if ($dataType === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING) {
            return (string) $rawValue;
        }

        if ($this->isDateFormat($formatCode) && is_numeric($rawValue)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $rawValue)->format('Y-m-d');
            } catch (\Exception $e) {
                $unix = ((int) $rawValue - 25569) * 86400;
                return $unix > 0 ? date('Y-m-d', $unix) : (string) $rawValue;
            }
        }

        if (is_numeric($rawValue)) {
            $float = (float) $rawValue;
            if ($float >= 1_000_000_000 && $float == floor($float)) {
                return sprintf('%.0f', $float);
            }
            if ($float == floor($float)) {
                return (string) (int) $float;
            }
            return (string) $float;
        }

        return (string) $rawValue;
    }

    // =========================================================================
    // PRIVATE: HELPERS
    // =========================================================================

    private function getExact(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && trim((string) ($data[$key] ?? '')) !== '') {
                return trim((string) $data[$key]);
            }
        }
        return null;
    }

    private function normalizeNik(?string $value): ?string
    {
        if ($value === null || trim($value) === '') return null;

        $value = trim($value);

        if (preg_match('/^[\d,\.]+[eE][+\-]?\d+$/', $value)) {
            return sprintf('%.0f', (float) str_replace(',', '.', $value));
        }

        $cleaned = preg_replace('/[^\d]/', '', $value);
        return (strlen($cleaned) >= 10 && strlen($cleaned) <= 20) ? $cleaned : $value;
    }

    private function isDateFormat(string $formatCode): bool
    {
        if (empty($formatCode)) return false;

        $lower    = strtolower($formatCode);
        $patterns = ['yyyy', 'yy', 'dd', 'mm', 'd/m', 'm/d', 'dd/mm', 'mm/dd', 'h:mm', 'hh:mm', 'am/pm', 'ss'];

        foreach ($patterns as $pattern) {
            if (str_contains($lower, $pattern)) return true;
        }

        return false;
    }

    private function normalizeDate(?string $value): ?string
    {
        if ($value === null || trim($value) === '') return null;

        $value = trim($value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return $value;

        if (preg_match('/^(\d{4}-\d{2}-\d{2})[\sT]/', $value, $m)) return $m[1];

        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $value, $m)) {
            [$day, $month, $year] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        if (ctype_digit($value)) {
            $serial = (int) $value;
            if ($serial > 1 && $serial < 73000) {
                try {
                    return ExcelDate::excelToDateTimeObject((float) $serial)->format('Y-m-d');
                } catch (\Exception $e) {
                    $unix = ($serial - 25569) * 86400;
                    if ($unix > 0) return date('Y-m-d', $unix);
                }
            }
        }

        $ts = strtotime($value);
        if ($ts !== false && $ts > 0) return date('Y-m-d', $ts);

        Log::warning("normalizeDate: gagal mem-parse nilai [{$value}]");
        return null;
    }
}
