<?php

namespace App\Console\Commands;

use App\Services\SmsService;
use Illuminate\Console\Command;

class TestSmsCommand extends Command
{
    protected $signature = 'test:sms {phone} {message?}';
    protected $description = 'Test SMS functionality';

    public function handle(SmsService $smsService)
    {
        $phone = $this->argument('phone');
        $message = $this->argument('message') ?? 'Hello from ' . config('app.name') . '! This is a test SMS.';

        $this->info("📱 Testing SMS to: {$phone}");
        $this->info("📝 Message: {$message}");
        $this->newLine();

        // Check balance first
        $this->info("💰 Checking SMS balance...");
        $balance = $smsService->getBalance();

        if ($balance !== null) {
            $this->info("✅ SMS Balance: ₦" . number_format($balance, 2));
        } else {
            $this->warn("⚠️  Could not retrieve SMS balance");
        }

        $this->newLine();

        // Send SMS
        $this->info("📤 Sending SMS...");
        $result = $smsService->sendSms($phone, $message);

        if ($result) {
            $this->info("✅ SMS sent successfully!");
        } else {
            $this->error("❌ SMS sending failed!");
            $this->line("Check the logs for more details: tail -f storage/logs/laravel.log");
        }

        $this->newLine();
        $this->info("📋 Configuration Check:");
        $this->table(
            ['Setting', 'Value', 'Status'],
            [
                ['SMS Driver', config('sms.default'), config('sms.default') ? '✅' : '❌'],
                ['API Key', config('sms.termii.api_key') ? 'Set' : 'Not Set', config('sms.termii.api_key') ? '✅' : '❌'],
                ['Sender ID', config('sms.termii.sender_id'), config('sms.termii.sender_id') ? '✅' : '❌'],
                ['API URL', config('sms.termii.api_url'), config('sms.termii.api_url') ? '✅' : '❌'],
            ]
        );

        return $result ? 0 : 1;
    }
}
