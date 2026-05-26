<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    <style>
        /* mPDF registers the Cairo font family via fontdata config (ArabicPdfRenderer). */
        @page { margin: 1.5cm 1.2cm; }

        * { font-family: cairo, sans-serif; }
        html, body { font-family: cairo, sans-serif; }

        body {
            font-size: 12px;
            color: #1f2937;
            line-height: 1.55;
            direction: rtl;
            text-align: right;
        }

        h1, h2, h3, h4 { color: #0f172a; margin: 0 0 .35rem; font-weight: 700; }
        h1 { font-size: 22px; }
        h2 { font-size: 16px; }
        h3 { font-size: 13px; }

        .doc-header {
            display: table;
            width: 100%;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 3px solid #d4a437;
        }
        .doc-header .logo-side {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 60%;
        }
        .doc-header .logo-side .brand {
            font-size: 24px; font-weight: 800; color: #0f172a;
        }
        .doc-header .logo-side .brand .erp {
            background: #d4a437; color: #fff; font-size: 11px;
            padding: 1px 6px; border-radius: 4px; vertical-align: middle;
        }
        .doc-header .logo-side .tag {
            color: #64748b; font-size: 11px; margin-top: 4px;
        }
        .doc-header .info-side {
            display: table-cell;
            vertical-align: middle;
            text-align: left;
            width: 40%;
            direction: ltr;
        }
        .doc-header .info-side .num {
            font-size: 14px; font-weight: 700; color: #0f172a;
            font-family: 'Courier New', monospace;
        }
        .doc-header .info-side .date {
            color: #64748b; font-size: 11px;
        }

        .doc-title {
            text-align: center;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin: 12px 0 16px;
            padding: 8px 12px;
            background: #f1f5f9;
            border-radius: 6px;
        }

        .section { margin-bottom: 14px; page-break-inside: avoid; }
        .section-title {
            font-size: 13px; font-weight: 700; color: #0f172a;
            background: #eef2ff; padding: 6px 10px;
            border-right: 3px solid #1d4ed8;
            margin-bottom: 8px;
        }

        table { width: 100%; border-collapse: collapse; }
        table.data-table th, table.data-table td {
            border: 1px solid #d1d5db; padding: 5px 8px; font-size: 11px;
            vertical-align: middle;
        }
        table.data-table th { background: #f3f4f6; font-weight: 700; color: #374151; }
        table.kv-table td { padding: 4px 6px; font-size: 11px; }
        table.kv-table td.label { background: #f9fafb; font-weight: 600; color: #4b5563; width: 25%; }
        table.kv-table td.val { color: #0f172a; }

        .totals-row { background: #fef3c7 !important; font-weight: 800; }
        .totals-row td { color: #92400e !important; }

        .signature-block { margin-top: 30px; }
        .signature-row { display: table; width: 100%; }
        .signature-cell {
            display: table-cell; width: 50%; padding: 10px 18px;
            vertical-align: top; text-align: center; font-size: 11px;
        }
        .signature-line {
            border-top: 1px solid #1f2937; padding-top: 6px;
            margin-top: 36px; color: #4b5563;
        }

        @page { footer: html_page_footer; }
        .doc-footer {
            font-size: 9px; color: #94a3b8; text-align: center;
            border-top: 1px solid #e5e7eb; padding-top: 4px;
        }

        .badge {
            display: inline-block; padding: 2px 7px; border-radius: 3px;
            font-size: 9px; font-weight: 700;
        }
        .badge-gold   { background: #fef3c7; color: #92400e; }
        .badge-green  { background: #dcfce7; color: #15803d; }
        .badge-blue   { background: #dbeafe; color: #1d4ed8; }
        .badge-gray   { background: #f1f5f9; color: #475569; }

        .terms {
            background: #fafafa; padding: 10px 12px; font-size: 10px;
            border-radius: 4px; line-height: 1.7; color: #4b5563;
        }
        .terms ol { margin: 6px 18px 0; padding: 0; }
        .terms li { margin-bottom: 3px; }

        .stamp {
            position: absolute; top: 100px; left: 50px;
            font-size: 22px; color: rgba(220, 38, 38, .25);
            font-weight: 900; transform: rotate(-22deg);
            border: 4px solid rgba(220, 38, 38, .25);
            padding: 6px 14px; border-radius: 6px;
        }
    </style>
    @stack('styles')
</head>
<body>

@if(isset($watermark))
    <div class="stamp">{{ $watermark }}</div>
@endif

<div class="doc-header">
    <div class="logo-side">
        <div class="brand">CoreX <span class="erp">ERP</span></div>
        <div class="tag">نظام إدارة شركات السياحة الدينية</div>
    </div>
    <div class="info-side">
        <div class="num">@yield('doc_number')</div>
        <div class="date">@yield('doc_date', now()->format('Y-m-d H:i'))</div>
    </div>
</div>

<div class="doc-title">@yield('doc_title')</div>

@yield('content')

<htmlpagefooter name="page_footer">
    <div class="doc-footer">
        تم إنشاء هذه الوثيقة بواسطة CoreX ERP — {{ now()->format('Y-m-d H:i') }} —
        صفحة {PAGENO} من {nbpg}
    </div>
</htmlpagefooter>

</body>
</html>
