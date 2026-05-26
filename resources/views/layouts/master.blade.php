<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ ($isRtl ?? true) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f172a">

    <title>@yield('title', config('app.name')) | {{ config('app.name') }}</title>

    {{-- Bootstrap 5 RTL --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    {{-- DataTables Bootstrap 5 --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

    {{-- Select2 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css">

    {{-- SweetAlert2 + Toastr --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    {{-- Cairo Font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-navy:    #0f172a;
            --brand-navy-2:  #1e293b;
            --brand-gold:    #d4a437;
            --brand-bg:      #f8f9fc;
            --brand-border:  #eef0f5;
            --text-primary:  #1f2937;
            --text-muted:    #6b7280;
        }

        * { -webkit-tap-highlight-color: transparent; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'Cairo', sans-serif;
            background: var(--brand-bg);
            color: var(--text-primary);
            font-size: .92rem;
        }

        /* ── Sidebar (white theme) ─────────────────────────────── */
        .sidebar {
            background: #fff;
            color: var(--text-primary);
            width: 260px;
            min-height: 100vh;
            flex-shrink: 0;
            border-left: 1px solid var(--brand-border);
            display: flex;
            flex-direction: column;
        }
        .sidebar .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .55rem;
            padding: 1.5rem 1rem 1.25rem;
            border-bottom: 1px solid var(--brand-border);
        }
        .sidebar .brand-logo {
            width: 42px; height: 42px; border-radius: 12px;
            background: linear-gradient(135deg, var(--brand-navy), var(--brand-navy-2));
            color: var(--brand-gold);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
            box-shadow: 0 4px 10px rgba(15,23,42,.18);
        }
        .sidebar .brand-text {
            font-weight: 800; font-size: 1.05rem; color: var(--brand-navy);
            letter-spacing: .3px;
        }
        .sidebar .brand-text .badge-erp {
            background: var(--brand-gold); color: #fff;
            font-size: .65rem; padding: .15rem .45rem; border-radius: 5px;
            font-weight: 700; margin-right: .25rem; vertical-align: middle;
        }

        .sidebar nav.menu { flex: 1; padding: .75rem .75rem 0; overflow-y: auto; }
        .sidebar nav.menu a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .7rem .95rem;
            color: #475569;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 4px;
            font-weight: 500;
            font-size: .92rem;
            transition: all .15s;
        }
        .sidebar nav.menu a .menu-icon { font-size: 1.1rem; color: #94a3b8; }
        .sidebar nav.menu a:hover {
            background: #f1f5f9;
            color: var(--brand-navy);
        }
        .sidebar nav.menu a:hover .menu-icon { color: var(--brand-navy); }
        .sidebar nav.menu a.active {
            background: var(--brand-navy);
            color: #fff;
            box-shadow: 0 4px 12px rgba(15,23,42,.22);
        }
        .sidebar nav.menu a.active .menu-icon { color: #fff; }

        /* ── Sidebar nested menu group ───────────────────────────── */
        .sidebar .menu-group { margin-bottom: 4px; }
        .sidebar .menu-group .menu-group-toggle .chev {
            font-size: .78rem; color: #94a3b8;
            transition: transform .2s ease;
        }
        .sidebar .menu-group.open .menu-group-toggle .chev { transform: rotate(180deg); }
        .sidebar .menu-group .submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height .25s ease;
            padding-right: .6rem;
        }
        .sidebar .menu-group.open .submenu { max-height: 320px; }
        .sidebar .menu-group .submenu a {
            font-size: .85rem;
            padding: .55rem .85rem;
            margin-right: .25rem;
            border-right: 2px solid transparent;
        }
        .sidebar .menu-group .submenu a .menu-icon { font-size: 1rem; }
        .sidebar .menu-group .submenu a.active {
            background: #eef2ff;
            color: var(--brand-navy);
            border-right-color: var(--brand-gold);
            box-shadow: none;
        }
        .sidebar .menu-group .submenu a.active .menu-icon { color: var(--brand-navy); }

        .sidebar .support-card {
            margin: 1rem .75rem 1.25rem;
            background: linear-gradient(135deg, #eef2ff, #f0f9ff);
            border: 1px solid #dbeafe;
            border-radius: 14px;
            padding: 1rem;
            display: flex; align-items: center; gap: .65rem;
        }
        .sidebar .support-card .support-icon {
            width: 38px; height: 38px; border-radius: 50%;
            background: #fff;
            display: flex; align-items: center; justify-content: center;
            color: #2563eb; font-size: 1.15rem; flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(37,99,235,.18);
        }
        .sidebar .support-card .support-text strong { display: block; font-size: .85rem; color: #1e3a8a; font-weight: 700; }
        .sidebar .support-card .support-text small { color: #475569; font-size: .72rem; }

        /* mobile sidebar */
        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed;
                top: 0; bottom: 0; right: 0;
                z-index: 1045;
                transform: translateX(100%);
                transition: transform .3s ease;
                overflow-y: auto;
                box-shadow: -2px 0 10px rgba(0,0,0,.18);
            }
            .sidebar.open { transform: translateX(0); }
            .sidebar-backdrop {
                position: fixed; inset: 0;
                background: rgba(0,0,0,.55);
                z-index: 1044; opacity: 0; pointer-events: none;
                transition: opacity .3s;
            }
            .sidebar-backdrop.show { opacity: 1; pointer-events: auto; }
        }

        /* ── Topbar (minimal flat look) ────────────────────────── */
        .topbar {
            background: var(--brand-bg);
            padding: 1.1rem 1.5rem .85rem;
            position: sticky; top: 0; z-index: 1030;
        }

        /* User chip — fully flat, no border/bg */
        .topbar .user-chip {
            display: inline-flex; align-items: center; gap: .65rem;
            cursor: pointer;
            user-select: none;
        }
        .topbar .user-chip > .chev { color: var(--text-muted); font-size: .8rem; }
        .topbar .user-chip .u-info { line-height: 1.15; text-align: right; }
        .topbar .user-chip .u-name { font-weight: 700; font-size: .92rem; color: var(--text-primary); white-space: nowrap; }
        .topbar .user-chip .u-role { font-size: .72rem; color: var(--text-muted); white-space: nowrap; }
        .topbar .user-chip .avatar,
        .topbar .user-chip .avatar-fallback {
            width: 40px; height: 40px; border-radius: 50%;
            object-fit: cover;
            background: linear-gradient(135deg, var(--brand-navy), var(--brand-navy-2));
            color: var(--brand-gold);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .95rem;
            flex-shrink: 0;
            border: 2px solid #fff;
            box-shadow: 0 0 0 1.5px var(--brand-gold);
        }

        /* Icon buttons — flat with red badge */
        .topbar .icon-btn {
            position: relative;
            width: 38px; height: 38px;
            background: transparent;
            border: none;
            border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
            color: #475569;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all .15s;
            padding: 0;
        }
        .topbar .icon-btn:hover { background: #eef0f5; color: var(--brand-navy); }
        .topbar .icon-btn .badge-count {
            position: absolute;
            top: -2px; left: -2px;
            background: #ef4444; color: #fff;
            font-size: .62rem; font-weight: 800;
            min-width: 17px; height: 17px;
            border-radius: 9px; padding: 0 4px;
            display: inline-flex; align-items: center; justify-content: center;
            border: 2px solid var(--brand-bg);
            line-height: 1;
        }

        /* Search box — clean, white, subtle */
        .topbar .search-box {
            flex: 1; max-width: 380px;
            position: relative;
        }
        .topbar .search-box .form-control {
            padding-right: 2.5rem;
            padding-left: 1rem;
            border-radius: 10px;
            background: #fff;
            border: 1px solid var(--brand-border);
            height: 40px;
            font-size: .88rem;
        }
        .topbar .search-box .form-control:focus {
            background: #fff;
            border-color: #cbd5e1;
            box-shadow: 0 0 0 .15rem rgba(15,23,42,.05);
        }
        .topbar .search-box i.search-icon {
            position: absolute;
            right: .9rem; top: 50%; transform: translateY(-50%);
            color: var(--text-muted);
            font-size: .9rem;
        }

        /* Title block on the right (RTL start) */
        .topbar .page-title-block { text-align: right; }
        .topbar .page-title-block h1 {
            font-size: clamp(1.1rem, 1.5vw, 1.45rem);
            font-weight: 800;
            color: var(--brand-navy);
            margin: 0 0 .1rem;
            line-height: 1.2;
        }
        .topbar .page-title-block p {
            color: var(--text-muted); margin: 0;
            font-size: .8rem; line-height: 1.3;
        }

        @media (max-width: 1199.98px) {
            .topbar .search-box { max-width: 280px; }
        }
        @media (max-width: 991.98px) {
            .topbar { padding: .85rem 1rem .65rem; }
            .topbar .user-chip .u-info { display: none; }
            .topbar .search-box { order: 99; max-width: 100%; width: 100%; margin-top: .5rem; }
            .topbar .page-title-block h1 { font-size: 1rem; }
            .topbar .page-title-block p { font-size: .7rem; }
        }
        @media (max-width: 575.98px) {
            .topbar { padding: .75rem .85rem .5rem; }
            .topbar .icon-btn { width: 34px; height: 34px; font-size: 1.05rem; }
            .topbar .user-chip .avatar,
            .topbar .user-chip .avatar-fallback { width: 36px; height: 36px; font-size: .85rem; }
            .topbar .page-title-block p { display: none; }
            .topbar .page-title-block h1 { font-size: .95rem; }
        }

        /* ── Main content ──────────────────────────────────────── */
        .main-content { flex: 1; min-width: 0; }
        main.page { padding: 0 1.5rem 1.5rem; }
        @media (max-width: 768px) { main.page { padding: 0 1rem 1rem; } }

        /* ── Page header (title + subtitle on right side of topbar row) */
        .page-title-block { text-align: left; }
        .page-title-block h1 {
            font-size: 1.55rem; font-weight: 800;
            color: var(--brand-navy); margin: 0 0 .15rem;
        }
        .page-title-block p { color: var(--text-muted); margin: 0; font-size: .85rem; }

        /* ── Cards ─────────────────────────────────────────────── */
        .card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 1px 4px rgba(15,23,42,.04);
            background: #fff;
        }
        .card-header {
            background: #fff;
            border-bottom: 1px solid var(--brand-border);
            border-radius: 14px 14px 0 0 !important;
            padding: 1rem 1.25rem;
        }
        .card-header h5, .card-header h6 { margin: 0; font-weight: 700; color: var(--brand-navy); }

        /* ── Stat card (KPI) ───────────────────────────────────── */
        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 1.1rem;
            box-shadow: 0 1px 4px rgba(15,23,42,.04);
            transition: transform .15s, box-shadow .15s;
            height: 100%;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(15,23,42,.07); }
        .stat-card .stat-head {
            display: flex; justify-content: space-between; align-items: flex-start;
            gap: .5rem; margin-bottom: .65rem;
        }
        .stat-card .stat-label { color: var(--text-muted); font-size: .82rem; font-weight: 500; margin-bottom: .15rem; }
        .stat-card .stat-value { font-size: 1.55rem; font-weight: 800; color: var(--brand-navy); line-height: 1; }
        .stat-card .stat-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.35rem; flex-shrink: 0;
        }
        .stat-card .stat-foot { margin-top: .65rem; font-size: .78rem; color: var(--text-muted); display: flex; align-items: center; gap: .25rem; }
        .stat-card .trend-up   { color: #16a34a; font-weight: 700; }
        .stat-card .trend-down { color: #dc2626; font-weight: 700; }

        .stat-icon-gold    { background: #fef3c7; color: #b45309; }
        .stat-icon-orange  { background: #ffedd5; color: #c2410c; }
        .stat-icon-green   { background: #dcfce7; color: #15803d; }
        .stat-icon-blue    { background: #dbeafe; color: #1d4ed8; }
        .stat-icon-teal    { background: #ccfbf1; color: #0f766e; }
        .stat-icon-indigo  { background: #e0e7ff; color: #4338ca; }

        /* ── Hero banner ───────────────────────────────────────── */
        .hero-banner {
            /* Wide image (2172×724 ≈ 3:1) — keep proportional but capped */
            height: clamp(160px, 19vw, 260px);
            border-radius: 18px;
            position: relative;
            overflow: hidden;
            margin-bottom: 1.25rem;
            box-shadow: 0 4px 18px rgba(15,23,42,.10);
            /* Fallback gradient (shown until hero-banner.jpg is added) */
            background-color: #0f172a;
            background-image:
                url('{{ asset('assets/admin/img/hero-banner.png') }}'),
                linear-gradient(135deg, #0c2461 0%, #1e3a8a 35%, #1e40af 65%, #c9a227 100%);
            background-size: cover, cover;
            background-position: center center, center center;
            background-repeat: no-repeat, no-repeat;
        }
        /* soft bottom→top vignette so any overlay text stays readable
           without hiding the scene (Kaaba left, skyline center, plane top, hotel right) */
        .hero-banner::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(
                to top,
                rgba(15,23,42,.55) 0%,
                rgba(15,23,42,.15) 45%,
                rgba(15,23,42,0)    100%
            );
        }
        @media (max-width: 768px) {
            .hero-banner { height: 150px; }
        }

        /* ── Pretty table ──────────────────────────────────────── */
        .pretty-table thead th {
            background: #f9fafb;
            color: #4b5563;
            font-weight: 700;
            font-size: .82rem;
            border-bottom: 1px solid var(--brand-border);
            padding: .85rem .65rem;
            text-align: right;   /* RTL: العنوان يبدأ من اليمين */
        }
        .pretty-table tbody td {
            padding: .85rem .65rem;
            vertical-align: middle;
            border-bottom: 1px solid #f3f4f6;
            font-size: .88rem;
            text-align: right;
        }
        .pretty-table tbody tr:hover { background: #fafbff; }

        /* ── DataTables RTL overrides ──────────────────────────── */
        /* DataTables يفرض text-align:left على .dt-* classes; نعكسها */
        table.dataTable thead th,
        table.dataTable thead td,
        table.dataTable tbody th,
        table.dataTable tbody td { text-align: right !important; }

        /* أيقونة الترتيب (السهم) — تظهر يسار النص في RTL */
        table.dataTable thead .sorting,
        table.dataTable thead .sorting_asc,
        table.dataTable thead .sorting_desc { background-position: left center; padding-left: 22px; padding-right: .65rem; }

        /* ── Forms ─────────────────────────────────────────────── */
        .form-label { font-weight: 600; color: #374151; margin-bottom: .35rem; font-size: .88rem; }
        .form-control, .form-select {
            border-radius: 9px;
            border: 1px solid var(--brand-border);
            padding: .55rem .85rem;
            font-size: .92rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--brand-navy);
            box-shadow: 0 0 0 .18rem rgba(15,23,42,.10);
        }
        .required-mark { color: #ef4444; margin-right: 2px; }

        /* ── Buttons ───────────────────────────────────────────── */
        .btn { font-weight: 600; border-radius: 9px; }
        .btn-primary {
            background: var(--brand-navy);
            border-color: var(--brand-navy);
        }
        .btn-primary:hover, .btn-primary:focus {
            background: var(--brand-navy-2);
            border-color: var(--brand-navy-2);
        }
        .btn-gold { background: var(--brand-gold); border-color: var(--brand-gold); color: #fff; }
        .btn-gold:hover { background: #b3892e; border-color: #b3892e; color: #fff; }
        .btn-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; padding: 0; }
        .table-actions .btn { margin-left: 3px; }

        /* ── Section divider ───────────────────────────────────── */
        .section-divider {
            display: flex;
            align-items: center;
            gap: .5rem;
            color: var(--brand-navy);
            font-weight: 700;
            margin: 1.5rem 0 1rem;
            padding-bottom: .5rem;
            border-bottom: 1px solid var(--brand-border);
        }

        /* Toastr RTL */
        .toast-top-left, .toast-bottom-left { left: 12px !important; right: auto !important; }

        /* ── DataTables themed polish ──────────────────────────── */
        table.dataTable { font-size: .88rem; }

        /* Top toolbar (length + filter) and bottom toolbar (info + paginate) */
        .dataTables_wrapper .row {
            margin-bottom: .65rem;
        }
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            font-size: .82rem; color: #475569; font-weight: 600;
        }
        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_filter label {
            display: flex; align-items: center; gap: .55rem;
            margin: 0; color: #64748b; font-weight: 700;
        }
        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            border-radius: 9px;
            border: 1.5px solid #e2e8f0;
            padding: .45rem .8rem;
            font-size: .85rem; font-weight: 600;
            background: #f8fafc;
            color: var(--brand-navy);
            transition: all .15s;
            margin: 0 .35rem !important;
        }
        .dataTables_wrapper .dataTables_filter input:focus,
        .dataTables_wrapper .dataTables_length select:focus {
            outline: none;
            border-color: var(--brand-gold);
            background: #fff;
            box-shadow: 0 0 0 .2rem rgba(212,164,55,.15);
        }
        .dataTables_wrapper .dataTables_filter input { min-width: 200px; }
        .dataTables_wrapper .dataTables_length select {
            min-width: 70px; cursor: pointer;
            padding-left: 1.75rem; padding-right: .8rem;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 16 16'><path fill='%2364748b' d='M8 11L3 6h10z'/></svg>");
            background-repeat: no-repeat;
            background-position: left .55rem center;
            -webkit-appearance: none; -moz-appearance: none; appearance: none;
        }

        /* Info text "Showing 1 to 25 of 1,000 entries" */
        .dataTables_wrapper .dataTables_info {
            font-size: .8rem; color: #64748b; font-weight: 600;
            padding-top: .75rem !important;
        }
        .dataTables_wrapper .dataTables_info b { color: var(--brand-navy); font-weight: 800; }

        /* Pagination — match corex-pagination look */
        .dataTables_wrapper .dataTables_paginate {
            padding-top: .35rem !important;
        }
        .dataTables_wrapper .dataTables_paginate .pagination {
            gap: .3rem; margin: 0;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button,
        .dataTables_wrapper .dataTables_paginate .page-link {
            display: inline-flex !important; align-items: center; justify-content: center;
            min-width: 36px; height: 36px; padding: 0 .7rem !important;
            border-radius: 9px !important;
            font-weight: 700 !important; font-size: .85rem !important;
            color: #475569 !important; background: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            text-decoration: none !important;
            transition: all .2s cubic-bezier(.4,0,.2,1) !important;
            cursor: pointer;
            margin: 0 !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover,
        .dataTables_wrapper .dataTables_paginate .page-link:hover {
            background: linear-gradient(135deg, #fffbeb, #fef3c7) !important;
            border-color: var(--brand-gold) !important;
            color: #92400e !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(212,164,55,.18);
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
            background: linear-gradient(135deg, var(--brand-navy), #1e293b) !important;
            color: #fff !important;
            border-color: var(--brand-navy) !important;
            box-shadow: 0 4px 12px rgba(15,23,42,.25);
            cursor: default;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover,
        .dataTables_wrapper .dataTables_paginate .page-item.disabled .page-link {
            background: #f1f5f9 !important; color: #cbd5e1 !important;
            cursor: not-allowed !important; border-color: #f1f5f9 !important;
            transform: none !important; box-shadow: none !important;
        }

        /* "Processing..." overlay */
        .dataTables_wrapper .dataTables_processing {
            background: rgba(255,255,255,.92) !important;
            border: 1px solid var(--brand-border) !important;
            border-radius: 12px !important;
            box-shadow: 0 8px 24px rgba(15,23,42,.10) !important;
            color: var(--brand-navy) !important;
            font-weight: 700;
        }

        /* No-data message */
        table.dataTable tbody .dataTables_empty {
            padding: 3rem 1rem !important;
            color: #94a3b8; font-size: .95rem;
        }

        @media (max-width: 768px) {
            .form-control, .form-select { min-height: 42px; font-size: 16px; }
            .btn { min-height: 38px; }
            .modal-dialog { margin: .5rem; }
        }

        /* ── Global responsive utilities ─────────────────────────── */
        @media (max-width: 991.98px) {
            .card-header { padding: .85rem 1rem; flex-wrap: wrap; gap: .5rem; }
            .card-header h5, .card-header h6 { font-size: .98rem; }
            .card-body { padding: 1rem; }
            .section-divider { margin: 1rem 0 .75rem; font-size: .92rem; }
        }
        @media (max-width: 575.98px) {
            main.page { padding: 0 .65rem .85rem; }
            .card { border-radius: 12px; }
            .card-header { padding: .75rem .85rem; }
            .card-body { padding: .85rem; }
            .stat-card { padding: .9rem; }
            .stat-card .stat-value { font-size: 1.3rem; }
            .stat-card .stat-icon { width: 38px; height: 38px; font-size: 1.1rem; }
            .btn { font-size: .85rem; padding: .45rem .75rem; }
            .btn-sm { font-size: .78rem; padding: .35rem .6rem; }
            .table-actions .btn { margin-left: 2px; margin-bottom: 2px; }
        }

        /* ── Horizontal scroll hint for tables on small screens ── */
        @media (max-width: 767.98px) {
            .table-responsive { -webkit-overflow-scrolling: touch; }
            .table-responsive::-webkit-scrollbar { height: 6px; }
            .table-responsive::-webkit-scrollbar-thumb {
                background: #cbd5e1; border-radius: 3px;
            }
        }

        /* ── Sidebar mobile-fix: ensure full height on iOS ─────── */
        @media (max-width: 991.98px) {
            .sidebar { width: min(280px, 86vw); }
        }

        /* ── DataTables responsive polish ─────────────────────── */
        @media (max-width: 575.98px) {
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_paginate {
                text-align: center !important;
                margin-top: .5rem;
            }
            .dataTables_wrapper .dataTables_filter input { width: 100%; max-width: none; margin: 0 !important; }
            .dataTables_paginate .paginate_button { padding: .25rem .55rem !important; font-size: .8rem; }
        }

        /* ── Touch-friendly tap targets ────────────────────────── */
        @media (hover: none) and (pointer: coarse) {
            .btn-icon { min-width: 36px; min-height: 36px; }
            .chip { padding: .5rem 1rem; }
            .form-tabs button, .show-tabs button { padding: .75rem 1rem; }
        }
    </style>

    @stack('styles')
</head>
<body>

<div class="d-flex flex-column flex-lg-row min-vh-100">

    {{-- Sidebar --}}
    @include('layouts.partials.sidebar')
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    {{-- Main Content --}}
    <div class="main-content">
        {{-- Topbar --}}
        @include('layouts.partials.header')

        {{-- Page Content --}}
        <main class="page">

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('info'))
                <div class="alert alert-info alert-dismissible fade show">
                    <i class="bi bi-info-circle me-1"></i> {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @auth
                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                    <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center" role="alert">
                        <i class="bi bi-envelope-exclamation me-2 fs-5"></i>
                        <div class="flex-grow-1">
                            <strong>بريدك الإلكتروني غير موّثق.</strong>
                            راجع رسالة التأكيد المُرسلة إلى <code dir="ltr">{{ auth()->user()->email }}</code> لتفعيل حسابك بالكامل.
                        </div>
                        <form action="{{ route('verification.send') }}" method="POST" class="d-inline ms-2">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning">
                                <i class="bi bi-arrow-clockwise"></i> إعادة إرسال
                            </button>
                        </form>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
            @endauth

            @yield('content')
        </main>
    </div>
</div>

{{-- Scripts --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    toastr.options = {
        "positionClass": "toast-top-left",
        "rtl": true, "closeButton": true, "progressBar": true, "timeOut": "4000"
    };

    // Mobile sidebar toggle
    (function () {
        const sidebar  = document.querySelector('.sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const openS  = () => { sidebar?.classList.add('open');  backdrop?.classList.add('show'); document.body.style.overflow = 'hidden'; };
        const closeS = () => { sidebar?.classList.remove('open'); backdrop?.classList.remove('show'); document.body.style.overflow = ''; };
        document.addEventListener('click', e => {
            if (e.target.closest('[data-toggle-sidebar]')) { e.preventDefault(); openS(); }
            if (e.target === backdrop) closeS();
            if (e.target.closest('.sidebar a') && window.innerWidth < 992) closeS();
        });
        window.addEventListener('resize', () => { if (window.innerWidth >= 992) closeS(); });
    })();

    // DataTables Arabic
    window.dtArabic = {
        "sEmptyTable":"ليست هناك بيانات متاحة في الجدول","sLoadingRecords":"جارٍ التحميل...",
        "sProcessing":"جارٍ التحميل...","sLengthMenu":"أظهر _MENU_ مدخلات","sZeroRecords":"لم يعثر على أية سجلات",
        "sInfo":"إظهار _START_ إلى _END_ من أصل _TOTAL_ مدخل","sInfoEmpty":"يعرض 0 إلى 0 من أصل 0 سجل",
        "sInfoFiltered":"(منتقاة من مجموع _MAX_ مُدخل)","sSearch":"ابحث:",
        "oPaginate":{"sFirst":"الأول","sPrevious":"السابق","sNext":"التالي","sLast":"الأخير"}
    };
    if (typeof $.fn.dataTable !== 'undefined') {
        $.extend(true, $.fn.dataTable.defaults, { autoWidth: false, language: window.dtArabic });
    }

    // Chart.js defaults
    if (typeof Chart !== 'undefined') {
        Chart.defaults.font.family = "'Cairo', sans-serif";
        Chart.defaults.color = '#475569';
    }

    // CoreX helpers
    window.CoreX = {
        initDataTable(selector, options) {
            options = options || {};
            return $(selector).DataTable($.extend(true, {
                processing: true, serverSide: true, responsive: true,
                order: [[0,'desc']], pageLength: 25,
                lengthMenu: [[10,25,50,100,-1],[10,25,50,100,'الكل']]
            }, options));
        },
        confirmDelete(callback, options) {
            options = options || {};
            Swal.fire({
                title: options.title || 'هل أنت متأكد؟',
                text:  options.text  || 'لا يمكن التراجع عن هذا الإجراء',
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
                confirmButtonText: 'نعم، احذف', cancelButtonText: 'إلغاء', reverseButtons: true
            }).then(r => { if (r.isConfirmed) callback(); });
        },
        ajaxDelete(url, table) {
            CoreX.confirmDelete(() => {
                $.ajax({ url, type: 'DELETE',
                    success: res => { toastr.success((res&&res.message)||'تم الحذف بنجاح'); if (table) table.ajax.reload(null,false); },
                    error: xhr => toastr.error((xhr.responseJSON&&xhr.responseJSON.message)||'فشل الحذف')
                });
            });
        }
    };

    $(function () {
        $('.select2').each(function () {
            $(this).select2({
                dir: 'rtl', theme: 'bootstrap-5',
                placeholder: $(this).data('placeholder') || 'اختر',
                allowClear: true, width: '100%'
            });
        });
    });
</script>

@stack('scripts')

</body>
</html>
