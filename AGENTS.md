# Repository guidance

## Compatibility and setup

- This is a Composer library: `Donchev\Log\` autoloads from `src/`. Keep library code compatible with PHP 8.4.1 and `psr/log` 3.x; tests use PHPUnit 13.
- Install development dependencies with `composer install`. `composer.lock` is intentionally ignored, so a local lockfile is not a repository-wide dependency pin.

## Verification

Run from the repository root; `phpunit.xml` loads `vendor/autoload.php` and discovers `tests/`.

- Full suite: `vendor/bin/phpunit`.
- Single file: `vendor/bin/phpunit tests/AbstractLoggerTest.php`.
- Single method (including its data sets): `vendor/bin/phpunit --filter 'AbstractLoggerTest::testFormatLineAsStringWhenOneLineLogIsTrue' tests/AbstractLoggerTest.php`.
- Add `--do-not-record-test-run-history` to avoid updating local PHPUnit test history; `--do-not-cache-result` is deprecated in PHPUnit 13. There are no configured Composer scripts, lint, formatter, or static-analysis commands.
- Test data providers must be public static methods referenced by `#[DataProvider(...)]`; PHPUnit 13 does not use the old `@dataProvider` annotations.
- Existing tests instantiate a no-op subclass of `AbstractLogger` and invoke protected helpers through reflection; they do not exercise concrete logger I/O. Multiline fixtures use `PHP_EOL` before `Context:` to match the formatter across platforms.

## Implementation gotchas

- `AbstractLogger::log()` validates the level, applies the minimum-level filter, validates context exceptions, interpolates, formats, then calls `write()`. Output/STDOUT/STDERR loggers inherit `FileLogger`; `NullLogger` still runs this pipeline and only discards the final write.
- Configuration keys are allowlisted by `AbstractLogger::CONFIG`; adding an option requires updating that allowlist. `line_format` consumes timestamp, level, message, context in that order via `vsprintf`; placeholder counts are not validated.
- Context validation handles `Exception`, not all `Throwable` values; an exception outside the `exception` key throws `RuntimeException`. `include_context=false` does not bypass validation or interpolation.
- `FileLogger` appends and adds `PHP_EOL` in `write()`, not in the shared formatter. `php://` streams bypass file prefixing and locking; for ordinary files, `file_prefix` is prepended to the entire supplied path, not just its basename.
