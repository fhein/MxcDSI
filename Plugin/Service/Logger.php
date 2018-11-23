<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 23.11.2018
 * Time: 12:11
 */

namespace MxcDropshipInnocigs\Plugin\Service;


use Throwable;
use Traversable;
use Zend\Log\LoggerInterface;

class Logger implements LoggerInterface
{
    /**
     * @var \Zend\Log\Logger $log
     */
    protected $log;

    public function __construct(\Zend\Log\Logger $log) {
        $this->log = $log;
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return LoggerInterface
     */
    public function emerg($message, $extra = [])
    {
        $this->log->emerg($message, $extra);
        return $this;
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return LoggerInterface
     */
    public function alert($message, $extra = [])
    {
        $this->log->alert($message, $extra);
        return $this;
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return LoggerInterface
     */
    public function crit($message, $extra = [])
    {
        $this->log->crit($message, $extra);
        return $this;
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return LoggerInterface
     */
    public function err($message, $extra = [])
    {
        $this->log->err($message, $extra);
        return $this;
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return LoggerInterface
     */
    public function warn($message, $extra = [])
    {
        $this->log->warn($message, $extra);
        return $this;
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return LoggerInterface
     */
    public function notice($message, $extra = [])
    {
        $this->log->notice($message, $extra);
        return $this;
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return LoggerInterface
     */
    public function info($message, $extra = [])
    {
        $this->log->info($message, $extra);
        return $this;
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return LoggerInterface
     */
    public function debug($message, $extra = [])
    {
        return $this->log->debug($message, $extra);
    }

    public function enter(string $function = null) {
        return $this->logAction(true, $function);
    }

    public function leave($function = null) {
        return $this->logAction(false, $function);
    }

    public function except(Throwable $e, bool $logTrace = true, bool $rethrow = true) {
        $this->log->emerg(sprintf('%s: %s', get_class($e), $e->getMessage()));
        if ($logTrace) $this->log->emerg('Call stack: ' . PHP_EOL . $e->getTraceAsString());
        if ($rethrow) throw($e);
        return $this;
    }

    protected function logAction(bool $start = true, string $function = null) {
        $marker = '***********************';
        $text = $start ? 'START:' : 'STOP:';
        $function = $function ?? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        $this->log->notice(sprintf('%s %s %s %s', $marker, $text, $function, $marker));
    }

    public function getCaller(int $levelUp = 1) {
        return debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $levelUp + 2)[$levelUp + 1]['function'];
    }

    public function getInnerLog() {
        return $this->log;
    }
}