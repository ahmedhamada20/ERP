# 👋 ابدأ من هنا — المحادثة الجاية

> آخر تحديث: 2026-05-25 — بعد إنجاز Sprint 6 Step 2

---

## 📊 الحالة الحالية

### Sprints المنجزة

- ✅ **Sprint 1** — Safety fixes (overpayment guard, refund workflow, password reset, email verify, backup system)
- ✅ **Sprint 2** — Accounting Spine كامل (Chart of Accounts → Journal → Vouchers → Trial Balance + P&L + GL Detail)
- ✅ **Sprint 3** — Suppliers Module (CRUD + Invoices + Statement + FIFO Aging Report)
- ✅ **Sprint 4** — Domestic Tourism (Schema + Programs + Bookings + Costs/Payments + GL auto-posting + P&L report + 26 tests)
- ✅ **Sprint 5** — CRM + WhatsApp (Leads Kanban + Opportunities + WhatsApp Cloud API + 6 notification triggers + 44 tests)
- 🟡 **Sprint 6** — HR + Payroll + Multi-Branch (in progress)

### Sprint 6 progress

| Step | Title | Status |
|---|---|---|
| 1 | Core schema (5 tables: branches/departments/positions/employees/employee_documents) | ✅ |
| 2 | Branch CRUD + multi-branch wiring (branch_id على 6 transaction tables + auto-fill trait) | ✅ |
| 3 | Employees CRUD + departments + positions + documents | ⏭️ التالي |
| 4 | Attendance + Leave requests | ⏭️ |
| 5 | Payroll engine + commissions + GL auto-posting | ⏭️ |
| 6 | Reports (payroll register / branch P&L) + feature tests | ⏭️ |

### الأرقام

