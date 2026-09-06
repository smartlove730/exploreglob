<?php

namespace App\Services;

use App\Models\WhatsappAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WhatsappCloudApiService
{
    /**
     * Get the base URL for the Meta Graph API.
     *
     * @param string $apiVersion
     * @return string
     */
    protected function getBaseUrl(string $apiVersion): string
    {
        return "https://graph.facebook.com/{$apiVersion}";
    }

    /**
     * Send an HTTP request to the Meta Graph API.
     *
     * @param string $method
     * @param WhatsappAccount $account
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected function request(string $method, WhatsappAccount $account, string $endpoint, array $data = []): array
    {
        $url = $this->getBaseUrl($account->api_version) . '/' . ltrim($endpoint, '/');

        Log::info("WhatsApp API {$method} Request to {$url}", ['data' => $data]);

        if ($method === 'post') {
            $response = Http::withToken($account->access_token)->post($url, $data);
        } else {
            $response = Http::withToken($account->access_token)->get($url, $data);
        }

        Log::info("WhatsApp API Response from {$url}", [
            'status' => $response->status(),
            'body' => $response->json()
        ]);

        if ($response->failed()) {
            $error = $response->json('error.message') ?? 'Unknown WhatsApp API error';
            throw new Exception("WhatsApp API Error: {$error}");
        }

        return $response->json() ?? [];
    }

    /**
     * Fetch all phone numbers from Meta's WABA
     * 
     * @param WhatsappAccount $account
     * @return array
     */
    public function getPhoneNumbers(WhatsappAccount $account): array
    {
        return $this->request('get', $account, "/{$account->business_account_id}/phone_numbers");
    }

    /**
     * Get a single phone number's details from Meta
     * 
     * @param WhatsappAccount $account
     * @param string $phoneNumberId
     * @return array
     */
    public function getPhoneNumberDetails(WhatsappAccount $account, string $phoneNumberId): array
    {
        return $this->request('get', $account, "/{$phoneNumberId}", [
            'fields' => 'verified_name,code_verification_status,quality_rating,display_phone_number,status,messaging_limit_tier,name_status,platform_type'
        ]);
    }

    /**
     * Register a phone number with Meta (request verification code)
     * 
     * @param WhatsappAccount $account
     * @param string $phoneNumberId
     * @param string $codeMethod
     * @return array
     */
    public function requestVerificationCode(WhatsappAccount $account, string $phoneNumberId, string $codeMethod = 'SMS'): array
    {
        return $this->request('post', $account, "/{$phoneNumberId}/request_code", [
            'code_method' => $codeMethod,
            'language' => 'en',
        ]);
    }

    /**
     * Verify the phone number with the code from Meta
     * 
     * @param WhatsappAccount $account
     * @param string $phoneNumberId
     * @param string $code
     * @return array
     */
    public function verifyCode(WhatsappAccount $account, string $phoneNumberId, string $code): array
    {
        return $this->request('post', $account, "/{$phoneNumberId}/verify_code", [
            'code' => $code
        ]);
    }

    /**
     * Register a phone number for cloud API (the final step to make it operational)
     * 
     * @param WhatsappAccount $account
     * @param string $phoneNumberId
     * @param string|null $pin
     * @return array
     */
    public function registerPhoneNumber(WhatsappAccount $account, string $phoneNumberId, string $pin = null): array
    {
        $data = [
            'messaging_product' => 'whatsapp',
        ];

        if ($pin) {
            $data['pin'] = $pin;
        }

        return $this->request('post', $account, "/{$phoneNumberId}/register", $data);
    }

    /**
     * Deregister a phone number from cloud API
     * 
     * @param WhatsappAccount $account
     * @param string $phoneNumberId
     * @return array
     */
    public function deregisterPhoneNumber(WhatsappAccount $account, string $phoneNumberId): array
    {
        return $this->request('post', $account, "/{$phoneNumberId}/deregister");
    }

    /**
     * Get business profile for a phone number
     * 
     * @param WhatsappAccount $account
     * @param string $phoneNumberId
     * @return array
     */
    public function getBusinessProfile(WhatsappAccount $account, string $phoneNumberId): array
    {
        return $this->request('get', $account, "/{$phoneNumberId}/whatsapp_business_profile", [
            'fields' => 'about,address,description,email,profile_picture_url,websites,vertical'
        ]);
    }

    /**
     * Update business profile for a phone number
     * 
     * @param WhatsappAccount $account
     * @param string $phoneNumberId
     * @param array $data
     * @return array
     */
    public function updateBusinessProfile(WhatsappAccount $account, string $phoneNumberId, array $data): array
    {
        $data['messaging_product'] = 'whatsapp';
        return $this->request('post', $account, "/{$phoneNumberId}/whatsapp_business_profile", $data);
    }

    /**
     * Upload a profile picture to Meta and get a handle
     * 
     * @param WhatsappAccount $account
     * @param string $filePath
     * @param string $mimeType
     * @return string
     * @throws Exception
     */
    public function uploadProfilePicture(WhatsappAccount $account, string $filePath, string $mimeType): string
    {
        $appId = config('services.whatsapp.app_id', config('services.facebook.app_id'));
        $fileSize = filesize($filePath);
        $fileName = basename($filePath);

        // Step 1: Create upload session
        $sessionUrl = $this->getBaseUrl($account->api_version) . "/{$appId}/uploads";
        $sessionResponse = Http::withToken($account->access_token)->post($sessionUrl, [
            'file_length' => $fileSize,
            'file_type' => $mimeType,
            'file_name' => $fileName,
        ]);

        if ($sessionResponse->failed()) {
            throw new Exception("Failed to create upload session: " . $sessionResponse->json('error.message', 'Unknown error'));
        }

        $sessionId = $sessionResponse->json('id');
        if (!$sessionId) {
            throw new Exception("No upload session ID returned.");
        }

        // Step 2: Upload the file data
        $fileData = file_get_contents($filePath);
        $uploadUrl = $this->getBaseUrl($account->api_version) . "/{$sessionId}";
        
        $uploadResponse = Http::withToken($account->access_token)
            ->withHeaders(['Authorization' => 'OAuth ' . $account->access_token])
            ->send('POST', $uploadUrl, [
                'headers' => ['file_offset' => '0'],
                'body' => $fileData
            ]);

        if ($uploadResponse->failed()) {
            throw new Exception("Failed to upload file data: " . $uploadResponse->json('error.message', 'Unknown error'));
        }

        $handle = $uploadResponse->json('h');
        if (!$handle) {
            throw new Exception("No file handle returned.");
        }

        return $handle;
    }

    /**
     * Get media URL from WhatsApp Cloud API
     */
    public function getMediaUrl(WhatsappAccount $account, string $mediaId): ?string
    {
        try {
            $url = $this->getBaseUrl($account->api_version) . '/' . $mediaId;
            $response = Http::withToken($account->access_token)->get($url);
            
            if ($response->successful()) {
                return $response->json('url');
            }
            
            Log::error('Failed to get media URL', ['media_id' => $mediaId, 'response' => $response->json()]);
            return null;
        } catch (Exception $e) {
            Log::error('Exception getting media URL: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Download media file from WhatsApp and store locally
     */
    public function downloadMedia(WhatsappAccount $account, string $mediaUrl, string $mimeType, ?string $filename = null): ?string
    {
        try {
            $response = Http::withToken($account->access_token)
                ->timeout(30)
                ->get($mediaUrl);
            
            if (!$response->successful()) {
                Log::error('Failed to download media', ['url' => $mediaUrl, 'status' => $response->status()]);
                return null;
            }
            
            $extension = $this->getExtensionFromMimeType($mimeType);
            $storagePath = 'whatsapp-media/' . date('Y/m');
            $fileName = $filename ?: (uniqid('wa_') . '.' . $extension);
            
            // Ensure unique filename
            $fullPath = $storagePath . '/' . $fileName;
            $diskPath = public_path('storage/' . $fullPath);
            
            // Create directory if needed
            $dir = dirname($diskPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            file_put_contents($diskPath, $response->body());
            
            return '/storage/' . $fullPath;
        } catch (Exception $e) {
            Log::error('Exception downloading media: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get file extension from MIME type
     */
    protected function getExtensionFromMimeType(string $mimeType): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'video/mp4' => 'mp4',
            'video/3gpp' => '3gp',
            'audio/aac' => 'aac',
            'audio/mp4' => 'm4a',
            'audio/mpeg' => 'mp3',
            'audio/amr' => 'amr',
            'audio/ogg' => 'ogg',
            'audio/ogg; codecs=opus' => 'ogg',
            'application/pdf' => 'pdf',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/msword' => 'doc',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'text/plain' => 'txt',
        ];
        
        return $map[$mimeType] ?? 'bin';
    }
}
