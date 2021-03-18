<?php

namespace Donchev\Log\Loggers;

use Donchev\Log\AbstractLogger;
use Psr\Log\LogLevel;

class NullLogger extends AbstractLogger
{
    /**
     * NullLogger constructor.
     *
     * @param string $minLevel
     * @param array $config
     */
    public function __construct(string $minLevel = LogLevel::DEBUG, array $config = [])
    {
        parent::__construct($minLevel, $config);
    }

    /**
     * Basically it does not print anything anywhere.
     *
     * @param string $line
     */
    protected function write(string $line)
    {
    }
}
