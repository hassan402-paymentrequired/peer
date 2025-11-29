<?php

namespace App\Console\Commands;

use App\Services\SmsService;
use Illuminate\Console\Command;

class TestKudiSmsCommand extends Command
{
    protected $signature = 'test:kudisms {phone?}';
    protected $description = 'Test KudiSMS integration';

    public function handle(SmsService $smsService)
    {
        $this->info('Testing KudiSMS Integration...');
        $this->newLine();

        // Check configuration
        $this->info('Configuration:');
        $this->table(
            ['Setting', 'Value', 'Status'],
            [
                ['Driver', config('sms.default'), config('sms.default') === 'kudisms' ? '✅' : '❌'],
                ['API Key', config('sms.kudisms.api_key') ? 'Set' : 'Not Set', config('sms.kudisms.api_key') ? '✅' : '❌'],
                ['Sender ID', config('sms.kudisms.sender_id'), config('sms.kudisms.sender_id') ? '✅' : '❌'],
                ['API URL', config('sms.kudisms.api_url'), config('sms.kudisms.api_url') ? '✅' : '❌'],
            ]
        );
        $this->newLine();

        if (!config('sms.kudisms.api_key')) {
            $this->error('KudiSMS API key not configured. Please set KUDISMS_API_KEY in your .env file.');
            return 1;
        }

        // Test SMS sending
        $phone = $this->argument('phone') ?? $this->ask('Enter phone number to test (e.g., 08012345678)');
        
        if (!$phone) {
            $this->error('Phone number is required');
            return 1;
        }

        $this->info("Sending test SMS to {$phone}...");
        $result = $smsService->sendSms($phone, 'Test message from KudiSMS integration. If you receive this, the integration is working!');

        if ($result) {
            $this->info('✅ SMS sent successfully!');
            $this->newLine();
            
            // Test OTP
            if ($this->confirm('Would you like to test OTP functionality?', true)) {
                $this->info('Sending OTP...');
                $pinId = $smsService->sendOtp($phone);
                
                if ($pinId) {
                    $this->info("✅ OTP sent successfully! Pin ID: {$pinId}");
                    $otp = $this->ask('Enter the OTP you received');
                    
                    if ($otp) {
                        $verified = $smsService->verifyOtp($pinId, $otp);
                        if ($verified) {
                            $this->info('✅ OTP verified successfully!');
                        } else {
                            $this->error('❌ OTP verification failed');
                        }
                    }
                } else {
                    $this->error('❌ Failed to send OTP');
                }
            }
        } else {
            $this->error('❌ Failed to send SMS. Check logs for details.');
            return 1;
        }

        return 0;
    }
}
