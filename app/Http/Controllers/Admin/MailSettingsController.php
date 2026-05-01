<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TestMail;
use App\Models\EmailLog;
use App\Models\MailSetting;
use App\Services\DynamicMailConfigService;
use App\Services\EmailLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailSettingsController extends Controller
{
    public function index()
    {
        return view('admin.mail-settings.index', [
            'setting' => MailSetting::query()->latest()->first(),
            'logs' => EmailLog::query()->with('user:id,name,email')->latest()->limit(100)->get(),
        ]);
    }

    public function update(Request $request, DynamicMailConfigService $mailConfig): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'string', 'in:cpanel,gmail'],
            'mailer' => ['required', 'string', 'in:smtp,log,array'],
            'host' => ['required_if:mailer,smtp', 'nullable', 'string', 'max:255'],
            'port' => ['required_if:mailer,smtp', 'nullable', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['nullable', 'string', 'in:tls,ssl,'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
            'from_email' => ['required', 'email', 'max:255'],
        ]);

        $setting = MailSetting::query()->latest()->first() ?: new MailSetting();
        $setting->fill(collect($data)->except('password')->all());
        if ($request->filled('password')) {
            $setting->password = $data['password'];
        }
        $setting->is_active = true;
        $setting->save();

        $mailConfig->apply($setting);

        return back()->with('success', 'Mail settings saved.');
    }

    public function test(Request $request, DynamicMailConfigService $mailConfig): RedirectResponse
    {
        $data = $request->validate([
            'test_email' => ['required', 'email', 'max:255'],
        ]);

        $setting = MailSetting::query()->where('is_active', true)->latest()->first();
        if (!$setting) {
            return back()->with('error', 'Save mail settings before sending a test email.');
        }

        try {
            $mailConfig->apply($setting);
            app(EmailLogService::class)->queued('test_email', $data['test_email'], 'Postzy test email', $request->user());
            Mail::to($data['test_email'])->queue(new TestMail());
            $setting->update(['last_tested_at' => now()]);

            return back()->with('success', 'Test email queued.');
        } catch (\Throwable $exception) {
            app(EmailLogService::class)->failed('test_email', $data['test_email'], $exception->getMessage(), 'Postzy test email', $request->user());

            return back()->with('error', 'Unable to queue test email: '.$exception->getMessage());
        }
    }
}
