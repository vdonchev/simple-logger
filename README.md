# Simple Logger

A simple [PSR-3](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md) compliant PHP
logging library.

## Installation

Requires PHP 8.4.1+ and `psr/log` 3.x.

`composer require donchev/simple-logger:^3.0`

## Upgrading from 2.x to 3.x

Version 3.0 requires PHP 8.4.1 or newer and `psr/log` 3.0.2 or newer within the 3.x series.
Update your application's PHP runtime and dependency constraints before upgrading.

If your custom logger overrides `log()`, its signature must be compatible with PSR-3 3.x:

```php
public function log($level, string|\Stringable $message, array $context = []): void
```

Messages can be strings or objects implementing `\Stringable`. Logging methods return `void`.

See [CHANGELOG.md](CHANGELOG.md) for release notes.

## Simple Usage

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$logger = new \Donchev\Log\Loggers\FileLogger('file.log');

$logger->debug('Log me');
```

##### Output

```
[2021-03-18 10:03:11 CET] [DEBUG]: Log me 
```

## Advanced usage

```php 
<?php

require_once __DIR__ . '/vendor/autoload.php';

$config = [
    'line_format' => '[%s] [%s]: %s %s',
    'date_format' => 'Y-m-d H:i:s T',
    'file_prefix' => 'pre_',
    'include_context' => true,
    'log_json' => false,
    'include_stack_trace' => true,
    'one_line_log' => false,
];

$logger = new \Donchev\Log\Loggers\FileLogger('file.log', \Psr\Log\LogLevel::INFO, $config);

$logger->debug('This message will not be logged in');
$logger->info(
    'Some cool message',
    [
        'Additional info' => 'I am the additional info',
        'An array of info' => [
            'Key A' => 'Content',
            'Key B' => [1, 2, 3]
        ],
        'An object' => new DateTime(),
    ]
);

$logger->log(
    \Psr\Log\LogLevel::WARNING,
    'Some cool warning here',
    [
        'exception' => new RuntimeException('Just happened')
    ]
);
```

##### Output:

```
[2021-03-18 10:01:20 CET] [INFO]: Some cool message 
Context:
(
    [Additional info] => I am the additional info
    [An array of info] => Array
        (
            [Key A] => Content
            [Key B] => Array
                (
                    [0] => 1
                    [1] => 2
                    [2] => 3
                )

        )

    [An object] => DateTime Object
        (
            [date] => 2021-03-18 10:01:20.256324
            [timezone_type] => 3
            [timezone] => Europe/Berlin
        )

)
[2021-03-18 10:01:20 CET] [WARNING]: Some cool warning here 
Context:
(
    [exception] => RuntimeException: Just happened in C:\dev\simple-logger-test\index.php:24
Stack trace:
#0 {main}
)
```

## Available Loggers

There are 5 different logger classes to choose from:

```
\Donchev\Log\Loggers\FileLogger
\Donchev\Log\Loggers\OutputLogger
\Donchev\Log\Loggers\StdOutLogger
\Donchev\Log\Loggers\StdErrLogger
\Donchev\Log\Loggers\NullLogger
```

## Minimum logging level

You can set a minimum logging level through the constructor by passing a string constant such as `\Psr\Log\LogLevel::INFO`. If set,
messages with lower level priority will not be logged.

_Log levels priorities:_

```
LogLevel::EMERGENCY => 7,
LogLevel::ALERT => 6,
LogLevel::CRITICAL => 5,
LogLevel::ERROR => 4,
LogLevel::WARNING => 3,
LogLevel::NOTICE => 2,
LogLevel::INFO => 1,
LogLevel::DEBUG => 0,
```

## Log message interpolation

You can use placeholders in your messages as [described](https://www.php-fig.org/psr/psr-3/#12-message) in the **PSR-3**
standard.

### Example:

```php
$logger = new FileLogger('file.log');
$logger->info(
    'Here comes the placeholder: {foo}!',
    ['foo' => 'Hi there from within the context']
);
```

##### Output:

```
[2021-03-18 10:43:56 CET] [INFO]: Here comes the placeholder: Hi there from within the context! 
Context:
(
    [foo] => Hi there from within the context
)
```

## Logger Options

You can pass an array of options through the constructor.

### Example:

```php
$logger = new \Donchev\Log\Loggers\OutputLogger(\Psr\Log\LogLevel::WARNING, [
    'file_prefix' => '',
    'include_context' => true,
    'log_json' => false,
    'one_line_log' => true,
]);
```

#### Available options

|Option Name|Default Value|Description|
|-----------|-------------|-----------|
|line_format|[%s] [%s]: %s %s|Passed to `vsprintf()` with timestamp, level, message, and context in that order. Use four `%s` placeholders to include all fields. The logger does not validate the placeholder count.|
|date_format|Y-m-d H:i:s T|Any [php datetime format](https://www.php.net/manual/en/datetime.format.php).|
|file_prefix|none|Prepended to the entire supplied file path when `FileLogger` is used. No prefix is added by default. Ignored for `php://` streams.|
|include_context|true|By default, context is written with each log message. If set to false, it will not write the context.|
|log_json|false|If set to true, all log messages will be written as json objects using `json_encode()`.|
|include_stack_trace|true|An `Exception` under the `exception` context key includes its full stack trace in the default text output. If set to false, it is replaced with the exception class name and message, if present.|
|one_line_log|false|If set to true, entire context will be encoded to json using `json_encode()`. This config option applies only when `log_json` config is set to `false`|

## Author

[Donchev](https://github.com/vdonchev)

## License

The MIT License (MIT)

Copyright (c) 2021 Donchev

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit
persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
