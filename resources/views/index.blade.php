<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Data Kunjungan SDP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --primary: #1e40af;
            --primary-hover: #1d3a9e;
            --primary-light: #eff6ff;
            --primary-glow: rgba(30,64,175,0.15);
            --accent: #3b82f6;
            --success: #059669;
            --success-light: #ecfdf5;
            --danger: #dc2626;
            --danger-hover: #b91c1c;
            --danger-light: #fef2f2;
            --warning: #d97706;
            --warning-light: #fffbeb;
            --bg: #f1f5f9;
            --surface: #ffffff;
            --surface2: #f8fafc;
            --surface3: #f1f5f9;
            --border: #e2e8f0;
            --border-strong: #cbd5e1;
            --text: #0f172a;
            --text-muted: #64748b;
            --text-light: #94a3b8;
            --shadow-xs: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.04);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.10), 0 4px 8px rgba(0,0,0,0.05);
            --shadow-xl: 0 20px 50px rgba(0,0,0,0.12), 0 8px 16px rgba(0,0,0,0.06);
            --radius: 14px;
            --radius-sm: 8px;
            --radius-xs: 6px;
            --ease: cubic-bezier(0.4, 0, 0.2, 1);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            font-size: 14px;
            line-height: 1.6;
        }

        /* ═══════════════════════════════════════
           HEADER
        ═══════════════════════════════════════ */
        .header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 28px;
            height: 62px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-sm);
        }

        .header-left { display: flex; align-items: center; gap: 14px; }

        .brand-mark {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 15px;
            box-shadow: 0 2px 8px rgba(30,64,175,0.30);
            flex-shrink: 0;
        }

        .brand-info h1 {
            font-size: 14px; font-weight: 700;
            color: var(--text); letter-spacing: -0.2px; line-height: 1.2;
        }
        .brand-info p { font-size: 11px; color: var(--text-muted); font-weight: 400; }

        .header-divider {
            width: 1px; height: 28px;
            background: var(--border); margin: 0 4px;
        }

        .header-right { display: flex; align-items: center; gap: 8px; }

        /* User badge */
        .user-badge {
            display: flex; align-items: center; gap: 8px;
            padding: 6px 12px 6px 8px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 30px;
            font-size: 12.5px; font-weight: 500;
            color: var(--text-muted);
        }
        .user-avatar {
            width: 24px; height: 24px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 10px; font-weight: 700;
        }

        /* ═══════════════════════════════════════
           BUTTONS
        ═══════════════════════════════════════ */
        .btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            font-family: inherit; font-size: 13px; font-weight: 600;
            cursor: pointer; transition: all 0.18s var(--ease);
            border: 1.5px solid transparent;
            white-space: nowrap; text-decoration: none;
            letter-spacing: 0.01em;
        }
        .btn i { font-size: 12px; }

        .btn-primary {
            background: var(--primary); color: white;
            box-shadow: 0 2px 6px rgba(30,64,175,0.25);
        }
        .btn-primary:hover {
            background: var(--primary-hover);
            box-shadow: 0 4px 12px rgba(30,64,175,0.35);
            transform: translateY(-1px);
        }

        .btn-import {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: white;
            box-shadow: 0 2px 8px rgba(30,64,175,0.30);
            padding: 8px 18px;
        }
        .btn-import:hover {
            box-shadow: 0 4px 16px rgba(30,64,175,0.40);
            transform: translateY(-1px);
            filter: brightness(1.05);
        }

        .btn-logout {
            background: var(--danger); color: white;
            box-shadow: 0 2px 6px rgba(220,38,38,0.25);
            border-color: transparent;
        }
        .btn-logout:hover {
            background: var(--danger-hover);
            box-shadow: 0 4px 12px rgba(220,38,38,0.35);
            transform: translateY(-1px);
        }

        .btn-ghost {
            background: transparent; color: var(--text-muted);
            border-color: var(--border);
        }
        .btn-ghost:hover {
            background: var(--surface2); color: var(--text);
            border-color: var(--border-strong);
        }

        .btn-sm { padding: 5px 10px; font-size: 11.5px; border-radius: var(--radius-xs); gap: 5px; }
        .btn-sm i { font-size: 11px; }

        .btn-edit { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .btn-edit:hover { background: #dbeafe; border-color: #93c5fd; }

        .btn-del { background: var(--danger-light); color: var(--danger); border-color: #fecaca; }
        .btn-del:hover { background: #fecaca; border-color: #f87171; }

        /* ═══════════════════════════════════════
           MAIN LAYOUT
        ═══════════════════════════════════════ */
        .main { padding: 24px 28px; max-width: 1800px; margin: 0 auto; }

        /* ═══════════════════════════════════════
           STATS
        ═══════════════════════════════════════ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px 20px;
            display: flex; align-items: center; gap: 14px;
            box-shadow: var(--shadow-xs);
            transition: box-shadow 0.2s, transform 0.2s;
            position: relative; overflow: hidden;
        }
        .stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-1px); }
        .stat-card::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0;
            height: 3px;
        }
        .stat-card.c-blue::before  { background: linear-gradient(90deg, var(--primary), var(--accent)); }
        .stat-card.c-green::before { background: linear-gradient(90deg, #059669, #10b981); }
        .stat-card.c-sky::before   { background: linear-gradient(90deg, #0284c7, #38bdf8); }
        .stat-card.c-amber::before { background: linear-gradient(90deg, #d97706, #f59e0b); }

        .stat-icon {
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; flex-shrink: 0;
        }
        .c-blue  .stat-icon { background: var(--primary-light); color: var(--primary); }
        .c-green .stat-icon { background: #d1fae5; color: #059669; }
        .c-sky   .stat-icon { background: #e0f2fe; color: #0284c7; }
        .c-amber .stat-icon { background: #fef3c7; color: #d97706; }

        .stat-info { min-width: 0; }
        .stat-label {
            font-size: 10.5px; color: var(--text-muted); font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.6px; display: block;
            margin-bottom: 2px;
        }
        .stat-val {
            font-size: 24px; font-weight: 700; color: var(--text);
            line-height: 1.1; font-family: 'DM Mono', monospace;
        }

        /* ═══════════════════════════════════════
           TABLE CARD
        ═══════════════════════════════════════ */
        .table-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .table-toolbar {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            background: var(--surface);
        }

        .toolbar-left { display: flex; align-items: center; gap: 10px; }

        .table-title {
            font-size: 14.5px; font-weight: 700; color: var(--text);
        }
        .table-count {
            background: var(--primary-light);
            color: var(--primary);
            font-size: 11px; font-weight: 700;
            padding: 2px 8px; border-radius: 20px;
        }

        .toolbar-filters { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        .search-wrap { position: relative; }
        .search-wrap i {
            position: absolute; left: 10px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-light); font-size: 12px; pointer-events: none;
        }
        .search-wrap input {
            padding: 7px 12px 7px 30px;
            border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            font-family: inherit; font-size: 13px; color: var(--text);
            background: var(--surface2); width: 210px;
            transition: all 0.18s;
        }
        .search-wrap input:focus {
            outline: none; border-color: var(--accent);
            background: white; box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
        }

        input[type="date"] {
            padding: 7px 10px;
            border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            font-family: inherit; font-size: 13px; color: var(--text);
            background: var(--surface2); transition: all 0.18s;
        }
        input[type="date"]:focus {
            outline: none; border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
        }

        /* ═══════════════════════════════════════
           TABLE
        ═══════════════════════════════════════ */
        .table-wrap { overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; }

        thead th {
            padding: 10px 14px;
            text-align: left; font-size: 10.5px; font-weight: 700;
            color: var(--text-muted); text-transform: uppercase;
            letter-spacing: 0.7px; white-space: nowrap;
            background: var(--surface2);
            border-bottom: 1px solid var(--border);
        }
        thead th:first-child { padding-left: 20px; }
        thead th:last-child  { padding-right: 20px; }

        tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.12s;
        }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f8faff; }

        tbody td {
            padding: 11px 14px; font-size: 13px;
            color: var(--text); vertical-align: middle;
        }
        tbody td:first-child { padding-left: 20px; }
        tbody td:last-child  { padding-right: 20px; }

        .td-no   { font-family: 'DM Mono', monospace; font-size: 11.5px; color: var(--text-light); }
        .td-date { font-family: 'DM Mono', monospace; font-size: 12px; color: var(--text-muted); white-space: nowrap; }
        .td-nik  { font-family: 'DM Mono', monospace; font-size: 11.5px; color: var(--primary); font-weight: 500; letter-spacing: 0.3px; }
        .td-hp   { font-family: 'DM Mono', monospace; font-size: 11.5px; color: #059669; }
        .td-name { font-weight: 600; color: var(--text); }
        .td-sub  { font-size: 12px; color: var(--text-muted); }
        .td-addr { font-size: 12px; color: var(--text-muted); }

        .badge {
            display: inline-flex; align-items: center;
            padding: 2px 9px; border-radius: 20px;
            font-size: 11px; font-weight: 600; white-space: nowrap;
        }
        .badge-laki      { background: #dbeafe; color: #1d4ed8; }
        .badge-perempuan { background: #fce7f3; color: #9d174d; }
        .badge-other     { background: var(--surface3); color: var(--text-muted); }

        .action-wrap { display: flex; gap: 5px; }

        /* ═══════════════════════════════════════
           EMPTY STATE
        ═══════════════════════════════════════ */
        .empty-wrap { padding: 60px 20px; text-align: center; }
        .empty-icon-box {
            width: 56px; height: 56px;
            background: var(--primary-light);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px; font-size: 22px; color: var(--primary);
        }
        .empty-wrap h3 { font-size: 15px; font-weight: 700; margin-bottom: 5px; }
        .empty-wrap p  { font-size: 13px; color: var(--text-muted); }

        /* ═══════════════════════════════════════
           PAGINATION
        ═══════════════════════════════════════ */
        .pagination-bar {
            padding: 14px 20px;
            border-top: 1px solid var(--border);
            display: flex; align-items: center;
            justify-content: space-between; gap: 12px; flex-wrap: wrap;
        }
        .pg-info { font-size: 12.5px; color: var(--text-muted); }
        .pg-list { display: flex; align-items: center; gap: 3px; list-style: none; }
        .pg-list .page-link {
            display: flex; align-items: center; justify-content: center;
            min-width: 30px; height: 30px; padding: 0 7px;
            border: 1.5px solid var(--border); border-radius: var(--radius-xs);
            font-size: 12.5px; font-weight: 500; color: var(--text-muted);
            text-decoration: none; transition: all 0.15s; background: white;
            font-family: inherit;
        }
        .pg-list .page-link:hover { border-color: var(--accent); color: var(--accent); background: var(--primary-light); }
        .pg-list .active .page-link { background: var(--primary); color: white; border-color: var(--primary); }
        .pg-list .disabled .page-link { opacity: 0.35; cursor: not-allowed; pointer-events: none; }

        /* ═══════════════════════════════════════
           MODALS
        ═══════════════════════════════════════ */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(15,23,42,0.55);
            backdrop-filter: blur(5px);
            z-index: 300;
            display: none; align-items: center; justify-content: center;
            padding: 20px;
        }
        .modal-overlay.show { display: flex; animation: fadeIn 0.2s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .modal-box {
            background: var(--surface);
            border-radius: 18px;
            box-shadow: var(--shadow-xl);
            width: 100%; max-width: 480px;
            animation: slideUp 0.25s var(--ease);
        }
        .modal-box.modal-wide { max-width: 640px; }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .modal-head {
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .modal-head h2 {
            font-size: 15px; font-weight: 700;
            display: flex; align-items: center; gap: 10px;
        }
        .modal-icon {
            width: 30px; height: 30px;
            background: var(--primary-light); border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: var(--primary); font-size: 12px;
        }
        .modal-close {
            width: 28px; height: 28px;
            border-radius: var(--radius-xs); border: 1.5px solid var(--border);
            background: var(--surface2); color: var(--text-muted);
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: all 0.15s; font-size: 13px;
        }
        .modal-close:hover { background: var(--danger-light); color: var(--danger); border-color: #fecaca; }

        .modal-body { padding: 22px 24px; }
        .modal-body-scroll { padding: 22px 24px; max-height: 62vh; overflow-y: auto; }
        .modal-foot {
            padding: 16px 24px;
            border-top: 1px solid var(--border);
            display: flex; justify-content: flex-end; gap: 8px;
        }

        /* ═══════════════════════════════════════
           FORM
        ═══════════════════════════════════════ */
        .fg { margin-bottom: 0; }
        .fg label { font-size: 11.5px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 5px; }
        .fi {
            width: 100%; padding: 8px 11px;
            border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            font-family: inherit; font-size: 13px; color: var(--text);
            background: var(--surface2); transition: all 0.18s;
        }
        .fi:focus { outline: none; border-color: var(--accent); background: white; box-shadow: 0 0 0 3px rgba(59,130,246,0.12); }
        select.fi { cursor: pointer; }

        .form-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .col-full { grid-column: span 2; }

        /* ═══════════════════════════════════════
           UPLOAD ZONE
        ═══════════════════════════════════════ */
        .drop-zone {
            border: 2px dashed var(--border);
            border-radius: var(--radius);
            padding: 36px 20px; text-align: center;
            cursor: pointer; transition: all 0.2s;
            background: var(--surface2); position: relative;
        }
        .drop-zone:hover, .drop-zone.over {
            border-color: var(--accent); background: var(--primary-light);
        }
        .drop-zone input[type="file"] {
            position: absolute; inset: 0; opacity: 0;
            cursor: pointer; width: 100%; height: 100%;
        }
        .drop-ico {
            width: 48px; height: 48px;
            background: white; border-radius: 12px;
            border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 12px; font-size: 20px; color: var(--primary);
            box-shadow: var(--shadow-sm);
        }
        .drop-zone h3 { font-size: 13.5px; font-weight: 600; margin-bottom: 4px; }
        .drop-zone p  { font-size: 12px; color: var(--text-muted); }

        .file-chip {
            display: none; align-items: center; gap: 10px;
            padding: 12px 14px;
            background: var(--success-light); border: 1.5px solid #6ee7b7;
            border-radius: var(--radius-sm); margin-top: 12px;
        }
        .file-chip.show { display: flex; }
        .file-chip .fi-ico { font-size: 20px; color: var(--success); }
        .file-chip .fi-info { flex: 1; min-width: 0; }
        .file-chip .fi-name { font-size: 12.5px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .file-chip .fi-size { font-size: 11px; color: var(--text-muted); }
        .file-chip .fi-rm { background: none; border: none; color: var(--text-light); cursor: pointer; padding: 3px; border-radius: 4px; transition: color 0.15s; }
        .file-chip .fi-rm:hover { color: var(--danger); }

        .notice {
            display: flex; gap: 10px;
            background: var(--warning-light); border: 1px solid #fde68a;
            border-radius: var(--radius-sm); padding: 11px 13px;
            margin-top: 14px; font-size: 12px;
        }
        .notice i { color: var(--warning); flex-shrink: 0; margin-top: 1px; }
        .notice p { color: #92400e; line-height: 1.5; }

        /* progress */
        .prog-wrap { display: none; margin-top: 14px; }
        .prog-wrap.show { display: block; }
        .prog-bar { height: 5px; background: var(--border); border-radius: 3px; overflow: hidden; margin-bottom: 7px; }
        .prog-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 3px; transition: width 0.4s; width: 0%;
        }
        .prog-text { font-size: 11.5px; color: var(--text-muted); text-align: center; }

        /* spinner */
        .spin {
            width: 14px; height: 14px;
            border: 2px solid rgba(255,255,255,0.35);
            border-top-color: white; border-radius: 50%;
            animation: spinning 0.65s linear infinite; display: none;
        }
        @keyframes spinning { to { transform: rotate(360deg); } }

        /* ═══════════════════════════════════════
           TOAST
        ═══════════════════════════════════════ */
        .toast-stack {
            position: fixed; top: 72px; right: 20px;
            z-index: 9999; display: flex; flex-direction: column; gap: 8px;
        }
        .toast {
            background: white; border-radius: 12px;
            box-shadow: var(--shadow-lg);
            padding: 13px 16px;
            display: flex; align-items: flex-start; gap: 11px;
            min-width: 280px; max-width: 380px;
            border-left: 3px solid;
            animation: toastIn 0.28s var(--ease);
        }
        @keyframes toastIn { from { opacity: 0; transform: translateX(16px); } to { opacity: 1; transform: translateX(0); } }
        .toast.ok  { border-color: var(--success); }
        .toast.err { border-color: var(--danger); }
        .toast-i { font-size: 15px; margin-top: 1px; flex-shrink: 0; }
        .toast.ok  .toast-i { color: var(--success); }
        .toast.err .toast-i { color: var(--danger); }
        .toast-b { flex: 1; }
        .toast-t { font-size: 13px; font-weight: 700; margin-bottom: 1px; }
        .toast-m { font-size: 12px; color: var(--text-muted); line-height: 1.4; }
    </style>
</head>
<body>

<!-- ══════════════════════════════
     HEADER
══════════════════════════════ -->
<header class="header">
    <div class="header-left">
        <div class="brand-mark"><i class="fas fa-users"></i></div>
        <div class="brand-info">
            <h1>Data Kunjungan SDP</h1>
            <p>Sistem Manajemen Pengunjung</p>
        </div>
        <div class="header-divider"></div>
    </div>

    <div class="header-right">
        {{-- Import di header, lebih menonjol --}}
        <button class="btn btn-import" onclick="openUpload()">
            <i class="fas fa-file-import"></i> Import Excel
        </button>

        {{-- Logout warna merah pojok kanan --}}
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </form>
    </div>
</header>

<!-- ══════════════════════════════
     MAIN
══════════════════════════════ -->
<main class="main">

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card c-blue">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Kunjungan</span>
                <div class="stat-val">{{ number_format($data->total()) }}</div>
            </div>
        </div>
        <div class="stat-card c-green">
            <div class="stat-icon"><i class="fas fa-mars"></i></div>
            <div class="stat-info">
                <span class="stat-label">Laki-laki</span>
                <div class="stat-val">{{ number_format(\App\Models\DataKunjungan::whereRaw("LOWER(jenis_kelamin) LIKE '%laki%'")->count()) }}</div>
            </div>
        </div>
        <div class="stat-card c-sky">
            <div class="stat-icon"><i class="fas fa-venus"></i></div>
            <div class="stat-info">
                <span class="stat-label">Perempuan</span>
                <div class="stat-val">{{ number_format(\App\Models\DataKunjungan::whereRaw("LOWER(jenis_kelamin) LIKE '%perempuan%' OR LOWER(jenis_kelamin) LIKE '%wanita%'")->count()) }}</div>
            </div>
        </div>
        <div class="stat-card c-amber">
            <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            <div class="stat-info">
                <span class="stat-label">Halaman</span>
                <div class="stat-val">{{ $data->currentPage() }}/{{ $data->lastPage() }}</div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="table-card">
        <div class="table-toolbar">
            <div class="toolbar-left">
                <span class="table-title">Daftar Pengunjung</span>
                <span class="table-count">{{ number_format($data->total()) }} data</span>
            </div>
            <form method="GET" action="{{ route('kunjungan.index') }}" class="toolbar-filters">
                <div class="search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Cari nama, NIK..." value="{{ request('search') }}">
                </div>
                <input type="date" name="tanggal_dari" value="{{ request('tanggal_dari') }}" title="Dari tanggal">
                <input type="date" name="tanggal_sampai" value="{{ request('tanggal_sampai') }}" title="Sampai tanggal">
                <button type="submit" class="btn btn-ghost" style="padding:7px 13px; font-size:12.5px;">
                    <i class="fas fa-filter"></i> Filter
                </button>
                @if(request()->hasAny(['search','tanggal_dari','tanggal_sampai']))
                    <a href="{{ route('kunjungan.index') }}" class="btn btn-ghost" style="padding:7px 11px; font-size:12.5px;">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </form>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Nama WBP</th>
                        <th>Nama Pengunjung</th>
                        <th>Kelamin</th>
                        <th>Hubungan</th>
                        <th>Sub Hubungan</th>
                        <th>Alamat</th>
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
                                $bc = str_contains($jk, 'laki') ? 'badge-laki' : (str_contains($jk, 'perempuan') || str_contains($jk, 'wanita') ? 'badge-perempuan' : 'badge-other');
                            @endphp
                            <span class="badge {{ $bc }}">{{ $item->jenis_kelamin ?? '-' }}</span>
                        </td>
                        <td>{{ $item->hubungan ?? '-' }}</td>
                        <td class="td-sub">{{ $item->sub_hubungan ?? '-' }}</td>
                        <td class="td-addr" style="max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $item->alamat_pengunjung }}">
                            {{ $item->alamat_pengunjung ?? '-' }}
                        </td>
                        <td class="td-nik">{{ $item->no_identitas ?? '-' }}</td>
                        <td class="td-hp">{{ $item->no_hp }}</td>
                        <td>
                            <div class="action-wrap">
                                <button class="btn btn-sm btn-edit"
                                    onclick="openEdit(
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
                                    <i class="fas fa-pen"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-del" onclick="doDelete({{ $item->id }})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11">
                            <div class="empty-wrap">
                                <div class="empty-icon-box"><i class="fas fa-inbox"></i></div>
                                <h3>Belum ada data kunjungan</h3>
                                <p>Klik tombol <strong>Import Excel</strong> di pojok kanan atas untuk mengunggah data.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($data->hasPages())
        <div class="pagination-bar">
            <div class="pg-info">
                Menampilkan {{ $data->firstItem() }}–{{ $data->lastItem() }} dari {{ number_format($data->total()) }} data
            </div>
            <ul class="pg-list">
                <li class="{{ !$data->onFirstPage() ? '' : 'disabled' }}">
                    <a class="page-link" href="{{ $data->previousPageUrl() }}"><i class="fas fa-chevron-left" style="font-size:10px;"></i></a>
                </li>
                @foreach($data->getUrlRange(max(1,$data->currentPage()-2), min($data->lastPage(),$data->currentPage()+2)) as $page => $url)
                <li class="{{ $page == $data->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                </li>
                @endforeach
                <li class="{{ $data->hasMorePages() ? '' : 'disabled' }}">
                    <a class="page-link" href="{{ $data->nextPageUrl() }}"><i class="fas fa-chevron-right" style="font-size:10px;"></i></a>
                </li>
            </ul>
        </div>
        @endif
    </div>

</main>

<!-- ══════════════════════════════
     MODAL UPLOAD
══════════════════════════════ -->
<div class="modal-overlay" id="modalUpload">
    <div class="modal-box">
        <div class="modal-head">
            <h2>
                <div class="modal-icon"><i class="fas fa-file-import"></i></div>
                Import Data Excel
            </h2>
            <button class="modal-close" onclick="closeUpload()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="drop-zone" id="dropZone">
                <input type="file" id="fileInput" accept=".xlsx,.xls" onchange="onPick(this)">
                <div class="drop-ico"><i class="fas fa-cloud-upload-alt"></i></div>
                <h3>Drag & drop file Excel di sini</h3>
                <p>atau klik untuk memilih file <strong>.xlsx / .xls</strong></p>
            </div>

            <div class="file-chip" id="fileChip">
                <i class="fas fa-file-excel fi-ico"></i>
                <div class="fi-info">
                    <div class="fi-name" id="fName">—</div>
                    <div class="fi-size" id="fSize">—</div>
                </div>
                <button class="fi-rm" onclick="clearFile()"><i class="fas fa-times"></i></button>
            </div>

            <div class="notice">
                <i class="fas fa-circle-info"></i>
                <p><strong>Catatan:</strong> Header kolom harus berada di baris ke-2. Baris pertama boleh berisi judul atau dikosongkan.</p>
            </div>

            <div class="prog-wrap" id="progWrap">
                <div class="prog-bar"><div class="prog-fill" id="progFill"></div></div>
                <div class="prog-text" id="progText">Memproses...</div>
            </div>
        </div>
        <div class="modal-foot">
            <button class="btn btn-ghost" onclick="closeUpload()">Batal</button>
            <button class="btn btn-primary" id="btnSave" onclick="doImport()">
                <span class="spin" id="spinSave"></span>
                <i class="fas fa-save" id="icoSave"></i>
                <span id="txtSave">Simpan</span>
            </button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════
     MODAL EDIT
══════════════════════════════ -->
<div class="modal-overlay" id="modalEdit">
    <div class="modal-box modal-wide">
        <div class="modal-head">
            <h2>
                <div class="modal-icon"><i class="fas fa-pen"></i></div>
                Edit Data Kunjungan
            </h2>
            <button class="modal-close" onclick="closeEdit()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body-scroll">
            <input type="hidden" id="eId">
            <div class="form-2col" style="gap:14px;">
                <div class="fg">
                    <label>No</label>
                    <input type="text" id="eNo" class="fi" placeholder="No">
                </div>
                <div class="fg">
                    <label>Nama WBP</label>
                    <input type="text" id="eWbp" class="fi" placeholder="Nama WBP">
                </div>
                <div class="fg">
                    <label>No Registrasi</label>
                    <input type="text" id="eReg" class="fi" placeholder="No Registrasi">
                </div>
                <div class="fg">
                    <label>No Kunjungan</label>
                    <input type="text" id="eKun" class="fi" placeholder="No Kunjungan">
                </div>
                <div class="fg">
                    <label>Nama Pengunjung</label>
                    <input type="text" id="ePeng" class="fi" placeholder="Nama Pengunjung">
                </div>
                <div class="fg">
                    <label>Jenis Kelamin</label>
                    <select id="eJk" class="fi">
                        <option value="">-- Pilih --</option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>
                <div class="fg">
                    <label>Hubungan</label>
                    <input type="text" id="eHub" class="fi" placeholder="Hubungan">
                </div>
                <div class="fg">
                    <label>Sub Hubungan</label>
                    <input type="text" id="eSub" class="fi" placeholder="Sub Hubungan">
                </div>
                <div class="fg col-full">
                    <label>Alamat Pengunjung</label>
                    <input type="text" id="eAlamat" class="fi" placeholder="Alamat Pengunjung">
                </div>
                <div class="fg">
                    <label>NIK / No Identitas</label>
                    <input type="text" id="eNik" class="fi" placeholder="NIK" maxlength="20">
                </div>
                <div class="fg">
                    <label>Tanggal Kunjungan</label>
                    <input type="date" id="eTgl" class="fi">
                </div>
                <div class="fg">
                    <label>No Kamar</label>
                    <input type="text" id="eKamar" class="fi" placeholder="No Kamar / Blok">
                </div>
                <div class="fg">
                    <label>Catatan / No HP</label>
                    <input type="text" id="eCatatan" class="fi" placeholder="Catatan atau No HP">
                </div>
            </div>
        </div>
        <div class="modal-foot">
            <button class="btn btn-ghost" onclick="closeEdit()">Batal</button>
            <button class="btn btn-primary" id="btnUpdate" onclick="doUpdate()">
                <span class="spin" id="spinUpdate"></span>
                <i class="fas fa-save" id="icoUpdate"></i>
                <span id="txtUpdate">Simpan Perubahan</span>
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast-stack" id="toastStack"></div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ══ UPLOAD ══
function openUpload()  { document.getElementById('modalUpload').classList.add('show'); }
function closeUpload() {
    if (document.getElementById('txtSave').textContent === 'Memproses...') return;
    document.getElementById('modalUpload').classList.remove('show');
    clearFile(); hideProg();
}
document.getElementById('modalUpload').addEventListener('click', e => { if (e.target === e.currentTarget) closeUpload(); });

const dz = document.getElementById('dropZone');
dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('over'); });
dz.addEventListener('dragleave', () => dz.classList.remove('over'));
dz.addEventListener('drop', e => {
    e.preventDefault(); dz.classList.remove('over');
    if (e.dataTransfer.files[0]) { document.getElementById('fileInput').files = e.dataTransfer.files; onPick(document.getElementById('fileInput')); }
});

function onPick(input) {
    const f = input.files[0]; if (!f) return;
    const ext = f.name.split('.').pop().toLowerCase();
    if (!['xlsx','xls'].includes(ext)) { toast('err','Format Salah','Hanya file .xlsx atau .xls.'); clearFile(); return; }
    if (f.size > 10*1024*1024) { toast('err','File Terlalu Besar','Maksimal 10MB.'); clearFile(); return; }
    document.getElementById('fName').textContent = f.name;
    document.getElementById('fSize').textContent = fmtBytes(f.size);
    document.getElementById('fileChip').classList.add('show');
}
function clearFile() {
    document.getElementById('fileInput').value = '';
    document.getElementById('fileChip').classList.remove('show');
}
function fmtBytes(b) {
    if (b < 1024) return b+' B';
    if (b < 1024*1024) return (b/1024).toFixed(1)+' KB';
    return (b/1024/1024).toFixed(1)+' MB';
}

function doImport() {
    const fi = document.getElementById('fileInput');
    if (!fi.files[0]) { toast('err','File Kosong','Pilih file Excel terlebih dahulu.'); return; }
    setBtnLoad('Save', true); showProg();
    const fd = new FormData(); fd.append('file', fi.files[0]); fd.append('_token', CSRF);
    let p = 0;
    const iv = setInterval(() => { if (p<85) { p += Math.random()*8; setProg(Math.min(p,85)); } }, 300);
    fetch('{{ route("kunjungan.import") }}', { method:'POST', body:fd })
    .then(r => r.json())
    .then(d => {
        clearInterval(iv); setProg(100);
        setTimeout(() => {
            setBtnLoad('Save', false); hideProg();
            if (d.success) { toast('ok','Berhasil!', d.message); closeUpload(); setTimeout(() => location.reload(), 1400); }
            else toast('err','Gagal', d.message);
        }, 500);
    })
    .catch(() => { clearInterval(iv); setBtnLoad('Save',false); hideProg(); toast('err','Error','Tidak dapat menghubungi server.'); });
}

function showProg() { document.getElementById('progWrap').classList.add('show'); }
function hideProg() { document.getElementById('progWrap').classList.remove('show'); setProg(0); }
function setProg(v) {
    document.getElementById('progFill').style.width = v+'%';
    document.getElementById('progText').textContent = v<100 ? `Memproses... ${Math.round(v)}%` : 'Menyimpan ke database...';
}

// ══ EDIT ══
function openEdit(id, no, wbp, reg, kun, peng, jk, hub, sub, alamat, nik, tgl, kamar, catatan) {
    document.getElementById('eId').value      = id;
    document.getElementById('eNo').value      = no;
    document.getElementById('eWbp').value     = wbp;
    document.getElementById('eReg').value     = reg;
    document.getElementById('eKun').value     = kun;
    document.getElementById('ePeng').value    = peng;
    document.getElementById('eJk').value      = jk;
    document.getElementById('eHub').value     = hub;
    document.getElementById('eSub').value     = sub;
    document.getElementById('eAlamat').value  = alamat;
    document.getElementById('eNik').value     = nik;
    document.getElementById('eTgl').value     = tgl;
    document.getElementById('eKamar').value   = kamar;
    document.getElementById('eCatatan').value = catatan;
    document.getElementById('modalEdit').classList.add('show');
}
function closeEdit() { document.getElementById('modalEdit').classList.remove('show'); }
document.getElementById('modalEdit').addEventListener('click', e => { if (e.target === e.currentTarget) closeEdit(); });

function doUpdate() {
    const id = document.getElementById('eId').value;
    const payload = {
        _token:'', _method:'PUT',
        no: document.getElementById('eNo').value,
        wbp: document.getElementById('eWbp').value,
        nomor_registrasi: document.getElementById('eReg').value,
        no_kunjungan: document.getElementById('eKun').value,
        pengunjung: document.getElementById('ePeng').value,
        jenis_kelamin: document.getElementById('eJk').value,
        hubungan: document.getElementById('eHub').value,
        sub_hubungan: document.getElementById('eSub').value,
        alamat_pengunjung: document.getElementById('eAlamat').value,
        no_identitas: document.getElementById('eNik').value,
        waktu_kunjungan: document.getElementById('eTgl').value,
        no_kamar: document.getElementById('eKamar').value,
        catatan: document.getElementById('eCatatan').value,
    };
    setBtnLoad('Update', true);
    fetch(`/${id}`, {
        method:'POST',
        headers:{ 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':CSRF },
        body: JSON.stringify({...payload, _token:CSRF})
    })
    .then(r => r.json())
    .then(d => {
        setBtnLoad('Update', false);
        if (d.success) { toast('ok','Berhasil', d.message); closeEdit(); setTimeout(() => location.reload(), 1200); }
        else toast('err','Gagal', d.message);
    })
    .catch(() => { setBtnLoad('Update',false); toast('err','Error','Tidak dapat menghubungi server.'); });
}

// ══ DELETE ══
function doDelete(id) {
    if (!confirm('Yakin ingin menghapus data ini?')) return;
    fetch(`/${id}`, {
        method:'DELETE',
        headers:{ 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' }
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) { toast('ok','Dihapus', d.message); setTimeout(() => location.reload(), 1200); }
        else toast('err','Gagal', d.message);
    })
    .catch(() => toast('err','Error','Tidak dapat menghubungi server.'));
}

// ══ LOADING STATE ══
function setBtnLoad(which, state) {
    const spin = document.getElementById('spin'+which);
    const ico  = document.getElementById('ico'+which);
    const txt  = document.getElementById('txt'+which);
    const btn  = document.getElementById('btn'+which);
    spin.style.display = state ? 'block' : 'none';
    ico.style.display  = state ? 'none' : 'inline-block';
    txt.textContent    = state ? 'Memproses...' : (which === 'Save' ? 'Simpan' : 'Simpan Perubahan');
    btn.disabled       = state;
}

// ══ TOAST ══
function toast(type, title, msg) {
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.innerHTML = `
        <i class="toast-i fas fa-${type==='ok'?'check-circle':'exclamation-circle'}"></i>
        <div class="toast-b">
            <div class="toast-t">${title}</div>
            <div class="toast-m">${msg}</div>
        </div>`;
    document.getElementById('toastStack').appendChild(el);
    setTimeout(() => {
        el.style.cssText = 'opacity:0;transform:translateX(16px);transition:.25s';
        setTimeout(() => el.remove(), 260);
    }, 4000);
}
</script>
</body>
</html>