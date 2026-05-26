<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Traits\HandlesImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    use HandlesImageUpload;

    public function index()
    {
        $settings = Setting::all()->toArray();

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name'    => ['required', 'string', 'max:120'],
            'company_email'   => ['nullable', 'email', 'max:120'],
            'company_phone'   => ['nullable', 'string', 'max:30'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'company_tax'     => ['nullable', 'string', 'max:60'],
            'currency'        => ['nullable', 'string', 'max:10'],
            'currency_symbol' => ['nullable', 'string', 'max:10'],
            'timezone'        => ['nullable', 'string', 'max:80'],
            'logo'            => ['nullable', 'image', 'mimes:jpeg,jpg,png,svg,webp', 'max:1024'],
            'favicon'         => ['nullable', 'image', 'mimes:png,ico,svg', 'max:512'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            foreach ($validated as $key => $value) {
                if (in_array($key, ['logo', 'favicon'])) continue;
                Setting::set($key, $value, 'general');
            }

            if ($request->hasFile('logo')) {
                $existing = Setting::get('logo');
                $path = $this->uploadImage($request->file('logo'), 'settings', $existing);
                Setting::set('logo', $path, 'general', 'image');
            }
            if ($request->hasFile('favicon')) {
                $existing = Setting::get('favicon');
                $path = $this->uploadImage($request->file('favicon'), 'settings', $existing);
                Setting::set('favicon', $path, 'general', 'image');
            }
        });

        return back()->with('success', __('messages.updated_successfully'));
    }
}
