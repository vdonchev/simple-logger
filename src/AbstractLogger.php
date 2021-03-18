<?php

namespace Donchev\Log;

use Exception;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

abstract class AbstractLogger extends \Psr\Log\AbstractLogger
{
    private const CONFIG = [
        'line_format' => '[%s] [%s]: %s %s',
        'date_format' => 'Y-m-d H:i:s T',
        'file_prefix' => '',
        'include_context' => true,
        'log_json' => false,
        'include_stack_trace' => true,
    ];

    private const LOG_LEVEL = [
        LogLevel::EMERGENCY => 7,
        LogLevel::ALERT => 6,
        LogLevel::CRITICAL => 5,
        LogLevel::ERROR => 4,
        LogLevel::WARNING => 3,
        LogLevel::NOTICE => 2,
        LogLevel::INFO => 1,
        LogLevel::DEBUG => 0,
    ];

    /**
     * @var string
     */
    private $minLevel;

    /**
     * @var array
     */
    private $config;

    /**
     * AbstractLogger constructor.
     *
     * @param string $minLevel
     * @param array $config
     */
    public function __construct(
        string $minLevel = LogLevel::DEBUG,
        array $config = []
    ) {
        $this->validateLevelName($minLevel);

        $this->minLevel = $minLevel;
        $this->config = array_merge(self::CONFIG, $config);
    }

    public function log($level, $message, array $context = [])
    {
        if ($line = $this->build($level, $message, $context)) {
            $this->write($line);
        }
    }

    /**
     * Return log line ready for print
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return string|null
     */
    protected function build(string $level, string $message, array $context): ?string
    {
        if (!$this->validateMinLevel($level)) {
            return null;
        }

        $message = $this->buildMessage($level, $message, $context);
        $context = $this->buildContext($context);

        return $this->formatLine($level, $message, $context);
    }

    /**
     * Return message string ready for print
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function buildMessage(string $level, string $message, array $context): string
    {
        $this->validateLevelName($level);

        return $this->interpolate($message, $context);
    }

    /**
     * Return context array ready for print
     *
     * @param array $context
     * @return array
     */
    protected function buildContext(array $context): array
    {
        if ($this->configIncludeContext() === false) {
            return $context;
        }

        foreach ($context as $key => $msg) {
            /** String */
            if (!is_object($msg)) {
                continue;
            }

            /** Exception */
            if ($key === 'exception' && is_subclass_of($msg, Exception::class)) {
                if ($this->configStackTrace()) {
                    $context[$key] = $msg->__toString();
                } else {
                    $context[$key] = get_class($msg) . ': ' . $msg->getMessage();
                }

                continue;
            }

            /** Object that implements __toString */
            if (method_exists($msg, '__toString')) {
                $context[$key] = $msg->__toString();
            }
        }

        return $context;
    }

    /**
     * Interpolate string according to PSR-3 Log interpolation requirements
     *
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        return strtr($message, $replace);
    }


    /**
     * Return log line string ready for print
     *
     * @param $level
     * @param $message
     * @param array $context
     * @return string
     */
    protected function formatLine($level, $message, array $context): string
    {
        $timestamp = date($this->configDateFormat());

        $logLine = [
            'time' => $timestamp,
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context
        ];

        if ($this->configLogJson()) {
            if (!$this->configIncludeContext()) {
                unset($logLine['context']);
            }

            return json_encode($logLine);
        }

        if (!$this->configIncludeContext() || !$logLine['context']) {
            $logLine['context'] = '';
        } else {
            $context = trim(print_r($context, true));
            $logLine['context'] = PHP_EOL . preg_replace('/Array/i', 'Context:', $context, 1);
        }

        return vsprintf($this->configLineFormat(), $logLine);
    }

    /**
     * Return true if current log level is equal or higher than min log level
     *
     * @param string $level
     * @return bool
     */
    protected function validateMinLevel(string $level): bool
    {
        return self::LOG_LEVEL[strtolower($level)] >= self::LOG_LEVEL[strtolower($this->minLevel)];
    }

    /**
     * Validate if level is allowed for use.
     *
     * @param string $level
     */
    protected function validateLevelName(string $level)
    {
        if (!defined(LogLevel::class . '::' . strtoupper($level))) {
            throw new InvalidArgumentException(sprintf("Warn '%s' level does not exists", $level));
        }
    }

    /**
     * Return 'line_format' option value
     *
     * @return string
     */
    protected function configLineFormat(): string
    {
        return $this->config['line_format'];
    }

    /**
     * Return 'date_format' option value
     *
     * @return string
     */
    protected function configDateFormat(): string
    {
        return $this->config['date_format'];
    }

    /**
     * Return 'include_context' option value
     *
     * @return bool
     */
    protected function configIncludeContext(): bool
    {
        return $this->config['include_context'];
    }

    /**
     * Return 'file_prefix' option value
     *
     * @return string
     */
    protected function configFilePrefix(): string
    {
        return $this->config['file_prefix'];
    }

    /**
     * Return 'log_json' option value
     *
     * @return bool
     */
    protected function configLogJson(): bool
    {
        return $this->config['log_json'];
    }

    /**
     * Return 'exception_trace' option value
     *
     * @return bool
     */
    protected function configStackTrace(): bool
    {
        return $this->config['include_stack_trace'];
    }

    /**
     * @param string $line
     * @return mixed
     */
    abstract protected function write(string $line);
}
