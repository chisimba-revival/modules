# Retired browser test builder

Retired on 15 October 2026 with Derek’s approval. This was a 2009 browser code
generator, not the PHPUnit command-line testing library. Its generator,
analyser, interface, old database helper and schema definitions have been removed.
Only an inert HTTP 410 controller, notice, catalogue metadata and regression
check remain for old URLs. No existing database records or generated user files
are deleted. The original implementation remains in Git history.

Run `php phpunit/tests/disabled_controller_test.php` from the modules root.
