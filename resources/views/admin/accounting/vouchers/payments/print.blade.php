<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سند صرف {{ $voucher->number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 1.5cm; }
        body { font-family: 'Cairo', sans-serif; color: #1f2937; margin: 0; }
        .doc { max-width: 800px; margin: 0 auto; }
        .header {
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 3px double #1f2937; padding-bottom: 1rem; margin-bottom: 1.5rem;
        }
        .brand h1 { margin: 0; font-size: 1.8rem; font-weight: 800; }
        .brand small { color: #6b7280; }
        .doc-title {
            text-align: center;
            background: #fee2e2; border: 2px solid #991b1b;
            padding: .75rem; font-size: 1.5rem; font-weight: 800;
            border-radius: 8px; color: #991b1b;
        }
        .meta {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 1rem; margin: 1.5rem 0;
        }
        .meta-item {
            border: 1px solid #d1d5db; padding: .75rem 1rem; border-radius: 6px;
        }
        .meta-label { color: #6b7280; font-size: .85rem; }
        .meta-value { font-weight: 700; font-size: 1.05rem; margin-top: .15rem; }

        .amount-box {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border: 2px solid #991b1b; border-radius: 10px;
            padding: 1.25rem; margin: 1.5rem 0; text-align: center;
        }
        .amount-box .label { font-size: .9rem; color: #7f1d1d; }
        .amount-box .value { font-size: 2.4rem; font-weight: 800; color: #991b1b; }
        .amount-box .words { color: #7f1d1d; margin-top: .4rem; font-weight: 600; }

        .description-box {
            background: #f9fafb; border: 1px solid #e5e7eb;
            padding: 1rem; border-radius: 8px; margin: 1.25rem 0;
        }

        .signatures {
            display: grid; grid-template-columns: 1fr 1fr 1fr;
            gap: 1.5rem; margin-top: 2.5rem; padding-top: 1.5rem;
        }
        .sig {
            border-top: 1.5px solid #1f2937; padding-top: .5rem;
            text-align: center; font-weight: 700; font-size: .9rem;
        }

        .footer {
            margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;
            font-size: .75rem; color: #6b7280; text-align: center;
        }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="doc">

    <button class="no-print" onclick="window.print()" style="position:fixed; top:1rem; left:1rem; padding:.5rem 1rem; cursor:pointer; background:#dc2626; color:#fff; border:none; border-radius:6px;">
        🖨️ طباعة
    </button>

    <div class="header">
        <div class="brand">
            <h1>{{ config('app.name') }}</h1>
            <small>نظام إدارة شركات السياحة</small>
        </div>
        <div style="text-align:end;">
            <div><strong>رقم السند:</strong> {{ $voucher->number }}</div>
            <div><strong>التاريخ:</strong> {{ $voucher->date->format('Y-m-d') }}</div>
        </div>
    </div>

    <div class="doc-title">سند صرف</div>

    <div class="meta">
        <div class="meta-item">
            <div class="meta-label">صرفنا للسيد/السيدة</div>
            <div class="meta-value">{{ $voucher->party_name }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">من حساب</div>
            <div class="meta-value">{{ $voucher->cashAccount->name }}</div>
        </div>

        <div class="meta-item">
            <div class="meta-label">طريقة الصرف / المرجع</div>
            <div class="meta-value">{{ $voucher->reference ?: '— نقدي —' }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">العملة وسعر الصرف</div>
            <div class="meta-value">
                {{ $voucher->currency }}
                @if($voucher->currency !== 'EGP')
                    <small style="color:#6b7280;">— سعر الصرف: {{ number_format($voucher->exchange_rate, 4) }}</small>
                @endif
            </div>
        </div>
    </div>

    <div class="amount-box">
        <div class="label">المبلغ المنصرف</div>
        <div class="value">{{ number_format($voucher->amount, 2) }} {{ $voucher->currency }}</div>
        @if($voucher->currency !== 'EGP')
            <div class="words">يعادل {{ number_format($voucher->amount_egp, 2) }} جنيه مصري</div>
        @endif
    </div>

    <div class="description-box">
        <div class="meta-label">البيان</div>
        <div style="margin-top:.4rem;">{{ $voucher->description }}</div>
    </div>

    @if($voucher->isCancelled())
        <div style="background:#fee2e2; border:2px solid #dc2626; padding:1rem; border-radius:8px; text-align:center; color:#991b1b; font-weight:800; font-size:1.2rem; transform: rotate(-2deg);">
            ⛔ سند ملغي ⛔<br>
            <span style="font-size:.85rem; font-weight:600;">السبب: {{ $voucher->cancellation_reason }}</span>
        </div>
    @endif

    <div class="signatures">
        <div class="sig">المستلم (المستفيد)</div>
        <div class="sig">المحاسب<br><small style="font-weight:500; color:#6b7280;">{{ $voucher->creator?->name }}</small></div>
        <div class="sig">المدير المالي</div>
    </div>

    <div class="footer">
        تم إنشاء هذا السند آلياً من نظام {{ config('app.name') }} بتاريخ {{ now()->format('Y-m-d H:i') }} —
        القيد المحاسبي المرتبط: <code>{{ $voucher->journalEntry?->number ?? '—' }}</code>
    </div>
</div>
</body>
</html>
