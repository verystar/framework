<?php
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 4/15/16 18:11
 */

namespace Very\Mail;

use Closure;
use Swift_Mailer;
use Swift_Message;
use InvalidArgumentException;

class Mailer {

    /**
     * The Swift Mailer instance.
     *
     * @var \Swift_Mailer
     */
    protected $swift;

    /**
     * The global from address and name.
     *
     * @var array
     */
    protected $from;

    /**
     * The log writer instance.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Indicates if the actual sending is disabled.
     *
     * @var bool
     */
    protected $pretending = false;

    /**
     * Array of failed recipients.
     *
     * @var array
     */
    protected $failedRecipients = array();

    /**
     * Array of parsed views containing html and text view name.
     *
     * @var array
     */
    protected $parsedViews = array();

    protected $message;

    /**
     * Create a new Mailer instance.
     */
    public function __construct() {
        $config = config('mail');

        switch ($config['driver']) {
            case "smtp":
                $transport = TransportManager::createSmtpDriver();
                break;
            case "sendmail":
                $transport = TransportManager::createSendmailDriver();
                break;
            default:
                $transport = TransportManager::createMailDriver();
        }

        $this->swift = Swift_Mailer::newInstance($transport);
    }

    /**
     * Set the global from address and name.
     *
     * @param  string $address
     * @param  string $name
     *
     * @return void
     */
    public function alwaysFrom($address, $name = null) {
        $this->from = compact('address', 'name');
    }

    /**
     * Send a new message using a view.
     *
     * @param  \Closure|string $callback
     *
     * @return mixed
     */
    public function send($callback) {

        if ($callback instanceof Closure) {
            $message = $this->createMessage();
            call_user_func($callback, $message);
        } else {
            throw new InvalidArgumentException("Callback is not valid.");
        }

        return $this->sendSwiftMessage($message);
    }

    /**
     * Send a Swift Message instance.
     *
     * @param  \Swift_Message $message
     *
     * @return mixed
     */
    protected function sendSwiftMessage($message) {
        if (!$this->pretending) {
            return $this->swift->send($message, $this->failedRecipients);
        } elseif (isset($this->logger)) {
            $this->logMessage($message);
        }
    }

    /**
     * Log that a message was sent.
     *
     * @param  \Swift_Message $message
     *
     * @return void
     */
    protected function logMessage($message) {
        $emails = implode(', ', array_keys((array)$message->getTo()));
        logger()->info("Pretending to mail message to: {$emails}");
    }

    /**
     * Create a new message instance.
     *
     * @return \Very\Mail\Message
     */
    protected function createMessage() {
        $message = new Message(new Swift_Message);

        // If a global from address has been specified we will set it on every message
        // instances so the developer does not have to repeat themselves every time
        // they create a new message. We will just go ahead and push the address.
        if (isset($this->from['address'])) {
            $message->from($this->from['address'], $this->from['name']);
        }

        return $message;
    }

    /**
     * Tell the mailer to not really send messages.
     *
     * @param  bool $value
     *
     * @return void
     */
    public function pretend($value = true) {
        $this->pretending = $value;
    }

    /**
     * Check if the mailer is pretending to send messages.
     *
     * @return bool
     */
    public function isPretending() {
        return $this->pretending;
    }

    /**
     * Get the Swift Mailer instance.
     *
     * @return \Swift_Mailer
     */
    public function getSwiftMailer() {
        return $this->swift;
    }

    /**
     * Get the array of failed recipients.
     *
     * @return array
     */
    public function failures() {
        return $this->failedRecipients;
    }

    /**
     * Set the Swift Mailer instance.
     *
     * @param  \Swift_Mailer $swift
     *
     * @return void
     */
    public function setSwiftMailer($swift) {
        $this->swift = $swift;
    }
}