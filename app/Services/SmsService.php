<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class SmsService
{
    protected string $driver;
    protected string $apiUrl;
    protected string $apiKey;
    protected string $senderId;

    public function __construct()
    {
        $this->driver = config('sms.default', 'kudisms');
        $driverConfig = config("sms.{$this->driver}");
        
        $this->apiUrl = $driverConfig['api_url'];
        $this->apiKey = $driverConfig['api_key'];
        $this->senderId = $driverConfig['sender_id'] ?? config('app.name');
    }

    /**
     * Send SMS to a single recipient
     */
    public function sendSms(string $phoneNumber, string $message, ?string $sender = null): bool
    {
        try {
            $sender = $sender ?? $this->senderId;
            $cleanPhone = $this->cleanPhoneNumber($phoneNumber);

            if ($this->driver === 'kudisms') {
                return $this->sendKudiSms($cleanPhone, $message, $sender);
            } else {
                return $this->sendTermiiSms($cleanPhone, $message, $sender);
            }
        } catch (Exception $e) {
            Log::error("{$this->driver} SMS service exception", [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send SMS via KudiSMS
     */
    protected function sendKudiSms(string $phone, string $message, string $sender): bool
    {
        try {
            $response = Http::post("{$this->apiUrl}/sms", [
                'token' => $this->apiKey,
                'senderID' => $sender,
                'recipients' => $phone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // KudiSMS returns status code 000 for success
                if (isset($data['status']) && $data['status'] === '000') {
                    Log::info('SMS sent successfully via KudiSMS', [
                        'phone' => $phone,
                        'response' => $data,
                    ]);
                    return true;
                }
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
     * Send SMS via Termii (legacy)
     */
    protected function sendTermiiSms(string $phone, string $message, string $sender): bool
    {
        try {
            $payload = [
                'to' => $phone,
                'from' => $sender,
                'sms' => $message,
                'type' => 'plain',
                'channel' => 'generic',
                'api_key' => $this->apiKey,
            ];

            $response = Http::post($this->apiUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['message_id']) || (isset($data['code']) && $data['code'] === 'ok')) {
                    Log::info('SMS sent successfully via Termii', [
                        'phone' => $phone,
                        'message_id' => $data['message_id'] ?? null,
                        'balance' => $data['balance'] ?? null,
                    ]);
                    return true;
                }
            }

            Log::error('Termii SMS sending failed', [
                'phone' => $phone,
                'response' => $response->json(),
                'status_code' => $response->status(),
            ]);

            return false;
        } catch (Exception $e) {
            Log::error('Termii SMS service exception', [
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
     * Send OTP (Local implementation)
     * Generates OTP locally, stores in cache, and sends via SMS or WhatsApp
     */
    public function sendOtp(string $phoneNumber, int $pinLength = 6, int $timeToLive = 10): ?string
    {
        try {
            $cleanPhone = $this->cleanPhoneNumber($phoneNumber);
            
            // Generate random OTP
            $otp = str_pad((string) random_int(0, pow(10, $pinLength) - 1), $pinLength, '0', STR_PAD_LEFT);
            
            // Store OTP in cache with TTL
            $cacheKey = "otp_{$cleanPhone}";
            Cache::put($cacheKey, $otp, now()->addMinutes($timeToLive));
            
            // Determine delivery method based on time
            $useWhatsApp = !$this->isWithinDeliveryWindow();
            $deliveryMethod = $useWhatsApp ? 'WhatsApp' : 'SMS';
            
            // Send OTP
            $message = "Your " . config('app.name') . " verification code is {$otp}. Valid for {$timeToLive} minutes.";
            
            if ($useWhatsApp && $this->driver === 'kudisms') {
                $sent = $this->sendWhatsAppOtp($cleanPhone, $message);
            } else {
                $sent = $this->sendSms($cleanPhone, $message);
            }
            
            if ($sent) {
                Log::info('OTP sent successfully', [
                    'phone' => $cleanPhone,
                    'cache_key' => $cacheKey,
                    'delivery_method' => $deliveryMethod,
                ]);
                // Return phone as pinId for compatibility
                // Store delivery method in cache for frontend feedback
                Cache::put("otp_method_{$cleanPhone}", $deliveryMethod, now()->addMinutes($timeToLive));
                return $cleanPhone;
            }
            
            // Clean up cache if sending failed
            Cache::forget($cacheKey);
            return null;
        } catch (Exception $e) {
            Log::error('OTP sending failed', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Verify OTP (Local implementation)
     */
    public function verifyOtp(string $pinId, string $pin): bool
    {
        try {
            // pinId is the phone number
            $cacheKey = "otp_{$pinId}";
            $storedOtp = Cache::get($cacheKey);
            
            if ($storedOtp && $storedOtp === $pin) {
                // OTP is valid, remove from cache
                Cache::forget($cacheKey);
                
                Log::info('OTP verified successfully', [
                    'phone' => $pinId,
                ]);
                return true;
            }
            
            Log::warning('OTP verification failed', [
                'phone' => $pinId,
                'reason' => $storedOtp ? 'incorrect_pin' : 'expired_or_not_found',
            ]);
            
            return false;
        } catch (Exception $e) {
            Log::error('OTP verification exception', [
                'pin_id' => $pinId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check SMS balance (KudiSMS)
     */
    public function getBalance(): ?float
    {
        if ($this->driver !== 'kudisms') {
            return null;
        }

        try {
            $response = Http::get("{$this->apiUrl}/balance", [
                'token' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['balance'] ?? null;
            }

            return null;
        } catch (Exception $e) {
            Log::error('KudiSMS balance check failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
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

    /**
     * Get delivery method used for last OTP
     */
    public function getOtpDeliveryMethod(string $phoneNumber): ?string
    {
        $cleanPhone = $this->cleanPhoneNumber($phoneNumber);
        return Cache::get("otp_method_{$cleanPhone}");
    }

    /**
     * Get sender IDs (Not applicable for KudiSMS)
     */
    public function getSenderIds(): array
    {
        return [];
    }
}
