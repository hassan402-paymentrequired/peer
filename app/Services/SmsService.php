<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SmsService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $senderId;

    public function __construct()
    {
        $this->apiUrl = config('sms.termii.api_url', 'https://v3.api.termii.com/api/sms/send');
        $this->apiKey = config('sms.termii.api_key');
        $this->senderId = config('sms.termii.sender_id', config('app.name'));
    }

    /**
     * Send SMS to a single recipient
     */
    public function sendSms(string $phoneNumber, string $message, ?string $sender = null): bool
    {
        try {
            $sender = $sender ?? $this->senderId;

            // Clean phone number for international format
            $cleanPhone = $this->cleanPhoneNumber($phoneNumber);

            $payload = [
                'to' => $cleanPhone,
                'from' => $sender,
                'sms' => $message,
                'type' => 'plain',
                'channel' => 'generic',
                'api_key' => $this->apiKey,
            ];

            $response = Http::post($this->apiUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();

                // Termii returns different response structure
                if (isset($data['message_id']) || (isset($data['code']) && $data['code'] === 'ok')) {
                    Log::info('SMS sent successfully via Termii', [
                        'phone' => $cleanPhone,
                        'message_id' => $data['message_id'] ?? null,
                        'balance' => $data['balance'] ?? null,
                    ]);
                    return true;
                }
            }

            Log::error('Termii SMS sending failed', [
                'phone' => $cleanPhone,
                'response' => $response->json(),
                'status_code' => $response->status(),
            ]);

            return false;
        } catch (Exception $e) {
            Log::error('Termii SMS service exception', [
                'phone' => $phoneNumber,
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
     * Check SMS balance
     */
    public function getBalance(): ?float
    {
        try {
            $response = Http::get('https://v3.api.termii.com/api/get-balance', [
                'api_key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['balance'] ?? null;
            }

            return null;
        } catch (Exception $e) {
            Log::error('Termii balance check failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get sender IDs from Termii
     */
    public function getSenderIds(): array
    {
        try {
            $response = Http::get('https://v3.api.termii.com/api/sender-id', [
                'api_key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? [];
            }

            return [];
        } catch (Exception $e) {
            Log::error('Termii sender ID fetch failed', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Send OTP via Termii
     */
    public function sendOtp(string $phoneNumber, int $pinLength = 6, int $timeToLive = 10): ?string
    {
        try {
            $cleanPhone = $this->cleanPhoneNumber($phoneNumber);

            $payload = [
                'api_key' => $this->apiKey,
                'message_type' => 'NUMERIC',
                'to' => $cleanPhone,
                'from' => $this->senderId,
                'channel' => 'generic',
                'pin_attempts' => 3,
                'pin_time_to_live' => $timeToLive,
                'pin_length' => $pinLength,
                'pin_placeholder' => '< 1234 >',
                'message_text' => 'Your ' . config('app.name') . ' verification code is < 1234 >. Valid for ' . $timeToLive . ' minutes.',
            ];

            $response = Http::post('https://v3.api.termii.com/api/sms/otp/send', $payload);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['pinId'])) {
                    Log::info('OTP sent successfully via Termii', [
                        'phone' => $cleanPhone,
                        'pin_id' => $data['pinId'],
                    ]);
                    return $data['pinId'];
                }
            }

            Log::error('Termii OTP sending failed', [
                'phone' => $cleanPhone,
                'response' => $response->json(),
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Termii OTP service exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Verify OTP via Termii
     */
    public function verifyOtp(string $pinId, string $pin): bool
    {
        try {
            $payload = [
                'api_key' => $this->apiKey,
                'pin_id' => $pinId,
                'pin' => $pin,
            ];

            $response = Http::post('https://v3.api.termii.com/api/sms/otp/verify', $payload);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['verified']) && $data['verified'] === true) {
                    Log::info('OTP verified successfully via Termii', [
                        'pin_id' => $pinId,
                    ]);
                    return true;
                }
            }

            Log::error('Termii OTP verification failed', [
                'pin_id' => $pinId,
                'response' => $response->json(),
            ]);

            return false;
        } catch (Exception $e) {
            Log::error('Termii OTP verification exception', [
                'pin_id' => $pinId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
