<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>إعادة تعيين كلمة المرور | {{ config('app.name') }}</title>

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
        .login-shell { width: 100%; max-width: 480px; }
        .login-brand { text-align: center; color: #fff; margin-bottom: 1.25rem; }
        .login-brand i { font-size: 3rem; }
        .login-brand h2 { font-weight: 800; margin: .5rem 0 .25rem; }
        .login-brand p { opacity: .9; margin: 0; }
        .login-card { background: white; border-radius: 18px; padding: 2.25rem 1.75rem; box-shadow: 0 20px 50px rgba(0,0,0,.25); }
        .login-card h4 { font-weight: 800; color: #1f2937; margin: 0 0 1.25rem; }
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
        .pw-hint {
            background: #fef3c7; border: 1px solid #fde68a; color: #92400e;
            padding: .6rem .8rem; border-radius: 8px; font-size: .8rem; margin-bottom: 1rem;
        }
        .pw-hint ul { margin: .25rem 0 0; padding-inline-start: 1.25rem; }
    </style>
</head>
<body>
    <div class="login-shell">
        <div class="login-brand">
            <i class="bi bi-shield-lock"></i>
            <h2>{{ config('app.name') }}</h2>
            <p>تعيين كلمة مرور جديدة</p>
        </div>

        <div class="login-card">
            <h4><i class="bi bi-key me-1 text-primary"></i> كلمة مرور جديدة</h4>

            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="pw-hint">
                <strong>متطلبات كلمة المرور:</strong>
                <ul class="mb-0">
                    <li>10 أحرف على الأقل</li>
                    <li>حرف كبير وحرف صغير</li>
                    <li>رقم على الأقل</li>
                    <li>رمز خاص (مثل: ! @ # $)</li>
                    <li>غير مسربة من قواعد بيانات اختراقات معروفة</li>
                </ul>
            </div>

            <form action="{{ route('password.update') }}" method="POST">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-3">
                    <label class="form-label" for="email">البريد الإلكتروني</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required
                               class="form-control @error('email') is-invalid @enderror"
                               placeholder="example@domain.com" dir="ltr">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">كلمة المرور الجديدة</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" id="password" name="password" required autofocus
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="••••••••••">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">تأكيد كلمة المرور</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                               class="form-control"
                               placeholder="••••••••••">
                    </div>
                </div>

                <button type="submit" class="btn btn-login w-100">
                    <i class="bi bi-check2-circle ms-1"></i> تحديث كلمة المرور
                </button>
            </form>
        </div>

        <div class="footer-note">
            &copy; {{ date('Y') }} {{ config('app.name') }} — جميع الحقوق محفوظة
        </div>
    </div>
</body>
</html>
