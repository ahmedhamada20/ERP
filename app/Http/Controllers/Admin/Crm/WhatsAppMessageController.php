<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\WhatsappMessage;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class WhatsAppMessageController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsapp) {}

    public function index()
    {
        $stats = DB::table('whatsapp_messages')
            ->selectRaw("
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'sent'      THEN 1 ELSE 0 END) AS sent,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered,
                SUM(CASE WHEN status = 'read'      THEN 1 ELSE 0 END) AS read_count,
                SUM(CASE WHEN status = 'failed'    THEN 1 ELSE 0 END) AS failed,
                SUM(CASE WHEN direction = 'inbound' THEN 1 ELSE 0 END) AS inbound
            ")
            ->first();

        return view('admin.crm.whatsapp.messages.index', [
            'stats'        => (array) $stats,
            'isConfigured' => $this->whatsapp->isConfigured(),
        ]);
    }

    public function data(Request $request)
    {
        $cols = [
            'id', 'to_phone', 'from_phone', 'direction', 'message_type',
            'template_name', 'body', 'status',
            'error_message', 'whatsapp_message_id',
            'related_type', 'related_id',
            'sent_at', 'delivered_at', 'read_at', 'created_at',
        ];

        $query = WhatsappMessage::query()->select($cols);

        if ($request->filled('status_filter'))    $query->where('status', $request->status_filter);
        if ($request->filled('direction_filter')) $query->where('direction', $request->direction_filter);
        if ($request->filled('type_filter'))      $query->where('message_type', $request->type_filter);

        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $query->where(function ($q) use ($term) {
                $q->where('to_phone', 'like', "%{$term}%")
                  ->orWhere('body', 'like', "%{$term}%")
                  ->orWhere('template_name', 'like', "%{$term}%")
                  ->orWhere('whatsapp_message_id', 'like', "%{$term}%");
            });
        }

        return DataTables::eloquent($query)
            ->editColumn('to_phone', fn (WhatsappMessage $m) =>
                '<span dir="ltr">' . e($m->to_phone) . '</span>'
            )
            ->editColumn('direction', fn (WhatsappMessage $m) =>
                $m->direction === 'outbound'
                    ? '<span class="badge bg-info-soft"><i class="bi bi-send"></i> صادر</span>'
                    : '<span class="badge bg-success-soft"><i class="bi bi-inbox"></i> وارد</span>'
            )
            ->editColumn('message_type', fn (WhatsappMessage $m) =>
                '<span class="badge bg-light text-dark">' . e($m->type_label) . '</span>'
                . ($m->template_name ? '<div class="x-small text-muted">' . e($m->template_name) . '</div>' : '')
            )
            ->editColumn('body', function (WhatsappMessage $m) {
                $preview = mb_substr($m->body ?? '', 0, 60);
                if (mb_strlen($m->body ?? '') > 60) $preview .= '…';
                return '<div class="small">' . e($preview) . '</div>';
            })
            ->editColumn('status', function (WhatsappMessage $m) {
                $icon = match ($m->status) {
                    'queued'    => 'hourglass',
                    'sent'      => 'check',
                    'delivered' => 'check-all',
                    'read'      => 'check-all text-primary',
                    'failed'    => 'x-octagon',
                    default     => 'circle',
                };
                return '<span class="badge bg-' . $m->status_badge . '-soft">'
                    . '<i class="bi bi-' . $icon . '"></i> ' . $m->status_label
                    . '</span>'
                    . ($m->error_message ? '<div class="x-small text-danger">' . e(mb_substr($m->error_message, 0, 50)) . '</div>' : '');
            })
            ->editColumn('created_at', fn (WhatsappMessage $m) =>
                '<div class="small">' . $m->created_at?->diffForHumans() . '</div>'
            )
            ->addColumn('actions', function (WhatsappMessage $m) {
                return '<a href="' . route('admin.crm.whatsapp.messages.show', $m) . '"
                           class="btn btn-icon btn-sm btn-light-primary" title="عرض">
                            <i class="bi bi-eye"></i>
                        </a>';
            })
            ->rawColumns(['to_phone', 'direction', 'message_type', 'body', 'status', 'created_at', 'actions'])
            ->make(true);
    }

    public function show(WhatsappMessage $message)
    {
        return view('admin.crm.whatsapp.messages.show', [
            'message' => $message,
            'related' => $message->related(),
        ]);
    }

    /**
     * Send a WhatsApp message from anywhere in the app (called by the
     * "Send WhatsApp" button on lead/booking pages).
     */
    public function send(Request $request): JsonResponse
    {
        if (! $this->whatsapp->isConfigured()) {
            return response()->json([
                'message' => 'إعدادات WhatsApp غير مكتملة. اذهب لإعدادات WhatsApp لضبطها.',
            ], 422);
        }

        $data = $request->validate([
            'to_phone'       => ['required', 'string', 'max:30'],
            'message_type'   => ['required', 'in:text,template'],
            'body'           => ['required_if:message_type,text', 'nullable', 'string', 'max:4000'],
            'template_name'  => ['required_if:message_type,template', 'nullable', 'string', 'max:200'],
            'template_params'=> ['nullable', 'array'],
            'related_type'   => ['nullable', 'string', 'max:60'],
            'related_id'     => ['nullable', 'string', 'max:30'],
        ]);

        try {
            $message = $data['message_type'] === 'template'
                ? $this->whatsapp->sendTemplate(
                    $data['to_phone'],
                    $data['template_name'],
                    $data['template_params'] ?? [],
                    $data['related_type'] ?? null,
                    $data['related_id'] ?? null,
                )
                : $this->whatsapp->sendText(
                    $data['to_phone'],
                    $data['body'],
                    $data['related_type'] ?? null,
                    $data['related_id'] ?? null,
                );

            $statusText = $message->status === 'sent'
                ? 'تم إرسال الرسالة بنجاح'
                : 'فشل الإرسال: ' . ($message->error_message ?? 'سبب غير محدد');

            return response()->json([
                'success' => $message->status === 'sent',
                'message' => $statusText,
                'data'    => [
                    'id'      => $message->id,
                    'status'  => $message->status,
                    'wamid'   => $message->whatsapp_message_id,
                ],
            ], $message->status === 'sent' ? 200 : 422);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Webhook endpoint — Meta calls this for delivery updates + inbound msgs.
     *
     * GET is the one-time verification handshake (returns hub.challenge).
     * POST is the actual event payload.
     *
     * IMPORTANT: This route lives under /api/ so it's exempt from CSRF/session
     * middleware. It's PUBLIC by design — Meta authenticates with the
     * verify_token (GET) and we trust the payload by source IP / signature
     * (production: verify X-Hub-Signature-256).
     */
    public function webhook(Request $request): Response|JsonResponse
    {
        if ($request->isMethod('GET')) {
            $challenge = $this->whatsapp->verifyWebhook(
                $request->query('hub_mode', ''),
                $request->query('hub_verify_token', ''),
                $request->query('hub_challenge', ''),
            );

            return $challenge
                ? response($challenge, 200)
                : response('Invalid verify token', 403);
        }

        // POST — event payload
        $payload = $request->all();
        Log::channel('single')->info('WhatsApp webhook payload', $payload);

        try {
            $entries = $payload['entry'] ?? [];
            foreach ($entries as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    $value = $change['value'] ?? [];

                    // Status updates (delivered/read/failed)
                    foreach ($value['statuses'] ?? [] as $s) {
                        $this->whatsapp->updateStatusFromWebhook(
                            $s['id'] ?? '',
                            $s['status'] ?? 'sent',
                            $s['errors'][0] ?? null,
                        );
                    }

                    // Inbound messages (someone replied to us)
                    foreach ($value['messages'] ?? [] as $m) {
                        $this->saveInboundMessage($m, $value['metadata'] ?? []);
                    }
                }
            }
        } catch (Throwable $e) {
            Log::channel('single')->error('WhatsApp webhook processing failed', [
                'error'   => $e->getMessage(),
                'payload' => $payload,
            ]);
        }

        // Always 200 — Meta retries on non-2xx and we don't want infinite loops
        return response()->json(['ok' => true]);
    }

    private function saveInboundMessage(array $m, array $metadata): void
    {
        // Skip if we already have this wamid
        if (WhatsappMessage::where('whatsapp_message_id', $m['id'] ?? '')->exists()) {
            return;
        }

        WhatsappMessage::create([
            'to_phone'            => $metadata['display_phone_number'] ?? '',
            'from_phone'          => $m['from'] ?? '',
            'direction'           => 'inbound',
            'message_type'        => $m['type'] ?? 'text',
            'body'                => $m['text']['body'] ?? json_encode($m, JSON_UNESCAPED_UNICODE),
            'status'              => 'delivered',
            'whatsapp_message_id' => $m['id'] ?? null,
            'delivered_at'        => now(),
        ]);
    }
}
