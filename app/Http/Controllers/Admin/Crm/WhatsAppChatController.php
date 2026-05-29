<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\WhatsappMessage;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * واجهة محادثة WhatsApp (شبيهة بالتطبيق): قائمة الأرقام على اليمين وخيط
 * المحادثة على اليسار، مع إرسال/استقبال نص وصورة وصوت وفيديو وملف.
 *
 * لا يوجد real-time push على استضافة مشتركة، لذلك الواجهة تعمل بـ polling
 * خفيف (after_id يجلب الجديد فقط). ULID قابل للترتيب زمنياً فنستخدمه مؤشراً.
 */
class WhatsAppChatController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsapp) {}

    public function index()
    {
        return view('admin.crm.whatsapp.chat', [
            'isConfigured' => $this->whatsapp->isConfigured(),
        ]);
    }

    /** قائمة المحادثات: آخر رسالة + وقتها + عدد غير المقروء لكل رقم. */
    public function conversations(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));

        $agg = WhatsappMessage::query()
            ->selectRaw('contact_phone,
                MAX(id) AS last_id,
                MAX(created_at) AS last_at,
                SUM(CASE WHEN direction = "inbound" AND agent_read_at IS NULL THEN 1 ELSE 0 END) AS unread')
            ->whereNotNull('contact_phone')
            ->when($q !== '', fn ($x) => $x->where('contact_phone', 'like', "%{$q}%"))
            ->groupBy('contact_phone')
            ->orderByDesc('last_at')
            ->limit(200)
            ->get();

        $lastMsgs = WhatsappMessage::whereIn('id', $agg->pluck('last_id')->all())
            ->get()->keyBy('id');

        $names = $this->resolveNames($agg->pluck('contact_phone')->all());

        $list = $agg->map(function ($row) use ($lastMsgs, $names) {
            $last = $lastMsgs->get($row->last_id);
            return [
                'phone'      => $row->contact_phone,
                'name'       => $names[$row->contact_phone] ?? null,
                'unread'     => (int) $row->unread,
                'preview'    => $this->previewText($last),
                'direction'  => $last?->direction,
                'time'       => optional($last?->created_at)->diffForHumans(null, true),
                'sort'       => optional($last?->created_at)->timestamp ?? 0,
            ];
        });

        return response()->json(['conversations' => $list->values()]);
    }

    /** خيط محادثة رقم واحد. after_id (اختياري) يجلب الرسائل الأحدث فقط للـ polling. */
    public function thread(Request $request, string $phone): JsonResponse
    {
        $after = $request->get('after_id');

        $messages = WhatsappMessage::conversation($phone)
            ->when($after, fn ($qq) => $qq->where('id', '>', $after))
            ->limit(500)
            ->get();

        // علّم الوارد كمقروء (إزالة عدّاد غير المقروء).
        WhatsappMessage::where('contact_phone', $phone)
            ->where('direction', 'inbound')
            ->whereNull('agent_read_at')
            ->update(['agent_read_at' => now()]);

        return response()->json([
            'messages'    => $messages->map(fn ($m) => $this->present($m))->values(),
            'window_open' => WhatsappMessage::windowOpenFor($phone),
            'contact'     => [
                'phone' => $phone,
                'name'  => $this->resolveNames([$phone])[$phone] ?? null,
            ],
        ]);
    }

    /** إرسال رسالة من نافذة المحادثة: نص أو وسائط (صورة/صوت/فيديو/ملف). */
    public function send(Request $request): JsonResponse
    {
        if (! $this->whatsapp->isConfigured()) {
            return response()->json(['message' => 'إعدادات WhatsApp غير مكتملة.'], 422);
        }

        $data = $request->validate([
            'to_phone'   => ['required', 'string', 'max:30'],
            'body'       => ['nullable', 'string', 'max:4000'],
            'attachment' => ['nullable', 'file', 'max:16384'], // 16MB حد واتساب التقريبي
        ]);

        if (empty($data['body']) && ! $request->hasFile('attachment')) {
            return response()->json(['message' => 'اكتب رسالة أو أرفق ملفاً.'], 422);
        }

        try {
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $mime = $file->getMimeType() ?: 'application/octet-stream';
                $type = match (true) {
                    str_starts_with($mime, 'image/') => 'image',
                    str_starts_with($mime, 'audio/') => 'audio',
                    str_starts_with($mime, 'video/') => 'video',
                    default                          => 'document',
                };

                $stored   = $file->store('whatsapp', 'public');
                $absolute = Storage::disk('public')->path($stored);

                // Store the RELATIVE path (not an APP_URL-based absolute URL) so
                // media renders correctly regardless of host/APP_URL config.
                $message = $this->whatsapp->sendMedia(
                    $data['to_phone'], $type, $absolute, $mime, $stored,
                    $data['body'] ?? null, $file->getClientOriginalName(),
                );
            } else {
                $message = $this->whatsapp->sendText($data['to_phone'], $data['body']);
            }

            return response()->json([
                'success'     => $message->status === 'sent',
                'message'     => $this->present($message),
                'error'       => $message->status === 'failed' ? ($message->error_message ?? 'فشل الإرسال') : null,
                'window_open' => WhatsappMessage::windowOpenFor($message->contact_phone),
            ], $message->status === 'failed' ? 422 : 200);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /** تحويل رسالة لصيغة JSON للعرض في الفقاعة. */
    private function present(WhatsappMessage $m): array
    {
        return [
            'id'             => $m->id,
            'direction'      => $m->direction,
            'type'           => $m->message_type,
            'body'           => $m->body,
            'media_url'      => $this->mediaWebUrl($m->media_url),
            'media_mime'     => $m->media_mime,
            'media_filename' => $m->media_filename,
            'template_name'  => $m->template_name,
            'status'         => $m->status,
            'status_icon'    => $m->status_icon,
            'status_label'   => $m->status_label,
            'error'          => $m->error_message,
            'time'           => optional($m->created_at)->format('H:i'),
            'date'           => optional($m->created_at)->format('Y-m-d'),
        ];
    }

    /**
     * يبني رابطاً جذرياً (/storage/...) للوسائط مستقلاً عن APP_URL/الدومين.
     * يصلّح أيضاً الصفوف القديمة المخزّنة كرابط مطلق خاطئ (مثل http://localhost/storage/..).
     */
    private function mediaWebUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        // رابط مطلق قديم: استخرج جزء /storage/ منه فقط.
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $pos = strpos($path, '/storage/');
            return $pos !== false ? substr($path, $pos) : $path;
        }
        return '/storage/' . ltrim($path, '/');
    }

    /** نص معاينة قصير لقائمة المحادثات. */
    private function previewText(?WhatsappMessage $m): string
    {
        if (! $m) return '';
        $icon = match ($m->message_type) {
            'image'    => '📷 صورة',
            'audio'    => '🎤 رسالة صوتية',
            'video'    => '🎬 فيديو',
            'document' => '📎 مستند',
            'template' => '📋 ' . ($m->template_name ?? 'قالب'),
            default    => $m->body ?? '',
        };
        $text = $m->message_type === 'text' ? ($m->body ?? '') : $icon;
        $text = mb_substr($text, 0, 45);
        return ($m->direction === 'outbound' ? 'أنت: ' : '') . $text;
    }

    /**
     * أفضل-جهد لربط أرقام المحادثات بأسماء العملاء/العملاء المحتملين.
     * يطابق بآخر 9 أرقام (يتجاوز اختلاف صيغة 010… مقابل 2010…).
     *
     * ملاحظة أداء: عند الحجم الكبير يُفضّل لاحقاً جدول conversations مخصّص
     * بدل البحث LIKE على أعمدة الهاتف.
     *
     * @param array<int,string> $phones
     * @return array<string,string> phone => name
     */
    private function resolveNames(array $phones): array
    {
        $phones = array_values(array_unique(array_filter($phones)));
        if (empty($phones)) return [];

        $tails = [];
        foreach ($phones as $p) {
            $tails[$this->tail($p)] = $p;
        }
        $tailKeys = array_keys($tails);

        $map = [];

        $scan = function ($rows, array $cols) use (&$map, $tails) {
            foreach ($rows as $r) {
                foreach ($cols as $c) {
                    $t = $this->tail((string) ($r->{$c} ?? ''));
                    if ($t !== '' && isset($tails[$t]) && ! isset($map[$tails[$t]])) {
                        $map[$tails[$t]] = $r->full_name;
                    }
                }
            }
        };

        $custWhere = function ($w) use ($tailKeys) {
            foreach ($tailKeys as $t) {
                $w->orWhere('phone', 'like', "%{$t}")
                  ->orWhere('mobile', 'like', "%{$t}")
                  ->orWhere('whatsapp', 'like', "%{$t}");
            }
        };
        $scan(
            Customer::query()->select('full_name', 'phone', 'mobile', 'whatsapp')->where($custWhere)->limit(500)->get(),
            ['phone', 'mobile', 'whatsapp']
        );

        $leadWhere = function ($w) use ($tailKeys) {
            foreach ($tailKeys as $t) {
                $w->orWhere('phone', 'like', "%{$t}")
                  ->orWhere('whatsapp', 'like', "%{$t}");
            }
        };
        $scan(
            Lead::query()->select('full_name', 'phone', 'whatsapp')->where($leadWhere)->limit(500)->get(),
            ['phone', 'whatsapp']
        );

        return $map;
    }

    /** آخر 9 أرقام من رقم هاتف (لمطابقة الصيغ المختلفة). */
    private function tail(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        return strlen($digits) >= 9 ? substr($digits, -9) : $digits;
    }
}
