<?php

namespace App\Services;

use App\Models\MailSetting;
use Illuminate\Support\Facades\Schema;

class DynamicMailConfigService
{
    public function apply(?MailSetting $setting = null): void
    {
        if (!$setting && Schema::hasTable('mail_settings')) {
            $setting = MailSetting::query()->where('is_active', true)->latest()->first();
        }

        if (!$setting) {
            return;
        }

        config([
            'mail.default' => $setting->mailer ?: 'smtp',
            'mail.mailers.smtp.scheme' => $setting->encryption === 'ssl' ? 'smtps' : null,
            'mail.mailers.smtp.host' => $setting->host,
            'mail.mailers.smtp.port' => $setting->port,
            'mail.mailers.smtp.encryption' => $setting->encryption ?: null,
            'mail.mailers.smtp.username' => $setting->username,
            'mail.mailers.smtp.password' => $setting->password,
            'mail.from.address' => $setting->from_email,
            'mail.from.name' => $setting->from_name,
        ]);

        app()->forgetInstance('mail.manager');
        app()->forgetInstance('mailer');
    }
}
