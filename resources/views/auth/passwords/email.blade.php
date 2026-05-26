<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>نسيت كلمة المرور | {{ config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        html, body { margin: 0; padding: 0; height: 100%; }
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 1rem;
        }
        .login-shell { width: 100%; max-width: 460px; }
        .login-brand { text-align: center; color: #fff; margin-bottom: 1.25rem; }
        .login-brand i { font-size: 3rem; }
        .login-brand h2 { font-weight: 800; margin: .5rem 0 .25rem; }
        .login-brand p { opacity: .9; margin: 0; }
        .login-card { background: white; border-radius: 18px; padding: 2.25rem 1.75rem; box-shadow: 0 20px 50px rgba(0,0,0,.25); }
        .login-card h4 { font-weight: 800; color: #1f2937; margin: 0 0 .75rem; }
        .login-card .hint { color: #6b7280; font-size: .88rem; margin-bottom: 1.25rem; }
        .form-label { font-weight: 600; font-size: .9rem; color: #374151; }
        .form-control { padding: .7rem 1rem; border-radius: 10px; border: 1px solid #e5e7eb; }
        .form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 .2rem rgba(99,102,241,.15); }
        .input-group-text { background: #f9fafb; border-color: #e5e7eb; color: #6b7280; }
        .btn-login {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff; font-weight: 700; border: none; padding: .75rem;
            border-radius: 10px; font-size: 1rem; transition: all .2s;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff; transform: translateY(-1px); box-shadow: 0 8px 20px rgba(99,102,241,.4);
        }
        .footer-note { text-align: center; color: rgba(255,255,255,.85); margin-top: 1rem; font-size: .82rem; }
        .alert { border-radius: 10px; }
        .back-link { color: #6366f1; font-weight: 600; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-shell">
        <div class="login-brand">
            <i class="bi bi-key"></i>
            <h2>{{ config('app.name') }}</h2>
            <p>استعادة كلمة المرور</p>
        </div>

        <div class="login-card">
            <h4><i class="bi bi-envelope-arrow-up me-1 text-primary"></i> نسيت كلمة المرور؟</h4>
            <p class="hint">أدخل البريد الإلكتروني المرتبط بحسابك، وسنرسل لك رابطاً لإعادة تعيين كلمة المرور.</p>

            @if(session('success'))
                <div class="alert alert-success d-flex align-items-center">
                    <i class="bi bi-check-circle me-2"></i>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="email">البريد الإلكتروني</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                               class="form-control @error('email') is-invalid @enderror"
                               placeholder="example@domain.com" dir="ltr">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-login w-100">
                    <i class="bi bi-send ms-1"></i> إرسال رابط إعادة التعيين
                </button>

                <div class="text-center mt-3">
                    <a href="{{ route('login') }}" class="back-link">
                        <i class="bi bi-arrow-right"></i> العودة لتسجيل الدخول
                    </a>
                </div>
            </form>
        </div>

        <div class="footer-note">
            &copy; {{ date('Y') }} {{ config('app.name') }} — جميع الحقوق محفوظة
        </div>
    </div>
</body>
</html>
