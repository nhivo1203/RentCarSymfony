<?php

namespace App\Services;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SendMailService
{
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    /**
     * @throws Exception
     */
    public function sendSimpleMail(string $email, string $subject, string $code): void
    {
        $mail = new PHPMailer(true);
        try {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->isSMTP();
            $mail->Host = $this->params->get('zoho.mail.host');
            $mail->SMTPAuth = true;
            $mail->Username = $this->params->get('zoho.mail.username');
            $mail->Password = $this->params->get('zoho.mail.password');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->params->get('zoho.mail.port');

            //Recipients
            $mail->setFrom($this->params->get('zoho.mail.username'), 'NhiVo RentCar');
            $mail->addAddress($email);

            //Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = "This is your code: <b>$code</b>";
            $mail->send();
        } catch (Exception $e) {
            throw new Exception("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
    }
}