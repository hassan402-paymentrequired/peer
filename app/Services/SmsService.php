<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;


enum SmsType: string
{
    case OTP = 'otp';
    case NOTIFICATION = 'notification';
    case WELCOME = 'welcome';
    case FORGOT_PASSWORD = 'forgot_password';
    case VERIFY_PHONE = 'verify_phone';
}

class SmsService
{
    protected string $driver;
    protected string $apiUrl;
    protected string $apiKey;
    protected string $senderId;

    public function __construct()
    {

        $this->apiUrl = config("sms.kudisms.api_url");
        $this->apiKey = config("sms.kudisms.api_key");
        $this->senderId = config("sms.kudisms.sender_id");
    }

    /**
     * Send SMS to a single recipient
     */
    public function sendSms(string $phoneNumber, string $message, string $channel): bool
    {
        try {
            $cleanPhone = $this->cleanPhoneNumber($phoneNumber);

            if ($channel === 'sms') {
                return $this->sendKudiSms($cleanPhone, $message);
            } else {
                return $this->sendWhatsAppOtp($cleanPhone, $message);
            }
        } catch (Exception $e) {
            Log::error("{$this->driver} SMS service exception", [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }



    protected function sendKudiSms(string $phone, string $message): bool
    {
        try {
            $response = Http::post("{$this->apiUrl}/sms", [
                'token' => $this->apiKey,
                'senderID' => $this->senderId,
                'recipients' => $phone,
                'message' => $message,
            ]);

            Log::info('SMS sent successfully via KudiSMS', [
                        'res' => $response->json()
                    ]);

            if ($response->successful()) {
                $data = $response->json();



                    Log::info('SMS sent successfully via KudiSMS', [
                        'phone' => $phone,
                        'response' => $data,
                    ]);
                    return true;
            }

            Log::error('KudiSMS sending failed', [
                'phone' => $phone,
                'response' => $response->json(),
                'status_code' => $response->status(),
            ]);

            return false;
        } catch (Exception $e) {
            Log::error('KudiSMS exception', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }


    /**
     * Send SMS to multiple recipients
     */
    public function sendBulkSms(array $phoneNumbers, string $message, ?string $sender = null): array
    {
        $results = [];

        foreach ($phoneNumbers as $phoneNumber) {
            $results[$phoneNumber] = $this->sendSms($phoneNumber, $message, $sender);
        }

        return $results;
    }

    /**
     * Clean and format phone number for Nigerian numbers
     */
    protected function cleanPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Handle different Nigerian phone number formats
        if (strlen($phone) === 11 && substr($phone, 0, 1) === '0') {
            // Convert 0801234567 to 2348012345678
            return '234' . substr($phone, 1);
        } elseif (strlen($phone) === 10) {
            // Convert 8012345678 to 2348012345678
            return '234' . $phone;
        } elseif (strlen($phone) === 13 && substr($phone, 0, 3) === '234') {
            // Already in correct format
            return $phone;
        }

        // Return as is if format is unclear
        return $phone;
    }

    /**
     * Check if current time is within SMS delivery window (8 AM - 8 PM Nigeria time)
     */
    protected function isWithinDeliveryWindow(): bool
    {
        $nigeriaTime = now()->timezone('Africa/Lagos');
        $hour = (int) $nigeriaTime->format('H');

        return $hour >= 8 && $hour < 20;
    }

    /**
     * Send OTP via WhatsApp (KudiSMS)
     */
    protected function sendWhatsAppOtp(string $phone, string $message): bool
    {
        try {
            $response = Http::post("{$this->apiUrl}/whatsapp", [
                'token' => $this->apiKey,
                'senderID' => $this->senderId,
                'recipients' => $phone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['status']) && $data['status'] === '000') {
                    Log::info('WhatsApp OTP sent successfully', [
                        'phone' => $phone,
                        'response' => $data,
                    ]);
                    return true;
                }
            }

            Log::error('WhatsApp OTP sending failed', [
                'phone' => $phone,
                'response' => $response->json(),
            ]);

            return false;
        } catch (Exception $e) {
            Log::error('WhatsApp OTP exception', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
