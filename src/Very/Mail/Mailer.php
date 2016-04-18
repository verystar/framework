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
     * Send a new message when only a raw text part.
     *
     * @param  string $text
     * @param  mixed  $callback
     *
     * @return int
     */
    public function raw($text, $callback) {
        return $this->send(array('raw' => $text), [], $callback);
    }

    /**
     * Send a new message when only a plain part.
     *
     * @param  string $view
     * @param  array  $data
     * @param  mixed  $callback
     *
     * @return int
     */
    public function plain($view, array $data, $callback) {
        return $this->send(array('text' => $view), $data, $callback);
    }

    /**
     * Send a new message using a view.
     *
     * @param  string|array    $view
     * @param  array           $data
     * @param  \Closure|string $callback
     *
     * @return mixed
     */
    public function send($view, array $data, $callback) {
        // First we need to parse the view, which could either be a string or an array
        // containing both an HTML and plain text versions of the view which should
        // be used when sending an e-mail. We will extract both of them out here.
        list($view, $plain, $raw) = $this->parseView($view);

        $data['message'] = $message = $this->createMessage();

        $this->callMessageBuilder($callback, $message);

        // Once we have retrieved the view content for the e-mail we will set the body
        // of this message using the HTML type, which will provide a simple wrapper
        // to creating view based emails that are able to receive arrays of data.
        $this->addContent($message, $view, $plain, $raw, $data);

        $message = $message->getSwiftMessage();

        return $this->sendSwiftMessage($message);
    }

    /**
     * Add the content to a given message.
     *
     * @param  \Very\Mail\Message $message
     * @param  string             $view
     * @param  string             $plain
     * @param  string             $raw
     * @param  array              $data
     *
     * @return void
     */
    protected function addContent($message, $view, $plain, $raw, $data) {
        if (isset($view)) {
            $message->setBody($this->getView($view, $data), 'text/html');
        }

        if (isset($plain)) {
            $message->addPart($this->getView($plain, $data), 'text/plain');
        }

        if (isset($raw)) {
            $message->addPart($raw, 'text/plain');
        }
    }

    /**
     * Parse the given view name or array.
     *
     * @param  string|array $view
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseView($view) {
        if (is_string($view))
            return [$view, null, null];

        // If the given view is an array with numeric keys, we will just assume that
        // both a "pretty" and "plain" view were provided, so we will return this
        // array as is, since must should contain both views with numeric keys.
        if (is_array($view) && isset($view[0])) {
            return [$view[0], $view[1], null];
        }

        // If the view is an array, but doesn't contain numeric keys, we will assume
        // the the views are being explicitly specified and will extract them via
        // named keys instead, allowing the developers to use one or the other.
        elseif (is_array($view)) {
            return [
                array_get($view, 'html'),
                array_get($view, 'text'),
                array_get($view, 'raw'),
            ];
        }

        throw new InvalidArgumentException("Invalid view.");
    }

    /**
     * Send a Swift Message instance.
     *
     * @param  \Swift_Message $message
     *
     * @return void
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
     * Call the provided message builder.
     *
     * @param  \Closure|string    $callback
     * @param  \Very\Mail\Message $message
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function callMessageBuilder($callback, $message) {
        if ($callback instanceof Closure) {
            return call_user_func($callback, $message);
        } elseif (is_string($callback)) {
            return $this->container->make($callback)->mail($message);
        }

        throw new InvalidArgumentException("Callback is not valid.");
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
