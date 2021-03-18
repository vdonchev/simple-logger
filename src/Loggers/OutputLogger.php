<?php

namespace Donchev\Log\Loggers;

use Psr\Log\LogLevel;

class OutputLogger extends FileLogger
{
    /**
     * OutputLogger constructor.
     *
     * @param string $minLogLevel
     * @param array $config
     */
    public function __construct(string $minLogLevel = LogLevel::DEBUG, array $config = [])
    {
        parent::__construct("php://output", $minLogLevel, $config);
    }
}
