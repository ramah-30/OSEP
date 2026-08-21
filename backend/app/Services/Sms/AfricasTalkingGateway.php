<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin client for the Africa's Talking SMS API. Kept intentionally small: mint a
 * request, post the form, and normalise the one recipient's result so callers
 * get a clean success/failure with the provider's message id and cost.
 *
 * @see https://developers.africastalking.com/docs/sms/sending/bulk
 */
class AfricasTalkingGateway
{
    private const PROD_URL = 'https://api.africastalking.com/version1/messaging';

    private const SANDBOX_URL = 'https://api.sandbox.africastalking.com/version1/messaging';

    /** SMS is only attempted when an API key is present. */
    public function configured(): bool
    {
        return filled(config('services.africastalking.api_key'));
    }

    /**
     * Send one SMS. Returns the provider's recipient record on success.
     *
     * @return array{number:string, status:string, messageId:string, cost:string}
     *
     * @throws RuntimeException when unconfigured or the provider rejects the send.
     */
    public function send(string $to, string $message): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('SMS gateway is not configured.');
        }

        $number = $this->normalise($to);
        if ($number === '') {
            throw new RuntimeException('Recipient has no valid phone number.');
        }

        $sandbox = (bool) config('services.africastalking.sandbox', true);

        $payload = [
            'username' => (string) config('services.africastalking.username', 'sandbox'),
            'to' => $number,
            'message' => $message,
        ];
        if ($from = config('services.africastalking.sender_id')) {
            $payload['from'] = $from;
        }

        $response = Http::asForm()
            ->withHeaders([
                'apiKey' => (string) config('services.africastalking.api_key'),
                'Accept' => 'application/json',
            ])
            ->post($sandbox ? self::SANDBOX_URL : self::PROD_URL, $payload);

        if ($response->failed()) {
            throw new RuntimeException('SMS gateway error: HTTP '.$response->status());
        }

        $recipient = $response->json('SMSMessageData.Recipients.0');

        if (! $recipient) {
            // No recipient echoed back usually means an auth/parameter problem.
            $desc = $response->json('SMSMessageData.Message') ?? 'no recipients accepted';
            throw new RuntimeException('SMS not accepted: '.$desc);
        }

        // AT marks a queued message "Success"; anything else is a rejection.
        if (($recipient['status'] ?? '') !== 'Success') {
            throw new RuntimeException('SMS rejected: '.($recipient['status'] ?? 'unknown status'));
        }

        return [
            'number' => $recipient['number'] ?? $number,
            'status' => $recipient['status'],
            'messageId' => $recipient['messageId'] ?? '',
            'cost' => $recipient['cost'] ?? '',
        ];
    }

    /**
     * Force an E.164-ish number: keep a leading +, strip everything else. AT
     * requires the international format (e.g. +255...).
     */
    private function normalise(string $phone): string
    {
        $plus = str_starts_with(trim($phone), '+') ? '+' : '';
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return $digits === '' ? '' : $plus.$digits;
    }
}
