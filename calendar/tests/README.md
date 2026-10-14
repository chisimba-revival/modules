# Native Calendar viewing checks

```sh
php calendar/tests/native_calendar_test.php
node calendar/tests/native_calendar_client_test.js
```

From an installed **local** Chisimba root:

```sh
CHISIMBA_CALENDAR_TEST=1 php packages/calendar/tests/native_calendar_pdo_test.php
```

The PDO check creates randomly named synthetic events inside a dbTable transaction,
verifies category selection and repeat rendering, and rolls back every record.
It does not modify existing accounts or events, send mail or invoke event writes
through the legacy controller.
