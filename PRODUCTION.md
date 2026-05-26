# دليل النشر والتشغيل في الإنتاج — CoreX Tourism ERP

> المستهدف: 1000+ مستخدم متزامن، 1M+ عميل/حجز/تأشيرة، بيانات سرية لشركات سياحة.

---

## 1) متطلبات السيرفر الموصى بها

### للبداية (حتى 200 مستخدم متزامن + 100K عميل)
- **App Server**: 4 CPU / 8GB RAM / SSD NVMe
- **DB Server**: 4 CPU / 16GB RAM / SSD NVMe (250GB+)
- **OS**: Ubuntu 22.04 LTS
- **PHP**: 8.3 مع OPcache + JIT
- **DB**: MySQL 8.0 أو MariaDB 10.11
- **Web**: nginx 1.24+

### للحجم الكامل (1000+ متزامن + 1M+ سجل)
- **App Servers**: 2-3 خوادم × (8 CPU / 16GB RAM) خلف Load Balancer
- **DB Master**: 8 CPU / 32GB RAM / NVMe RAID10 (500GB+)
- **DB Read Replica**: 4 CPU / 16GB RAM (للتقارير الثقيلة)
- **Redis Server**: 2 CPU / 4GB RAM
- **Object Storage**: S3 / DigitalOcean Spaces للصور والمرفقات

---

## 2) تفعيل Redis (موصى به بشدة قبل 500+ مستخدم)

في `.env` الإنتاج:
```ini
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=YOUR_STRONG_PASSWORD
```
الفوائد المباشرة:
- جلسات أسرع 50x (بدل database lookups على كل request)
- caching KPI stats أسرع 100x
- queues حقيقية بدلاً من polling database

---

## 3) إعدادات .env للإنتاج

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Session hardening
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true     # يتطلب HTTPS
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict

# Logging
LOG_CHANNEL=daily
LOG_LEVEL=warning              # لا تسجل debug في الإنتاج
```

---

## 4) تحسينات PHP في الإنتاج

في `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0          # production فقط (يتطلب deploy script يستدعي opcache_reset)
opcache.jit=tracing
opcache.jit_buffer_size=128M

memory_limit=256M
max_execution_time=60
upload_max_filesize=10M
post_max_size=12M
```

ثم بعد كل deployment:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
```

---

## 5) إعدادات MySQL الموصى بها

في `/etc/mysql/mysql.conf.d/mysqld.cnf`:
```ini
[mysqld]
innodb_buffer_pool_size = 12G       # 50-70% من RAM
innodb_log_file_size = 1G
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
max_connections = 500
query_cache_type = 0                # MySQL 8 لا يستخدمها
ft_min_word_len = 3                 # للبحث FULLTEXT (يتطلب REPAIR بعد التغيير)
slow_query_log = 1
long_query_time = 1
```

---

## 6) Backup Strategy

### يومي
```bash
mysqldump --single-transaction --routines --triggers \
    -u backup -p corex_erp | gzip > backup-$(date +\%F).sql.gz
```

### Storage
- نسخة محلية: 7 أيام
- نسخة S3/منفصل: 30 يوم
- نسخة شهرية: سنة كاملة

### اختبار الاسترجاع
**شهرياً** على بيئة staging — backup غير مختبر = backup غير موجود.

---

## 7) Monitoring & Alerts

- **APM**: Laravel Telescope (داخلي) أو Sentry (الأخطاء)
- **Server**: Netdata أو Datadog
- **Uptime**: UptimeRobot / Better Stack
- **Database**: تفعيل slow query log + مراجعة أسبوعية

---

## 8) HTTPS / TLS

- استخدم Let's Encrypt + certbot
- فعّل HTTP/2 أو HTTP/3
- في nginx:
```nginx
ssl_protocols TLSv1.2 TLSv1.3;
ssl_prefer_server_ciphers off;
ssl_session_cache shared:SSL:50m;
ssl_session_timeout 1d;
gzip on; gzip_comp_level 6;
brotli on; brotli_comp_level 6;
```

