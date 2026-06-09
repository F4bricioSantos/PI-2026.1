<?php

namespace Backend\Services;

class EmailService
{
    public static string $lastError = '';

    public static function enviar(string $toEmail, string $toName, string $assunto, string $corpoHTML): bool
    {
        $apiKey = getenv('BREVO_API_KEY') ?: '';

        if (!$apiKey) {
            self::$lastError = 'BREVO_API_KEY nao configurada';
            return false;
        }

        $payload = json_encode([
            'sender' => [
                'email' => 'fabriciosantos43@aluno.unifapce.edu.br',
                'name'  => 'ReformAí',
            ],
            'to' => [
                ['email' => $toEmail, 'name' => $toName],
            ],
            'subject'     => $assunto,
            'htmlContent' => $corpoHTML,
        ]);

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'api-key: ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        self::$lastError = $curlError ?: "HTTP $httpCode: " . substr($response, 0, 200);
        return false;
    }
}