- **279 test / 763 assertion** كلها بتنجح (zero regressions)
- 6 sprints (5 complete + 1 in progress)
- BelongsToBranch trait wired على religious + domestic bookings
- 1510 سجل قديم اتـ backfill بـ main branch بنجاح
- كل اللي خلصت محفوظ في الـ memory في `C:\Users\Ahmed\.claude\projects\d--jops-CoreXSolution-ERP\memory\`

---

## ⚠️ قبل ما تبدأ — نفّذ الأوامر دي مرة واحدة

```bash
cd d:/jops/CoreXSolution/ERP
php artisan migrate
php artisan db:seed --class=DefaultBranchSeeder
php artisan db:seed --class=RolePermissionSeeder
php artisan permission:cache-reset
php artisan config:clear
```

---

## 💬 الـ Prompts الجاهزة (اختار اللي يناسبك)

### 🎯 الموصى به: كمّل Sprint 6 — Step 3 (Employees CRUD)

> **عاوز أكمل Sprint 6 — Step 3: Employees CRUD + Departments + Positions + Documents.**
> اعرضلي الخطة وابدأ في Step 3 على طريقة الـ small steps المعتادة (تراجعي خطوة خطوة).
> هتحتاج:
> - EmployeeController + Request + 5 views (مع multi-section form: identity / contact / org / salary / payment)
> - DepartmentController + PositionController (CRUDs أبسط)
> - Document upload modal + expiry tracking
> - Org tree في show page
> - 3 sidebar entries جديدة تحت HR

### الخيار التاني: اقفز لـ Payroll Engine (Step 5)

> **عاوز أقفز لـ Sprint 6 — Step 5: Payroll Engine.**
> هتحتاج:
> - payroll_runs + payslips + payroll_components tables
> - Commission rules engine بيستخدم effective\* methods من Employee model
> - Auto-post للـ GL (DR salary expense, CR cash/bank + salary payable)
> - شاشة "تشغيل الرواتب الشهرية" مع approval workflow
> ملاحظة: لازم Step 3 يخلص الأول علشان نقدر نختبر فعلياً (محتاجين employees).

### الخيار التالت: اختبار يدوي في المتصفح

> **شغّلي السيرفر وفتحلي صفحة الفروع علشان أجربها.**

### الخيار الرابع: مراجعة كل اللي خلصناه

> **راجعلي الـ Sprints اللي خلصت بنظرة عامة + قائمة بكل الـ features الموجودة دلوقتي. مفيش refactor — مجرد summary.**

### الخيار الخامس: لو في bug عاوز تصلحه

> **في bug في [الصفحة المشكلة].**
> - الـ flow اللي بتعمله: [الخطوات بالتفصيل]
> - اللي حصل: [الـ error أو السلوك الغلط]
> - اللي متوقع: [السلوك الصحيح]
> ساعدني أصلحه.

---

## 🎨 تذكير سريع بتفضيلاتك

- **نمط الشغل:** صغير ومتكرر، تراجعي في كل خطوة قبل ما أكمل
- **اللغة:** كل الـ UI + الرسائل بالعربي (RTL)
- **التكنولوجي ستاك:** Laravel 11 + AdminLTE 3 + Spatie permissions + Yajra DataTables + MySQL + ULID + SQLite للـ tests
- **الـ tests:** SQLite in-memory، بعد كل sprint بختبر بـ regression check خضرا
- **القرارات المعمارية المعتمدة:**
  - Domestic + Religious في جداول منفصلة (مش polymorphic)
  - WhatsApp Cloud API → real (settings في DB مش .env)
  - Opportunities convert → polymorphic لـ religious + domestic
  - Employees جدول منفصل عن users مع user_id optional
  - branch_id على كل transaction tables (nullable + auto-fill)
  - Commission engine في Position مع override per Employee

---

## 📁 ملفات الـ Memory المحفوظة

محفوظة في `C:\Users\Ahmed\.claude\projects\d--jops-CoreXSolution-ERP\memory\`:

| الملف | المحتوى |
|---|---|
| `MEMORY.md` | الفهرس الرئيسي (يتحمل تلقائياً في كل محادثة) |
| `user_profile.md` | Senior Laravel dev for tourism ERP |
| `project_corex_erp.md` | Tech stack + Arabic-first UI |
| `feedback_arabic_ui.md` | Full Arabic UI + RTL |
| `feedback_tech_stack.md` | Laravel MVC + DataTables AJAX + Spatie |
| `feedback_workflow_small_steps.md` | تراجع كل خطوة |
| `project_scale_requirements.md` | 1000+ users, 1M+ records, ULID, FULLTEXT |
| `project_religious_tourism_module.md` | الديني — 9 tables, 13 controllers, 43 routes |
| `project_sprint1_safety_fixes.md` | الـ safety guards |
| `project_sprint2_accounting_spine.md` | الـ GL الكامل + auto-posting |
| `project_sprint3_suppliers_module.md` | AP subsidiary ledger + aging |
| `project_sprint4_domestic_module.md` | السياحة الداخلية كاملة + 26 tests |
| `project_sprint5_crm_whatsapp.md` | CRM + WhatsApp + 44 tests |
| `project_sprint6_hr_payroll.md` | HR في progress (Steps 1-2 done) |
| `project_next_sprints_roadmap.md` | الخطة العامة |

أي محادثة جديدة في نفس المشروع هتقرأهم تلقائياً.

---

## 🗺️ الخريطة الكاملة لما بعد Sprint 6

| Sprint | المحتوى | الحالة |
|---|---|---|
| 6 | HR + Payroll + Multi-Branch (4 steps متبقية) | 🟡 |
| 7 | السياحة الدولية / international tourism | ⏸️ مقترح |
| 8 | Loyalty / points + customer portal | ⏸️ مقترح |
| 9 | Mobile app API (sanctum tokens) | ⏸️ مقترح |
| 10 | Multi-currency reporting + FX gains/losses | ⏸️ مقترح |

كل ده بعدين — أول حاجة نخلص Sprint 6.

---

## 🔑 المختصرات السريعة لما تتذكر

- لو نسيت اسم branch model: `App\Models\Branch` ومعاه `Branch::main()` يرجع الرئيسي
- لو احتجت تضيف branch_id لجدول جديد: `use BelongsToBranch;` + `'branch_id'` في fillable
- لو احتجت تختبر معين: `php artisan test --filter "اسم_الكلاس"`
- WhatsApp settings: `/admin/crm/whatsapp/settings` (تحتاج access_token + phone_number_id من Meta)
- Webhook URL لـ Meta: `https://YOUR_DOMAIN/api/whatsapp/webhook`

---

🟢 **جاهز للبدء**: افتح محادثة جديدة في نفس المجلد، اقرأ هذا الملف، واختار prompt.
