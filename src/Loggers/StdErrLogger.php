<?php

namespace Donchev\Log\Loggers;

use Psr\Log\LogLevel;

class StdErrLogger extends FileLogger
{
    /**
     * StdErrLogger constructor.
     *
     * @param string $minLogLevel
     * @param array $config
     */
    public function __construct(string $minLogLevel = LogLevel::DEBUG, array $config = [])
    {
        parent::__construct("php://stderr", $minLogLevel, $config);
    }
}
