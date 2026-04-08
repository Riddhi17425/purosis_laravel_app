<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OtpTransactionService
{
    /**
     * Generate OTP
     */
    public static function generateOtp($length = 4): string
    {
        $otp = rand(1000, 9999);
        return $otp;
    }

    /**
     * Build OTP message
     */
    public static function buildMessage($otp): string
    {
        return  "Your OTP for login to Purosis App is {$otp}. Do not share this OTP with anyone. It is valid for 10 minutes.";    
    }

    /**
     * Send OTP SMS
     */
    public static function sendOtp(string $mobile, $otp): array
    {
        $message = self::buildMessage($otp);

        try {
            $response = Http::timeout(30)->get(config('services.otp_transaction.url'), [
                'APIkey'     => config('services.otp_transaction.api_key'),
                'SenderID'   => config('services.otp_transaction.sender_id'),
                'SMSType'    => config('services.otp_transaction.sms_type'),
                'Mobile'     => $mobile,
                'MsgText'    => $message,
                'EntityID'   => config('services.otp_transaction.entity_id'),
                'TemplateID' => config('services.otp_transaction.template_id'),
            ]);

            Log::info('Parameters', [
                'APIkey'     => config('services.otp_transaction.api_key'),
                'SenderID'   => config('services.otp_transaction.sender_id'),
                'SMSType'    => config('services.otp_transaction.sms_type'),
                'Mobile'     => $mobile,
                'MsgText'    => $message,
                'EntityID'   => config('services.otp_transaction.entity_id'),
                'TemplateID' => config('services.otp_transaction.template_id'),
            ]);

            Log::info('OTP SMS API Response', [
                'mobile'   => $mobile,
                'message'  => $message,
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success'  => $response->successful(),
                'status'   => $response->status(),
                'message'  => 'OTP SMS API called successfully.',
                'response' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('OTP SMS API Error', [
                'mobile' => $mobile,
                'error'  => $e->getMessage(),
            ]);

            return [
                'success'  => false,
                'status'   => 500,
                'message'  => 'Failed to send OTP SMS.',
                'response' => $e->getMessage(),
            ];
        }
    }
}