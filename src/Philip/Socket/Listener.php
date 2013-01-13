<?php
namespace Philip\Socket;

use Monolog\Logger;

class Listener
{
    /** @var resource $socket */
    private $socket;

    /** @var \Philip\Socket\Receiver */
    private $receiver;

    /** @var \Monolog\Logger */
    protected $logger;

    public function __construct(Receiver $receiver = null, Logger $logger = null)
    {
        $this->receiver = $receiver ?: new Receiver();
        $this->logger = $logger;
    }

    private function open($hostname, $port = 6667)
    {
        stream_set_blocking(STDIN, 0);
        $this->socket = fsockopen($hostname, $port);

        return (bool) $this->socket;
    }

    private function listen($interval = null, $delay = null)
    {
        do {
            $data = fgets($this->socket, 512);

            if (!empty($data)) {
                $this->receive($data, $interval, $delay);
            }
        } while (!feof($this->socket));
    }

    private function receive($raw, $interval = null, $delay = null)
    {
        $this->getReceiver()->receive($raw, $this->socket, $interval, $delay);
    }

    /**
     * @return Receiver
     */
    public function getReceiver()
    {
        return $this->receiver;
    }

    public function close()
    {
        if (isset($this->socket)) {
            fclose($this->socket);
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
