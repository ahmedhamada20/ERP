@csrf
@isset($entry) @method('PUT') @endisset

@php
    $editing = isset($entry) && $entry;
    $oldLines = old('lines', $editing
        ? $entry->lines->map(fn($l) => [
            'account_id'  => $l->account_id,
            'debit'       => (float) $l->debit  > 0 ? $l->debit  : '',
            'credit'      => (float) $l->credit > 0 ? $l->credit : '',
            'description' => $l->description,
        ])->all()
        : [
            ['account_id' => '', 'debit' => '', 'credit' => '', 'description' => ''],
            ['account_id' => '', 'debit' => '', 'credit' => '', 'description' => ''],
        ]);
@endphp

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <label class="form-label">التاريخ *</label>
        <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
               value="{{ old('date', $editing ? $entry->date->format('Y-m-d') : now()->toDateString()) }}" required>
        @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">المرجع</label>
        <input type="text" name="reference" class="form-control"
               value="{{ old('reference', $editing ? $entry->reference : '') }}"
               placeholder="رقم حجز / فاتورة / مستند">
    </div>
    <div class="col-md-6">
        <label class="form-label">البيان *</label>
        <input type="text" name="description" class="form-control @error('description') is-invalid @enderror"
               value="{{ old('description', $editing ? $entry->description : '') }}" required>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

@error('lines')
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> {{ $message }}</div>
@enderror

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-list-ul"></i> سطور القيد</h6>
        <button type="button" class="btn btn-sm btn-outline-success" id="addLineBtn">
            <i class="bi bi-plus-lg"></i> سطر جديد
        </button>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0 lines-table" style="background:#fff;">
            <thead style="background:#f9fafb;">
                <tr>
                    <th width="40">#</th>
                    <th>الحساب *</th>
                    <th width="160">مدين</th>
                    <th width="160">دائن</th>
                    <th>البيان</th>
                    <th width="50"></th>
                </tr>
            </thead>
            <tbody id="linesBody">
                @foreach($oldLines as $i => $ln)
                <tr class="line-row">
                    <td class="line-num text-muted">{{ $i + 1 }}</td>
                    <td>
                        <select name="lines[{{ $i }}][account_id]" class="form-select form-select-sm account-select" required>
                            <option value="">— اختر الحساب —</option>
                            @foreach($accounts as $a)
                                <option value="{{ $a->id }}" {{ ($ln['account_id'] ?? '') === $a->id ? 'selected' : '' }}>
                                    {{ $a->code }} — {{ $a->name }}
                                </option>
                            @endforeach
                        </select>
                        @error("lines.{$i}.account_id")<div class="text-danger small">{{ $message }}</div>@enderror
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" name="lines[{{ $i }}][debit]"
                               value="{{ $ln['debit'] ?? '' }}"
                               class="form-control form-control-sm text-end debit-input" placeholder="0.00">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" name="lines[{{ $i }}][credit]"
                               value="{{ $ln['credit'] ?? '' }}"
                               class="form-control form-control-sm text-end credit-input" placeholder="0.00">
                        @error("lines.{$i}.debit")<div class="text-danger small">{{ $message }}</div>@enderror
                    </td>
                    <td>
                        <input type="text" name="lines[{{ $i }}][description]"
                               value="{{ $ln['description'] ?? '' }}"
                               class="form-control form-control-sm" placeholder="بيان السطر">
                    </td>
                    <td>
                        <button type="button" class="btn btn-icon btn-sm btn-light-danger remove-line">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot style="background:#f1f5f9; font-weight:700;">
                <tr>
                    <td colspan="2" class="text-end">الإجمالي</td>
                    <td class="text-end"><span id="sumDebit">0.00</span> ج.م</td>
                    <td class="text-end"><span id="sumCredit">0.00</span> ج.م</td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td colspan="2" class="text-end">الفرق</td>
                    <td colspan="2" class="text-end" id="balanceCell" style="font-size:1.05rem;">0.00 ج.م</td>
                    <td colspan="2" id="balanceMsg"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@can('accounting.journal.post')
