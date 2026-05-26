<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Http\Request;

class LeadActivityController extends Controller
{
    public function store(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'type'             => ['required', 'in:call,whatsapp,email,sms,meeting,visit,note'],
            'subject'          => ['nullable', 'string', 'max:200'],
            'body'             => ['nullable', 'string', 'max:2000'],
            'outcome'          => ['nullable', 'in:positive,neutral,negative,no_answer,follow_up'],
            'next_action_date' => ['nullable', 'date'],
        ]);

        $lead->activities()->create($data);

        return back()->with('success', 'تم تسجيل النشاط بنجاح');
    }

    public function markDone(Lead $lead, LeadActivity $activity)
    {
        abort_unless($activity->lead_id === $lead->id, 404);

        $activity->update(['next_action_done' => true]);

        return back()->with('success', 'تم إكمال المتابعة');
    }

    public function destroy(Lead $lead, LeadActivity $activity)
    {
        abort_unless($activity->lead_id === $lead->id, 404);

        // Don't allow deleting status_change activities (they're audit trail)
        if ($activity->type === 'status_change') {
            return response()->json(['message' => 'لا يمكن حذف سجلات تغيير الحالة'], 422);
        }

        $activity->delete();

        return response()->json(['message' => 'تم حذف النشاط']);
    }
}
