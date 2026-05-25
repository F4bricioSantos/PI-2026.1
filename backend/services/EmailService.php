<?php

namespace Backend\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

class EmailService
{
    private static string $smtpHost = 'smtp.gmail.com';
    private static int    $smtpPort = 587;
    
    private static string $smtpUser = 'fabriciosantos43@aluno.unifapce.edu.br'; 
    private static string $smtpPass = 'pnkhuxqupfrmiayw'; 
    
    private static string $senderName  = 'ReformAí';

    public static function enviar(string $toEmail, string $toName, string $assunto, string $corpoHTML): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->SMTPAuth   = true;
            $mail->Username   = self::$smtpUser;
            $mail->Password   = self::$smtpPass;
            $mail->SMTPSecure = 'tls';
            $mail->Host       = self::$smtpHost;
            $mail->Port       = self::$smtpPort;
            $mail->CharSet    = 'UTF-8';

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            $mail->setFrom(self::$smtpUser, self::$senderName);
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body    = $corpoHTML;
            $mail->AltBody = strip_tags($corpoHTML);

            $mail->send();
            return true; 
        } catch (Exception $e) {
            error_log("Erro PHPMailer com Gmail: {$mail->ErrorInfo}");
            return false;
        }
    }
}