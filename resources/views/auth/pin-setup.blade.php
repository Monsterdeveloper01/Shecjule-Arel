<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Setup PIN — Schedule</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card auth-card-setup">
            <div class="auth-logo">
                <div class="auth-logo-icon">S</div>
                <h1 class="auth-title">Selamat Datang!</h1>
                <p class="auth-subtitle">Setup akun pertama kamu</p>
            </div>

            <form method="POST" action="{{ route('pin.setup') }}" id="pinSetupForm">
                @csrf

                <div class="form-group">
                    <label for="nameInput" class="form-label">Nama Kamu</label>
                    <input type="text" name="name" id="nameInput" class="form-input" placeholder="Contoh: Arel" value="{{ old('name') }}" required>
                    @error('name')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Buat PIN (6 digit)</label>
                    <div class="pin-display">
                        <div class="pin-dots" id="pinDotsSetup">
                            <div class="pin-dot" data-index="0"></div>
                            <div class="pin-dot" data-index="1"></div>
                            <div class="pin-dot" data-index="2"></div>
                            <div class="pin-dot" data-index="3"></div>
                            <div class="pin-dot" data-index="4"></div>
                            <div class="pin-dot" data-index="5"></div>
                        </div>
                    </div>
                    <input type="hidden" name="pin" id="pinInput">
                    @error('pin')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Konfirmasi PIN</label>
                    <div class="pin-display">
                        <div class="pin-dots" id="pinDotsConfirm">
                            <div class="pin-dot" data-index="0"></div>
                            <div class="pin-dot" data-index="1"></div>
                            <div class="pin-dot" data-index="2"></div>
                            <div class="pin-dot" data-index="3"></div>
                            <div class="pin-dot" data-index="4"></div>
                            <div class="pin-dot" data-index="5"></div>
                        </div>
                    </div>
                    <input type="hidden" name="pin_confirmation" id="pinConfirmInput">
                </div>

                <div class="numpad" id="numpad">
                    @for ($i = 1; $i <= 9; $i++)
                        <button type="button" class="numpad-key" data-key="{{ $i }}">{{ $i }}</button>
                    @endfor
                    <button type="button" class="numpad-key numpad-empty" disabled></button>
                    <button type="button" class="numpad-key" data-key="0">0</button>
                    <button type="button" class="numpad-key numpad-delete" data-key="delete" aria-label="Hapus">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 4H8l-7 8 7 8h13a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"></path>
                            <line x1="18" y1="9" x2="12" y2="15"></line>
                            <line x1="12" y1="9" x2="18" y2="15"></line>
                        </svg>
                    </button>
                </div>

                <button type="submit" class="btn-primary btn-full" id="setupSubmitBtn" disabled>
                    Mulai Pakai Schedule
                </button>
            </form>
        </div>

        <div class="auth-footer">
            <p>Telkom University — Sistem Informasi</p>
        </div>
    </div>
</body>
</html>