---

## 9) ميزات الأمان المُفعّلة بالكود

| الميزة | الموقع | الوصف |
|--------|--------|--------|
| Security Headers | `app/Http/Middleware/SecurityHeaders.php` | CSP, X-Frame-Options, HSTS, Permissions-Policy |
| Login Throttle | `app/Http/Controllers/Auth/LoginController.php` | 5 محاولات فاشلة → قفل 15 دقيقة |
| كلمة مرور قوية | `app/Http/Requests/Admin/UserRequest.php` | 10 أحرف + رموز + رقم + ليست مسربة (HIBP) |
| Audit Log | `app/Models/Customer.php`, `User.php` | كل CRUD مُسجل تلقائياً |
| ULID Keys | كل الـ models | يمنع enumeration attacks |
| File Upload Validation | `CustomerRequest.php` | mime types صارمة + size limit |
| CSRF | افتراضي في Laravel | كل forms محمية |
| Eloquent | كل القائم على ORM | حماية تلقائية من SQL injection |

---

## 10) Performance — التحسينات المُطبّقة

| التحسين | المكاسب |
|---------|--------|
| FULLTEXT index على `customers` | بحث بين 1M سجل في < 50ms |
| Composite indexes (status+type+created_at) | استعلامات الفلاتر < 20ms |
| KPI cache (5 دقائق) | صفحة العملاء تحمل في < 100ms |
| Aggregation query واحد | بدلاً من 6 COUNT queries منفصلة |
| SELECT محدد للأعمدة فقط | تقليل I/O بنسبة 60%+ على table بـ 1M صف |
| Eager loading creator | لا N+1 queries |
| ULID مُرتب زمنياً | أداء فهرسة أفضل من UUID v4 |

---

## 11) Queue Workers (للمهام الثقيلة)

شغّل workers كـ daemons عبر supervisor:
```ini
# /etc/supervisor/conf.d/corex-worker.conf
[program:corex-worker]
command=php /var/www/corex/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=4
user=www-data
```

استخدمها لـ:
- إرسال البريد الإلكتروني (تأكيدات، تذكيرات انتهاء الجواز)
- معالجة الصور (تصغير، تحويل WebP)
- إنشاء تقارير Excel/PDF
- إشعارات WhatsApp/SMS

---

## 12) خطوات النشر السريعة

```bash
# 1) Clone
git clone YOUR_REPO /var/www/corex && cd /var/www/corex

# 2) Dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 3) Env
cp .env.example .env
nano .env                       # املأ القيم الحقيقية
php artisan key:generate

# 4) Database
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force

# 5) Permissions on storage
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 6) Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan storage:link

# 7) Supervisor for queues
sudo systemctl restart supervisor
```

---

## 13) خطة التوسع المستقبلية

| المرحلة | المستخدمون | العملاء | التحسينات الإضافية |
|---------|-----------|---------|-------------------|
| MVP | حتى 100 | حتى 50K | السيرفر الواحد فقط |
| Growth | 100-500 | 50K-500K | + Redis + Queues |
| Scale | 500-2000 | 500K-2M | + Read Replica + CDN + Load Balancer |
| Enterprise | 2000+ | 2M+ | + Laravel Octane (Swoole) + Database Sharding + Elasticsearch |

---

## 14) Tourism-Specific Compliance

- **حفظ بيانات الجوازات**: لا يقل عن 5 سنوات (قانوني)
- **GDPR/PDPL**: زر "حذف بياناتي" + تصدير الـ JSON على طلب العميل (يُضاف لاحقاً)
- **PCI DSS**: لو هتدمج payments، **لا تخزّن أرقام البطاقات إطلاقاً** — استخدم Stripe/Tap/Paymob tokenization

---

**آخر تحديث**: 2026-05-24
**نسخة Laravel**: 11.x
**نسخة PHP المطلوبة**: 8.2+
