<?php namespace Very\Mail;

use Swift_SmtpTransport as SmtpTransport;
use Swift_MailTransport as MailTransport;
use Swift_SendmailTransport as SendmailTransport;

class TransportManager {

    /**
     * Create an instance of the SMTP Swift Transport driver.
     *
     * @return \Swift_SmtpTransport
     */
    public static function createSmtpDriver() {
        $config = config('mail');

        // The Swift SMTP transport instance will allow us to use any SMTP backend
        // for delivering mail such as Sendgrid, Amazon SES, or a custom server
        // a developer has available. We will just pass this configured host.
        $transport = SmtpTransport::newInstance(
            $config['host'], $config['port']
        );

        if (isset($config['encryption'])) {
            $transport->setEncryption($config['encryption']);
        }

        // Once we have the transport we will check for the presence of a username
        // and password. If we have it we will set the credentials on the Swift
        // transporter instance so that we'll properly authenticate delivery.
        if (isset($config['username'])) {
            $transport->setUsername($config['username']);

            $transport->setPassword($config['password']);
        }

        return $transport;
    }

    /**
     * Create an instance of the Sendmail Swift Transport driver.
     *
     * @return \Swift_SendmailTransport
     */
    public static function createSendmailDriver() {
        $command = config('mail', 'sendmail');

        return SendmailTransport::newInstance($command);
    }

    /**
     * Create an instance of the Mail Swift Transport driver.
     *
     * @return \Swift_MailTransport
     */
    public static function createMailDriver() {
        return MailTransport::newInstance();
    }
}
