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
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class DataKunjunganController extends Controller
{
    // =====================================================================
    // INDEX
    // =====================================================================
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

    // =====================================================================
    // IMPORT
    // =====================================================================
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ], [
            'file.required' => 'File harus dipilih.',
            'file.mimes'    => 'Format file harus XLS atau XLSX.',
            'file.max'      => 'Ukuran file maksimal 10MB.',
        ]);

        try {
            $file      = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());
            $filePath  = $file->getPathname();

            $rows = $this->readExcel($filePath);

            Log::info('Import file: ' . $file->getClientOriginalName());
            Log::info('Total baris terbaca (termasuk header): ' . count($rows));

            if (empty($rows)) {
                return response()->json(['success' => false, 'message' => 'File kosong atau tidak ada data.'], 422);
            }

            // Ambil & normalisasi heading
            // Skip baris pertama
            array_shift($rows);

            // Ambil baris kedua sebagai heading
            $rawHeadings = array_shift($rows);
            Log::info('Heading asli: ' . json_encode($rawHeadings));

            $headings = array_map(function ($h) {
                $h = trim((string) $h);
                $h = mb_strtolower($h);
                $h = preg_replace('/[\s\.\-\/\\\\]+/', '_', $h);
                $h = preg_replace('/[^a-z0-9_]/', '', $h);
                $h = trim($h, '_');
                return $h;
            }, $rawHeadings);

            Log::info('Heading normalisasi: ' . json_encode($headings));

            if (empty(array_filter($headings))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Heading kolom tidak terbaca. Pastikan baris pertama berisi nama kolom.',
                ], 422);
            }

            $imported = 0;
            $skipped  = 0;
            $batch    = [];

            foreach ($rows as $rowIndex => $row) {
                $row = array_pad((array) $row, count($headings), null);
                $row = array_slice($row, 0, count($headings));
                $data = array_combine($headings, $row);

                // Skip baris kosong
                if (empty(array_filter($data, fn($v) => $v !== null && trim((string)$v) !== ''))) {
                    $skipped++;
                    continue;
                }

                Log::debug("Baris " . ($rowIndex + 2) . " data: " . json_encode($data));

                $batch[] = [
                    'no'                => $this->getExact($data, ['no', 'nomor', 'No']),
                    'wbp'               => $this->getExact($data, ['wbp', 'nama_wbp', 'namawbp', 'WBP', 'warga']),
                    'nomor_registrasi'  => $this->getExact($data, ['Nomor Registrasi','nomor_registrasi', 'no_registrasi', 'nomer_registrasi']),
                    'no_kunjungan'      => $this->getExact($data, ['No Kunjungan','no_kunjungan', 'nomor_kunjungan', 'nomer_kunjungan']),
                    'pengunjung'        => $this->getExact($data, ['Pengunjung','pengunjung', 'nama_pengunjung']),
                    'jenis_kelamin'     => $this->getExact($data, ['Jenis Kelamin','jenis_kelamin', 'jeniskelamin', 'gender']),
                    'hubungan'          => $this->getExact($data, ['Hubungan','hubungan', 'hub']),
                    'sub_hubungan'      => $this->getExact($data, ['Sub Hubungan','sub_hubungan', 'subhubungan', 'sub_hub']),
                    'alamat_pengunjung' => $this->getExact($data, ['Alamat Pengunjung','alamat_pengunjung', 'alamat_penunjung', 'alamat', 'address']),
                    'no_identitas'      => $this->normalizeNik(
                        $this->getExact($data, ['No Identitas','no_identitas', 'noidentitas', 'nik', 'no_ktp', 'ktp', 'nomor_identitas'])
                    ),
                    'waktu_kunjungan'   => $this->normalizeDate(
                        $this->getExact($data, ['Waktu Kunjungan','waktu_kunjungan', 'waktu', 'tanggal', 'tanggal_kunjungan', 'tgl', 'tgl_kunjungan'])
                    ),
                    'no_kamar'          => $this->getExact($data, ['No Kamar','no_kamar', 'nokamar', 'kamar', 'blok', 'no_blok']),
                    'catatan'           => $this->getExact($data, ['Catatan','catatan', 'notes', 'keterangan', 'ket']),
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

    // =====================================================================
    // UPDATE
    // =====================================================================
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = DataKunjungan::findOrFail($id);
            $item->update([
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
            ]);
            return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =====================================================================
    // DESTROY
    // =====================================================================
    public function destroy(int $id): JsonResponse
    {
        try {
            DataKunjungan::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =====================================================================
    // PRIVATE: BACA EXCEL
    // =====================================================================
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

        // String / Text
        if ($dataType === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING) {
            return (string) $rawValue;
        }

        // Format tanggal → konversi ke Y-m-d
        if ($this->isDateTimeFormat($formatCode) && is_numeric($rawValue)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $rawValue)->format('Y-m-d');
            } catch (\Exception $e) {
                $unix = ((int)$rawValue - 25569) * 86400;
                return $unix > 0 ? date('Y-m-d', $unix) : (string)$rawValue;
            }
        }

        // Numerik
        if (is_numeric($rawValue)) {
            $floatVal = (float) $rawValue;
            if ($floatVal >= 1_000_000_000 && $floatVal == floor($floatVal)) {
                return sprintf('%.0f', $floatVal);
            }
            if ($floatVal == floor($floatVal)) {
                return (string)(int)$floatVal;
            }
            return (string)$floatVal;
        }

        return (string) $rawValue;
    }

    // =====================================================================
    // PRIVATE: HELPERS
    // =====================================================================

    /**
     * Ambil nilai dari $data berdasarkan key yang PERSIS cocok (exact match).
     * Tidak menggunakan str_contains agar kolom 'no' tidak tertukar dengan
     * 'no_kunjungan', 'no_kamar', dst.
     */
    private function getExact(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && trim((string)($data[$key] ?? '')) !== '') {
                return trim((string) $data[$key]);
            }
        }
        return null;
    }

    private function normalizeNik(?string $value): ?string
    {
        if ($value === null || trim($value) === '') return null;
        $value = trim($value);

        // Scientific notation: 3,57123E+15 atau 3.57123E+15
        if (preg_match('/^[\d,\.]+[eE][+\-]?\d+$/', $value)) {
            return sprintf('%.0f', (float) str_replace(',', '.', $value));
        }

        $cleaned = preg_replace('/[^\d]/', '', $value);
        return (strlen($cleaned) >= 10 && strlen($cleaned) <= 20) ? $cleaned : $value;
    }

    private function isDateTimeFormat(string $formatCode): bool
    {
        if (empty($formatCode)) return false;
        $lower = strtolower($formatCode);
        $dateTimePatterns = [
            'yyyy',
            'yy',
            'y',
            'mm',
            'm',
            'dd',
            'd',
            'd/m/y',
            'm/d/y',
            'dd/mm',
            'mm/dd',
            'h:mm',
            'hh:mm',
            'am/pm',
            'ss',
            'h:mm:ss'
        ];
        foreach ($dateTimePatterns as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Normalisasi tanggal ke format Y-m-d untuk disimpan ke database.
     */
    private function normalizeDate(?string $value): ?string
    {
        if ($value === null || trim($value) === '') return null;
        $value = trim($value);

        // Sudah Y-m-d
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        // Y-m-d H:i:s → ambil tanggal saja
        if (preg_match('/^(\d{4}-\d{2}-\d{2})[\sT]/', $value, $m)) {
            return $m[1];
        }

        // dd/mm/yyyy atau dd-mm-yyyy (tanpa jam)
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $value, $m)) {
            [$day, $month, $year] = [(int)$m[1], (int)$m[2], (int)$m[3]];
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        // Excel serial number (angka bulat, range 1900–2100)
        if (ctype_digit($value)) {
            $serial = (int)$value;
            if ($serial > 1 && $serial < 73000) {
                try {
                    return ExcelDate::excelToDateTimeObject((float)$serial)->format('Y-m-d');
                } catch (\Exception $e) {
                    $unix = ($serial - 25569) * 86400;
                    if ($unix > 0) return date('Y-m-d', $unix);
                }
            }
        }

        // Fallback strtotime
        $ts = strtotime($value);
        if ($ts !== false && $ts > 0) {
            return date('Y-m-d', $ts);
        }

        Log::warning("normalizeDate: tidak dapat mem-parse: [{$value}]");
        return null;
    }
}
