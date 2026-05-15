<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Data Kunjungan SDP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --primary: #1e40af;
            --primary-hover: #1d3a9e;
            --primary-light: #eff6ff;
            --accent: #3b82f6;
            --success: #059669;
            --success-light: #ecfdf5;
            --danger: #dc2626;
            --danger-hover: #b91c1c;
            --danger-light: #fef2f2;
            --warning: #d97706;
            --warning-light: #fffbeb;
            --purple: #7c3aed;
            --purple-light: #f5f3ff;
            --bg: #f1f5f9;
            --surface: #ffffff;
            --surface2: #f8fafc;
            --surface3: #f1f5f9;
            --border: #e2e8f0;
            --border-strong: #cbd5e1;
            --text: #0f172a;
            --text-muted: #64748b;
            --text-light: #94a3b8;
            --shadow-xs: 0 1px 2px rgba(0, 0, 0, .05);
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, .08), 0 1px 2px rgba(0, 0, 0, .04);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, .08), 0 2px 4px rgba(0, 0, 0, .04);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, .10), 0 4px 8px rgba(0, 0, 0, .05);
            --shadow-xl: 0 20px 50px rgba(0, 0, 0, .12), 0 8px 16px rgba(0, 0, 0, .06);
            --radius: 14px;
            --radius-sm: 8px;
            --radius-xs: 6px;
            --ease: cubic-bezier(.4, 0, .2, 1);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            font-size: 14px;
            line-height: 1.6;
        }

        /* ═══ HEADER ═══ */
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

        .header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-mark {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 15px;
            box-shadow: 0 2px 8px rgba(30, 64, 175, .30);
            flex-shrink: 0;
        }

        .brand-info h1 {
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -.2px;
            line-height: 1.2;
        }

        .brand-info p {
            font-size: 11px;
            color: var(--text-muted);
        }

        .header-divider {
            width: 1px;
            height: 28px;
            background: var(--border);
            margin: 0 4px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ═══ BUTTONS ═══ */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all .18s var(--ease);
            border: 1.5px solid transparent;
            white-space: nowrap;
            text-decoration: none;
            letter-spacing: .01em;
        }

        .btn i {
            font-size: 12px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 2px 6px rgba(30, 64, 175, .25);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            box-shadow: 0 4px 12px rgba(30, 64, 175, .35);
            transform: translateY(-1px);
        }

        .btn-import {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: white;
            box-shadow: 0 2px 8px rgba(30, 64, 175, .30);
            padding: 8px 18px;
        }

        .btn-import:hover {
            box-shadow: 0 4px 16px rgba(30, 64, 175, .40);
            transform: translateY(-1px);
            filter: brightness(1.05);
        }

        .btn-sync {
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            color: white;
            box-shadow: 0 2px 8px rgba(124, 58, 237, .30);
            padding: 8px 18px;
        }

        .btn-sync:hover {
            box-shadow: 0 4px 16px rgba(124, 58, 237, .40);
            transform: translateY(-1px);
            filter: brightness(1.08);
        }

        .btn-sync:disabled {
            opacity: .7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-sync-sm {
            background: var(--purple-light);
            color: var(--purple);
            border-color: #ddd6fe;
            font-size: 12px;
            padding: 6px 13px;
        }

        .btn-sync-sm:hover {
            background: #ede9fe;
            border-color: #c4b5fd;
        }

        .btn-logout {
            background: var(--danger);
            color: white;
            box-shadow: 0 2px 6px rgba(220, 38, 38, .25);
        }

        .btn-logout:hover {
            background: var(--danger-hover);
            box-shadow: 0 4px 12px rgba(220, 38, 38, .35);
            transform: translateY(-1px);
        }

        .btn-ghost {
            background: transparent;
            color: var(--text-muted);
            border-color: var(--border);
        }

        .btn-ghost:hover {
            background: var(--surface2);
            color: var(--text);
            border-color: var(--border-strong);
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 11.5px;
            border-radius: var(--radius-xs);
            gap: 5px;
        }

        .btn-sm i {
            font-size: 11px;
        }

        .btn-edit {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }

        .btn-edit:hover {
            background: #dbeafe;
            border-color: #93c5fd;
        }

        .btn-del {
            background: var(--danger-light);
            color: var(--danger);
            border-color: #fecaca;
        }

        .btn-del:hover {
            background: #fecaca;
            border-color: #f87171;
        }

        /* ═══ MAIN ═══ */
        .main {
            padding: 24px 28px;
            max-width: 1800px;
            margin: 0 auto;
        }

        /* ═══ SYNC STATUS BAR ═══ */
        .sync-bar {
            background: linear-gradient(135deg, #faf5ff 0%, #f0fdf4 100%);
            border: 1px solid #e9d5ff;
            border-radius: var(--radius);
            padding: 14px 20px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            transition: background .3s;
        }

        .sync-bar.error-state {
            background: linear-gradient(135deg, #fef2f2, #fff7f7);
            border-color: #fecaca;
        }

        .sync-bar-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .sync-pulse-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #a855f7;
            position: relative;
            flex-shrink: 0;
        }

        .sync-pulse-dot::after {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            background: rgba(168, 85, 247, .25);
            animation: pulse-ring 2s infinite;
        }

        .sync-pulse-dot.offline {
            background: #94a3b8;
        }

        .sync-pulse-dot.offline::after {
            display: none;
        }

        .sync-pulse-dot.ok {
            background: var(--success);
        }

        .sync-pulse-dot.ok::after {
            background: rgba(5, 150, 105, .25);
        }

        @keyframes pulse-ring {
            0% {
                transform: scale(.8);
                opacity: 1
            }

            100% {
                transform: scale(1.8);
                opacity: 0
            }
        }

        .sync-bar-text {
            font-size: 13px;
            color: #5b21b6;
            font-weight: 500;
        }

        .sync-bar-text strong {
            font-weight: 700;
        }

        .sync-bar.error-state .sync-bar-text {
            color: var(--danger);
        }

        .sync-bar-meta {
            font-size: 11.5px;
            color: #7c3aed;
            opacity: .8;
        }

        .sync-bar-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ═══ STATS ═══ */
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
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: var(--shadow-xs);
            transition: box-shadow .2s, transform .2s;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .stat-card.c-blue::before {
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .stat-card.c-green::before {
            background: linear-gradient(90deg, #059669, #10b981);
        }

        .stat-card.c-sky::before {
            background: linear-gradient(90deg, #0284c7, #38bdf8);
        }

        .stat-card.c-amber::before {
            background: linear-gradient(90deg, #d97706, #f59e0b);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .c-blue .stat-icon {
            background: var(--primary-light);
            color: var(--primary);
        }

        .c-green .stat-icon {
            background: #d1fae5;
            color: #059669;
        }

        .c-sky .stat-icon {
            background: #e0f2fe;
            color: #0284c7;
        }

        .c-amber .stat-icon {
            background: #fef3c7;
            color: #d97706;
        }

        .stat-label {
            font-size: 10.5px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .6px;
            display: block;
            margin-bottom: 2px;
        }

        .stat-val {
            font-size: 24px;
            font-weight: 700;
            color: var(--text);
            line-height: 1.1;
            font-family: 'DM Mono', monospace;
        }

        /* ═══ TABLE CARD ═══ */
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
        }

        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-title {
            font-size: 14.5px;
            font-weight: 700;
            color: var(--text);
        }

        .table-count {
            background: var(--primary-light);
            color: var(--primary);
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
        }

        .toolbar-filters {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .search-wrap {
            position: relative;
        }

        .search-wrap i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 12px;
            pointer-events: none;
        }

        .search-wrap input {
            padding: 7px 12px 7px 30px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 13px;
            color: var(--text);
            background: var(--surface2);
            width: 210px;
            transition: all .18s;
        }

        .search-wrap input:focus {
            outline: none;
            border-color: var(--accent);
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
        }

        input[type="date"] {
            padding: 7px 10px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 13px;
            color: var(--text);
            background: var(--surface2);
            transition: all .18s;
        }

        input[type="date"]:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
        }

        /* ═══ TABLE ═══ */
        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            padding: 10px 14px;
            text-align: left;
            font-size: 10.5px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .7px;
            white-space: nowrap;
            background: var(--surface2);
            border-bottom: 1px solid var(--border);
        }

        thead th:first-child {
            padding-left: 20px;
        }

        thead th:last-child {
            padding-right: 20px;
        }

        tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background .12s;
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #f8faff;
        }

        tbody td {
            padding: 11px 14px;
            font-size: 13px;
            color: var(--text);
            vertical-align: middle;
        }

        tbody td:first-child {
            padding-left: 20px;
        }

        tbody td:last-child {
            padding-right: 20px;
        }

        .td-no {
            font-family: 'DM Mono', monospace;
            font-size: 11.5px;
            color: var(--text-light);
        }

        .td-date {
            font-family: 'DM Mono', monospace;
            font-size: 12px;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .td-nik {
            font-family: 'DM Mono', monospace;
            font-size: 11.5px;
            color: var(--primary);
            font-weight: 500;
        }

        .td-hp {
            font-family: 'DM Mono', monospace;
            font-size: 11.5px;
            color: #059669;
        }

        .td-name {
            font-weight: 600;
            color: var(--text);
        }

        .td-sub {
            font-size: 12px;
            color: var(--text-muted);
        }

        .td-addr {
            font-size: 12px;
            color: var(--text-muted);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-laki {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-perempuan {
            background: #fce7f3;
            color: #9d174d;
        }

        .badge-other {
            background: var(--surface3);
            color: var(--text-muted);
        }

        .action-wrap {
            display: flex;
            gap: 5px;
        }

        /* Foto dari SIPIRMAN — pakai proxy URL */
        .foto-link {
            color: var(--primary);
            font-weight: 500;
            font-size: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .foto-link:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        .foto-link.from-sipirman {
            color: var(--purple);
        }

        .foto-link.from-sipirman:hover {
            color: #6d28d9;
        }

        /* ═══ EMPTY STATE ═══ */
        .empty-wrap {
            padding: 60px 20px;
            text-align: center;
        }

        .empty-icon-box {
            width: 56px;
            height: 56px;
            background: var(--primary-light);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            font-size: 22px;
            color: var(--primary);
        }

        .empty-wrap h3 {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .empty-wrap p {
            font-size: 13px;
            color: var(--text-muted);
        }

        /* ═══ PAGINATION ═══ */
        .pagination-bar {
            padding: 14px 20px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .pg-info {
            font-size: 12.5px;
            color: var(--text-muted);
        }

        .pg-list {
            display: flex;
            align-items: center;
            gap: 3px;
            list-style: none;
        }

        .pg-list .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 30px;
            height: 30px;
            padding: 0 7px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-xs);
            font-size: 12.5px;
            font-weight: 500;
            color: var(--text-muted);
            text-decoration: none;
            transition: all .15s;
            background: white;
            font-family: inherit;
        }

        .pg-list .page-link:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: var(--primary-light);
        }

        .pg-list .active .page-link {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pg-list .disabled .page-link {
            opacity: .35;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* ═══ MODALS ═══ */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .55);
            backdrop-filter: blur(5px);
            z-index: 300;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.show {
            display: flex;
            animation: fadeIn .2s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0
            }

            to {
                opacity: 1
            }
        }

        .modal-box {
            background: var(--surface);
            border-radius: 18px;
            box-shadow: var(--shadow-xl);
            width: 100%;
            max-width: 480px;
            animation: slideUp .25s var(--ease);
        }

        .modal-box.modal-wide {
            max-width: 640px;
        }

        .modal-box.modal-sync {
            max-width: 740px;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px) scale(.97)
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1)
            }
        }

        .modal-head {
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-head h2 {
            font-size: 15px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-icon {
            width: 30px;
            height: 30px;
            background: var(--primary-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 12px;
        }

        .modal-icon.purple {
            background: var(--purple-light);
            color: var(--purple);
        }

        .modal-close {
            width: 28px;
            height: 28px;
            border-radius: var(--radius-xs);
            border: 1.5px solid var(--border);
            background: var(--surface2);
            color: var(--text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .15s;
            font-size: 13px;
        }

        .modal-close:hover {
            background: var(--danger-light);
            color: var(--danger);
            border-color: #fecaca;
        }

        .modal-body {
            padding: 22px 24px;
        }

        .modal-body-scroll {
            padding: 22px 24px;
            max-height: 62vh;
            overflow-y: auto;
        }

        .modal-foot {
            padding: 16px 24px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        /* ═══ SYNC MODAL ═══ */
        .sync-preview-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }

        .sync-stat-box {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            text-align: center;
        }

        .sync-stat-box .ssb-val {
            font-size: 22px;
            font-weight: 700;
            font-family: 'DM Mono', monospace;
            color: var(--text);
            line-height: 1.1;
        }

        .sync-stat-box .ssb-lbl {
            font-size: 10.5px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-top: 3px;
        }

        .sync-stat-box.highlight {
            background: var(--purple-light);
            border-color: #ddd6fe;
        }

        .sync-stat-box.highlight .ssb-val {
            color: var(--purple);
        }

        .sync-stat-box.loading .ssb-val {
            color: var(--text-light);
            animation: pulseFade 1.2s infinite;
        }

        @keyframes pulseFade {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: .3
            }
        }

        /* Log panel */
        .log-panel {
            background: #0f172a;
            border-radius: var(--radius-sm);
            padding: 14px;
            font-family: 'DM Mono', monospace;
            font-size: 11.5px;
            color: #94a3b8;
            max-height: 260px;
            overflow-y: auto;
            line-height: 1.7;
        }

        .log-panel-empty {
            color: #475569;
            font-style: italic;
        }

        .log-line {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            padding: 1px 0;
        }

        .log-time {
            color: #475569;
            flex-shrink: 0;
        }

        .log-msg {
            flex: 1;
            word-break: break-word;
        }

        .log-msg.info {
            color: #7dd3fc;
        }

        .log-msg.success {
            color: #6ee7b7;
        }

        .log-msg.error {
            color: #fca5a5;
        }

        .log-msg.warning {
            color: #fcd34d;
        }

        /* Tabs */
        .sync-tabs {
            display: flex;
            border-bottom: 1px solid var(--border);
            margin: 0 -24px 18px;
            padding: 0 24px;
        }

        .sync-tab {
            padding: 10px 16px;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all .15s;
            background: none;
            border-top: none;
            border-left: none;
            border-right: none;
            font-family: inherit;
        }

        .sync-tab:hover {
            color: var(--text);
        }

        .sync-tab.active {
            color: var(--purple);
            border-bottom-color: var(--purple);
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        /* History items */
        .hist-item {
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            margin-bottom: 10px;
            overflow: hidden;
        }

        .hist-head {
            padding: 10px 14px;
            background: var(--surface2);
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            user-select: none;
        }

        .hist-head:hover {
            background: var(--surface3);
        }

        .hist-head-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .hist-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10.5px;
            font-weight: 700;
        }

        .hist-badge.success {
            background: var(--purple-light);
            color: var(--purple);
        }

        .hist-badge.failed {
            background: var(--danger-light);
            color: var(--danger);
        }

        .hist-date {
            font-size: 12px;
            color: var(--text-muted);
            font-family: 'DM Mono', monospace;
        }

        .hist-caret {
            font-size: 10px;
            color: var(--text-light);
            transition: transform .2s;
        }

        .hist-item.open .hist-caret {
            transform: rotate(180deg);
        }

        .hist-body {
            display: none;
            padding: 12px 14px;
            border-top: 1px solid var(--border);
        }

        .hist-item.open .hist-body {
            display: block;
        }

        .hist-stats {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .hstat {
            background: var(--surface3);
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 11px;
        }

        .hstat strong {
            color: var(--text);
            font-weight: 700;
            font-family: 'DM Mono', monospace;
        }

        /* ═══ FORM ═══ */
        .fg {
            margin-bottom: 0;
        }

        .fg label {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--text-muted);
            display: block;
            margin-bottom: 5px;
        }

        .fi {
            width: 100%;
            padding: 8px 11px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 13px;
            color: var(--text);
            background: var(--surface2);
            transition: all .18s;
        }

        .fi:focus {
            outline: none;
            border-color: var(--accent);
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
        }

        select.fi {
            cursor: pointer;
        }

        .form-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .col-full {
            grid-column: span 2;
        }

        .foto-preview-wrap {
            margin-top: 6px;
            display: none;
        }

        .foto-preview-wrap.show {
            display: block;
        }

        .foto-preview-wrap img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--radius-xs);
            border: 1px solid var(--border);
        }

        .foto-preview-wrap span {
            display: block;
            font-size: 10.5px;
            color: var(--text-muted);
            margin-top: 3px;
        }

        /* ═══ UPLOAD ZONE ═══ */
        .drop-zone {
            border: 2px dashed var(--border);
            border-radius: var(--radius);
            padding: 36px 20px;
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            background: var(--surface2);
            position: relative;
        }

        .drop-zone:hover,
        .drop-zone.over {
            border-color: var(--accent);
            background: var(--primary-light);
        }

        .drop-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .drop-ico {
            width: 48px;
            height: 48px;
            background: white;
            border-radius: 12px;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 20px;
            color: var(--primary);
            box-shadow: var(--shadow-sm);
        }

        .drop-zone h3 {
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .drop-zone p {
            font-size: 12px;
            color: var(--text-muted);
        }

        .file-chip {
            display: none;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            background: var(--success-light);
            border: 1.5px solid #6ee7b7;
            border-radius: var(--radius-sm);
            margin-top: 12px;
        }

        .file-chip.show {
            display: flex;
        }

        .file-chip .fi-ico {
            font-size: 20px;
            color: var(--success);
        }

        .file-chip .fi-info {
            flex: 1;
            min-width: 0;
        }

        .file-chip .fi-name {
            font-size: 12.5px;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-chip .fi-size {
            font-size: 11px;
            color: var(--text-muted);
        }

        .file-chip .fi-rm {
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            padding: 3px;
            border-radius: 4px;
            transition: color .15s;
        }

        .file-chip .fi-rm:hover {
            color: var(--danger);
        }

        .notice {
            display: flex;
            gap: 10px;
            background: var(--warning-light);
            border: 1px solid #fde68a;
            border-radius: var(--radius-sm);
            padding: 11px 13px;
            margin-top: 14px;
            font-size: 12px;
        }

        .notice i {
            color: var(--warning);
            flex-shrink: 0;
            margin-top: 1px;
        }

        .notice p {
            color: #92400e;
            line-height: 1.5;
        }

        .prog-wrap {
            display: none;
            margin-top: 14px;
        }

        .prog-wrap.show {
            display: block;
        }

        .prog-bar {
            height: 5px;
            background: var(--border);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 7px;
        }

        .prog-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 3px;
            transition: width .4s;
            width: 0%;
        }

        .prog-text {
            font-size: 11.5px;
            color: var(--text-muted);
            text-align: center;
        }

        /* ═══ SPINNER / TOAST ═══ */
        .spin {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, .35);
            border-top-color: white;
            border-radius: 50%;
            animation: spinning .65s linear infinite;
            display: none;
        }

        @keyframes spinning {
            to {
                transform: rotate(360deg)
            }
        }

        .sync-running-icon {
            display: inline-block;
            animation: spinning .8s linear infinite;
        }

        .toast-stack {
            position: fixed;
            top: 72px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .toast {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            padding: 13px 16px;
            display: flex;
            align-items: flex-start;
            gap: 11px;
            min-width: 280px;
            max-width: 380px;
            border-left: 3px solid;
            animation: toastIn .28s var(--ease);
        }

        @keyframes toastIn {
            from {
                opacity: 0;
                transform: translateX(16px)
            }

            to {
                opacity: 1;
                transform: translateX(0)
            }
        }

        .toast.ok {
            border-color: var(--success);
        }

        .toast.err {
            border-color: var(--danger);
        }

        .toast.sync {
            border-color: var(--purple);
        }

        .toast-i {
            font-size: 15px;
            margin-top: 1px;
            flex-shrink: 0;
        }

        .toast.ok .toast-i {
            color: var(--success);
        }

        .toast.err .toast-i {
            color: var(--danger);
        }

        .toast.sync .toast-i {
            color: var(--purple);
        }

        .toast-b {
            flex: 1;
        }

        .toast-t {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 1px;
        }

        .toast-m {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.4;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
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
            <button class="btn btn-import" onclick="openUpload()">
                <i class="fas fa-file-import"></i> Import Excel
            </button>
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>
    </header>

    <main class="main">

        <!-- SYNC STATUS BAR -->
        <div class="sync-bar" id="syncBar">
            <div class="sync-bar-left">
                <div class="sync-pulse-dot" id="syncDot"></div>
                <div>
                    <div class="sync-bar-text" id="syncBarText">
                        <strong>Sinkronisasi SIPIRMAN</strong> —
                        <span id="syncBarMsg">Memeriksa koneksi ke database SIPIRMAN...</span>
                    </div>
                    <div class="sync-bar-meta" id="syncBarMeta">Memuat status...</div>
                </div>
            </div>
            <div class="sync-bar-right">
                <button class="btn btn-sync-sm btn-sm" onclick="doPreview()" id="btnBarPreview">
                    <i class="fas fa-eye"></i> Preview
                </button>
                <button class="btn btn-sync btn-sm" onclick="openSync()" style="padding:5px 12px; font-size:12px;">
                    <i class="fas fa-sync-alt"></i> Sinkronisasi
                </button>
            </div>
        </div>

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
                    <div class="stat-val">{{ number_format($totalLaki) }}</div>
                </div>
            </div>
            <div class="stat-card c-sky">
                <div class="stat-icon"><i class="fas fa-venus"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Perempuan</span>
                    <div class="stat-val">{{ number_format($totalWanita) }}</div>

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
                        <input type="text" name="search" placeholder="Cari nama, NIK..."
                            value="{{ request('search') }}">
                    </div>
                    <input type="date" name="tanggal_dari" value="{{ request('tanggal_dari') }}"
                        title="Dari tanggal">
                    <input type="date" name="tanggal_sampai" value="{{ request('tanggal_sampai') }}"
                        title="Sampai tanggal">
                    <button type="submit" class="btn btn-ghost" style="padding:7px 13px;font-size:12.5px;">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    @if (request()->hasAny(['search', 'tanggal_dari', 'tanggal_sampai']))
                        <a href="{{ route('kunjungan.index') }}" class="btn btn-ghost"
                            style="padding:7px 11px;font-size:12.5px;">
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
                            <th>Foto KTP</th>
                            <th>Foto Diri</th>
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
                                        $bc = str_contains($jk, 'laki')
                                            ? 'badge-laki'
                                            : (str_contains($jk, 'perempuan') || str_contains($jk, 'wanita')
                                                ? 'badge-perempuan'
                                                : 'badge-other');
                                    @endphp
                                    <span class="badge {{ $bc }}">{{ $item->jenis_kelamin ?? '-' }}</span>
                                </td>
                                <td>{{ $item->hubungan ?? '-' }}</td>
                                <td class="td-sub">{{ $item->sub_hubungan ?? '-' }}</td>
                                <td class="td-addr"
                                    style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                    title="{{ $item->alamat_pengunjung }}">{{ $item->alamat_pengunjung ?? '-' }}</td>
                                <td class="td-nik">{{ $item->no_identitas ?? '-' }}</td>
                                <td class="td-hp">{{ $item->no_hp }}</td>

                                {{-- FOTO KTP — gunakan proxy jika path dari SIPIRMAN --}}
                                <td>
                                    @if ($item->foto_ktp)
                                        @php
                                            $ktpUrl = route('sinkronisasi.foto', [
                                                'path' => $item->foto_ktp,
                                                'folder' => 'ktp', 
                                            ]);
                                        @endphp
                                        <a href="{{ $ktpUrl }}" target="_blank"
                                            class="foto-link from-sipirman">
                                            <i class="fas fa-id-card"></i> Lihat KTP
                                        </a>
                                    @else
                                        <span style="color:var(--text-light);">–</span>
                                    @endif
                                </td>

                                {{-- FOTO DIRI — gunakan proxy --}}
                                <td>
                                    @if ($item->foto_diri)
                                        @php
                                            $diriUrl = route('sinkronisasi.foto', [
                                                'path' => $item->foto_diri,
                                                'folder' => 'foto_diri', 
                                            ]);
                                        @endphp
                                        <a href="{{ $diriUrl }}" target="_blank"
                                            class="foto-link from-sipirman">
                                            <i class="fas fa-portrait"></i> Lihat Foto
                                        </a>
                                    @else
                                        <span style="color:var(--text-light);">–</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="action-wrap">
                                        <button class="btn btn-sm btn-edit"
                                            onclick='openEdit(
                                        {{ $item->id }},
                                        @json($item->wbp),
                                        @json($item->pengunjung),
                                        @json($item->jenis_kelamin),
                                        @json($item->hubungan),
                                        @json($item->sub_hubungan),
                                        @json($item->alamat_pengunjung),
                                        @json($item->no_identitas),
                                        @json($item->waktu_kunjungan),
                                        @json($item->catatan),
                                        @json($item->foto_ktp),
                                        @json($item->foto_diri)
                                    )'>
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
                                <td colspan="13">
                                    <div class="empty-wrap">
                                        <div class="empty-icon-box"><i class="fas fa-inbox"></i></div>
                                        <h3>Belum ada data kunjungan</h3>
                                        <p>Klik tombol <strong>Import Excel</strong> untuk mengunggah data.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($data->hasPages())
                <div class="pagination-bar">
                    <div class="pg-info">
                        Menampilkan {{ $data->firstItem() }}–{{ $data->lastItem() }} dari
                        {{ number_format($data->total()) }} data
                    </div>
                    <ul class="pg-list">
                        <li class="{{ !$data->onFirstPage() ? '' : 'disabled' }}">
                            <a class="page-link" href="{{ $data->previousPageUrl() }}">
                                <i class="fas fa-chevron-left" style="font-size:10px;"></i>
                            </a>
                        </li>
                        @foreach ($data->getUrlRange(max(1, $data->currentPage() - 2), min($data->lastPage(), $data->currentPage() + 2)) as $page => $url)
                            <li class="{{ $page == $data->currentPage() ? 'active' : '' }}">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endforeach
                        <li class="{{ $data->hasMorePages() ? '' : 'disabled' }}">
                            <a class="page-link" href="{{ $data->nextPageUrl() }}">
                                <i class="fas fa-chevron-right" style="font-size:10px;"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            @endif
        </div>
    </main>

    <!-- ══════════════════════════════════════
     MODAL SINKRONISASI
══════════════════════════════════════ -->
    <div class="modal-overlay" id="modalSync">
        <div class="modal-box modal-sync">
            <div class="modal-head">
                <h2>
                    <div class="modal-icon purple"><i class="fas fa-sync-alt"></i></div>
                    Sinkronisasi Data SIPIRMAN
                </h2>
                <button class="modal-close" onclick="closeSync()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body-scroll">

                <!-- TABS -->
                <div class="sync-tabs">
                    <button class="sync-tab active" id="tabBtnSync" onclick="showTab('tabSync',this)">
                        <i class="fas fa-sync-alt" style="font-size:11px;margin-right:5px;"></i>Sinkronisasi
                    </button>
                
                </div>

                <!-- TAB SYNC -->
                <div class="tab-pane active" id="tabSync">

                    <!-- Koneksi status -->
                    <div id="connStatus"
                        style="margin-bottom:14px;padding:10px 14px;border-radius:var(--radius-sm);background:var(--surface2);border:1px solid var(--border);font-size:12.5px;display:flex;align-items:center;gap:8px;">
                        <i class="fas fa-circle-notch fa-spin" style="color:var(--text-light);"></i>
                        <span id="connStatusText">Memeriksa koneksi ke SIPIRMAN...</span>
                    </div>

                    <!-- Preview grid -->
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                        <span style="font-size:13px;font-weight:600;">Pratinjau Sinkronisasi</span>
                        <button class="btn btn-sync-sm btn-sm" onclick="doPreview()" id="btnPreview">
                            <i class="fas fa-refresh"></i> Refresh
                        </button>
                    </div>
                    <div class="sync-preview-grid">
                        <div class="sync-stat-box loading">
                            <div class="ssb-val" id="pvTotalKunjungan">—</div>
                            <div class="ssb-lbl">Total Kunjungan</div>
                        </div>
                        <div class="sync-stat-box loading">
                            <div class="ssb-val" id="pvTotalPenitip">—</div>
                            <div class="ssb-lbl">Data di SIPIRMAN</div>
                        </div>
                        <div class="sync-stat-box highlight loading">
                            <div class="ssb-val" id="pvPerluSync">—</div>
                            <div class="ssb-lbl">Perlu Disinkron</div>
                        </div>
                        {{-- <div class="sync-stat-box highlight loading">
                            <div class="ssb-val" id="pvMatch">—</div>
                            <div class="ssb-lbl">Akan Dicocokkan</div>
                        </div>
                        <div class="sync-stat-box loading">
                            <div class="ssb-val" id="pvKtp">—</div>
                            <div class="ssb-lbl">Foto KTP Akan Diisi</div>
                        </div>
                        <div class="sync-stat-box loading">
                            <div class="ssb-val" id="pvDiri">—</div>
                            <div class="ssb-lbl">Foto Diri Akan Diisi</div>
                        </div> --}}
                    </div>
                    <div id="previewError"
                        style="display:none;color:var(--danger);font-size:12px;padding:8px 12px;background:var(--danger-light);border-radius:var(--radius-xs);margin-bottom:12px;">
                    </div>

                    <!-- Keterangan -->
                    <div class="notice" style="margin-bottom:16px;">
                        <i class="fas fa-circle-info"></i>
                        <p>Pencocokan berdasarkan <strong>NIK</strong> (prioritas) atau <strong>Nama
                                Pengunjung</strong>.
                            Data yang belum ada di SIPIRMAN akan diekspor. Foto kosong akan diisi dari SIPIRMAN.
                            Foto yang sudah ada <strong>tidak ditimpa</strong>. Sinkronisasi berjalan <strong>dua
                                arah</strong>.</p>
                    </div>

                    <!-- Log panel -->
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                        <span style="font-size:12px;font-weight:600;color:var(--text-muted);">
                            <i class="fas fa-terminal" style="margin-right:5px;"></i>Log Proses
                        </span>
                        <button onclick="clearLog()" class="btn btn-ghost btn-sm"
                            style="font-size:11px;padding:3px 8px;">
                            <i class="fas fa-trash"></i> Bersihkan
                        </button>
                    </div>
                    <div class="log-panel" id="syncLogPanel">
                        <span class="log-panel-empty">Log sinkronisasi akan muncul di sini...</span>
                    </div>
                </div>

                <!-- TAB HISTORY -->
                {{-- <div class="tab-pane" id="tabHistory">
                    <div id="historyContent"
                        style="color:var(--text-muted);font-size:13px;text-align:center;padding:24px 0;">
                        <i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>Memuat riwayat...
                    </div>
                </div> --}}

            </div>
            <div class="modal-foot">
                <button class="btn btn-ghost" onclick="closeSync()">Tutup</button>
                <button class="btn btn-sync" id="btnRunSync" onclick="doSync()">
                    <span class="spin" id="spinSync"></span>
                    <i class="fas fa-sync-alt" id="icoSync"></i>
                    <span id="txtSync">Jalankan Sinkronisasi</span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL UPLOAD -->
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
                    <p><strong>Catatan:</strong> Header kolom harus berada di baris ke-2. Baris pertama boleh berisi
                        judul atau dikosongkan.</p>
                </div>
                <div class="prog-wrap" id="progWrap">
                    <div class="prog-bar">
                        <div class="prog-fill" id="progFill"></div>
                    </div>
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

    <!-- MODAL EDIT -->
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
                    <div class="fg col-full">
                        <label>Nama WBP</label>
                        <input type="text" id="eWbp" class="fi" placeholder="Nama WBP">
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
                    <div class="fg col-full">
                        <label>Catatan / No HP</label>
                        <input type="text" id="eCatatan" class="fi" placeholder="Catatan atau No HP">
                    </div>
                    <div class="fg">
                        <label>Foto KTP</label>
                        <input type="file" id="eFotoKtp" class="fi" accept="image/*"
                            onchange="previewFoto(this,'previewKtp','previewKtpWrap')">
                        <div class="foto-preview-wrap" id="previewKtpWrap">
                            <img id="previewKtp" src="" alt="Preview KTP">
                            <span id="currentKtpText"></span>
                        </div>
                    </div>
                    <div class="fg">
                        <label>Foto Diri</label>
                        <input type="file" id="eFotoDiri" class="fi" accept="image/*"
                            onchange="previewFoto(this,'previewDiri','previewDiriWrap')">
                        <div class="foto-preview-wrap" id="previewDiriWrap">
                            <img id="previewDiri" src="" alt="Preview Foto Diri">
                            <span id="currentDiriText"></span>
                        </div>
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

    <div class="toast-stack" id="toastStack"></div>

    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;


        window.addEventListener('DOMContentLoaded', () => {
            checkConnectionStatus();
            setTimeout(() => {
                runAutoSync();
            }, 2000);
        });

        function checkConnectionStatus() {
            fetch('{{ route('sinkronisasi.check') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    }
                })
                .then(r => r.json())
                .then(d => {
                    const dot = document.getElementById('syncDot');
                    const bar = document.getElementById('syncBar');
                    const meta = document.getElementById('syncBarMeta');

                    if (d.online) {
                        dot.className = 'sync-pulse-dot ok';
                        bar.classList.remove('error-state');
                        meta.textContent = 'Terhubung ke SIPIRMAN — ' + d.total_penitip + ' penitip terdaftar.';
                        doPreviewSilent();
                    } else {
                        dot.className = 'sync-pulse-dot offline';
                        bar.classList.add('error-state');
                        document.getElementById('syncBarMsg').textContent = 'Tidak dapat terhubung ke SIPIRMAN.';
                        meta.textContent = d.message || 'Periksa konfigurasi database SIPIRMAN di .env';
                    }
                })
                .catch(() => {
                    document.getElementById('syncDot').className = 'sync-pulse-dot offline';
                    document.getElementById('syncBarMsg').textContent = 'Gagal memeriksa koneksi SIPIRMAN.';
                });
        }

        function runAutoSync() {
            fetch('{{ route('sinkronisasi.run') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    }
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        const s = d.stats || {};
                        const ada = (s.update_foto_ktp || 0) + (s.update_foto_diri || 0) +
                            (s.penitip_baru || 0) + (s.sdp_updated || 0);

                        if (ada > 0) {
                            toast('sync', 'Auto-Sync Selesai', d.message);
                            setTimeout(() => location.reload(), 2500);
                        } else {
                            document.getElementById('syncBarMsg').textContent =
                                'Semua data sudah sinkron dengan SIPIRMAN.';
                            document.getElementById('syncBarMeta').textContent =
                                'Auto-sync: ' + new Date().toLocaleString('id-ID');
                            document.getElementById('syncDot').className = 'sync-pulse-dot ok';
                        }
                    }
                })
                .catch(() => {
                });
        }

        // ══════════════════════════════════════════════════════════════════════════
        // SYNC MODAL
        // ══════════════════════════════════════════════════════════════════════════

        function openSync() {
            document.getElementById('modalSync').classList.add('show');
            // Reset tab ke Sinkronisasi
            showTab('tabSync', document.getElementById('tabBtnSync'));
            checkConnInModal();
            doPreview();
        }

        function closeSync() {
            document.getElementById('modalSync').classList.remove('show');
        }

        document.getElementById('modalSync').addEventListener('click', e => {
            if (e.target === e.currentTarget) closeSync();
        });

        function showTab(tabId, btn) {
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.sync-tab').forEach(b => b.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            btn.classList.add('active');
        }

        // Cek koneksi di dalam modal
        function checkConnInModal() {
            const el = document.getElementById('connStatus');
            const tx = document.getElementById('connStatusText');
            el.style.background = 'var(--surface2)';
            el.style.borderColor = 'var(--border)';
            tx.innerHTML =
                '<i class="fas fa-circle-notch fa-spin" style="margin-right:6px;color:var(--text-light);"></i>Memeriksa koneksi...';

            fetch('{{ route('sinkronisasi.check') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    }
                })
                .then(r => r.json())
                .then(d => {
                    if (d.online) {
                        el.style.background = '#f0fdf4';
                        el.style.borderColor = '#6ee7b7';
                        tx.innerHTML =
                            '<i class="fas fa-check-circle" style="color:var(--success);margin-right:6px;"></i>' +
                            '<strong style="color:var(--success);">Terhubung</strong> — ' +
                            d.total_penitip + ' data penitip di SIPIRMAN.';
                    } else {
                        el.style.background = 'var(--danger-light)';
                        el.style.borderColor = '#fecaca';
                        tx.innerHTML =
                            '<i class="fas fa-times-circle" style="color:var(--danger);margin-right:6px;"></i>' +
                            '<strong style="color:var(--danger);">Tidak terhubung</strong> — ' + (d.message || '');
                    }
                })
                .catch(() => {
                    tx.innerHTML =
                        '<i class="fas fa-exclamation-triangle" style="color:var(--warning);margin-right:6px;"></i>Gagal memeriksa koneksi.';
                });
        }

        // Preview (dengan update UI)
        function doPreview() {
            const btn = document.getElementById('btnPreview');
            const errEl = document.getElementById('previewError');
            const boxBtn = document.getElementById('btnBarPreview');

            if (btn) btn.disabled = true;
            if (boxBtn) boxBtn.disabled = true;
            if (errEl) errEl.style.display = 'none';

            // Tampilkan loading di grid
            ['pvTotalKunjungan', 'pvTotalPenitip', 'pvPerluSync', 'pvMatch', 'pvKtp', 'pvDiri'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.textContent = '...';
                    el.closest('.sync-stat-box').classList.add('loading');
                }
            });

            fetch('{{ route('sinkronisasi.preview') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    }
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        setPreview('pvTotalKunjungan', d.total_kunjungan);
                        setPreview('pvTotalPenitip', d.total_penitip);
                        setPreview('pvPerluSync', d.perlu_sync);
                        setPreview('pvMatch', d.akan_dicocokkan);
                        setPreview('pvKtp', d.foto_ktp_akan_diisi);
                        setPreview('pvDiri', d.foto_diri_akan_diisi);

                        document.getElementById('syncBarMsg').textContent =
                            (d.perlu_sync || 0) + ' data perlu disinkronisasi · ' +
                            (d.akan_dicocokkan || 0) + ' dapat dicocokkan · ' +
                            (d.akan_ekspor_baru || 0) + ' akan diekspor ke SIPIRMAN.';
                    } else {
                        if (errEl) {
                            errEl.textContent = d.message || 'Gagal memuat preview.';
                            errEl.style.display = 'block';
                        }
                        ['pvTotalKunjungan', 'pvTotalPenitip', 'pvPerluSync', 'pvMatch', 'pvKtp', 'pvDiri'].forEach(
                            id => {
                                const el = document.getElementById(id);
                                if (el) el.textContent = '✗';
                            });
                        document.getElementById('syncBarMsg').textContent = 'Gagal terhubung ke SIPIRMAN.';
                    }
                })
                .catch(() => {
                    if (errEl) {
                        errEl.textContent = 'Tidak dapat menghubungi server.';
                        errEl.style.display = 'block';
                    }
                })
                .finally(() => {
                    if (btn) btn.disabled = false;
                    if (boxBtn) boxBtn.disabled = false;
                });
        }

        // Preview silent (untuk auto-load di status bar tanpa modal terbuka)
        function doPreviewSilent() {
            fetch('{{ route('sinkronisasi.preview') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    }
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        document.getElementById('syncBarMsg').textContent =
                            (d.perlu_sync || 0) + ' data perlu disinkronisasi · ' +
                            (d.akan_dicocokkan || 0) + ' dapat dicocokkan.';
                    }
                })
                .catch(() => {});
        }

        function setPreview(id, val) {
            const el = document.getElementById(id);
            if (!el) return;
            el.textContent = (val ?? 0).toLocaleString('id');
            el.closest('.sync-stat-box').classList.remove('loading');
        }

        // Jalankan sinkronisasi manual
        // Ganti fungsi doSync() yang lama di blade dengan ini:

        async function doSync() {
            const btn = document.getElementById('btnRunSync');

            // loading button
            btn.disabled = true;
            document.getElementById('spinSync').style.display = 'block';
            document.getElementById('icoSync').style.display = 'none';
            document.getElementById('txtSync').textContent = 'Menyinkronisasi...';

            // reset log
            clearLog();
            appendLog('info', 'Memulai sinkronisasi...');

            // timeout 10 menit
            const controller = new AbortController();
            const timeoutId = setTimeout(() => {
                controller.abort();
            }, 600000);

            try {

                // request sync
                const response = await fetch('{{ route('sinkronisasi.run') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                    },
                    signal: controller.signal,
                });

                clearTimeout(timeoutId);

                // cek response
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const d = await response.json();

                // tampilkan log realtime hasil backend
                clearLog();

                if (d.log && Array.isArray(d.log) && d.log.length > 0) {

                    d.log.forEach(item => {

                        appendLog(
                            item.level || 'info',
                            item.message || '-',
                            item.time || null
                        );

                    });

                } else {

                    appendLog('warning', 'Tidak ada log sinkronisasi.');

                }

                // sukses
                if (d.success) {

                    toast('sync', 'Sinkronisasi Selesai', d.message);

                    document.getElementById('syncDot').className = 'sync-pulse-dot ok';

                    document.getElementById('syncBarMsg').textContent = d.message;

                    document.getElementById('syncBarMeta').textContent =
                        'Terakhir sync: ' +
                        new Date().toLocaleString('id-ID') +
                        ' (' + d.elapsed + 's)';

                    doPreview();

                    // tombol berubah menjadi selesai
                    document.getElementById('txtSync').textContent = 'Sinkronisasi Selesai';

                    document.getElementById('icoSync').className =
                        'fas fa-check';

                    // tampilkan notifikasi di log
                    appendLog(
                        'success',
                        'Sinkronisasi selesai. Silakan tutup popup atau refresh halaman.'
                    );
                } else {

                    appendLog(
                        'error',
                        'Sinkronisasi gagal: ' + (d.message || 'Unknown error')
                    );

                    toast(
                        'err',
                        'Sinkronisasi Gagal',
                        d.message || 'Terjadi kesalahan saat sinkronisasi.'
                    );

                }

            } catch (err) {

                clearTimeout(timeoutId);

                if (err.name === 'AbortError') {

                    appendLog(
                        'error',
                        'Request timeout — sinkronisasi terlalu lama.'
                    );

                    toast(
                        'err',
                        'Timeout',
                        'Sinkronisasi terlalu lama. Silakan coba lagi.'
                    );

                } else {

                    appendLog(
                        'error',
                        'Error: ' + err.message
                    );

                    toast(
                        'err',
                        'Error',
                        err.message
                    );

                }

            } finally {

                // reset button
                resetSyncBtn();

            }
        }

        function resetSyncBtn() {
            const btn = document.getElementById('btnRunSync');
            btn.disabled = false;
            document.getElementById('spinSync').style.display = 'none';
            document.getElementById('icoSync').style.display = 'inline-block';
            document.getElementById('txtSync').textContent = 'Jalankan Sinkronisasi';
            document.getElementById('btnSyncHeader').innerHTML =
                '<i class="fas fa-sync-alt"></i> Sinkronisasi SIPIRMAN';
        }

        // Log panel
        function appendLog(level, message, time) {
            const panel = document.getElementById('syncLogPanel');
            // Hapus placeholder
            const placeholder = panel.querySelector('.log-panel-empty');
            if (placeholder) placeholder.remove();

            const line = document.createElement('div');
            line.className = 'log-line';
            const t = time || new Date().toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            line.innerHTML =
                `<span class="log-time">[${t}]</span>` +
                `<span class="log-msg ${level}">${message}</span>`;
            panel.appendChild(line);
            panel.scrollTop = panel.scrollHeight;
        }

        function clearLog() {
            document.getElementById('syncLogPanel').innerHTML =
                '<span class="log-panel-empty">Log sinkronisasi akan muncul di sini...</span>';
        }

        // ══ RIWAYAT LOG (FIX #5) ═══════════════════════════════════════════════
        let historyLoaded = false;

        function loadHistory() {
            if (historyLoaded) return;
            historyLoaded = true;

            fetch('{{ route('sinkronisasi.log') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    }
                })
                .then(r => r.json())
                .then(d => {
                    const el = document.getElementById('historyContent');

                    if (!d.success || !d.history || d.history.length === 0) {
                        el.innerHTML = `<div style="color:var(--text-muted);font-size:13px;text-align:center;padding:24px 0;">
                    <i class="fas fa-inbox" style="font-size:20px;opacity:.4;display:block;margin-bottom:8px;"></i>
                    Belum ada riwayat sinkronisasi.</div>`;
                        return;
                    }

                    let html = '';
                    d.history.forEach((h, i) => {
                        const s = h.stats || {};
                        const status = h.status || 'success';
                        html += `
                <div class="hist-item" id="hist_${i}">
                    <div class="hist-head" onclick="toggleHist(${i})">
                        <div class="hist-head-left">
                            <span class="hist-badge ${status}">
                                <i class="fas fa-${status === 'success' ? 'check' : 'times'}" style="font-size:9px;"></i>
                                ${status === 'success' ? 'Berhasil' : 'Gagal'}
                            </span>
                            <span class="hist-date">${h.started_at || '—'}</span>
                            <span style="font-size:11px;color:var(--text-light);">${h.elapsed ? '(' + h.elapsed + 's)' : ''}</span>
                        </div>
                        <i class="fas fa-chevron-down hist-caret"></i>
                    </div>
                    <div class="hist-body">
                        <div class="hist-stats">
                            <div class="hstat"><strong>${s.matched ?? 0}</strong> dicocokkan</div>
                            <div class="hstat"><strong>${s.penitip_baru ?? 0}</strong> baru → SIPIRMAN</div>
                            <div class="hstat"><strong>${s.foto_ktp_diisi ?? 0}</strong> foto KTP diisi</div>
                            <div class="hstat"><strong>${s.foto_diri_diisi ?? 0}</strong> foto diri diisi</div>
                            <div class="hstat"><strong>${s.no_hp_diisi ?? 0}</strong> no HP diisi</div>
                            <div class="hstat"><strong>${s.skipped ?? 0}</strong> dilewati</div>
                            <div class="hstat"><strong>${s.error ?? 0}</strong> error</div>
                        </div>
                        <div class="log-panel" style="max-height:180px;">
                            ${h.status === 'failed' && h.error
                                ? `<div class="log-line"><span class="log-msg error">FATAL: ${h.error}</span></div>`
                                : (h.log || []).map(l =>
                                    `<div class="log-line">
                                                                                                                            <span class="log-time">[${l.time || ''}]</span>
                                                                                                                            <span class="log-msg ${l.level || 'info'}">${l.message}</span>
                                                                                                                        </div>`).join('')
                            }
                        </div>
                    </div>
                </div>`;
                    });

                    el.innerHTML = html;
                })
                .catch(() => {
                    document.getElementById('historyContent').innerHTML =
                        '<p style="color:var(--danger);font-size:13px;padding:16px 0;">Gagal memuat riwayat.</p>';
                });
        }

        function toggleHist(i) {
            document.getElementById('hist_' + i).classList.toggle('open');
        }

        // ══ UPLOAD ══════════════════════════════════════════════════════════════

        function openUpload() {
            document.getElementById('modalUpload').classList.add('show');
        }

        function closeUpload() {
            if (document.getElementById('txtSave').textContent === 'Memproses...') return;
            document.getElementById('modalUpload').classList.remove('show');
            clearFile();
            hideProg();
        }

        document.getElementById('modalUpload').addEventListener('click', e => {
            if (e.target === e.currentTarget) closeUpload();
        });

        const dz = document.getElementById('dropZone');
        dz.addEventListener('dragover', e => {
            e.preventDefault();
            dz.classList.add('over');
        });
        dz.addEventListener('dragleave', () => dz.classList.remove('over'));
        dz.addEventListener('drop', e => {
            e.preventDefault();
            dz.classList.remove('over');
            if (e.dataTransfer.files[0]) {
                document.getElementById('fileInput').files = e.dataTransfer.files;
                onPick(document.getElementById('fileInput'));
            }
        });

        function onPick(input) {
            const f = input.files[0];
            if (!f) return;
            const ext = f.name.split('.').pop().toLowerCase();
            if (!['xlsx', 'xls'].includes(ext)) {
                toast('err', 'Format Salah', 'Hanya .xlsx atau .xls.');
                clearFile();
                return;
            }
            if (f.size > 10 * 1024 * 1024) {
                toast('err', 'File Terlalu Besar', 'Maksimal 10MB.');
                clearFile();
                return;
            }
            document.getElementById('fName').textContent = f.name;
            document.getElementById('fSize').textContent = fmtBytes(f.size);
            document.getElementById('fileChip').classList.add('show');
        }

        function clearFile() {
            document.getElementById('fileInput').value = '';
            document.getElementById('fileChip').classList.remove('show');
        }

        function fmtBytes(b) {
            if (b < 1024) return b + ' B';
            if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB';
            return (b / 1024 / 1024).toFixed(1) + ' MB';
        }

        function doImport() {
            const fi = document.getElementById('fileInput');
            if (!fi.files[0]) {
                toast('err', 'File Kosong', 'Pilih file Excel terlebih dahulu.');
                return;
            }
            setBtnLoad('Save', true);
            showProg();
            const fd = new FormData();
            fd.append('file', fi.files[0]);
            fd.append('_token', CSRF);
            let p = 0;
            const iv = setInterval(() => {
                if (p < 85) {
                    p += Math.random() * 8;
                    setProg(Math.min(p, 85));
                }
            }, 300);
            fetch('{{ route('kunjungan.import') }}', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(d => {
                    clearInterval(iv);
                    setProg(100);
                    setTimeout(() => {
                        setBtnLoad('Save', false);
                        hideProg();
                        if (d.success) {

                            toast('ok', 'Berhasil!', d.message);

                            closeUpload();

                            doPreview();

                            runAutoSync();
                        } else toast('err', 'Gagal', d.message);
                    }, 500);
                })
                .catch(() => {
                    clearInterval(iv);
                    setBtnLoad('Save', false);
                    hideProg();
                    toast('err', 'Error', 'Tidak dapat menghubungi server.');
                });
        }

        function showProg() {
            document.getElementById('progWrap').classList.add('show');
        }

        function hideProg() {
            document.getElementById('progWrap').classList.remove('show');
            setProg(0);
        }

        function setProg(v) {
            document.getElementById('progFill').style.width = v + '%';
            document.getElementById('progText').textContent =
                v < 100 ? `Memproses... ${Math.round(v)}%` : 'Menyimpan ke database...';
        }

        // ══ EDIT ════════════════════════════════════════════════════════════════

        function openEdit(id, wbp, peng, jk, hub, sub, alamat, nik, tgl, catatan, fotoKtp, fotoDiri) {
            document.getElementById('eId').value = id;
            document.getElementById('eWbp').value = wbp ?? '';
            document.getElementById('ePeng').value = peng ?? '';
            document.getElementById('eJk').value = jk ?? '';
            document.getElementById('eHub').value = hub ?? '';
            document.getElementById('eSub').value = sub ?? '';
            document.getElementById('eAlamat').value = alamat ?? '';
            document.getElementById('eNik').value = nik ?? '';
            document.getElementById('eTgl').value = tgl ?? '';
            document.getElementById('eCatatan').value = catatan ?? '';
            document.getElementById('eFotoKtp').value = '';
            document.getElementById('eFotoDiri').value = '';
            setCurrentFoto('previewKtp', 'previewKtpWrap', 'currentKtpText', fotoKtp, 'KTP', 'ktp');
            setCurrentFoto('previewDiri', 'previewDiriWrap', 'currentDiriText', fotoDiri, 'Foto Diri', 'foto_diri');
            document.getElementById('modalEdit').classList.add('show');
        }

        // Ganti dua fungsi setCurrentFotoKtp & setCurrentFotoDiri 
        // dengan satu fungsi universal ini:
        function setCurrentFoto(imgId, wrapId, textId, path, label, folder) {
            const wrap = document.getElementById(wrapId);
            const img = document.getElementById(imgId);
            const txt = document.getElementById(textId);

            if (path) {
                const url = '{{ route('sinkronisasi.foto') }}?path=' + encodeURIComponent(path) +
                    '&folder=' + encodeURIComponent(folder || '');
                img.src = url;
                txt.textContent = label + ' tersimpan — pilih file baru untuk mengganti.';
                wrap.classList.add('show');
            } else {
                img.src = '';
                txt.textContent = '';
                wrap.classList.remove('show');
            }
        }

        function previewFoto(input, imgId, wrapId) {
            if (!input.files[0]) return;
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById(imgId).src = e.target.result;
                document.getElementById(wrapId).classList.add('show');
                const textId = wrapId === 'previewKtpWrap' ? 'currentKtpText' : 'currentDiriText';
                document.getElementById(textId).textContent = 'File baru dipilih: ' + input.files[0].name;
            };
            reader.readAsDataURL(input.files[0]);
        }

        function closeEdit() {
            document.getElementById('modalEdit').classList.remove('show');
        }

        document.getElementById('modalEdit').addEventListener('click', e => {
            if (e.target === e.currentTarget) closeEdit();
        });

        function doUpdate() {
            const id = document.getElementById('eId').value;
            const fd = new FormData();
            fd.append('_method', 'PUT');
            fd.append('_token', CSRF);
            fd.append('wbp', document.getElementById('eWbp').value);
            fd.append('pengunjung', document.getElementById('ePeng').value);
            fd.append('jenis_kelamin', document.getElementById('eJk').value);
            fd.append('hubungan', document.getElementById('eHub').value);
            fd.append('sub_hubungan', document.getElementById('eSub').value);
            fd.append('alamat_pengunjung', document.getElementById('eAlamat').value);
            fd.append('no_identitas', document.getElementById('eNik').value);
            fd.append('waktu_kunjungan', document.getElementById('eTgl').value);
            fd.append('catatan', document.getElementById('eCatatan').value);
            const ktpFile = document.getElementById('eFotoKtp').files[0];
            const diriFile = document.getElementById('eFotoDiri').files[0];
            if (ktpFile) fd.append('foto_ktp', ktpFile);
            if (diriFile) fd.append('foto_diri', diriFile);
            setBtnLoad('Update', true);
            fetch(`/kunjungan/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json'
                    },
                    body: fd,
                })
                .then(r => r.json())
                .then(d => {
                    setBtnLoad('Update', false);
                    if (d.success) {
                        toast('ok', 'Berhasil', d.message);
                        closeEdit();
                        setTimeout(() => location.reload(), 1200);
                    } else toast('err', 'Gagal', d.message);
                })
                .catch(() => {
                    setBtnLoad('Update', false);
                    toast('err', 'Error', 'Tidak dapat menghubungi server.');
                });
        }

        // ══ DELETE ══════════════════════════════════════════════════════════════

        function doDelete(id) {
            if (!confirm('Yakin ingin menghapus data ini?')) return;
            fetch(`/kunjungan/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json'
                    },
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        toast('ok', 'Dihapus', d.message);
                        setTimeout(() => location.reload(), 1200);
                    } else toast('err', 'Gagal', d.message);
                })
                .catch(() => toast('err', 'Error', 'Tidak dapat menghubungi server.'));
        }

        // ══ LOADING STATE ════════════════════════════════════════════════════════

        function setBtnLoad(which, state) {
            document.getElementById('spin' + which).style.display = state ? 'block' : 'none';
            document.getElementById('ico' + which).style.display = state ? 'none' : 'inline-block';
            document.getElementById('txt' + which).textContent = state ?
                'Memproses...' : (which === 'Save' ? 'Simpan' : 'Simpan Perubahan');
            document.getElementById('btn' + which).disabled = state;
        }

        // ══ TOAST ════════════════════════════════════════════════════════════════

        function toast(type, title, msg) {
            const icons = {
                ok: 'check-circle',
                err: 'exclamation-circle',
                sync: 'sync-alt'
            };
            const el = document.createElement('div');
            el.className = `toast ${type}`;
            el.innerHTML = `
            <i class="toast-i fas fa-${icons[type]||'info-circle'}"></i>
            <div class="toast-b">
                <div class="toast-t">${title}</div>
                <div class="toast-m">${msg}</div>
            </div>`;
            document.getElementById('toastStack').appendChild(el);
            setTimeout(() => {
                el.style.cssText = 'opacity:0;transform:translateX(16px);transition:.25s';
                setTimeout(() => el.remove(), 260);
            }, 5000);
        }
    </script>
</body>

</html>
