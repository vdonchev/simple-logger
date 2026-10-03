<?php

use Donchev\Log\AbstractLogger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class AbstractLoggerTest extends TestCase
{
    protected static function getMethod(string $name): ReflectionMethod
    {
        $class = new ReflectionClass(AbstractLogger::class);
        $method = $class->getMethod($name);

        return $method;
    }

    public static function getLevels(): array
    {
        $levels = new ReflectionClass(LogLevel::class);
        $levels = $levels->getConstants();

        $items = [];
        foreach ($levels as $level) {
            $items[] = [$level];
        }

        return $items;
    }

    public static function getMessagesWithoutPlaceholders(): array
    {
        return [
            ["Are you opening {{{{ the door?"],
            ["Could I { open the door?"],
            ["What room am I in?"],
            ["Leapin' lizards!"],
            ["No problem {{} !That's all !Time is up"],
            ["Will you open the door?"],
            ["China is much bigger } than Japan."],
            ["I was born as a baby."],
            ["He never {{{}}} wants to go on a rollercoaster again!"],
            ["When did you open } the door?"],
        ];
    }

    public static function getMessagesWithPlaceholders(): array
    {
        return [
            ["Are you opening {key} the door?", "Are you opening key the door?"],
            ["Could I open the {door}?", "Could I open the door?"],
            ["What room am I in?", "What room am I in?"],
            ["Leapin' {room} lizards!", "Leapin' room lizards!"],
            ["No problem {{wall} !That's all !Time is up", "No problem {wall !That's all !Time is up"],
            ["Will you open the door?", "Will you open the door?"],
            ["China is much bigger } than Japan.", "China is much bigger } than Japan."],
            ["I was born as a baby.", "I was born as a baby."],
            [
                "He never {{{lucky}}} wants to go on a rollercoaster again!",
                "He never {{lucky}} wants to go on a rollercoaster again!"
            ],
            ["When did you open } the door?", "When did you open } the door?"],
        ];
    }

    public static function getContextWithInvalidExceptions(): array
    {
        return [
            [['not_exception_key' => new InvalidArgumentException()], RuntimeException::class],
            [[new InvalidArgumentException()], RuntimeException::class],
        ];
    }

    public static function getContextWithValidExceptions(): array
    {
        return [
            [['exception' => new InvalidArgumentException()]],
            [[123, 'asd', 'key' => 69, 'exception' => new InvalidArgumentException()]],
        ];
    }

    public static function getMessageArray(): array
    {
        return [
            [
                [
                    'timestamp' => '2021-03-19 13:21:48 CET',
                    'level' => strtoupper(LogLevel::DEBUG),
                    'message' => 'Some Message',
                    'context' => [1, 2, 3]
                ],
                json_encode('[2021-03-19 13:21:48 CET] [DEBUG]: Some Message ' . PHP_EOL
                    . "Context:\n(\n    [0] => 1\n    [1] => 2\n    [2] => 3\n)")
            ],
            [
                [
                    'timestamp' => '2021-03-19 13:21:48 CET',
                    'level' => strtoupper(LogLevel::INFO),
                    'message' => 'Message',
                    'context' => ['key' => 'value']
                ],
                json_encode('[2021-03-19 13:21:48 CET] [INFO]: Message ' . PHP_EOL
                    . "Context:\n(\n    [key] => value\n)")
            ],
        ];
    }

    public static function getMessageArrayForOneLineLog(): array
    {
        return [
            [
                [
                    'timestamp' => '2021-03-19 13:21:48 CET',
                    'level' => strtoupper(LogLevel::DEBUG),
                    'message' => 'Some Message',
                    'context' => [1, 2, 3]
                ],
                '"[2021-03-19 13:21:48 CET] [DEBUG]: Some Message [1,2,3]"'
            ],
            [
                [
                    'timestamp' => '2021-03-19 13:21:48 CET',
                    'level' => strtoupper(LogLevel::INFO),
                    'message' => 'Message',
                    'context' => ['key' => 'value']
                ],
                '"[2021-03-19 13:21:48 CET] [INFO]: Message {\"key\":\"value\"}"'
            ],
        ];
    }

    public static function getMessageArrayForJson(): array
    {
        return [
            [
                [
                    'timestamp' => '2021-03-19 13:21:48 CET',
                    'level' => strtoupper(LogLevel::DEBUG),
                    'message' => 'Some Message',
                    'context' => [1, 2, 3]
                ],
                '{"timestamp":"2021-03-19 13:21:48 CET","level":"DEBUG","message":"Some Message","context":[1,2,3]}'
            ],
            [
                [
                    'timestamp' => '2021-03-19 13:21:48 CET',
                    'level' => strtoupper(LogLevel::INFO),
                    'message' => 'Message',
                    'context' => ['key' => 'value']
                ],
                '{"timestamp":"2021-03-19 13:21:48 CET","level":"INFO","message":"Message","context":{"key":"value"}}'
            ],
        ];
    }

    #[DataProvider('getLevels')]
    public function testValidateLevelNameWithCorrectLevels($level)
    {
        $logger = $this->getAbstractLogger();
        $validateMinLevel = $this->getMethod('validateLevelName');

        $this->assertNull($validateMinLevel->invokeArgs($logger, [$level]));
    }

    #[DataProvider('getLevels')]
    public function testValidateLevelNameWithCorrectLevelsIncorrectCasing($level)
    {
        $logger = $this->getAbstractLogger();
        $validateMinLevel = $this->getMethod('validateLevelName');

        $level = ucwords($level);
        $level[3] = strtoupper($level[3]);

        $this->assertNull($validateMinLevel->invokeArgs($logger, [$level]));
    }

    #[DataProvider('getLevels')]
    public function testValidateLevelNameWithIncorrectLevel($level)
    {
        $logger = $this->getAbstractLogger();
        $validateLevelName = $this->getMethod('validateLevelName');

        $level .= '_fake';

        $this->expectException(InvalidArgumentException::class);
        $validateLevelName->invokeArgs($logger, [$level]);
    }

    public function testMinLeveReachedWithLowerPriorityLevels()
    {
        $logger = $this->getAbstractLogger(LogLevel::ERROR);
        $validateMinLevel = $this->getMethod('minLevelReached');

        $this->assertFalse($validateMinLevel->invokeArgs($logger, [LogLevel::DEBUG]));
        $this->assertFalse($validateMinLevel->invokeArgs($logger, [LogLevel::INFO]));
        $this->assertFalse($validateMinLevel->invokeArgs($logger, [LogLevel::NOTICE]));
        $this->assertFalse($validateMinLevel->invokeArgs($logger, [LogLevel::WARNING]));
    }

    public function testMinLeveReachedWithHigherPriorityLevels()
    {
        $logger = $this->getAbstractLogger(LogLevel::ERROR);
        $validateMinLevel = $this->getMethod('minLevelReached');

        $this->assertTrue($validateMinLevel->invokeArgs($logger, [LogLevel::ERROR]));
        $this->assertTrue($validateMinLevel->invokeArgs($logger, [LogLevel::CRITICAL]));
        $this->assertTrue($validateMinLevel->invokeArgs($logger, [LogLevel::ALERT]));
        $this->assertTrue($validateMinLevel->invokeArgs($logger, [LogLevel::EMERGENCY]));
    }

    #[DataProvider('getMessagesWithoutPlaceholders')]
    public function testInterpolateWithNoPlaceholdersAndNoContext($message)
    {
        $logger = $this->getAbstractLogger();
        $interpolate = $this->getMethod('interpolate');

        $interpolatedMessage = $interpolate->invokeArgs($logger, [$message, []]);

        $this->assertEquals($message, $interpolatedMessage);
    }

    #[DataProvider('getMessagesWithoutPlaceholders')]
    public function testInterpolateWithNoPlaceholdersAndContext($message)
    {
        $logger = $this->getAbstractLogger();
        $interpolate = $this->getMethod('interpolate');

        $interpolatedMessage = $interpolate->invokeArgs($logger, [$message, ['test' => 'Some text']]);

        $this->assertEquals($message, $interpolatedMessage);
    }

    #[DataProvider('getMessagesWithPlaceholders')]
    public function testInterpolateWithPlaceholdersAndNoContext($message, $_expected)
    {
        $logger = $this->getAbstractLogger();
        $interpolate = $this->getMethod('interpolate');

        $interpolatedMessage = $interpolate->invokeArgs($logger, [$message, []]);

        $this->assertEquals($message, $interpolatedMessage);
    }

    #[DataProvider('getMessagesWithPlaceholders')]
    public function testInterpolateWithPlaceholdersAndContext($message, $expected)
    {
        $logger = $this->getAbstractLogger();
        $interpolate = $this->getMethod('interpolate');

        $interpolatedMessage = $interpolate->invokeArgs(
            $logger,
            [
                $message,
                [
                    "key" => "key",
                    "room" => "room",
                    "door" => "door",
                    "wall" => "wall",
                    "lucky" => "lucky"
                ]
            ]
        );

        $this->assertEquals($expected, $interpolatedMessage);
    }

    public function testGetExceptionNameWithEmptyExceptionMessage()
    {
        $logger = $this->getAbstractLogger();
        $getExceptionName = $this->getMethod('getExceptionName');

        $res = $getExceptionName->invokeArgs($logger, [new RuntimeException()]);
        $this->assertEquals('RuntimeException Object', $res);

        $res = $getExceptionName->invokeArgs($logger, [new Exception()]);
        $this->assertEquals('Exception Object', $res);

        $res = $getExceptionName->invokeArgs($logger, [new InvalidArgumentException()]);
        $this->assertEquals('InvalidArgumentException Object', $res);
    }

    public function testGetExceptionNameWithExceptionMessage()
    {
        $logger = $this->getAbstractLogger();
        $getExceptionName = $this->getMethod('getExceptionName');

        $res = $getExceptionName->invokeArgs($logger, [new RuntimeException('Some text')]);
        $this->assertEquals('RuntimeException Object (Message: Some text)', $res);

        $res = $getExceptionName->invokeArgs($logger, [new Exception('Some text')]);
        $this->assertEquals('Exception Object (Message: Some text)', $res);

        $res = $getExceptionName->invokeArgs($logger, [new InvalidArgumentException('Some text')]);
        $this->assertEquals('InvalidArgumentException Object (Message: Some text)', $res);
    }

    public function testValidateContextExceptionsWithoutExceptions()
    {
        $logger = $this->getAbstractLogger();
        $validateContextExceptions = $this->getMethod('validateContextExceptions');

        $res = $validateContextExceptions->invokeArgs($logger, [[1, 2, 3, 4]]);
        $this->assertEquals([1, 2, 3, 4], $res);

        $res = $validateContextExceptions->invokeArgs($logger, [['key' => 'value']]);
        $this->assertEquals(['key' => 'value'], $res);

        $res = $validateContextExceptions->invokeArgs($logger, [[]]);
        $this->assertEquals([], $res);

        $now = new DateTime();
        $res = $validateContextExceptions->invokeArgs($logger, [[1, $now]]);
        $this->assertEquals([1, $now], $res);
    }

    #[DataProvider('getContextWithInvalidExceptions')]
    public function testValidateContextExceptionsWithExceptionsThatAreNotUnderExceptionKey($context, $expected)
    {
        $logger = $this->getAbstractLogger();
        $validateContextExceptions = $this->getMethod('validateContextExceptions');

        $this->expectException($expected);
        $validateContextExceptions->invokeArgs($logger, [$context]);
    }

    #[DataProvider('getContextWithValidExceptions')]
    public function testValidateContextExceptionsWithExceptions($context)
    {
        $logger = $this->getAbstractLogger();
        $validateContextExceptions = $this->getMethod('validateContextExceptions');

        $res = $validateContextExceptions->invokeArgs($logger, [$context]);
        $this->assertEquals($context, $res);
    }

    #[DataProvider('getMessageArrayForJson')]
    public function testFormatLineAsJson($line, $output)
    {
        $logger = $this->getAbstractLogger();
        $formatLineAsJson = $this->getMethod('formatLineAsJson');

        $res = $formatLineAsJson->invokeArgs($logger, [$line]);

        $this->assertEquals($output, $res);
    }

    #[DataProvider('getMessageArray')]
    public function testFormatLineAsString($line, $output)
    {
        $logger = $this->getAbstractLogger();
        $formatLineAsString = $this->getMethod('formatLineAsString');

        $res = $formatLineAsString->invokeArgs($logger, [$line]);

        $this->assertEquals($output, json_encode($res));
    }
    #[DataProvider('getMessageArrayForOneLineLog')]
    public function testFormatLineAsStringWhenOneLineLogIsTrue($line, $output)
    {
        $logger = $this->getAbstractLogger(null, ['one_line_log' => true]);
        $formatLineAsString = $this->getMethod('formatLineAsString');

        $res = $formatLineAsString->invokeArgs($logger, [$line]);

        $this->assertEquals($output, json_encode($res));
    }

    public function testLogAcceptsStringableMessage()
    {
        $logger = new class(LogLevel::DEBUG, ['log_json' => true]) extends AbstractLogger {
            public array $lines = [];

            protected function write(string $line)
            {
                $this->lines[] = $line;
            }
        };
        $message = new class implements Stringable {
            public function __toString(): string
            {
                return 'Hello {name}';
            }
        };

        $logger->info($message, ['name' => 'World']);

        $this->assertCount(1, $logger->lines);
        $line = json_decode($logger->lines[0], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('INFO', $line['level']);
        $this->assertSame('Hello World', $line['message']);
        $this->assertSame(['name' => 'World'], $line['context']);
    }

    protected function getAbstractLogger(?string $level = null, array $config = []): AbstractLogger
    {
        return new class($level ?? LogLevel::DEBUG, $config) extends AbstractLogger {
            protected function write(string $line)
            {
            }
        };
    }
}
