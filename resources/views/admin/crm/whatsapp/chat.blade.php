@extends('layouts.master')

@section('title', 'محادثات WhatsApp')
@section('page_title', 'محادثات WhatsApp')
@section('page_subtitle', 'محادثة مباشرة مع عملائك عبر Meta Cloud API')

@push('styles')
<style>
    :root { --wa-bg:#efeae2; --wa-out:#d9fdd3; --wa-in:#ffffff; --wa-green:#075e54; --wa-teal:#128c7e; }

    .wa-wrap { display:flex; height:calc(100vh - 170px); min-height:520px; border-radius:14px; overflow:hidden;
               box-shadow:0 2px 12px rgba(15,23,42,.08); border:1px solid var(--brand-border); background:#fff; }

    /* ── Conversation list (right in RTL) ── */
    .wa-list { width:340px; flex-shrink:0; border-inline-start:1px solid #e9edef; display:flex; flex-direction:column; background:#fff; }
    .wa-list-head { padding:.75rem; background:#f0f2f5; border-bottom:1px solid #e9edef; }
    .wa-list-head input { border-radius:20px; background:#fff; border:1px solid #e9edef; }
    .wa-convos { overflow-y:auto; flex:1; }
    .wa-convo { display:flex; gap:.7rem; padding:.7rem .9rem; cursor:pointer; border-bottom:1px solid #f2f2f2; align-items:center; }
    .wa-convo:hover { background:#f5f6f6; }
    .wa-convo.active { background:#e9f3ff; }
    .wa-avatar { width:46px; height:46px; border-radius:50%; background:var(--wa-teal); color:#fff; display:flex;
                 align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
    .wa-convo-body { flex:1; min-width:0; }
    .wa-convo-name { font-weight:700; font-size:.92rem; color:#111b21; display:flex; justify-content:space-between; gap:.5rem; }
    .wa-convo-time { font-size:.68rem; color:#667781; font-weight:400; flex-shrink:0; }
    .wa-convo-prev { font-size:.8rem; color:#667781; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:flex; justify-content:space-between; gap:.5rem; }
    .wa-badge { background:#25d366; color:#fff; border-radius:12px; font-size:.68rem; padding:1px 7px; font-weight:700; flex-shrink:0; }

    /* ── Conversation thread (left in RTL) ── */
    .wa-chat { flex:1; display:flex; flex-direction:column; min-width:0;
               background:var(--wa-bg) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40'%3E%3Crect width='40' height='40' fill='%23efeae2'/%3E%3Ccircle cx='20' cy='20' r='1' fill='%23d9d2c8'/%3E%3C/svg%3E"); }
    .wa-chat-head { padding:.65rem 1rem; background:#f0f2f5; border-bottom:1px solid #e9edef; display:flex; align-items:center; gap:.7rem; }
    .wa-chat-head .name { font-weight:700; color:#111b21; }
    .wa-chat-head .phone { font-size:.75rem; color:#667781; direction:ltr; }
    .wa-messages { flex:1; overflow-y:auto; padding:1rem 1.2rem; display:flex; flex-direction:column; gap:.35rem; }

    .wa-bubble { max-width:72%; padding:.45rem .65rem .35rem; border-radius:9px; font-size:.9rem; position:relative;
                 box-shadow:0 1px .5px rgba(11,20,26,.13); word-wrap:break-word; }
    .wa-bubble.out { align-self:flex-start; background:var(--wa-out); }
    .wa-bubble.in  { align-self:flex-end;   background:var(--wa-in); }
    .wa-bubble .txt { white-space:pre-wrap; }
    .wa-meta { font-size:.62rem; color:#667781; text-align:end; margin-top:2px; display:flex; gap:3px; justify-content:flex-end; align-items:center; }
    .wa-bubble.read .wa-meta .tick { color:#53bdeb; }
    .wa-bubble img.media { max-width:240px; border-radius:7px; display:block; cursor:pointer; }
    .wa-bubble video.media { max-width:260px; border-radius:7px; display:block; }
    .wa-bubble audio.media { max-width:240px; display:block; }
    .wa-file { display:flex; align-items:center; gap:.5rem; background:rgba(0,0,0,.04); padding:.5rem .6rem; border-radius:7px; color:inherit; text-decoration:none; }
    .wa-tpl-tag { font-size:.6rem; background:#fff3cd; color:#856404; border-radius:4px; padding:0 4px; margin-bottom:3px; display:inline-block; }
    .wa-day { align-self:center; background:#ffffffd0; color:#54656f; font-size:.7rem; padding:3px 10px; border-radius:8px; margin:.4rem 0; }

    /* ── Composer ── */
    .wa-composer { padding:.6rem .8rem; background:#f0f2f5; border-top:1px solid #e9edef; }
    .wa-window-warn { background:#fff3cd; color:#856404; font-size:.78rem; padding:.45rem .7rem; border-radius:8px; margin-bottom:.5rem; display:none; }
    .wa-input-row { display:flex; align-items:flex-end; gap:.5rem; }
    .wa-input-row textarea { flex:1; resize:none; border-radius:20px; border:1px solid #e9edef; padding:.55rem .9rem; max-height:120px; }
    .wa-icon-btn { width:42px; height:42px; border-radius:50%; border:none; background:transparent; color:#54656f; font-size:1.25rem; flex-shrink:0; }
    .wa-icon-btn:hover { background:#e2e6ea; }
    .wa-send-btn { background:var(--wa-teal); color:#fff; }
    .wa-send-btn:hover { background:var(--wa-green); color:#fff; }
    .wa-rec { color:#dc3545 !important; }
    .wa-emoji-panel { display:none; background:#fff; border:1px solid #e9edef; border-radius:10px; padding:.5rem; max-height:180px;
                      overflow-y:auto; margin-bottom:.5rem; font-size:1.4rem; line-height:1.6; }
    .wa-emoji-panel span { cursor:pointer; padding:2px; border-radius:4px; }
    .wa-emoji-panel span:hover { background:#f0f2f5; }
    .wa-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#54656f; gap:.6rem; text-align:center; }
    .wa-rec-bar { display:none; align-items:center; gap:.6rem; color:#dc3545; font-weight:600; }
    .wa-att-preview { display:none; background:#fff; border:1px solid #e9edef; border-radius:8px; padding:.4rem .6rem; margin-bottom:.5rem;
                      font-size:.82rem; align-items:center; gap:.5rem; }
</style>
@endpush

@section('content')

@if(!$isConfigured)
<div class="alert alert-warning d-flex align-items-center gap-2">
    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
    <div class="flex-fill"><strong>WhatsApp غير مُكوَّن</strong> — اضبط الاعتمادات أولاً من الإعدادات.</div>
    @can('whatsapp.manage_settings')
    <a href="{{ route('admin.crm.whatsapp.settings.edit') }}" class="btn btn-sm btn-warning">الإعدادات</a>
    @endcan
</div>
@endif

<div class="wa-wrap">
    {{-- قائمة المحادثات --}}
    <div class="wa-list">
        <div class="wa-list-head">
            <input type="search" id="waSearch" class="form-control form-control-sm" placeholder="🔍 بحث برقم الهاتف...">
        </div>
        <div class="wa-convos" id="waConvos">
            <div class="text-center text-muted p-4 small">جارٍ التحميل...</div>
        </div>
    </div>

    {{-- خيط المحادثة --}}
    <div class="wa-chat">
        <div id="waEmpty" class="wa-empty">
            <i class="bi bi-whatsapp" style="font-size:3.5rem; color:#25d366;"></i>
            <div>اختر محادثة من القائمة لبدء المراسلة</div>
        </div>

        <div id="waActive" class="d-none flex-column h-100" style="flex:1;">
            <div class="wa-chat-head">
                <div class="wa-avatar" id="waHeadAvatar">?</div>
                <div>
                    <div class="name" id="waHeadName">—</div>
                    <div class="phone" id="waHeadPhone"></div>
                </div>
                <div class="ms-auto">
                    <button class="wa-icon-btn" id="waRefresh" title="تحديث"><i class="bi bi-arrow-clockwise"></i></button>
                </div>
            </div>

            <div class="wa-messages" id="waMessages"></div>

            <div class="wa-composer">
                <div class="wa-window-warn" id="waWindowWarn">
                    <i class="bi bi-clock-history"></i> خارج نافذة الـ 24 ساعة — قد يُرفض النص الحر. استخدم قالباً معتمداً للبدء.
                </div>
                <div class="wa-emoji-panel" id="waEmojiPanel"></div>
                <div class="wa-att-preview" id="waAttPreview">
                    <i class="bi bi-paperclip"></i> <span id="waAttName" class="flex-fill text-truncate"></span>
                    <button class="btn btn-sm btn-link text-danger p-0" id="waAttClear"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="wa-rec-bar" id="waRecBar">
                    <i class="bi bi-record-circle-fill"></i> جارٍ التسجيل <span id="waRecTime">0:00</span>
                    <button class="btn btn-sm btn-outline-danger" id="waRecCancel">إلغاء</button>
                    <button class="btn btn-sm btn-success" id="waRecStop">إيقاف وإرفاق</button>
                </div>
                <div class="wa-input-row" id="waInputRow">
                    <button class="wa-icon-btn" id="waEmojiBtn" title="إيموجي"><i class="bi bi-emoji-smile"></i></button>
                    <button class="wa-icon-btn" id="waAttachBtn" title="إرفاق ملف"><i class="bi bi-paperclip"></i></button>
                    <button class="wa-icon-btn" id="waMicBtn" title="تسجيل صوت"><i class="bi bi-mic"></i></button>
                    <textarea id="waText" rows="1" placeholder="اكتب رسالة..."></textarea>
                    <button class="wa-icon-btn wa-send-btn" id="waSendBtn" title="إرسال"><i class="bi bi-send-fill"></i></button>
                </div>
                <input type="file" id="waFileInput" class="d-none" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx">
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const WA = {
        csrf: '{{ csrf_token() }}',
        urlConvos: '{{ route('admin.crm.whatsapp.chat.conversations') }}',
        urlThread: '{{ url('admin/crm/whatsapp/chat/thread') }}',
        urlSend:   '{{ route('admin.crm.whatsapp.chat.send') }}',
    };
</script>
@verbatim
<script>
$(function () {
    let activePhone = null;
    let lastId = null;          // مؤشر آخر رسالة مُحمّلة (ULID) للـ polling
    let pendingFile = null;     // ملف/صوت مرفق جاهز للإرسال
    let convoTimer = null, threadTimer = null;
    let mediaRecorder = null, recChunks = [], recSeconds = 0, recInterval = null;

    const $convos   = $('#waConvos');
    const $messages = $('#waMessages');

    const esc = s => $('<div>').text(s == null ? '' : s).html();
    const phoneFmt = p => '+' + String(p || '').replace(/\D/g, '');
    const initial = s => (s || '?').trim().charAt(0).toUpperCase() || '?';

    // ── قائمة المحادثات ─────────────────────────────────────────
    function loadConvos() {
        const q = $('#waSearch').val() || '';
        $.getJSON(WA.urlConvos, { q }).done(res => {
            if (!res.conversations.length) {
                $convos.html('<div class="text-center text-muted p-4 small">لا توجد محادثات بعد.</div>');
                return;
            }
            $convos.html(res.conversations.map(c => {
                const name = c.name || phoneFmt(c.phone);
                const badge = c.unread > 0 ? `<span class="wa-badge">${c.unread}</span>` : '';
                return `
                <div class="wa-convo ${c.phone === activePhone ? 'active' : ''}" data-phone="${esc(c.phone)}" data-name="${esc(c.name || '')}">
                    <div class="wa-avatar">${esc(initial(name))}</div>
                    <div class="wa-convo-body">
                        <div class="wa-convo-name"><span class="text-truncate">${esc(name)}</span><span class="wa-convo-time">${esc(c.time || '')}</span></div>
                        <div class="wa-convo-prev"><span class="text-truncate">${esc(c.preview)}</span>${badge}</div>
                    </div>
                </div>`;
            }).join(''));
        });
    }

    $convos.on('click', '.wa-convo', function () {
        openConversation($(this).data('phone').toString(), $(this).data('name'));
    });

    $('#waSearch').on('input', () => { clearTimeout(window._waSrch); window._waSrch = setTimeout(loadConvos, 300); });

    // ── فتح محادثة ──────────────────────────────────────────────
    function openConversation(phone, name) {
        activePhone = phone;
        lastId = null;
        pendingFile = null; clearAttachment();
        $('#waEmpty').addClass('d-none');
        $('#waActive').removeClass('d-none').addClass('d-flex');
        $('#waHeadName').text(name || phoneFmt(phone));
        $('#waHeadPhone').text(phoneFmt(phone));
        $('#waHeadAvatar').text(initial(name || phone));
        $('#waConvos .wa-convo').removeClass('active').filter(`[data-phone="${phone}"]`).addClass('active');
        $messages.empty();
        loadThread(true);
        startThreadPolling();
    }

    // ── تحميل خيط المحادثة (incremental عبر after_id) ────────────
    function loadThread(reset) {
        if (!activePhone) return;
        const params = lastId ? { after_id: lastId } : {};
        $.getJSON(`${WA.urlThread}/${activePhone}`, params).done(res => {
            if (reset) $messages.empty();
            let lastDay = $messages.find('.wa-day').last().data('day') || null;
            res.messages.forEach(m => {
                if (m.date && m.date !== lastDay) {
                    $messages.append(`<div class="wa-day" data-day="${m.date}">${m.date}</div>`);
                    lastDay = m.date;
                }
                $messages.append(renderBubble(m));
                lastId = m.id;
            });
            if (res.messages.length) scrollDown();

            // نافذة الـ 24 ساعة
            $('#waWindowWarn').toggle(!res.window_open);
        });
    }

    function renderBubble(m) {
        const side = m.direction === 'outbound' ? 'out' : 'in';
        const readCls = m.status === 'read' ? 'read' : '';
        let inner = '';

        if (m.type === 'template') inner += `<div class="wa-tpl-tag">قالب: ${esc(m.template_name || '')}</div>`;

        if (m.type === 'image' && m.media_url) {
            inner += `<a href="${esc(m.media_url)}" target="_blank"><img class="media" src="${esc(m.media_url)}"></a>`;
        } else if (m.type === 'video' && m.media_url) {
            inner += `<video class="media" controls src="${esc(m.media_url)}"></video>`;
        } else if (m.type === 'audio' && m.media_url) {
            inner += `<audio class="media" controls src="${esc(m.media_url)}"></audio>`;
        } else if (m.type === 'document' && m.media_url) {
            inner += `<a class="wa-file" href="${esc(m.media_url)}" target="_blank" download>
                        <i class="bi bi-file-earmark-arrow-down fs-5"></i>
                        <span class="text-truncate">${esc(m.media_filename || 'مستند')}</span></a>`;
        }
        if (m.body) inner += `<div class="txt">${esc(m.body)}</div>`;

        // علامات الحالة للصادر
        let ticks = '';
        if (m.direction === 'outbound') {
            const map = { queued:'bi-clock', sent:'bi-check', delivered:'bi-check-all', read:'bi-check-all', failed:'bi-exclamation-circle text-danger' };
            ticks = `<i class="bi ${map[m.status] || 'bi-clock'} tick"></i>`;
        }
        const errLine = m.status === 'failed' && m.error ? `<div class="text-danger" style="font-size:.65rem;">⚠ ${esc(m.error)}</div>` : '';

        return `<div class="wa-bubble ${side} ${readCls}">${inner}${errLine}
                  <div class="wa-meta">${esc(m.time || '')} ${ticks}</div></div>`;
    }

    function scrollDown() { $messages.scrollTop($messages[0].scrollHeight); }

    // ── الإرسال ─────────────────────────────────────────────────
    function sendMessage() {
        if (!activePhone) return;
        const body = $('#waText').val().trim();
        if (!body && !pendingFile) return;

        const fd = new FormData();
        fd.append('to_phone', activePhone);
        if (body) fd.append('body', body);
        if (pendingFile) fd.append('attachment', pendingFile, pendingFile.name || 'file');

        const $btn = $('#waSendBtn').prop('disabled', true);
        $('#waText').val('').css('height', 'auto');

        fetch(WA.urlSend, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': WA.csrf, 'Accept': 'application/json' },
            body: fd,
        }).then(async r => {
            const data = await r.json().catch(() => ({}));
            if (r.ok && data.message) {
                let lastDay = $messages.find('.wa-day').last().data('day') || null;
                if (data.message.date && data.message.date !== lastDay) {
                    $messages.append(`<div class="wa-day" data-day="${data.message.date}">${data.message.date}</div>`);
                }
                $messages.append(renderBubble(data.message));
                lastId = data.message.id;
                scrollDown();
                $('#waWindowWarn').toggle(!data.window_open);
            } else {
                alert(data.error || data.message || 'فشل الإرسال');
            }
        }).catch(() => alert('تعذّر الاتصال بالخادم'))
          .finally(() => { $btn.prop('disabled', false); clearAttachment(); loadConvos(); });
    }

    $('#waSendBtn').on('click', sendMessage);
    $('#waText').on('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    }).on('input', function () { this.style.height = 'auto'; this.style.height = Math.min(this.scrollHeight, 120) + 'px'; });

    $('#waRefresh').on('click', () => loadThread(true));

    // ── المرفقات (صورة/فيديو/ملف) ───────────────────────────────
    $('#waAttachBtn').on('click', () => $('#waFileInput').click());
    $('#waFileInput').on('change', function () {
        if (this.files[0]) setAttachment(this.files[0]);
        this.value = '';
    });
    function setAttachment(file) {
        pendingFile = file;
        $('#waAttName').text(file.name || 'مرفق');
        $('#waAttPreview').css('display', 'flex');
    }
    function clearAttachment() {
        pendingFile = null;
        $('#waAttPreview').hide();
        $('#waAttName').text('');
    }
    $('#waAttClear').on('click', clearAttachment);

    // ── الإيموجي ────────────────────────────────────────────────
    const EMOJIS = '😀 😁 😂 🤣 😊 😍 😘 😎 🤔 😉 🙂 😅 😢 😭 😤 😡 👍 👎 👏 🙏 💪 🤝 ❤️ 💔 🔥 ⭐ ✅ ❌ ⚠️ 🎉 🎁 💰 📞 📱 📅 📍 🕌 🕋 ✈️ 🚌 🏨 🧳 🌙 ☀️ 💯 🙌 👌 🤲'.split(' ');
    $('#waEmojiPanel').html(EMOJIS.map(e => `<span>${e}</span>`).join(''));
    $('#waEmojiBtn').on('click', () => $('#waEmojiPanel').slideToggle(120));
    $('#waEmojiPanel').on('click', 'span', function () {
        const ta = $('#waText')[0];
        const s = ta.selectionStart, e = ta.selectionEnd, v = ta.value;
        ta.value = v.slice(0, s) + $(this).text() + v.slice(e);
        ta.focus(); ta.selectionStart = ta.selectionEnd = s + $(this).text().length;
    });

    // ── تسجيل الصوت (MediaRecorder) ─────────────────────────────
    $('#waMicBtn').on('click', async function () {
        if (!navigator.mediaDevices || !window.MediaRecorder) {
            alert('متصفحك لا يدعم تسجيل الصوت.'); return;
        }
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            const mime = MediaRecorder.isTypeSupported('audio/ogg;codecs=opus') ? 'audio/ogg;codecs=opus'
                       : (MediaRecorder.isTypeSupported('audio/webm;codecs=opus') ? 'audio/webm;codecs=opus' : '');
            mediaRecorder = new MediaRecorder(stream, mime ? { mimeType: mime } : undefined);
            recChunks = [];
            mediaRecorder.ondataavailable = e => { if (e.data.size) recChunks.push(e.data); };
            mediaRecorder.onstop = () => {
                stream.getTracks().forEach(t => t.stop());
                const type = mediaRecorder.mimeType || 'audio/webm';
                const ext = type.includes('ogg') ? 'ogg' : 'webm';
                const blob = new Blob(recChunks, { type });
                setAttachment(new File([blob], `voice-${Date.now()}.${ext}`, { type }));
            };
            mediaRecorder.start();
            recSeconds = 0;
            $('#waInputRow').hide(); $('#waRecBar').css('display', 'flex');
            recInterval = setInterval(() => {
                recSeconds++;
                $('#waRecTime').text(Math.floor(recSeconds / 60) + ':' + String(recSeconds % 60).padStart(2, '0'));
            }, 1000);
        } catch (err) { alert('تعذّر الوصول للميكروفون: ' + err.message); }
    });
    function endRecUI() { clearInterval(recInterval); $('#waRecBar').hide(); $('#waInputRow').css('display', 'flex'); }
    $('#waRecStop').on('click', () => { if (mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop(); endRecUI(); });
    $('#waRecCancel').on('click', () => {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') { mediaRecorder.onstop = null; mediaRecorder.stop(); mediaRecorder.stream?.getTracks().forEach(t => t.stop()); }
        recChunks = []; endRecUI();
    });

    // ── Polling ────────────────────────────────────────────────
    function startThreadPolling() {
        clearInterval(threadTimer);
        threadTimer = setInterval(() => loadThread(false), 4000);
    }
    loadConvos();
    convoTimer = setInterval(loadConvos, 7000);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) { clearInterval(threadTimer); clearInterval(convoTimer); }
        else { startThreadPolling(); loadConvos(); convoTimer = setInterval(loadConvos, 7000); }
    });
});
</script>
@endverbatim
@endpush
