<?php

namespace Gos\Component\Yolo;

class YoloException extends \Exception
{
    /** @var  int */
    protected $timeout;

    /** @var  int */
    protected $retry;

    /**
     * @param int $retry
     */
    public function setRetry($retry)
    {
        $this->retry = $retry;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }
}
