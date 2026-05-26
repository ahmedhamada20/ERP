<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>تأكيد البريد الإلكتروني | {{ config('app.name') }}</title>

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
        .verify-shell { width: 100%; max-width: 500px; }
        .verify-brand { text-align: center; color: #fff; margin-bottom: 1.25rem; }
        .verify-brand i { font-size: 3rem; }
        .verify-brand h2 { font-weight: 800; margin: .5rem 0 .25rem; }
        .verify-brand p { opacity: .9; margin: 0; }
        .verify-card {
            background: white; border-radius: 18px; padding: 2.25rem 1.75rem;
            box-shadow: 0 20px 50px rgba(0,0,0,.25); text-align: center;
        }
        .verify-card h4 { font-weight: 800; color: #1f2937; margin: 0 0 .75rem; }
        .verify-card .hint { color: #6b7280; font-size: .95rem; margin-bottom: 1.5rem; line-height: 1.7; }
        .email-pill {
            display: inline-block; background: #eef2ff; color: #4f46e5;
            padding: .3rem .9rem; border-radius: 999px; font-weight: 600; direction: ltr;
        }
        .btn-action {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff; font-weight: 700; border: none; padding: .75rem 1.5rem;
            border-radius: 10px; font-size: 1rem; transition: all .2s; text-decoration: none;
            display: inline-block;
        }
        .btn-action:hover {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff; transform: translateY(-1px); box-shadow: 0 8px 20px rgba(99,102,241,.4);
        }
        .btn-link-logout { color: #6b7280; text-decoration: none; font-size: .88rem; }
        .btn-link-logout:hover { color: #ef4444; }
        .alert { border-radius: 10px; text-align: start; }
        .footer-note { text-align: center; color: rgba(255,255,255,.85); margin-top: 1rem; font-size: .82rem; }
    </style>
</head>
<body>
    <div class="verify-shell">
        <div class="verify-brand">
            <i class="bi bi-envelope-check"></i>
            <h2>{{ config('app.name') }}</h2>
            <p>تأكيد البريد الإلكتروني</p>
        </div>

        <div class="verify-card">
            <h4><i class="bi bi-envelope-paper text-primary me-1"></i> راجع بريدك الإلكتروني</h4>

            <p class="hint">
                أرسلنا رابط تأكيد إلى:<br>
                <span class="email-pill mt-2">{{ auth()->user()->email }}</span>
            </p>

            <p class="hint">
                اضغط على الرابط في الرسالة لتفعيل حسابك. إذا لم تجد الرسالة، تأكد من مجلد <strong>البريد المهمل (Spam)</strong>.
            </p>

            @if(session('success'))
                <div class="alert alert-success d-flex align-items-center">
                    <i class="bi bi-check-circle me-2"></i>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <div>{{ $errors->first() }}</div>
                </div>
            @endif

            <form action="{{ route('verification.send') }}" method="POST" class="mb-3">
                @csrf
                <button type="submit" class="btn-action">
                    <i class="bi bi-arrow-clockwise ms-1"></i> إعادة إرسال رابط التأكيد
                </button>
            </form>

            <form action="{{ route('logout') }}" method="POST" class="mt-3">
                @csrf
                <button type="submit" class="btn-link-logout">
                    <i class="bi bi-box-arrow-right"></i> تسجيل الخروج
                </button>
            </form>
        </div>

        <div class="footer-note">
            &copy; {{ date('Y') }} {{ config('app.name') }} — جميع الحقوق محفوظة
        </div>
    </div>
</body>
</html>
