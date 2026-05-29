<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * أمر النشر/الاستعادة الموحّد.
 *
 * شغّله بعد أي deploy (git pull + composer install) أو بعد استعادة قاعدة
 * بيانات (استيراد SQL dump). يجمع الخطوات القياسية في مكان واحد، وأهمها
 * مزامنة جدول sequences مع البيانات الفعلية لمنع أخطاء Duplicate entry.
 *
 * ملاحظة: استعادة الـ SQL لا تمرّ بـ artisan إطلاقاً، لذا لا يوجد hook
 * تلقائي لها — هذا الأمر هو الـ hook اليدوي الواحد الذي تستدعيه بعد الاستعادة.
 */
class AppDeployCommand extends Command
{
    protected $signature = 'app:deploy
        {--skip-migrate : تخطّي تشغيل المايجريشن (مفيد بعد restore لقاعدة محدّثة بالفعل)}';

    protected $description = 'خطوات النشر/الاستعادة الموحّدة: migrate + sequences:sync + تحسين الكاش';

    public function handle(): int
    {
        $this->info('🚀 بدء خطوات النشر/الاستعادة');
        $this->newLine();

        if (!$this->option('skip-migrate')) {
            $this->components->task('تشغيل المايجريشن', function () {
                return $this->call('migrate', ['--force' => true]) === self::SUCCESS;
            });
        } else {
            $this->components->warn('تم تخطّي المايجريشن (--skip-migrate)');
        }

        // الأهم: مزامنة العدّادات مع أعلى رقم فعلي في كل جدول.
        $this->newLine();
        $this->info('🔄 مزامنة العدّادات (sequences)');
        $this->call('sequences:sync');

        // إعادة بناء الكاش بإعدادات/مسارات/فيوز الإنتاج الجديدة.
        $this->newLine();
        $this->components->task('تحسين الكاش (config/route/view)', function () {
            return $this->call('optimize') === self::SUCCESS;
        });

        $this->newLine();
        $this->info('✅ اكتمل النشر/الاستعادة بنجاح.');

        return self::SUCCESS;
    }
}
