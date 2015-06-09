<?php

namespace Gos\Component\Yolo\Callback;

class PingBack
{
    /** @var  string */
    protected $host;

    /** @var  int */
    protected $port;

    /** @var  string */
    protected $protocol;

    /**
     * @param string $host
     * @param int    $port
     * @param string $protocol
     */
    public function __construct($host, $port, $protocol = 'tcp')
    {
        $this->host = $host;
        $this->port = $port;
        $this->protocol = $protocol;
    }

    /**
     * @param int $timeout
     *
     * @return array|bool
     */
    public function ping($timeout = 1)
    {
        if ($socket = @fsockopen($this->protocol . '://' . $this->host, $this->port, $errCode, $errStr, $timeout)) {
            fclose($socket);

            return true;
        }

        return false;
    }

    /**
     * @param int $timeout
     *
     * @return array|bool
     */
    public function __invoke($timeout = 1)
    {
        return $this->ping($timeout);
    }
}
