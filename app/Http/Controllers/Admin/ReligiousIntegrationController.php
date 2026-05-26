<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReligiousBooking;
use App\Services\Religious\SafaService;
use App\Services\Religious\UmrahPortalService;

class ReligiousIntegrationController extends Controller
{
    public function syncSafa(ReligiousBooking $booking, SafaService $safa)
    {
        try {
            $result = $safa->pullForBooking($booking);
            $msg = sprintf(
                'تمت مزامنة صفا بنجاح. الباركود الجماعي: %s — معتمرون تم تحديثهم: %d',
                $result['group_barcode'],
                $result['pilgrims_updated']
            );
            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'فشلت مزامنة صفا: ' . $e->getMessage());
        }
    }

    public function syncPortal(ReligiousBooking $booking, UmrahPortalService $portal)
    {
        try {
            $result = $portal->syncBooking($booking);
            return back()->with('success', 'تمت المزامنة مع بوابة العمرة. المرجع: ' . $result['reference']);
        } catch (\Throwable $e) {
            return back()->with('error', 'فشلت المزامنة: ' . $e->getMessage());
        }
    }
}
