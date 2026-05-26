<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

/**
 * Unified reports landing page — مركز التقارير.
 *
 * Lists all reports available across the modules with permission gating
 * and quick stats so users can drill into the right report from one spot.
 */
class ReportsHubController extends Controller
{
    public function index()
    {
        return view('admin.reports.hub');
    }
}
