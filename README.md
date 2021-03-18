# Simple Logger
A simple [PSR-3](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md) compliant PHP
logging library.

## Installation
`composer require donchev/simple-logger`

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
There are 5 different logger classes to chose from:
```
\Donchev\Log\Loggers\FileLogger
\Donchev\Log\Loggers\OutputLogger
\Donchev\Log\Loggers\StdOutLogger
\Donchev\Log\Loggers\StdErrLogger
\Donchev\Log\Loggers\NullLogger
```

## Minimum logging level

You can set a minimum logging level through the constructor - passing a `Psr\Log\LogLevel` as an argument. 
If set, messages with lower level priority will not be logged.

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
You can use placeholders in your messages as [described](https://www.php-fig.org/psr/psr-3/#12-message) in the **PSR-3** standard. 
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
]);
```
#### Available options
|Option Name|Default Value|Description|
|-----------|-------------|-----------|
|line_format|[%s] [%s]: %s %s|The value of line_format is passed to `sprintf()`. You need to specify exactly 4 placeholders `%s` for a format to be considered valid.
|date_format|Y-m-d H:i:s T|Any [php datetime format](https://www.php.net/manual/en/datetime.format.php).|
|file_prefix|none|It prefix the filename when `FileLogger` is used. By default no prefix is added to the filename.|
|include_context|true|By default, context is written with each log message. If set to false, it will not write the context.|
|log_json|false|If set to true, all log messages will be written as json objects using `json_encode()`.|
|include_stack_trace|true|If you pass a context entry with key named "exception" and value an exception object, then the logger will write a useful exception info, including the full stack trace. If you set this option to false, only the exception name will be written.|

## Author
[Donchev](https://github.com/vdonchev)

## License
The MIT License (MIT)

Copyright (c) 2021 Donchev

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.