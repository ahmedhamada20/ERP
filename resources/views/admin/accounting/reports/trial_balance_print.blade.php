<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ميزان المراجعة — {{ $as_of->format('Y-m-d') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 1.5cm; }
        body { font-family: 'Cairo', sans-serif; color: #1f2937; margin: 0; font-size: 12px; }
        .header { border-bottom: 3px double #1f2937; padding-bottom: 1rem; margin-bottom: 1rem; }
        .header h1 { margin: 0 0 .2rem; font-size: 1.5rem; }
        .header .sub { color: #6b7280; font-size: .85rem; }
        .header .as-of { float: left; }

        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #d1d5db; padding: .35rem .55rem; vertical-align: middle; }
        thead th { background: #f3f4f6; font-weight: 700; }
        .section { background: #eef2ff; font-weight: 700; color: #3730a3; }
        .code { font-family: 'JetBrains Mono', monospace; font-weight: 600; }
        .amount { text-align: end; font-family: 'JetBrains Mono', monospace; font-weight: 600; }
        tfoot td { background: #1f2937; color: #fff; font-weight: 800; padding: .65rem .55rem; }

        .balance-note {
            text-align: center; margin-top: 1rem;
            padding: .55rem; border-radius: 6px; font-weight: 700;
        }
        .balance-note.ok    { background: #dcfce7; color: #15803d; border: 2px solid #86efac; }
        .balance-note.error { background: #fee2e2; color: #b91c1c; border: 2px solid #fca5a5; }

        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

<button class="no-print" onclick="window.print()" style="position:fixed; top:1rem; left:1rem; padding:.5rem 1rem; cursor:pointer; background:#4f46e5; color:#fff; border:none; border-radius:6px;">
    🖨️ طباعة
</button>

<div class="header">
    <div class="as-of">
        <strong>كما في:</strong> {{ $as_of->format('Y-m-d') }}<br>
        <small>تم الإنشاء: {{ now()->format('Y-m-d H:i') }}</small>
    </div>
    <h1>{{ config('app.name') }}</h1>
    <div class="sub">ميزان المراجعة</div>
</div>

<table>
    <thead>
        <tr>
            <th width="60">الكود</th>
            <th>اسم الحساب</th>
            <th width="120">مدين</th>
            <th width="120">دائن</th>
        </tr>
    </thead>
    <tbody>
        @foreach($grouped as $type => $items)
            @php
                $typeName = ['asset'=>'الأصول','liability'=>'الخصوم','equity'=>'حقوق الملكية','revenue'=>'الإيرادات','expense'=>'المصروفات'][$type] ?? $type;
                $secDr = $items->sum('debit_column');
                $secCr = $items->sum('credit_column');
            @endphp
            <tr class="section">
                <td colspan="2">{{ $typeName }}</td>
                <td class="amount">{{ $secDr > 0 ? number_format($secDr, 2) : '' }}</td>
                <td class="amount">{{ $secCr > 0 ? number_format($secCr, 2) : '' }}</td>
            </tr>
            @foreach($items as $row)
            <tr>
                <td class="code">{{ $row->code }}</td>
                <td>{{ $row->name }}</td>
                <td class="amount">{{ $row->debit_column  > 0 ? number_format($row->debit_column,  2) : '' }}</td>
                <td class="amount">{{ $row->credit_column > 0 ? number_format($row->credit_column, 2) : '' }}</td>
            </tr>
            @endforeach
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" style="text-align:center;">الإجمالي العام</td>
            <td class="amount">{{ number_format($totals['debit'], 2) }}</td>
            <td class="amount">{{ number_format($totals['credit'], 2) }}</td>
        </tr>
    </tfoot>
</table>

<div class="balance-note {{ $totals['balanced'] ? 'ok' : 'error' }}">
    @if($totals['balanced'])
        ✓ الميزان متوازن
    @else
        ⚠ فرق: {{ number_format(abs($totals['diff']), 2) }} ج.م
    @endif
</div>

</body>
</html>
