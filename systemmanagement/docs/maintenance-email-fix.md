# Maintenance email correction — 9 September 2026

Implemented locally in System Management 0.4. Missing either saved maintenance date now appends “Unplanned maintenance”; a complete saved window retains formatted dates. Sending mail does not change site availability.

Audience, subject and original multiline message survive validation, queue failures and successful submissions. Feedback distinguishes no messages queued, confirmed queued count, partial or uncertain outcome and delivery confirmation. Partial/uncertain feedback directs administrators to the delivery history before retrying.

Validation: PHP 8.5 syntax and existing contracts passed; maintenance_email_test.php covers planned/unplanned dates, private per-recipient queues, validation/CSRF, empty audiences, partial failure, uncertain exceptions and safely escaped retained drafts. Browser inspected the real rendered template with mocked queue/empty recipients. Live browser submission was interrupted by a browser automation confirmation-dialog timeout; no live delivery test was performed. No production changes or real email sends. Local module catalogue updated to 0.4.