<div class="form-check form-switch mt-3">
    <input type="hidden" name="post_immediately" value="0">
    <input type="checkbox" name="post_immediately" id="postImmediately" value="1" class="form-check-input">
    <label class="form-check-label" for="postImmediately">
        <strong>ترحيل القيد فور الحفظ</strong> — لا يمكن التعديل بعدها
    </label>
</div>
@endcan

{{-- Template for new line rows --}}
<template id="lineTemplate">
    <tr class="line-row">
        <td class="line-num text-muted">__N__</td>
        <td>
            <select name="lines[__I__][account_id]" class="form-select form-select-sm account-select" required>
                <option value="">— اختر الحساب —</option>
                @foreach($accounts as $a)
                    <option value="{{ $a->id }}">{{ $a->code }} — {{ $a->name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.01" min="0" name="lines[__I__][debit]"  class="form-control form-control-sm text-end debit-input"  placeholder="0.00"></td>
        <td><input type="number" step="0.01" min="0" name="lines[__I__][credit]" class="form-control form-control-sm text-end credit-input" placeholder="0.00"></td>
        <td><input type="text" name="lines[__I__][description]" class="form-control form-control-sm" placeholder="بيان السطر"></td>
        <td><button type="button" class="btn btn-icon btn-sm btn-light-danger remove-line"><i class="bi bi-x-lg"></i></button></td>
    </tr>
</template>

@push('scripts')
<script>
(function () {
    const body = document.getElementById('linesBody');
    const tmpl = document.getElementById('lineTemplate').innerHTML;

    function renumber() {
        [...body.querySelectorAll('tr.line-row')].forEach((row, idx) => {
            row.querySelector('.line-num').textContent = idx + 1;
            row.querySelectorAll('input, select').forEach(el => {
                if (el.name) el.name = el.name.replace(/lines\[\d+\]/, `lines[${idx}]`);
            });
        });
    }

    function recalc() {
        let debit = 0, credit = 0;
        body.querySelectorAll('.line-row').forEach(row => {
            debit  += parseFloat(row.querySelector('.debit-input').value)  || 0;
            credit += parseFloat(row.querySelector('.credit-input').value) || 0;
        });
        document.getElementById('sumDebit').textContent  = debit.toFixed(2);
        document.getElementById('sumCredit').textContent = credit.toFixed(2);
        const diff = debit - credit;
        const cell = document.getElementById('balanceCell');
        const msg  = document.getElementById('balanceMsg');
        cell.textContent = (diff >= 0 ? '+' : '') + diff.toFixed(2) + ' ج.م';
        if (Math.abs(diff) < 0.01 && debit > 0) {
            cell.style.color = '#15803d';
            msg.innerHTML = '<span class="badge bg-success"><i class="bi bi-check2-circle"></i> متوازن</span>';
        } else if (debit === 0 && credit === 0) {
            cell.style.color = '#6b7280';
            msg.innerHTML = '<span class="text-muted small">أدخل المبالغ</span>';
        } else {
            cell.style.color = '#b91c1c';
            msg.innerHTML = '<span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i> غير متوازن</span>';
        }
    }

    // Mutual-exclusion for debit/credit on same line
    body.addEventListener('input', e => {
        if (e.target.classList.contains('debit-input') && parseFloat(e.target.value) > 0) {
            const credit = e.target.closest('tr').querySelector('.credit-input');
            credit.value = '';
        }
        if (e.target.classList.contains('credit-input') && parseFloat(e.target.value) > 0) {
            const debit = e.target.closest('tr').querySelector('.debit-input');
            debit.value = '';
        }
        recalc();
    });

    // Add new line
    document.getElementById('addLineBtn').addEventListener('click', () => {
        const idx = body.querySelectorAll('.line-row').length;
        const html = tmpl.replaceAll('__I__', idx).replaceAll('__N__', idx + 1);
        body.insertAdjacentHTML('beforeend', html);
        recalc();
    });

    // Remove line (but keep at least 2)
    body.addEventListener('click', e => {
        if (e.target.closest('.remove-line')) {
            if (body.querySelectorAll('.line-row').length <= 2) {
                alert('يجب الإبقاء على سطرين على الأقل');
                return;
            }
            e.target.closest('tr').remove();
            renumber();
            recalc();
        }
    });

    recalc();
})();
</script>
@endpush
