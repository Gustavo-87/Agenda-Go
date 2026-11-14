<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $token = config('services.whatsapp.verify_token');

        if (
            $token &&
            $request->query('hub_mode') === 'subscribe' &&
            hash_equals($token, (string) $request->query('hub_verify_token'))
        ) {
            return response(
                $request->query('hub_challenge'),
                200
            )->header('Content-Type', 'text/plain');
        }

        return response('Verificación rechazada', 403);
    }

    public function receive(Request $request)
    {
        $secret = config('services.whatsapp.app_secret');

        if (!$secret) {
            return response('App Secret no configurado', 503);
        }

        $signature = $request->header('X-Hub-Signature-256', '');
        $expected = 'sha256=' . hash_hmac(
            'sha256',
            $request->getContent(),
            $secret
        );

        logger()->info('WhatsApp: validación de firma', [
            'valid' => hash_equals($expected, $signature),
            'has_signature' => $signature !== '',
        ]);

        if (!hash_equals($expected, $signature)) {
            return response('Firma inválida', 403);
        }

        foreach ($request->input('entry', []) as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                foreach ($change['value']['messages'] ?? [] as $message) {
                    if (($message['type'] ?? '') !== 'text') {
                        continue;
                    }

                    $phone = $message['from'] ?? null;

                    logger()->info('WhatsApp destinatario diagnóstico', [
                        'ultimos_4' => substr((string) $phone, -4),
                        'phone_id' => $change['value']['metadata']['phone_number_id'] ?? null,
                    ]);


                    if (!$phone) {
                        continue;
                    }

                    logger()->info('WhatsApp token webhook', [
                        'fingerprint' => substr(hash('sha256', (string) config('services.whatsapp.access_token')), 0, 12),
                    ]);

                    $response = Http::withToken(
                        config('services.whatsapp.access_token')
                    )->post(
                        'https://graph.facebook.com/v27.0/' .
                        config('services.whatsapp.phone_number_id') .
                        '/messages',
                        [
                            'messaging_product' => 'whatsapp',
                            'to' => $phone,
                            'type' => 'text',
                            'text' => [
                                'body' => app(\App\Services\ReservaWhatsAppService::class)->procesar($phone, $message['text']['body'] ?? ''),
                            ],
                        ]
                    );

                    if ($response->failed()) {
                        logger()->error('Error enviando WhatsApp', [
                            'status' => $response->status(),
                            'error' => $response->json('error.message'),
                            'code' => $response->json('error.code'),
                        ]);
                    }
                }
            }
        }

        logger()->info('Webhook de WhatsApp recibido', [
            'object' => $request->input('object'),
        ]);

        return response()->json(['status' => 'received']);
    }
}
