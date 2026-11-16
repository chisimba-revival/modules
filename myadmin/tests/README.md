# Mail reporting checks

Run `mailreport_render_test.php`, `mailqueuehealth_test.php` and
`myadmin_contract_test.php` with PHP CLI.

`maildeliveryreport_test.php` requires an isolated MySQL/MariaDB database named
`mail_report_test`. Set `MAIL_REPORT_TEST_DSN`, `MAIL_REPORT_TEST_USER` and
`MAIL_REPORT_TEST_PASSWORD`. It creates temporary synthetic tables only, never
sends mail, and covers date boundaries, pagination, filters and denied access.

Mail timestamps currently follow Communications' runtime-local `date()` storage
contract. The report displays that time zone and does not reinterpret historical
wall times as UTC. Daily sent totals use `sent_at`; subject totals and date filters
use `date_created`. Provider response codes describe handoff attempts, not inbox
receipt. Reports select neither bodies nor raw provider errors, tokens or keys.
Administrator checks apply before queries; the controller is GET/HEAD only with
private/no-store caching. No resend/export action is included.
