<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Data Kunjungan SDP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --primary: #1a56db;
            --primary-dark: #1345b7;
            --primary-light: #e8effd;
            --accent: #0ea5e9;
            --success: #059669;
            --danger: #dc2626;
            --warning: #d97706;
            --bg: #f0f4ff;
            --surface: #ffffff;
            --surface2: #f8faff;
            --border: #dbe4f7;
            --text: #111827;
            --text-muted: #6b7280;
            --text-light: #9ca3af;
            --shadow-sm: 0 1px 3px rgba(26,86,219,0.08), 0 1px 2px rgba(0,0,0,0.04);
            --shadow: 0 4px 16px rgba(26,86,219,0.10), 0 1px 4px rgba(0,0,0,0.04);
            --shadow-lg: 0 8px 32px rgba(26,86,219,0.14), 0 2px 8px rgba(0,0,0,0.06);
            --radius: 12px;
            --radius-sm: 8px;
            --transition: 0.2s ease;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            font-size: 14px;
            line-height: 1.5;
        }

        /* ─── Header ─── */
        .header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 32px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-sm);
        }
        .header-brand { display: flex; align-items: center; gap: 12px; }
        .brand-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 16px;
            box-shadow: 0 2px 8px rgba(26,86,219,0.30);
        }
        .brand-text h1 { font-size: 15px; font-weight: 700; letter-spacing: -0.3px; }
        .brand-text span { font-size: 11px; color: var(--text-muted); }
        .header-actions { display: flex; align-items: center; gap: 10px; }

        /* ─── Buttons ─── */
        .btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 18px;
            border-radius: var(--radius-sm);
            font-family: inherit; font-size: 13.5px; font-weight: 600;
            cursor: pointer; transition: all var(--transition);
            border: 1.5px solid transparent;
            text-decoration: none; white-space: nowrap;
        }
        .btn-primary { background: var(--primary); color: white; box-shadow: 0 2px 8px rgba(26,86,219,0.25); }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(26,86,219,0.35); }
        .btn-ghost { background: transparent; color: var(--text-muted); border-color: var(--border); }
        .btn-ghost:hover { background: var(--surface2); color: var(--text); border-color: #c0cfe8; }
        .btn-sm { padding: 5px 10px; font-size: 12px; border-radius: 6px; }
        .btn-edit { background: #e0f2fe; color: #0284c7; border: 1.5px solid #bae6fd; }
        .btn-edit:hover { background: #bae6fd; }
        .btn-delete { background: #fef2f2; color: var(--danger); border: 1.5px solid #fecaca; }
        .btn-delete:hover { background: #fecaca; }

        /* ─── Main ─── */
        .main { padding: 28px 32px; max-width: 1700px; margin: 0 auto; }

        /* ─── Stats ─── */
        .stats-bar { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 18px 22px;
            display: flex; align-items: center; gap: 14px;
            box-shadow: var(--shadow-sm);
        }
        .stat-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 17px; flex-shrink: 0; }
        .stat-icon.blue  { background: var(--primary-light); color: var(--primary); }
        .stat-icon.green { background: #d1fae5; color: var(--success); }
        .stat-icon.sky   { background: #e0f2fe; color: #0284c7; }
        .stat-icon.amber { background: #fef3c7; color: var(--warning); }
        .stat-info label { font-size: 11.5px; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.4px; display: block; }
        .stat-info .stat-value { font-size: 22px; font-weight: 700; line-height: 1.2; font-family: 'JetBrains Mono', monospace; }

        /* ─── Table Card ─── */
        .table-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm); overflow: hidden; }
        .table-header {
            padding: 18px 24px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
        }
        .table-title { font-size: 15px; font-weight: 700; }
        .table-title span { font-size: 12px; color: var(--text-muted); font-weight: 400; margin-left: 8px; }
        .table-filters { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .search-box { position: relative; }
        .search-box i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: 12px; }
        .search-box input {
            padding: 8px 12px 8px 32px; border: 1.5px solid var(--border);
            border-radius: var(--radius-sm); font-family: inherit; font-size: 13px;
            color: var(--text); background: var(--surface2); width: 220px; transition: all var(--transition);
        }
        .search-box input:focus { outline: none; border-color: var(--primary); background: white; box-shadow: 0 0 0 3px rgba(26,86,219,0.10); }
        input[type="date"] {
            padding: 8px 10px; border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            font-family: inherit; font-size: 13px; color: var(--text); background: var(--surface2); transition: all var(--transition);
        }
        input[type="date"]:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(26,86,219,0.10); }

        /* ─── Table ─── */
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: var(--surface2); }
        thead th {
            padding: 11px 14px; text-align: left;
            font-size: 11px; font-weight: 700; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 0.6px;
            border-bottom: 1px solid var(--border); white-space: nowrap;
        }
        thead th:first-child { padding-left: 24px; }
        thead th:last-child  { padding-right: 24px; }
        tbody tr { border-bottom: 1px solid var(--border); transition: background var(--transition); }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: var(--surface2); }
        tbody td { padding: 11px 14px; font-size: 13px; color: var(--text); vertical-align: middle; }
        tbody td:first-child { padding-left: 24px; }
        tbody td:last-child  { padding-right: 24px; }

        .td-no   { font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--text-muted); font-weight: 500; }
        .td-nik  { font-family: 'JetBrains Mono', monospace; font-size: 12px; letter-spacing: 0.5px; color: var(--primary-dark); font-weight: 500; }
        .td-date { font-family: 'JetBrains Mono', monospace; font-size: 12px; white-space: nowrap; }
        .td-phone { font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--success); }
        .td-name { font-weight: 600; }
        .td-sub  { font-size: 12px; color: var(--text-muted); }

        .badge { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
        .badge-laki      { background: #dbeafe; color: #1d4ed8; }
        .badge-perempuan { background: #fce7f3; color: #9d174d; }
        .badge-other     { background: #f3f4f6; color: #4b5563; }

        /* ─── Empty State ─── */
        .empty-state { text-align: center; padding: 60px 24px; }
        .empty-icon { width: 64px; height: 64px; background: var(--primary-light); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 26px; color: var(--primary); }
        .empty-state h3 { font-size: 16px; font-weight: 700; margin-bottom: 6px; }
        .empty-state p  { font-size: 13.5px; color: var(--text-muted); }

        /* ─── Pagination ─── */
        .pagination-wrap {
            padding: 16px 24px; border-top: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
        }
        .pagination-info { font-size: 13px; color: var(--text-muted); }
        .pagination { display: flex; align-items: center; gap: 4px; list-style: none; }
        .page-item .page-link {
            display: flex; align-items: center; justify-content: center;
            min-width: 32px; height: 32px; padding: 0 8px;
            border: 1.5px solid var(--border); border-radius: 8px;
            font-size: 13px; font-weight: 500; color: var(--text-muted);
            text-decoration: none; transition: all var(--transition); font-family: inherit; background: white;
        }
        .page-item .page-link:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
        .page-item.active .page-link { background: var(--primary); color: white; border-color: var(--primary); }
        .page-item.disabled .page-link { opacity: 0.4; cursor: not-allowed; }

        /* ─── Modal ─── */
        .modal-backdrop {
            position: fixed; inset: 0;
            background: rgba(17,24,39,0.5); backdrop-filter: blur(4px);
            z-index: 200; display: none; align-items: center; justify-content: center; padding: 20px;
        }
        .modal-backdrop.show { display: flex; }
        .modal {
            background: var(--surface); border-radius: 16px;
            box-shadow: var(--shadow-lg); width: 100%; max-width: 480px;
            animation: modalIn 0.25s ease;
        }
        .modal-lg { max-width: 620px; }
        @keyframes modalIn { from { opacity: 0; transform: translateY(-16px) scale(0.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .modal-header { padding: 22px 24px 18px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .modal-header h2 { font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .modal-header h2 .icon { width: 32px; height: 32px; background: var(--primary-light); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 13px; }
        .modal-close { width: 30px; height: 30px; border-radius: 8px; border: none; background: var(--surface2); color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all var(--transition); font-size: 14px; }
        .modal-close:hover { background: #fef2f2; color: var(--danger); }
        .modal-body { padding: 24px; }
        .modal-body-scroll { padding: 24px; max-height: 65vh; overflow-y: auto; }
        .modal-footer { padding: 18px 24px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px; }

        /* ─── Form ─── */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-col-full { grid-column: span 2; }
        .form-group label { font-size: 12px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 5px; }
        .form-input {
            width: 100%; padding: 8px 11px;
            border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            font-family: inherit; font-size: 13px; color: var(--text);
            background: var(--surface2); transition: all var(--transition);
        }
        .form-input:focus { outline: none; border-color: var(--primary); background: white; box-shadow: 0 0 0 3px rgba(26,86,219,0.10); }
        select.form-input { cursor: pointer; }

        /* ─── Upload Zone ─── */
        .upload-zone {
            border: 2px dashed var(--border); border-radius: var(--radius);
            padding: 40px 24px; text-align: center; cursor: pointer;
            transition: all var(--transition); background: var(--surface2); position: relative;
        }
        .upload-zone:hover, .upload-zone.dragover { border-color: var(--primary); background: var(--primary-light); }
        .upload-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
        .upload-icon { width: 52px; height: 52px; background: white; border-radius: 12px; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; font-size: 22px; color: var(--primary); box-shadow: var(--shadow-sm); }
        .upload-zone h3 { font-size: 14px; font-weight: 600; margin-bottom: 4px; }
        .upload-zone p  { font-size: 12.5px; color: var(--text-muted); }
        .file-selected { display: none; align-items: center; gap: 12px; padding: 14px 16px; background: #f0fdf4; border: 1.5px solid #86efac; border-radius: var(--radius-sm); margin-top: 14px; }
        .file-selected.show { display: flex; }
        .file-selected .file-icon { font-size: 22px; color: var(--success); }
        .file-selected .file-info { flex: 1; min-width: 0; }
        .file-selected .file-name { font-size: 13px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .file-selected .file-size { font-size: 11.5px; color: var(--text-muted); }
        .file-selected .remove-file { background: none; border: none; color: #9ca3af; cursor: pointer; padding: 4px; border-radius: 4px; transition: color var(--transition); }
        .file-selected .remove-file:hover { color: var(--danger); }
        .upload-notice { background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-sm); padding: 12px 14px; margin-top: 16px; display: flex; gap: 10px; font-size: 12.5px; }
        .upload-notice i { color: var(--warning); margin-top: 1px; flex-shrink: 0; }
        .upload-notice p { color: #92400e; }

        /* ─── Progress ─── */
        .upload-progress { display: none; margin-top: 16px; }
        .upload-progress.show { display: block; }
        .progress-bar { height: 6px; background: var(--border); border-radius: 4px; overflow: hidden; margin-bottom: 8px; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, var(--primary), var(--accent)); border-radius: 4px; transition: width 0.4s ease; width: 0%; }
        .progress-text { font-size: 12px; color: var(--text-muted); text-align: center; }

        /* ─── Spinner ─── */
        .spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.4); border-top-color: white; border-radius: 50%; animation: spin 0.7s linear infinite; display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ─── Toast ─── */
        .toast-container { position: fixed; top: 80px; right: 24px; z-index: 999; display: flex; flex-direction: column; gap: 10px; }
        .toast { background: white; border-radius: 12px; box-shadow: var(--shadow-lg); padding: 14px 18px; display: flex; align-items: flex-start; gap: 12px; min-width: 300px; max-width: 400px; border-left: 4px solid; animation: toastIn 0.3s ease; }
        @keyframes toastIn { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
        .toast.success { border-color: var(--success); }
        .toast.error   { border-color: var(--danger); }
        .toast-icon { font-size: 16px; margin-top: 1px; }
        .toast.success .toast-icon { color: var(--success); }
        .toast.error   .toast-icon { color: var(--danger); }
        .toast-body { flex: 1; }
        .toast-title   { font-size: 13.5px; font-weight: 700; margin-bottom: 2px; }
        .toast-message { font-size: 12.5px; color: var(--text-muted); }

        /* ─── Action buttons in table ─── */
        .action-group { display: flex; gap: 6px; }
    </style>
</head>
<body>

<!-- ─── Header ─── -->
<header class="header">
    <div class="header-brand">
        <div class="brand-icon"><i class="fas fa-users"></i></div>
        <div class="brand-text">
            <h1>Data Kunjungan SDP</h1>
            <span>Sistem Manajemen Pengunjung</span>
        </div>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="openUploadModal()">
            <i class="fas fa-file-import"></i> Import File
        </button>
    </div>
</header>

<!-- ─── Main ─── -->
<main class="main">

    <!-- Stats -->
    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <label>Total Kunjungan</label>
                <div class="stat-value">{{ number_format($data->total()) }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-male"></i></div>
            <div class="stat-info">
                <label>Laki-laki</label>
                <div class="stat-value">{{ number_format(\App\Models\DataKunjungan::whereRaw("LOWER(jenis_kelamin) LIKE '%laki%'")->count()) }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon sky"><i class="fas fa-female"></i></div>
            <div class="stat-info">
                <label>Perempuan</label>
                <div class="stat-value">{{ number_format(\App\Models\DataKunjungan::whereRaw("LOWER(jenis_kelamin) LIKE '%perempuan%' OR LOWER(jenis_kelamin) LIKE '%wanita%'")->count()) }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-info">
                <label>Halaman</label>
                <div class="stat-value">{{ $data->currentPage() }}/{{ $data->lastPage() }}</div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="table-card">
        <div class="table-header">
            <div>
                <div class="table-title">
                    Daftar Pengunjung
                    <span>{{ number_format($data->total()) }} total data</span>
                </div>
            </div>
            <form method="GET" action="{{ route('kunjungan.index') }}" class="table-filters">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Cari nama, NIK..." value="{{ request('search') }}">
                </div>
                <input type="date" name="tanggal_dari" value="{{ request('tanggal_dari') }}" title="Dari tanggal">
                <input type="date" name="tanggal_sampai" value="{{ request('tanggal_sampai') }}" title="Sampai tanggal">
                <button type="submit" class="btn btn-ghost" style="padding:8px 14px;">
                    <i class="fas fa-filter"></i> Filter
                </button>
                @if(request()->hasAny(['search','tanggal_dari','tanggal_sampai']))
                    <a href="{{ route('kunjungan.index') }}" class="btn btn-ghost" style="padding:8px 14px;">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </form>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Nama WBP</th>
                        <th>Nama Pengunjung</th>
                        <th>Jenis Kelamin</th>
                        <th>Hubungan</th>
                        <th>Sub Hubungan</th>
                        <th>Alamat Pengunjung</th>
                        <th>NIK</th>
                        <th>No HP</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $index => $item)
                    <tr>
                        <td class="td-no">{{ $data->firstItem() + $index }}</td>
                        <td class="td-date">{{ $item->tanggal }}</td>
                        <td><span class="td-name">{{ $item->wbp ?? '-' }}</span></td>
                        <td><span class="td-name">{{ $item->pengunjung ?? '-' }}</span></td>
                        <td>
                            @php
                                $jk = strtolower($item->jenis_kelamin ?? '');
                                $badgeClass = str_contains($jk, 'laki') ? 'badge-laki' : (str_contains($jk, 'perempuan') || str_contains($jk, 'wanita') ? 'badge-perempuan' : 'badge-other');
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $item->jenis_kelamin ?? '-' }}</span>
                        </td>
                        <td>{{ $item->hubungan ?? '-' }}</td>
                        <td class="td-sub">{{ $item->sub_hubungan ?? '-' }}</td>
                        <td style="max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $item->alamat_pengunjung }}">
                            {{ $item->alamat_pengunjung ?? '-' }}
                        </td>
                        <td class="td-nik">{{ $item->no_identitas ?? '-' }}</td>
                        <td class="td-phone">{{ $item->no_hp }}</td>
                        <td>
                            <div class="action-group">
                                <button class="btn btn-sm btn-edit"
                                    onclick="openEditModal(
                                        {{ $item->id }},
                                        '{{ addslashes($item->no) }}',
                                        '{{ addslashes($item->wbp) }}',
                                        '{{ addslashes($item->nomor_registrasi) }}',
                                        '{{ addslashes($item->no_kunjungan) }}',
                                        '{{ addslashes($item->pengunjung) }}',
                                        '{{ addslashes($item->jenis_kelamin) }}',
                                        '{{ addslashes($item->hubungan) }}',
                                        '{{ addslashes($item->sub_hubungan) }}',
                                        '{{ addslashes($item->alamat_pengunjung) }}',
                                        '{{ addslashes($item->no_identitas) }}',
                                        '{{ $item->waktu_kunjungan }}',
                                        '{{ addslashes($item->no_kamar) }}',
                                        '{{ addslashes($item->catatan) }}'
                                    )">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-delete" onclick="confirmDelete({{ $item->id }})">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11">
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                                <h3>Belum ada data kunjungan</h3>
                                <p>Klik tombol <strong>Import File</strong> untuk mulai mengunggah data Excel.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($data->hasPages())
        <div class="pagination-wrap">
            <div class="pagination-info">
                Menampilkan {{ $data->firstItem() }}–{{ $data->lastItem() }} dari {{ number_format($data->total()) }} data
            </div>
            <ul class="pagination">
                <li class="page-item {{ !$data->onFirstPage() ? '' : 'disabled' }}">
                    <a class="page-link" href="{{ $data->previousPageUrl() }}"><i class="fas fa-chevron-left"></i></a>
                </li>
                @foreach($data->getUrlRange(max(1,$data->currentPage()-2), min($data->lastPage(),$data->currentPage()+2)) as $page => $url)
                <li class="page-item {{ $page == $data->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                </li>
                @endforeach
                <li class="page-item {{ $data->hasMorePages() ? '' : 'disabled' }}">
                    <a class="page-link" href="{{ $data->nextPageUrl() }}"><i class="fas fa-chevron-right"></i></a>
                </li>
            </ul>
        </div>
        @endif
    </div>

</main>

<!-- ─── Modal Upload ─── -->
<div class="modal-backdrop" id="uploadModal">
    <div class="modal">
        <div class="modal-header">
            <h2><span class="icon"><i class="fas fa-file-upload"></i></span> Import Data Kunjungan</h2>
            <button class="modal-close" onclick="closeUploadModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="upload-zone" id="uploadZone">
                <input type="file" id="fileInput" accept=".csv,.xlsx,.xls" onchange="onFileSelect(this)">
                <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                <h3>Drag & drop file di sini</h3>
                <p>atau klik untuk memilih file  <strong>Excel (.xlsx/.xls)</strong></p>
            </div>
            <div class="file-selected" id="fileSelected">
                <i class="fas fa-file-excel file-icon"></i>
                <div class="file-info">
                    <div class="file-name" id="fileName">—</div>
                    <div class="file-size" id="fileSize">—</div>
                </div>
                <button class="remove-file" onclick="removeFile()"><i class="fas fa-times"></i></button>
            </div>
            <div class="upload-notice">
                <i class="fas fa-info-circle"></i>
                <p><strong>Catatan:</strong> Pastikan kolom NIK atau no identitas berformat text dan header berada pada rows kedua.</p>
            </div>
            <div class="upload-progress" id="uploadProgress">
                <div class="progress-bar"><div class="progress-fill" id="progressFill"></div></div>
                <div class="progress-text" id="progressText">Memproses file...</div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeUploadModal()">Batal</button>
            <button class="btn btn-primary" id="btnSimpan" onclick="submitImport()">
                <span class="spinner" id="btnSpinner"></span>
                <i class="fas fa-save" id="btnIcon"></i>
                <span id="btnText">Simpan</span>
            </button>
        </div>
    </div>
</div>

<!-- ─── Modal Edit ─── -->
<div class="modal-backdrop" id="editModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h2><span class="icon"><i class="fas fa-edit"></i></span> Edit Data Kunjungan</h2>
            <button class="modal-close" onclick="closeEditModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body-scroll">
            <input type="hidden" id="edit_id">
            <div class="form-grid">
                <div class="form-group">
                    <label>No</label>
                    <input type="text" id="edit_no" class="form-input" placeholder="No">
                </div>
                <div class="form-group">
                    <label>Nama WBP</label>
                    <input type="text" id="edit_wbp" class="form-input" placeholder="Nama WBP">
                </div>
                <div class="form-group">
                    <label>No Registrasi</label>
                    <input type="text" id="edit_nomor_registrasi" class="form-input" placeholder="No Registrasi">
                </div>
                <div class="form-group">
                    <label>No Kunjungan</label>
                    <input type="text" id="edit_no_kunjungan" class="form-input" placeholder="No Kunjungan">
                </div>
                <div class="form-group">
                    <label>Nama Pengunjung</label>
                    <input type="text" id="edit_pengunjung" class="form-input" placeholder="Nama Pengunjung">
                </div>
                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select id="edit_jenis_kelamin" class="form-input">
                        <option value="">-- Pilih --</option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Hubungan</label>
                    <input type="text" id="edit_hubungan" class="form-input" placeholder="Hubungan">
                </div>
                <div class="form-group">
                    <label>Sub Hubungan</label>
                    <input type="text" id="edit_sub_hubungan" class="form-input" placeholder="Sub Hubungan">
                </div>
                <div class="form-group form-col-full">
                    <label>Alamat Pengunjung</label>
                    <input type="text" id="edit_alamat_pengunjung" class="form-input" placeholder="Alamat Pengunjung">
                </div>
                <div class="form-group">
                    <label>NIK / No Identitas</label>
                    <input type="text" id="edit_no_identitas" class="form-input" placeholder="NIK" maxlength="20">
                </div>
                <div class="form-group">
                    <label>Tanggal Kunjungan</label>
                    <input type="date" id="edit_waktu_kunjungan" class="form-input">
                </div>
                <div class="form-group">
                    <label>No Kamar</label>
                    <input type="text" id="edit_no_kamar" class="form-input" placeholder="No Kamar / Blok">
                </div>
                <div class="form-group">
                    <label>Catatan / No HP</label>
                    <input type="text" id="edit_catatan" class="form-input" placeholder="Catatan atau No HP">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeEditModal()">Batal</button>
            <button class="btn btn-primary" id="btnUpdate" onclick="submitUpdate()">
                <span class="spinner" id="updateSpinner"></span>
                <i class="fas fa-save" id="updateIcon"></i>
                <span id="updateText">Simpan</span>
            </button>
        </div>
    </div>
</div>

<!-- ─── Toast ─── -->
<div class="toast-container" id="toastContainer"></div>

<script>
// =====================================================================
// UPLOAD MODAL
// =====================================================================
function openUploadModal() {
    document.getElementById('uploadModal').classList.add('show');
}
function closeUploadModal() {
    if (document.getElementById('btnText').textContent === 'Memproses...') return;
    document.getElementById('uploadModal').classList.remove('show');
    removeFile();
    hideProgress();
}
document.getElementById('uploadModal').addEventListener('click', function(e) {
    if (e.target === this) closeUploadModal();
});

// Drag & Drop
const zone = document.getElementById('uploadZone');
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('dragover');
    if (e.dataTransfer.files[0]) {
        document.getElementById('fileInput').files = e.dataTransfer.files;
        onFileSelect(document.getElementById('fileInput'));
    }
});

function onFileSelect(input) {
    const file = input.files[0];
    if (!file) return;
    const allowedExts = ['.csv', '.xlsx', '.xls'];
    const ext = '.' + file.name.split('.').pop().toLowerCase();
    if (!allowedExts.includes(ext)) {
        showToast('error', 'Format Tidak Didukung', 'Hanya file CSV, XLSX, atau XLS.');
        removeFile(); return;
    }
    if (file.size > 10 * 1024 * 1024) {
        showToast('error', 'File Terlalu Besar', 'Ukuran maksimal 10MB.');
        removeFile(); return;
    }
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = formatBytes(file.size);
    document.getElementById('fileSelected').classList.add('show');
}

function removeFile() {
    document.getElementById('fileInput').value = '';
    document.getElementById('fileSelected').classList.remove('show');
}

function formatBytes(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function submitImport() {
    const fileInput = document.getElementById('fileInput');
    if (!fileInput.files[0]) {
        showToast('error', 'File Belum Dipilih', 'Pilih file CSV atau Excel terlebih dahulu.');
        return;
    }
    setUploadLoading(true);
    showProgress();

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    let prog = 0;
    const progInterval = setInterval(() => {
        if (prog < 85) { prog += Math.random() * 8; setProgress(Math.min(prog, 85)); }
    }, 300);

    fetch('{{ route("kunjungan.import") }}', { method: 'POST', body: formData })
    .then(async res => {
        const data = await res.json();
        clearInterval(progInterval);
        setProgress(100);
        setTimeout(() => {
            setUploadLoading(false);
            hideProgress();
            if (data.success) {
                showToast('success', 'Import Berhasil!', data.message);
                closeUploadModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('error', 'Import Gagal', data.message);
            }
        }, 500);
    })
    .catch(() => {
        clearInterval(progInterval);
        setUploadLoading(false);
        hideProgress();
        showToast('error', 'Kesalahan', 'Tidak dapat menghubungi server.');
    });
}

function setUploadLoading(state) {
    document.getElementById('btnSpinner').style.display = state ? 'block' : 'none';
    document.getElementById('btnIcon').style.display    = state ? 'none' : 'inline-block';
    document.getElementById('btnText').textContent      = state ? 'Memproses...' : 'Simpan';
    document.getElementById('btnSimpan').disabled       = state;
}
function showProgress() { document.getElementById('uploadProgress').classList.add('show'); }
function hideProgress() { document.getElementById('uploadProgress').classList.remove('show'); setProgress(0); }
function setProgress(val) {
    document.getElementById('progressFill').style.width = val + '%';
    document.getElementById('progressText').textContent = val < 100 ? `Memproses... ${Math.round(val)}%` : 'Menyimpan ke database...';
}

// =====================================================================
// EDIT MODAL
// =====================================================================
function openEditModal(id, no, wbp, nomor_registrasi, no_kunjungan, pengunjung,
    jenis_kelamin, hubungan, sub_hubungan, alamat_pengunjung,
    no_identitas, waktu_kunjungan, no_kamar, catatan) {

    document.getElementById('edit_id').value                = id;
    document.getElementById('edit_no').value                = no;
    document.getElementById('edit_wbp').value               = wbp;
    document.getElementById('edit_nomor_registrasi').value  = nomor_registrasi;
    document.getElementById('edit_no_kunjungan').value      = no_kunjungan;
    document.getElementById('edit_pengunjung').value        = pengunjung;
    document.getElementById('edit_jenis_kelamin').value     = jenis_kelamin;
    document.getElementById('edit_hubungan').value          = hubungan;
    document.getElementById('edit_sub_hubungan').value      = sub_hubungan;
    document.getElementById('edit_alamat_pengunjung').value = alamat_pengunjung;
    document.getElementById('edit_no_identitas').value      = no_identitas;
    document.getElementById('edit_waktu_kunjungan').value   = waktu_kunjungan;
    document.getElementById('edit_no_kamar').value          = no_kamar;
    document.getElementById('edit_catatan').value           = catatan;

    document.getElementById('editModal').classList.add('show');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
}
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});

function submitUpdate() {
    const id = document.getElementById('edit_id').value;

    const payload = {
        _token:            document.querySelector('meta[name="csrf-token"]').content,
        _method:           'PUT',
        no:                document.getElementById('edit_no').value,
        wbp:               document.getElementById('edit_wbp').value,
        nomor_registrasi:  document.getElementById('edit_nomor_registrasi').value,
        no_kunjungan:      document.getElementById('edit_no_kunjungan').value,
        pengunjung:        document.getElementById('edit_pengunjung').value,
        jenis_kelamin:     document.getElementById('edit_jenis_kelamin').value,
        hubungan:          document.getElementById('edit_hubungan').value,
        sub_hubungan:      document.getElementById('edit_sub_hubungan').value,
        alamat_pengunjung: document.getElementById('edit_alamat_pengunjung').value,
        no_identitas:      document.getElementById('edit_no_identitas').value,
        waktu_kunjungan:   document.getElementById('edit_waktu_kunjungan').value,
        no_kamar:          document.getElementById('edit_no_kamar').value,
        catatan:           document.getElementById('edit_catatan').value,
    };

    setUpdateLoading(true);

    fetch(`/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept':       'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        setUpdateLoading(false);
        if (data.success) {
            showToast('success', 'Berhasil', data.message);
            closeEditModal();
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast('error', 'Gagal', data.message);
        }
    })
    .catch(() => {
        setUpdateLoading(false);
        showToast('error', 'Kesalahan', 'Tidak dapat menghubungi server.');
    });
}

function setUpdateLoading(state) {
    document.getElementById('updateSpinner').style.display = state ? 'block' : 'none';
    document.getElementById('updateIcon').style.display    = state ? 'none' : 'inline-block';
    document.getElementById('updateText').textContent      = state ? 'Menyimpan...' : 'Simpan';
    document.getElementById('btnUpdate').disabled          = state;
}

// =====================================================================
// DELETE
// =====================================================================
function confirmDelete(id) {
    if (!confirm('Yakin ingin menghapus data ini? Tindakan tidak dapat dibatalkan.')) return;

    fetch(`/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept':       'application/json',
        },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Berhasil', data.message);
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast('error', 'Gagal', data.message);
        }
    })
    .catch(() => showToast('error', 'Kesalahan', 'Tidak dapat menghubungi server.'));
}

// =====================================================================
// TOAST
// =====================================================================
function showToast(type, title, message) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="toast-icon fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <div class="toast-body">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
    `;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(20px)';
        toast.style.transition = '0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}
</script>
</body>
</html>