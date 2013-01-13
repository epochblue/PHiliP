<?php
namespace Philip\Socket;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Monolog\Logger;
use Philip\IRC\Request;
use Philip\IRC\Event;

class Receiver
{
    /** @var \Symfony\Component\EventDispatcher\EventDispatcher */
    private $dispatcher;

    /** @var \Monolog\Logger */
    protected $logger;

    /**
     * @param \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher
     * @param \Monolog\Logger $logger
     */
    public function __construct(EventDispatcher $dispatcher = null, Logger $logger = null)
    {
        $this->dispatcher = $dispatcher ?: new EventDispatcher();
        $this->logger = $logger;
    }

    /**
     * @param string $raw
     * @param resource $socket
     */
    public function receive($raw, $socket)
    {
        $request   = new Request($raw);
        $cmd       = strtolower($request->getCommand());

        if ($cmd === 'privmsg') {
            $event_name = 'message.' . ($request->isPrivateMessage() ? 'private' : 'channel');
        } else {
            $event_name = 'server.' . $cmd;
        }

        if ($request->getSendingUser() !== $this->config['nick']) {
            $event = new Event($request);
            $this->getDispatcher()->dispatch($event_name, $event);
            $responses = $event->getResponses();

            if (!empty($responses)) {
                $this->send($responses, $socket);
            }
        }
    }

    /**
     * @param \Philip\IRC\Response[] $responses
     * @param resource $socket
     * @param int $interval
     * @param int $delay
     */
    public function send($responses, $socket, $interval = null, $delay = null)
    {
        if (!is_array($responses)) {
            $responses = array($responses);
        }

        foreach ($responses as $response) {
            $response .= "\r\n";
            fwrite($socket, $response);

            if (null !== $this->logger) {
                $this->logger->debug('<-- ' . $response);
            }

            if (null !== $interval) {
                usleep($interval);
            }
        }

        if (null !== $delay) {
            usleep($delay);
        }
    }

    public function getDispatcher()
    {
        return $this->dispatcher;
    }
}
