<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppMessage;
use App\Services\FlujoReservasService;
use Illuminate\Http\Request;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use RuntimeException;
use Throwable;

class WhatsAppFlowController extends Controller
{
    public function handle(Request $request)
    {
        $archivo = storage_path('app/private/whatsapp_flow_private.pem');

        if (! is_readable($archivo)) {
            return response('Clave no disponible', 503);
        }

        $datos = $request->validate([
            'encrypted_aes_key' => 'required|string',
            'encrypted_flow_data' => 'required|string',
            'initial_vector' => 'required|string',
        ]);

        try {
            $clavePrivada = PublicKeyLoader::loadPrivateKey(
                file_get_contents($archivo)
            )->withPadding(RSA::ENCRYPTION_OAEP)
                ->withHash('sha256')
                ->withMGFHash('sha256');

            $claveCifrada = base64_decode($datos['encrypted_aes_key'], true);
            $vector = base64_decode($datos['initial_vector'], true);
            $contenido = base64_decode($datos['encrypted_flow_data'], true);

            if ($claveCifrada === false || $vector === false ||
                $contenido === false || strlen($vector) !== 16 ||
                strlen($contenido) < 16) {
                throw new RuntimeException('Solicitud cifrada inválida');
            }

            $clave = $clavePrivada->decrypt($claveCifrada);

            if (strlen($clave) !== 16) {
                throw new RuntimeException('Clave AES inválida');
            }

            $texto = openssl_decrypt(
                substr($contenido, 0, -16),
                'aes-128-gcm',
                $clave,
                OPENSSL_RAW_DATA,
                $vector,
                substr($contenido, -16)
            );

            if ($texto === false) {
                throw new RuntimeException('No se pudo descifrar');
            }

            $solicitud = json_decode($texto, true, 512, JSON_THROW_ON_ERROR);

            $remitente = trim((string) ($solicitud['phone_number'] ?? $solicitud['data']['phone_number'] ?? ''));

            if ($remitente !== '') {
                WhatsAppMessage::create([
                    'direction' => 'inbound',
                    'phone' => $remitente,
                    'body' => 'flow:'.(string) ($solicitud['action'] ?? ''),
                    'status' => 'flow',
                ]);
            }

            $respuesta = json_encode(
                app(FlujoReservasService::class)
                    ->procesar($solicitud),
                JSON_THROW_ON_ERROR
            );

            $vectorRespuesta = $vector ^ str_repeat("\xff", 16);

            $cifrado = openssl_encrypt(
                $respuesta,
                'aes-128-gcm',
                $clave,
                OPENSSL_RAW_DATA,
                $vectorRespuesta,
                $etiqueta
            );

            if ($cifrado === false) {
                throw new RuntimeException('No se pudo cifrar');
            }

            return response(
                base64_encode($cifrado.$etiqueta),
                200
            )->header('Content-Type', 'text/plain');

        } catch (Throwable $e) {
            report($e);

            return response('Error de cifrado', 421);
        }
    }
}
