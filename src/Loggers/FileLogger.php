<?php

namespace Donchev\Log\Loggers;

use Donchev\Log\AbstractLogger;
use Psr\Log\LogLevel;
use RuntimeException;

class FileLogger extends AbstractLogger
{
    /**
     * @var resource
     */
    private $fileHandle;

    /**
     * @var bool
     */
    private $lock = true;

    /**
     * FileLogger constructor.
     *
     * @param $file
     * @param string $minLogLevel
     * @param array $config
     */
    public function __construct(
        string $file,
        string $minLogLevel = LogLevel::DEBUG,
        array $config = []
    ) {
        parent::__construct($minLogLevel, $config);

        if (preg_match('/^php:\/\//i', $file)) {
            $this->lock = false;
        } else {
            $file = $this->configFilePrefix() . $file;
        }

        $this->fileHandle = fopen($file, 'a');

        if (!$this->fileHandle) {
            throw new RuntimeException(sprintf('Could not open "%s" for writing', $file));
        }
    }

    public function __destruct()
    {
        if ($this->fileHandle) {
            fclose($this->fileHandle);
        }
    }

    protected function write(string $line)
    {
        if ($this->lock) {
            flock($this->fileHandle, LOCK_EX);
        }

        fwrite($this->fileHandle, $line . PHP_EOL);
        fflush($this->fileHandle);

        if ($this->lock) {
            flock($this->fileHandle, LOCK_UN);
        }
    }
}
