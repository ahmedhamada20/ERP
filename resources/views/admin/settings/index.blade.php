@extends('layouts.master')

@section('title', 'الإعدادات العامة')
@section('page_title', 'الإعدادات العامة')
@section('breadcrumb')
    <li class="breadcrumb-item active">الإعدادات</li>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="card mb-3">
        <div class="card-header">
            <h5><i class="bi bi-building text-primary me-1"></i> بيانات الشركة</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">اسم الشركة <span class="required-mark">*</span></label>
                    <input type="text" name="company_name" value="{{ old('company_name', $settings['company_name'] ?? config('app.name')) }}"
                           class="form-control @error('company_name') is-invalid @enderror" required>
                    @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">بريد الشركة</label>
                    <input type="email" name="company_email" value="{{ old('company_email', $settings['company_email'] ?? '') }}"
                           class="form-control @error('company_email') is-invalid @enderror" dir="ltr">
                    @error('company_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">هاتف الشركة</label>
                    <input type="text" name="company_phone" value="{{ old('company_phone', $settings['company_phone'] ?? '') }}" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label">الرقم الضريبي</label>
                    <input type="text" name="company_tax" value="{{ old('company_tax', $settings['company_tax'] ?? '') }}" class="form-control">
                </div>

                <div class="col-12">
                    <label class="form-label">عنوان الشركة</label>
                    <input type="text" name="company_address" value="{{ old('company_address', $settings['company_address'] ?? '') }}" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5><i class="bi bi-sliders text-success me-1"></i> الإعدادات التشغيلية</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">العملة (كود ISO)</label>
                    <input type="text" name="currency" value="{{ old('currency', $settings['currency'] ?? 'EGP') }}" class="form-control" dir="ltr" placeholder="EGP, SAR, USD...">
                </div>
                <div class="col-md-4">
                    <label class="form-label">رمز العملة</label>
                    <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings['currency_symbol'] ?? 'ج.م') }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">المنطقة الزمنية</label>
                    <select name="timezone" class="form-select select2" dir="ltr">
                        @php
                            $tz = old('timezone', $settings['timezone'] ?? 'Africa/Cairo');
                            $zones = ['Africa/Cairo','Asia/Riyadh','Asia/Dubai','Asia/Kuwait','Asia/Qatar','Asia/Amman','Asia/Beirut','UTC'];
                        @endphp
                        @foreach($zones as $z)
                            <option value="{{ $z }}" {{ $tz===$z ? 'selected':'' }}>{{ $z }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5><i class="bi bi-image text-info me-1"></i> الشعار والأيقونة</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">شعار الشركة</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    @if(!empty($settings['logo']))
                        <div class="mt-2"><img src="{{ asset('storage/'.$settings['logo']) }}" class="rounded border p-2 bg-light" width="160"></div>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">أيقونة الموقع (favicon)</label>
                    <input type="file" name="favicon" class="form-control" accept="image/*">
                    @if(!empty($settings['favicon']))
                        <div class="mt-2"><img src="{{ asset('storage/'.$settings['favicon']) }}" class="rounded border" width="64"></div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @can('settings.update')
    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary btn-lg px-4">
            <i class="bi bi-save ms-1"></i> حفظ الإعدادات
        </button>
    </div>
    @endcan
</form>
@endsection
