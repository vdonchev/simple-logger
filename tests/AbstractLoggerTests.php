<?php

use Donchev\Log\AbstractLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class AbstractLoggerTests extends TestCase
{
    protected static function getMethod(string $name): ReflectionMethod
    {
        $class = new ReflectionClass(AbstractLogger::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    public function getLevels(): array
    {
        $levels = new ReflectionClass(LogLevel::class);
        $levels = $levels->getConstants();

        $items = [];
        foreach ($levels as $level) {
            $items[] = [$level];
        }

        return $items;
    }

    public function getMessagesWithoutPlaceholders(): array
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

    public function getMessagesWithPlaceholders(): array
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

    public function getContextWithInvalidExceptions(): array
    {
        return [
            [['not_exception_key' => new InvalidArgumentException()], RuntimeException::class],
            [[new InvalidArgumentException()], RuntimeException::class],
        ];
    }

    public function getContextWithValidExceptions(): array
    {
        return [
            [['exception' => new InvalidArgumentException()]],
            [[123, 'asd', 'key' => 69, 'exception' => new InvalidArgumentException()]],
        ];
    }

    public function getMessageArray(): array
    {
        return [
            [
                [
                    'timestamp' => '2021-03-19 13:21:48 CET',
                    'level' => strtoupper(LogLevel::DEBUG),
                    'message' => 'Some Message',
                    'context' => [1, 2, 3]
                ],
                '"[2021-03-19 13:21:48 CET] [DEBUG]: Some Message \r\nContext:\n(\n    [0] => 1\n    [1] => 2\n    [2] => 3\n)"'
            ],
            [
                [
                    'timestamp' => '2021-03-19 13:21:48 CET',
                    'level' => strtoupper(LogLevel::INFO),
                    'message' => 'Message',
                    'context' => ['key' => 'value']
                ],
                '"[2021-03-19 13:21:48 CET] [INFO]: Message \r\nContext:\n(\n    [key] => value\n)"'
            ],
        ];
    }

    public function getMessageArrayForJson(): array
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

    /**
     * @dataProvider getLevels
     */
    public function testValidateLevelNameWithCorrectLevels($level)
    {
        $logger = $this->getAbstractLogger();
        $validateMinLevel = $this->getMethod('validateLevelName');

        $this->assertNull($validateMinLevel->invokeArgs($logger, [$level]));
    }

    /**
     * @dataProvider getLevels
     */
    public function testValidateLevelNameWithCorrectLevelsIncorrectCasing($level)
    {
        $logger = $this->getAbstractLogger();
        $validateMinLevel = $this->getMethod('validateLevelName');

        $level = ucwords($level);
        $level[3] = strtoupper($level[3]);

        $this->assertNull($validateMinLevel->invokeArgs($logger, [$level]));
    }

    /**
     * @dataProvider getLevels
     */
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

    /**
     * @dataProvider getMessagesWithoutPlaceholders
     */
    public function testInterpolateWithNoPlaceholdersAndNoContext($message)
    {
        $logger = $this->getAbstractLogger();
        $interpolate = $this->getMethod('interpolate');

        $interpolatedMessage = $interpolate->invokeArgs($logger, [$message, []]);

        $this->assertEquals($message, $interpolatedMessage);
    }

    /**
     * @dataProvider getMessagesWithoutPlaceholders
     */
    public function testInterpolateWithNoPlaceholdersAndContext($message)
    {
        $logger = $this->getAbstractLogger();
        $interpolate = $this->getMethod('interpolate');

        $interpolatedMessage = $interpolate->invokeArgs($logger, [$message, ['test' => 'Some text']]);

        $this->assertEquals($message, $interpolatedMessage);
    }

    /**
     * @dataProvider getMessagesWithPlaceholders
     */
    public function testInterpolateWithPlaceholdersAndNoContext($message)
    {
        $logger = $this->getAbstractLogger();
        $interpolate = $this->getMethod('interpolate');

        $interpolatedMessage = $interpolate->invokeArgs($logger, [$message, []]);

        $this->assertEquals($message, $interpolatedMessage);
    }

    /**
     * @dataProvider getMessagesWithPlaceholders
     */
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

    /**
     * @dataProvider getContextWithInvalidExceptions
     */
    public function testValidateContextExceptionsWithExceptionsThatAreNotUnderExceptionKey($context, $expected)
    {
        $logger = $this->getAbstractLogger();
        $validateContextExceptions = $this->getMethod('validateContextExceptions');

        $this->expectException($expected);
        $validateContextExceptions->invokeArgs($logger, [$context]);
    }

    /**
     * @dataProvider getContextWithValidExceptions
     */
    public function testValidateContextExceptionsWithExceptions($context)
    {
        $logger = $this->getAbstractLogger();
        $validateContextExceptions = $this->getMethod('validateContextExceptions');

        $res = $validateContextExceptions->invokeArgs($logger, [$context]);
        $this->assertEquals($context, $res);
    }

    /**
     * @dataProvider getMessageArrayForJson
     */
    public function testFormatLineAsJson($line, $output)
    {
        $logger = $this->getAbstractLogger();
        $formatLineAsJson = $this->getMethod('formatLineAsJson');

        $res = $formatLineAsJson->invokeArgs($logger, [$line]);

        $this->assertEquals($output, $res);
    }

    /**
     * @dataProvider getMessageArray
     */
    public function testFormatLineAsString($line, $output)
    {
        $logger = $this->getAbstractLogger();
        $formatLineAsString = $this->getMethod('formatLineAsString');

        $res = $formatLineAsString->invokeArgs($logger, [$line]);

        $this->assertEquals($output, json_encode($res));
    }

    protected function getAbstractLogger(string $level = null, array $config = []): AbstractLogger
    {
        return $this->getMockForAbstractClass(
            AbstractLogger::class,
            [$level ?? LogLevel::DEBUG, $config]
        );
    }
}
