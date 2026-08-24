# Time and Date Service

`timeanddate-service` is the canonical boundary for new Chisimba capabilities
that create, compare, store or display dates and times.

## Rules

- Store and compare instants in UTC as `Y-m-d H:i:s`.
- Use named IANA timezones such as `Africa/Johannesburg` at input and display boundaries.
- Do not call `date_default_timezone_set()` in consuming modules.
- Pass an explicit timezone when an operation is not site-wide. Otherwise use
  the configured site timezone, with UTC as the safe fallback.
- Use `parseLocal()` for form input. It rejects invalid values and local times
  PHP would otherwise normalize across a daylight-saving gap.
- Treat a null result as invalid input or configuration; do not guess.

## Initial API

- `nowUtc()` and `nowStorage()`
- `parseStorage()` and `toStorage()`
- `parseLocal()` and `toUtc()`
- `inTimezone()`
- `formatDate()`, `formatTime()` and `formatDateTime()`
- `siteTimezone()` and `isValidTimezone()`

The module owns no tables and does not implement calendars, recurrence,
reminders, user timezone preferences, duration policy, or worker scheduling.

## Configuration

Optional `timeanddate-service` sysconfig values:

- `TIMEANDDATE_TIMEZONE` (default `UTC`)
- `TIMEANDDATE_DATE_FORMAT` (default `j F Y`)
- `TIMEANDDATE_TIME_FORMAT` (default `H:i`)
- `TIMEANDDATE_DATETIME_FORMAT` (default `j F Y, H:i`)
