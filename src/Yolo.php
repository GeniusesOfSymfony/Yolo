<?php

namespace Gos\Component\Yolo;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Yolo implements LoggerAwareInterface
{
    const DEFAULT_MAX_RETRY = 5;
    const DEFAULT_TIMEOUT = 5;

    /** @var callable  */
    protected $process;

    /** @var  array */
    protected $args;

    /** @var int  */
    protected $maxRetry;

    /** @var int  */
    protected $timeout;

    /** @var array  */
    protected $allowedExceptions;

    /** @var  LoggerInterface */
    protected $logger;

    /** @var  array */
    protected $logInfos;

    /**
     * @param callable $process
     * @param array    $args
     * @param null     $maxRetry
     * @param null     $timeout
     * @param array    $allowedExceptions
     */
    public function __construct(
        callable $process,
        Array $args = array(),
        $maxRetry = self::DEFAULT_MAX_RETRY,
        $timeout = self::DEFAULT_TIMEOUT,
        Array $allowedExceptions = array()
    ) {
        if (null === $maxRetry) {
            $maxRetry = self::DEFAULT_MAX_RETRY;
        }

        if (null === $timeout) {
            $timeout = self::DEFAULT_TIMEOUT;
        }

        $this->process = $process;
        $this->args = $args;
        $this->timeout = $timeout;
        $this->maxRetry = $maxRetry;
        $this->allowedExceptions = $allowedExceptions;
        $this->logger = new NullLogger();
        $this->logInfos = array();

        $this->extractLogInfos();
    }

    protected function extractLogInfos()
    {
        if (is_array($this->process)) {
            if (is_object($this->process[0])) {
                $reflect = new \ReflectionClass($this->process[0]);
                $this->logInfos['callable'] = $reflect->getShortName();
            }
        }

        if (is_string($this->process)) {
            $dis = explode('::', $this->process, 1);
            $this->logInfos['callable'] = $dis[0];
        }
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return mixed
     *
     * @throws YoloException
     * @throws \Exception
     */
    public function run()
    {
        for ($i = 1; $i <= $this->maxRetry; $i++) {
            try {
                return call_user_func_array($this->process, $this->args);
            } catch (\Exception $e) {
                if (in_array(get_class($e), $this->allowedExceptions)) {
                    throw $e;
                }
            }

            if ($i < $this->maxRetry) {
                sleep($this->timeout);
                $this->logger->warning(sprintf('[%s] Can\'t perform action, retrying [%s/%s]', $this->logInfos['callable'], $i, $this->maxRetry));
            } else {
                $this->logger->error(sprintf('[%s] %s retry has failed, aborting [%s/%s]', $this->logInfos['callable'], $i, $this->maxRetry));
                $retryException = new YoloException('', 0, $e);
                $retryException->setRetry($this->maxRetry);
                $retryException->setTimeout($this->timeout);
                throw $retryException;
            }
        }
    }

    /**
     * @param $callback
     *
     * @return mixed|null
     *
     * @throws \Exception
     */
    public function tryUntil($callback)
    {
        try {
            return call_user_func_array($this->process, $this->args);
        } catch (\Exception $e) {
            if (in_array(get_class($e), $this->allowedExceptions)) {
                throw $e;
            }
        }

        $this->logger->warning(sprintf('[%s] Waiting until callback return a success', $this->logInfos['callable']));

        $startTime = time();
        $retry = 1;

        for (;;) {
            if ($callback instanceof YoloInterface) {
                $available = $callback->isAvailable();
            } else {
                $available = call_user_func($callback);
            }

            if (true === $available) {
                try {
                    return call_user_func_array($this->process, $this->args);
                } catch (\Exception $e) {
                    $this->logger->warning(sprintf('[%s] Can\'t perform action [%s/%s]', $this->logInfos['callable'], $retry, $this->maxRetry));

                    if (in_array(get_class($e), $this->allowedExceptions)) {
                        throw $e;
                    }

                    ++$retry;
                }

                if ($retry >= $this->maxRetry) {
                    $this->logger->error(sprintf('[%s] retry has failed, aborting [%s/%s]', $this->logInfos['callable'], $retry, $this->maxRetry));

                    $retryException = new YoloException('', 0, $e);
                    $retryException->setRetry($this->maxRetry);
                    $retryException->setTimeout($this->timeout);
                    throw $retryException;
                }
            } else {
                $this->logger->warning(sprintf('[%s] Can\'t perform action, waiting next tick', $this->logInfos['callable']));
            }

            if ((time() - $startTime) >= $this->timeout) {
                $retryException = new YoloException('Timed out');
                $retryException->setRetry($this->maxRetry);
                $retryException->setTimeout($this->timeout);

                throw $retryException;
            }

            sleep(1);
        }
    }
}
