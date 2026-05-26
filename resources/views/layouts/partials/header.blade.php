<div class="topbar">
    <div class="d-flex align-items-center gap-3 flex-wrap">

        {{-- 1) Page title — first in DOM = rightmost in RTL --}}
        <div class="page-title-block">
            <h1>@yield('page_title', 'لوحة تحكم إدارة السياحة والسفر')</h1>
            <p>@yield('page_subtitle', 'إدارة متكاملة لجميع عمليات السياحة والسفر في منصة واحدة')</p>
        </div>

        {{-- 2) Push the rest to the visual left --}}
        <div class="ms-auto d-flex align-items-center gap-3 flex-wrap">

            {{-- Search box --}}
            <div class="search-box">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="form-control" placeholder="ابحث عن حجوزات، عملاء، برامج سياحية...">
            </div>

            {{-- Notifications --}}
            <button class="icon-btn" type="button" title="الإشعارات">
                <i class="bi bi-bell"></i>
                <span class="badge-count">5</span>
            </button>

            {{-- Messages --}}
            <button class="icon-btn" type="button" title="الرسائل">
                <i class="bi bi-envelope"></i>
                <span class="badge-count">3</span>
            </button>

            @auth
            {{-- User chip — avatar (right) → info → chevron (left) --}}
            <div class="user-chip" id="userChip">
                <div class="avatar-fallback">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="u-info">
                    <div class="u-name">{{ auth()->user()->name }}</div>
                    <div class="u-role">
                        @foreach(auth()->user()->getRoleNames() as $r)
                            {{ $r === 'super-admin' ? 'مدير النظام' : $r }}@if(!$loop->last), @endif
                        @endforeach
                    </div>
                </div>
                <i class="bi bi-chevron-down chev"></i>
            </div>
            @endauth

            {{-- mobile sidebar toggle --}}
            <button class="icon-btn d-lg-none" data-toggle-sidebar type="button" title="القائمة">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </div>

    @auth
    <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display:none;">@csrf</form>
    <script>
        document.getElementById('userChip')?.addEventListener('click', function () {
            Swal.fire({
                title: 'هل تريد تسجيل الخروج؟',
                icon: 'question', showCancelButton: true,
                confirmButtonText: 'نعم، تسجيل الخروج', cancelButtonText: 'إلغاء',
                confirmButtonColor: '#dc2626', reverseButtons: true
            }).then(r => { if (r.isConfirmed) document.getElementById('logout-form').submit(); });
        });
    </script>
    @endauth
</div>
