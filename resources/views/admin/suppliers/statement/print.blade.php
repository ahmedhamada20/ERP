<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>كشف حساب {{ $supplier->name }} — {{ $from->format('Y-m-d') }} إلى {{ $to->format('Y-m-d') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 landscape; margin: 1.2cm; }
        body { font-family: 'Cairo', sans-serif; color: #1f2937; margin: 0; font-size: 11px; }
        .header { border-bottom: 3px double #1f2937; padding-bottom: .75rem; margin-bottom: .75rem; }
        .header h1 { margin: 0 0 .2rem; font-size: 1.3rem; }
        .header .sub { color: #6b7280; font-size: .85rem; }
        .header .period { float: left; text-align: end; }

        .summary { display:grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap:.5rem; margin:.5rem 0; }
        .sc { padding:.55rem .75rem; border:1px solid #d1d5db; border-radius:5px; }
        .sc .lbl { color:#6b7280; font-size:.7rem; }
        .sc .val { font-weight:800; font-size:1rem; }

        table { width: 100%; border-collapse: collapse; margin-top: .75rem; font-size: 10.5px; }
        th, td { border: 1px solid #d1d5db; padding: .25rem .4rem; vertical-align: middle; }
        thead th { background: #f3f4f6; font-weight: 700; }
        .row-num { font-family: 'JetBrains Mono', monospace; font-weight: 600; }
        .amount { text-align: end; font-family: 'JetBrains Mono', monospace; font-weight: 700; }
        .opening-row td { background: #f1f5f9; font-style: italic; }
        .closing-row td { background: #1f2937; color: #fff; font-weight: 800; padding: .5rem .4rem; }

        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

<button class="no-print" onclick="window.print()" style="position:fixed; top:.5rem; left:.5rem; padding:.4rem .8rem; cursor:pointer; background:#4f46e5; color:#fff; border:none; border-radius:5px;">
    🖨️ طباعة
</button>

<div class="header">
    <div class="period">
        <strong>{{ $from->format('Y-m-d') }} ← {{ $to->format('Y-m-d') }}</strong><br>
        <small>{{ now()->format('Y-m-d H:i') }}</small>
    </div>
    <h1>{{ config('app.name') }} · كشف حساب مورد</h1>
    <div class="sub">
        <strong>{{ $supplier->code }}</strong> — {{ $supplier->name }} ({{ $supplier->type_label }})
        @if($supplier->phone) — <span dir="ltr">{{ $supplier->phone }}</span> @endif
    </div>
</div>

<div class="summary">
    <div class="sc"><div class="lbl">الرصيد الافتتاحي</div><div class="val">{{ number_format($opening, 2) }}</div></div>
    <div class="sc"><div class="lbl">فواتير الفترة</div><div class="val" style="color:#b91c1c;">{{ number_format($total_invoices, 2) }}</div></div>
    <div class="sc"><div class="lbl">سدادات الفترة</div><div class="val" style="color:#15803d;">{{ number_format($total_payments, 2) }}</div></div>
    <div class="sc"><div class="lbl">الرصيد الختامي</div><div class="val" style="color:#4338ca;">{{ number_format($closing, 2) }}</div></div>
</div>

<table>
    <thead>
        <tr>
            <th width="80">التاريخ</th>
            <th width="60">النوع</th>
            <th width="110">الرقم</th>
            <th>البيان</th>
            <th width="90">مدين (سداد)</th>
            <th width="90">دائن (فاتورة)</th>
            <th width="100">الرصيد</th>
        </tr>
    </thead>
    <tbody>
        <tr class="opening-row">
            <td colspan="6">— الرصيد الافتتاحي قبل {{ $from->format('Y-m-d') }} —</td>
            <td class="amount">{{ number_format($opening, 2) }}</td>
        </tr>
        @forelse($lines as $line)
            <tr>
                <td>{{ $line->date->format('Y-m-d') }}</td>
                <td>{{ $line->type === 'invoice' ? 'فاتورة' : 'سداد' }}</td>
                <td class="row-num">{{ $line->number }}</td>
                <td>{{ $line->description }}</td>
                <td class="amount">{{ $line->debit  > 0 ? number_format($line->debit,  2) : '' }}</td>
                <td class="amount">{{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}</td>
                <td class="amount">{{ number_format($line->running_balance, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center; color:#6b7280; padding:1rem;">لا توجد حركات</td></tr>
        @endforelse
        <tr class="closing-row">
            <td colspan="4" style="text-align:end;">إجمالي الفترة + الرصيد الختامي</td>
            <td class="amount">{{ number_format($total_payments, 2) }}</td>
            <td class="amount">{{ number_format($total_invoices, 2) }}</td>
            <td class="amount">{{ number_format($closing, 2) }}</td>
        </tr>
    </tbody>
</table>

</body>
</html>
