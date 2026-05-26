<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>قائمة الدخل — {{ $from->format('Y-m-d') }} إلى {{ $to->format('Y-m-d') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 1.5cm; }
        body { font-family: 'Cairo', sans-serif; color: #1f2937; margin: 0; font-size: 12px; }
        .header { border-bottom: 3px double #1f2937; padding-bottom: 1rem; margin-bottom: 1rem; }
        .header h1 { margin: 0 0 .2rem; font-size: 1.5rem; }
        .header .sub { color: #6b7280; font-size: .85rem; }
        .header .period { float: left; }

        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: .4rem .6rem; vertical-align: middle; }
        .section { background: #eef2ff; font-weight: 700; color: #3730a3; padding: .55rem; border-top: 1.5px solid #c7d2fe; }
        .acc-row td { padding-inline-start: 1.5rem; border-bottom: 1px solid #f3f4f6; }
        .code { font-family: 'JetBrains Mono', monospace; font-weight: 600; }
        .amount { text-align: end; font-family: 'JetBrains Mono', monospace; font-weight: 700; }
        .subtotal { background: #f9fafb; font-weight: 800; border-top: 1px dashed #d1d5db; }
        .key { background: #1f2937; color: #fff; font-weight: 800; padding: .65rem .6rem; }
        .net { background: #15803d; color: #fff; font-weight: 800; font-size: 1.05rem; padding: .85rem .6rem; }
        .net.loss { background: #b91c1c; }

        .summary { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .8rem; margin: 1rem 0; }
        .sc { padding: .85rem 1rem; border: 1px solid #d1d5db; border-radius: 6px; }
        .sc .lbl { color: #6b7280; font-size: .8rem; }
        .sc .val { font-weight: 800; font-size: 1.25rem; margin-top: .25rem; }
        .sc.rev .val { color: #1e40af; }
        .sc.gross .val { color: #15803d; }
        .sc.net .val { color: #92400e; }
        .sc.net.loss .val { color: #b91c1c; }

        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

<button class="no-print" onclick="window.print()" style="position:fixed; top:1rem; left:1rem; padding:.5rem 1rem; cursor:pointer; background:#4f46e5; color:#fff; border:none; border-radius:6px;">
    🖨️ طباعة
</button>

<div class="header">
    <div class="period">
        <strong>الفترة:</strong> {{ $from->format('Y-m-d') }} ← {{ $to->format('Y-m-d') }}<br>
        <small>تم الإنشاء: {{ now()->format('Y-m-d H:i') }}</small>
    </div>
    <h1>{{ config('app.name') }}</h1>
    <div class="sub">قائمة الدخل (Profit & Loss Statement)</div>
</div>

<div class="summary">
    <div class="sc rev">
        <div class="lbl">إجمالي الإيرادات</div>
        <div class="val">{{ number_format($revenue['total'], 2) }} ج.م</div>
    </div>
    <div class="sc gross">
        <div class="lbl">مجمل الربح ({{ $gross_margin }}%)</div>
        <div class="val">{{ number_format($gross_profit, 2) }} ج.م</div>
    </div>
    <div class="sc net {{ $net_profit < 0 ? 'loss' : '' }}">
        <div class="lbl">{{ $net_profit >= 0 ? 'صافي الربح' : 'صافي الخسارة' }} ({{ $net_margin }}%)</div>
        <div class="val">{{ number_format($net_profit, 2) }} ج.م</div>
    </div>
</div>

<table>
    @php
        $renderSection = function ($title, $rows, $total, $totalLabel) {
            echo '<tr><td colspan="3" class="section">' . $title . '</td></tr>';
            if ($rows->isEmpty()) {
                echo '<tr class="acc-row"><td colspan="3" style="color:#6b7280;">لا توجد حركات</td></tr>';
            } else {
                foreach ($rows as $r) {
                    echo '<tr class="acc-row">';
                    echo '<td width="60" class="code">' . e($r->code) . '</td>';
                    echo '<td>' . e($r->name) . '</td>';
                    echo '<td width="120" class="amount">' . number_format($r->amount, 2) . '</td>';
                    echo '</tr>';
                }
            }
            echo '<tr class="subtotal"><td colspan="2" style="text-align:end;">' . $totalLabel . '</td>';
            echo '<td class="amount">' . number_format($total, 2) . '</td></tr>';
        };
    @endphp

    @php $renderSection('الإيرادات', $revenue['rows'], $revenue['total'], 'إجمالي الإيرادات'); @endphp
    @php $renderSection('تكلفة الخدمات', $cost_of_services['rows'], $cost_of_services['total'], 'إجمالي تكلفة الخدمات'); @endphp

    <tr><td colspan="2" class="key" style="text-align:end;">مجمل الربح</td>
        <td class="key amount">{{ number_format($gross_profit, 2) }}</td></tr>

    @php $renderSection('مصروفات تشغيلية', $operating_expense['rows'], $operating_expense['total'], 'إجمالي التشغيلية'); @endphp
    @php $renderSection('مصروفات أخرى', $other_expense['rows'], $other_expense['total'], 'إجمالي الأخرى'); @endphp

    <tr><td colspan="2" class="net {{ $net_profit < 0 ? 'loss' : '' }}" style="text-align:end;">
        {{ $net_profit >= 0 ? 'صافي الربح' : 'صافي الخسارة' }}
    </td>
    <td class="net {{ $net_profit < 0 ? 'loss' : '' }} amount">{{ number_format($net_profit, 2) }}</td></tr>
</table>

</body>
</html>
