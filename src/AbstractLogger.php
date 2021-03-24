<?php

namespace Donchev\Log;

use DateTime;
use Exception;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use RuntimeException;

abstract class AbstractLogger extends \Psr\Log\AbstractLogger
{
    private const CONFIG = [
        'line_format' => '[%s] [%s]: %s %s',
        'date_format' => 'Y-m-d H:i:s T',
        'file_prefix' => '',
        'include_context' => true,
        'log_json' => false,
        'include_stack_trace' => true,
        'one_line_log' => false,
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

        $config = array_intersect_key($config, self::CONFIG);
        $this->config = array_merge(self::CONFIG, $config);
    }

    public function log($level, $message, array $context = [])
    {
        $this->validateLevelName($level);

        if (!$this->minLevelReached($level)) {
            return null;
        }

        $context = $this->validateContextExceptions($context);

        $message = $this->interpolate($message, $context);

        if ($line = $this->formatLine($level, $message, $context)) {
            $this->write($line);
        }
    }

    /**
     * If an Exception object is passed in the context data, it MUST be in the 'exception' key.
     * If Exception object is not in 'exception' key a RuntimeException is thrown.
     *
     * @param array $context
     * @return array
     */
    protected function validateContextExceptions(array $context): array
    {
        foreach ($context as $key => $value) {
            if (is_object($value)
                && (get_class($value) === Exception::class || is_subclass_of($value, Exception::class))) {
                if ($key !== 'exception') {
                    throw new RuntimeException(
                        "If an Exception object is passed in the context data, it MUST be in the 'exception' key."
                    );
                }

                if (!$this->configStackTrace()) {
                    $context[$key] = $this->getExceptionName($value);
                }
            }
        }

        return $context;
    }

    /**
     * Return a short string representation of an Exception object.
     *
     * @param Exception $exception
     * @return string
     */
    protected function getExceptionName(Exception $exception): string
    {
        $text = get_class($exception) . ' Object';
        if ($exception->getMessage() != '') {
            $text .= ' (Message: ' . $exception->getMessage() . ')';
        }

        return $text;
    }

    /**
     * Return log line string ready for print
     *
     * @param $level
     * @param $message
     * @param array $context
     * @param DateTime|null $date
     * @return string
     */
    protected function formatLine($level, $message, array $context, ?DateTime $date = null): string
    {
        $timestamp = $date ? $date->format($this->configDateFormat()) : date($this->configDateFormat());

        $line = [
            'timestamp' => $timestamp,
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context
        ];

        if ($this->configLogJson()) {
            return $this->formatLineAsJson($line);
        }

        return $this->formatLineAsString($line);
    }

    /**
     * Return ready for print log line in json format using json_encode()
     *
     * @param array $items
     * @return string
     */
    protected function formatLineAsJson(array $items): string
    {
        if (!$this->configIncludeContext()) {
            unset($items['context']);
        }

        return json_encode($items);
    }

    /**
     * Return ready for print log line
     *
     * @param array $items
     * @return string
     */
    protected function formatLineAsString(array $items): string
    {
        if (!$this->configIncludeContext() || !$items['context']) {
            $items['context'] = '';
        } else {
            if ($this->configOneLineLog()) {
                $items['context'] = json_encode($items['context']);
            } else {
                $items['context'] = trim(print_r($items['context'], true));
                $items['context'] = PHP_EOL . preg_replace('/Array/i', 'Context:', $items['context'], 1);
            }
        }

        return vsprintf($this->configLineFormat(), $items);
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
     * Return true if current log level is equal or higher than min log level
     *
     * @param string $level
     * @return bool
     */
    protected function minLevelReached(string $level): bool
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
     * Return 'one_line_log' option value
     *
     * @return bool
     */
    protected function configOneLineLog(): bool
    {
        return $this->config['one_line_log'];
    }

    /**
     * @param string $line
     * @return mixed
     */
    abstract protected function write(string $line);
}
