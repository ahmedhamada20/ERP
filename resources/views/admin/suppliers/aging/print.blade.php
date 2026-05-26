<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>أعمار ديون الموردين — {{ $as_of->format('Y-m-d') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 landscape; margin: 1.2cm; }
        body { font-family: 'Cairo', sans-serif; color:#1f2937; margin:0; font-size: 10.5px; }
        .header { border-bottom:3px double #1f2937; padding-bottom:.75rem; margin-bottom:.75rem; }
        .header h1 { margin:0 0 .2rem; font-size:1.3rem; }
        .header .sub { color:#6b7280; font-size:.85rem; }
        .header .as-of { float:left; text-align:end; }

        table { width:100%; border-collapse:collapse; margin-top:.75rem; font-size: 10px; }
        th, td { border:1px solid #d1d5db; padding:.3rem .4rem; vertical-align:middle; }
        thead th { background:#f3f4f6; font-weight:700; text-align:center; }
        .amount { text-align:end; font-family:'JetBrains Mono', monospace; font-weight:700; }
        .total { background:#fee2e2; font-weight:800; }
        tfoot td { background:#1f2937; color:#fff; font-weight:800; padding:.45rem .4rem; }

        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

<button class="no-print" onclick="window.print()" style="position:fixed; top:.5rem; left:.5rem; padding:.4rem .8rem; cursor:pointer; background:#4f46e5; color:#fff; border:none; border-radius:5px;">
    🖨️ طباعة
</button>

<div class="header">
    <div class="as-of">
        <strong>كما في:</strong> {{ $as_of->format('Y-m-d') }}<br>
        <small>{{ now()->format('Y-m-d H:i') }}</small>
    </div>
    <h1>{{ config('app.name') }} · أعمار ديون الموردين</h1>
    <div class="sub">إجمالي مستحقات الموردين مقسومة حسب فترات الـ overdue</div>
</div>

<table>
    <thead>
        <tr>
            <th width="35%">المورد</th>
            <th width="8%">النوع</th>
            <th width="11%">إجمالي مستحق</th>
            <th width="9%">حالي</th>
            <th width="9%">1-30</th>
            <th width="9%">31-60</th>
            <th width="9%">61-90</th>
            <th width="9%">91-120</th>
            <th width="9%">+120</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
        <tr>
            <td><strong>{{ $row['supplier']->code }}</strong> · {{ $row['supplier']->name }}</td>
            <td style="text-align:center;">{{ $row['supplier']->type_label }}</td>
            <td class="amount total">{{ number_format($row['outstanding'], 2) }}</td>
            <td class="amount">{{ $row['current']    > 0 ? number_format($row['current'], 2)    : '' }}</td>
            <td class="amount">{{ $row['d_1_30']     > 0 ? number_format($row['d_1_30'], 2)     : '' }}</td>
            <td class="amount">{{ $row['d_31_60']    > 0 ? number_format($row['d_31_60'], 2)    : '' }}</td>
            <td class="amount">{{ $row['d_61_90']    > 0 ? number_format($row['d_61_90'], 2)    : '' }}</td>
            <td class="amount">{{ $row['d_91_120']   > 0 ? number_format($row['d_91_120'], 2)   : '' }}</td>
            <td class="amount">{{ $row['d_120_plus'] > 0 ? number_format($row['d_120_plus'], 2) : '' }}</td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center; padding:1.5rem; color:#6b7280;">لا يوجد موردون لهم أرصدة مستحقة</td></tr>
        @endforelse
    </tbody>
    @if($rows->isNotEmpty())
    <tfoot>
        <tr>
            <td colspan="2" style="text-align:end;">الإجمالي العام</td>
            <td class="amount">{{ number_format($grand_total, 2) }}</td>
            <td class="amount">{{ number_format($totals['current'], 2) }}</td>
            <td class="amount">{{ number_format($totals['d_1_30'], 2) }}</td>
            <td class="amount">{{ number_format($totals['d_31_60'], 2) }}</td>
            <td class="amount">{{ number_format($totals['d_61_90'], 2) }}</td>
            <td class="amount">{{ number_format($totals['d_91_120'], 2) }}</td>
            <td class="amount">{{ number_format($totals['d_120_plus'], 2) }}</td>
        </tr>
    </tfoot>
    @endif
</table>

</body>
</html>
