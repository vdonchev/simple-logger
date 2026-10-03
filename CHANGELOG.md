# Changelog

## 3.0.0 - 2026-10-03

### Breaking changes

- Raise the minimum PHP version from 7.1 to 8.4.1.
- Require `psr/log` ^3.0.2 instead of ^1.1.
- Update `AbstractLogger::log()` to accept `string|\Stringable` messages and return `void`, matching PSR-3 3.x. Custom overrides must use a compatible signature.

### Updated

- Upgrade development dependencies to PHPUnit ^13.4 and migrate the test configuration and data providers.
- Add coverage for Stringable messages and make multiline test fixtures portable across platforms.
- Remove deprecated reflection calls and implicit nullable parameter declarations from tests.
- Verify compatibility on PHP 8.5.11, including concrete logger I/O smoke checks.
- Clarify installation, upgrade instructions, and configuration behavior in the README.
