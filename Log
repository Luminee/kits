<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Log
{
    protected $path;

    protected $loggers = [];

    public function __construct($daily = false)
    {
        $path = $daily ? '/' . date('Y-m-d') : '';
        $this->path = storage_path() . '/logs' . $path;
    }

    protected function getLogger($name)
    {
        if (isset($this->loggers[$name])) return $this->loggers[$name];
        return $this->setLogger($name);
    }

    /**
     * @param $name
     * @return Logger
     * @throws
     */
    protected function setLogger($name)
    {
        $logger = new Logger($name);
        $logger->pushHandler(new StreamHandler($this->path . '/' . $name . '.log'));
        $this->loggers[$name] = $logger;
        return $logger;
    }

    protected function logMessage($method, $module, $message = null)
    {
        if (is_null($message)) list($module, $message) = ['local', $module];
        $this->getLogger($module)->$method($message);
    }

    /**
     * @param $module
     * @param null $message
     */
    public function debug($module, $message = null)
    {
        $this->logMessage('debug', $module, $message);
    }

    /**
     * @param $module
     * @param null $message
     */
    public function info($module, $message = null)
    {
        $this->logMessage('info', $module, $message);
    }

    /**
     * @param $module
     * @param null $message
     */
    public function notice($module, $message = null)
    {
        $this->logMessage('notice', $module, $message);
    }

    /**
     * @param $module
     * @param null $message
     */
    public function warning($module, $message = null)
    {
        $this->logMessage('warning', $module, $message);
    }

    /**
     * @param $module
     * @param null $message
     */
    public function error($module, $message = null)
    {
        $this->logMessage('error', $module, $message);
    }

    /**
     * @param $module
     * @param null $message
     */
    public function critical($module, $message = null)
    {
        $this->logMessage('critical', $module, $message);
    }

    /**
     * @param $module
     * @param null $message
     */
    public function alert($module, $message = null)
    {
        $this->logMessage('alert', $module, $message);
    }

    /**
     * @param $module
     * @param null $message
     */
    public function emergency($module, $message = null)
    {
        $this->logMessage('emergency', $module, $message);
    }

}
