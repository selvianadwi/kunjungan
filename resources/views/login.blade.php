<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Data Kunjungan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* ── Background foto gedung ── */
        .bg-photo {
            position: fixed;
            inset: 0;
            background-image: url('assets/img/bg-rutan.jpg');
            background-size: cover;
            background-position: center 40%;
            background-repeat: no-repeat;
            z-index: 0;
        }

        /* Overlay gelap agar foto tidak terlalu terang */
        .bg-overlay {
            position: fixed;
            inset: 0;
            background: linear-gradient(
                135deg,
                rgba(10, 30, 80, 0.82) 0%,
                rgba(14, 55, 130, 0.75) 40%,
                rgba(8, 40, 100, 0.85) 100%
            );
            z-index: 1;
        }

        /* Subtle noise texture */
        .bg-overlay::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none;
        }

        /* ── Wrapper ── */
        .page-wrapper {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 960px;
            display: flex;
            align-items: center;
            gap: 60px;
            animation: fadeUp 0.6s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Sisi kiri: info instansi ── */
        .left-panel {
            flex: 1;
            color: white;
            display: none;
        }

        @media (min-width: 800px) {
            .left-panel { display: block; }
        }

        .left-panel .inst-logo {
            width: 70px; height: 70px;
            background: rgba(255,255,255,0.12);
            border: 1.5px solid rgba(255,255,255,0.25);
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 30px; color: #ffd700;
            margin-bottom: 24px;
            backdrop-filter: blur(8px);
        }

        .left-panel h2 {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: rgba(255,255,255,0.6);
            margin-bottom: 10px;
        }

        .left-panel h1 {
            font-size: 28px;
            font-weight: 800;
            line-height: 1.25;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }

        .left-panel .subtitle {
            font-size: 14px;
            color: rgba(255,255,255,0.65);
            margin-bottom: 36px;
            line-height: 1.6;
        }

        .divider-line {
            width: 48px; height: 3px;
            background: linear-gradient(90deg, #ffd700, transparent);
            border-radius: 2px;
            margin-bottom: 32px;
        }

        .feature-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13.5px;
            color: rgba(255,255,255,0.75);
        }

        .feature-item .feat-icon {
            width: 32px; height: 32px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; color: #7dd3fc;
            flex-shrink: 0;
        }

        /* ── Sisi kanan: login card ── */
        .login-card {
            width: 100%;
            max-width: 400px;
            background: rgba(255, 255, 255, 0.97);
            border-radius: 20px;
            box-shadow:
                0 24px 64px rgba(0, 0, 0, 0.35),
                0 0 0 1px rgba(255,255,255,0.15);
            overflow: hidden;
            flex-shrink: 0;
            backdrop-filter: blur(20px);
        }

        /* Header card */
        .card-top {
            background: linear-gradient(135deg, #1a3a8f 0%, #1a56db 50%, #0ea5e9 100%);
            padding: 30px 36px 26px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .card-top::before {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 120px; height: 120px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
        }

        .card-top::after {
            content: '';
            position: absolute;
            bottom: -30px; left: -30px;
            width: 90px; height: 90px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }

        .card-avatar {
            width: 60px; height: 60px;
            background: rgba(255,255,255,0.18);
            border: 2px solid rgba(255,255,255,0.35);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
            font-size: 26px; color: white;
            position: relative; z-index: 1;
        }

        .card-top h1 {
            font-size: 17px; font-weight: 800;
            color: white; margin-bottom: 3px;
            position: relative; z-index: 1;
        }

        .card-top p {
            font-size: 12px;
            color: rgba(255,255,255,0.72);
            position: relative; z-index: 1;
        }

        /* Body card */
        .card-body { padding: 30px 36px 32px; }

        /* Alert error */
        .alert-error {
            display: flex; align-items: center; gap: 10px;
            background: #fef2f2; border: 1.5px solid #fecaca;
            border-radius: 10px; padding: 11px 14px;
            margin-bottom: 22px; font-size: 13px; color: #dc2626;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%,100% { transform: translateX(0); }
            20%      { transform: translateX(-5px); }
            40%      { transform: translateX(5px); }
            60%      { transform: translateX(-3px); }
            80%      { transform: translateX(3px); }
        }

        /* Form */
        .form-group { margin-bottom: 18px; }

        .form-group label {
            display: block;
            font-size: 12px; font-weight: 700;
            color: #374151; margin-bottom: 7px;
            text-transform: uppercase; letter-spacing: 0.5px;
        }

        .input-wrap { position: relative; }

        .input-icon {
            position: absolute; left: 13px; top: 50%;
            transform: translateY(-50%);
            color: #9ca3af; font-size: 13px;
            pointer-events: none;
            transition: color 0.2s;
        }

        .input-wrap:focus-within .input-icon {
            color: #1a56db;
        }

        .form-input {
            width: 100%;
            padding: 11px 14px 11px 40px;
            border: 1.5px solid #dbe4f7; border-radius: 10px;
            font-family: inherit; font-size: 14px; color: #111827;
            background: #f8faff; outline: none;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            border-color: #1a56db; background: white;
            box-shadow: 0 0 0 3px rgba(26,86,219,0.10);
        }

        .form-input.is-error { border-color: #dc2626; background: #fef9f9; }

        .toggle-pass {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #9ca3af; font-size: 13px; padding: 4px;
            border-radius: 4px; transition: color 0.2s;
        }
        .toggle-pass:hover { color: #1a56db; }

        /* Submit */
        .btn-login {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #1a3a8f, #1a56db 60%, #0ea5e9);
            color: white; border: none; border-radius: 10px;
            font-family: inherit; font-size: 14.5px; font-weight: 700;
            cursor: pointer; transition: all 0.2s ease;
            box-shadow: 0 4px 16px rgba(26,86,219,0.35);
            display: flex; align-items: center; justify-content: center; gap: 9px;
            margin-top: 6px; letter-spacing: 0.2px;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 22px rgba(26,86,219,0.45);
        }

        .btn-login:active { transform: translateY(0); }

        .btn-login:disabled {
            opacity: 0.75; cursor: not-allowed; transform: none;
        }

        .spinner {
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,0.35);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* Footer card */
        .card-footer {
            padding: 14px 36px 16px;
            border-top: 1px solid #f0f4ff;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
        }

        .sec-badge {
            display: flex; align-items: center; gap: 5px;
            font-size: 11px; color: #9ca3af;
        }

        .sec-badge i { color: #059669; font-size: 10px; }

        /* Copyright bawah */
        .bottom-credit {
            position: fixed;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 3;
            font-size: 11.5px;
            color: rgba(255,255,255,0.4);
            white-space: nowrap;
        }
    </style>
</head>
<body>

<!-- Background foto gedung -->
<div class="bg-photo"></div>
<div class="bg-overlay"></div>

<div class="page-wrapper">

    <!-- ── Panel Kiri ── -->
    <div class="left-panel">
        <div class="inst-logo">
            <i class="fas fa-balance-scale"></i>
        </div>

        <h1>Sistem Data Kunjungan SDP</h1>
        <p class="subtitle"> Platform pengelolaan data kunjungan SDP
            Rutan Kelas IIB Rembang secara digital, akurat, dan efisien.</p>

        <div class="divider-line"></div>

        {{-- <div class="feature-list">
            <div class="feature-item">
                <div class="feat-icon"><i class="fas fa-file-import"></i></div>
                <span>Import data kunjungan dari Excel / CSV</span>
            </div>
            <div class="feature-item">
                <div class="feat-icon"><i class="fas fa-id-card"></i></div>
                <span>Validasi otomatis NIK pengunjung</span>
            </div>
            <div class="feature-item">
                <div class="feat-icon"><i class="fas fa-shield-alt"></i></div>
                <span>Akses aman berbasis autentikasi</span>
            </div>
            <div class="feature-item">
                <div class="feat-icon"><i class="fas fa-chart-bar"></i></div>
                <span>Rekap statistik kunjungan real-time</span>
            </div>
        </div> --}}
    </div>

    <!-- ── Login Card ── -->
    <div class="login-card">

        <div class="card-top">
            <div class="card-avatar">
                <i class="fas fa-users"></i>
            </div>
            <h1>Data Kunjungan</h1>
            <p>Masuk untuk mengakses sistem</p>
        </div>

        <div class="card-body">

            @if ($errors->has('login'))
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ $errors->first('login') }}</span>
            </div>
            @endif

            @if(session('error'))
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ session('error') }}</span>
            </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" id="loginForm">
                @csrf

                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrap">
                        <i class="fas fa-user input-icon"></i>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-input {{ $errors->has('login') ? 'is-error' : '' }}"
                            value="{{ old('username') }}"
                            placeholder="Masukkan username"
                            autocomplete="username"
                            autofocus
                            required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock input-icon"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input {{ $errors->has('login') ? 'is-error' : '' }}"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required>
                        <button type="button" class="toggle-pass" onclick="togglePassword()" tabindex="-1">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="btnLogin">
                    <span class="spinner" id="btnSpinner"></span>
                    <i class="fas fa-sign-in-alt" id="btnIcon"></i>
                    <span id="btnText">Masuk</span>
                </button>
            </form>
        </div>

    </div>
</div>

<div class="bottom-credit">
    &copy; {{ date('Y') }} Rutan Kelas IIB Rembang
</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('toggleIcon');
    input.type     = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}

document.getElementById('loginForm').addEventListener('submit', function () {
    const btn     = document.getElementById('btnLogin');
    const spinner = document.getElementById('btnSpinner');
    const icon    = document.getElementById('btnIcon');
    const text    = document.getElementById('btnText');

    btn.disabled          = true;
    spinner.style.display = 'block';
    icon.style.display    = 'none';
    text.textContent      = 'Memproses...';
});
</script>
</body>
</html>