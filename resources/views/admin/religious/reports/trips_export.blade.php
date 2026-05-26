<table>
    <thead>
        <tr>
            <th>#</th>
            <th>رقم الحجز</th>
            <th>النوع</th>
            <th>العميل</th>
            <th>الهاتف</th>
            <th>البرنامج</th>
            <th>تاريخ الحجز</th>
            <th>تاريخ السفر</th>
            <th>المدة</th>
            <th>البالغون</th>
            <th>الأطفال</th>
            <th>إجمالي الأفراد</th>
            <th>نوع التأشيرة</th>
            <th>نوع التسكين</th>
            <th>سعر البيع</th>
            <th>إجمالي التكلفة</th>
            <th>صافي الربح</th>
            <th>هامش الربح %</th>
            <th>الموظف المسؤول</th>
            <th>المدير المسؤول</th>
            <th>الحالة</th>
            <th>المرحلة</th>
            <th>باركود صفا</th>
        </tr>
    </thead>
    <tbody>
        @foreach($bookings as $i => $b)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $b->booking_number }}</td>
            <td>{{ $b->type_label }}</td>
            <td>{{ $b->customer?->full_name }}</td>
            <td>{{ $b->customer?->phone }}</td>
            <td>{{ $b->program?->name }}</td>
            <td>{{ $b->booking_date?->format('Y-m-d') }}</td>
            <td>{{ $b->trip_date?->format('Y-m-d') }}</td>
            <td>{{ $b->duration_days }}</td>
            <td>{{ $b->adults_count }}</td>
            <td>{{ $b->children_count }}</td>
            <td>{{ $b->adults_count + $b->children_count }}</td>
            <td>{{ $b->visa_type_label }}</td>
            <td>{{ $b->accommodation_label }}</td>
            <td>{{ number_format($b->selling_price, 2) }}</td>
            <td>{{ number_format($b->total_cost, 2) }}</td>
            <td>{{ number_format($b->net_profit, 2) }}</td>
            <td>{{ $b->profit_margin }}%</td>
            <td>{{ $b->employee?->name }}</td>
            <td>{{ $b->manager?->name }}</td>
            <td>{{ $b->status_label }}</td>
            <td>{{ $b->workflow_label }}</td>
            <td>{{ $b->safa_barcode }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr style="font-weight:bold; background:#fef3c7;">
            <td colspan="11">الإجمالي</td>
            <td>{{ $totals['pilgrims_total'] }}</td>
            <td colspan="2"></td>
            <td>{{ number_format($totals['sales_total'], 2) }}</td>
            <td>{{ number_format($totals['cost_total'], 2) }}</td>
            <td>{{ number_format($totals['profit_total'], 2) }}</td>
            <td colspan="6"></td>
        </tr>
    </tfoot>
</table>
