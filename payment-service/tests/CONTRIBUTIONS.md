# Local contribution rollout

Contributions use the existing Payment Service catalogue, immutable prices,
verified events and payment records. They are once-off and neither grant nor
revoke membership/course access. No extra donation ledger has been introduced.

`resources/contributions-zar.json` holds the requested five offers as deployment
data: base amounts R50, R100, R250, R500 and R1000, with the requested 15% added.
Totals are R57.50, R115, R287.50, R575 and R1150. This is not a general tax engine
or a donation tax certificate. Confirm the merchant's tax treatment before live
deployment. Product names are editable catalogue data; interface wording uses
registered language strings.

Update Payment Service through Module Catalogue, then explicitly import:

```
php packages/payment-service/scripts/import-contributions.php /path/to/app /absolute/path/to/contributions-zar.json
```

The importer validates integer totals, uses canonical catalogue methods, refuses
conflicting existing products/prices and is repeatable. It does not run during
ordinary module installation and does not change provider configuration.

Local contribution list: `index.php?module=payment-service&action=catalogue&purpose=contribution`.
This catalogue checkout requires a signed-in account. The reconciled 1.034 release
also retains the existing guest Yoco journey at `action=contributions`. Guest
checkout requires its explicit enable setting and correctly configured provider
credentials; consolidation does not enable it or change credentials. Both flows
use the shared payment ledger and neither grants access. Guest orders retain
their immutable VAT snapshot and verified, idempotent receipt flow.

Version 1.034 registers the nullable guest identity and VAT schema upgrades through
Module Catalogue. The older explicit schema script remains a repeatable diagnostic
and recovery tool, not a required substitute for normal catalogue updates.

Paystack already supports the site's recurring membership plans through its
existing adapter. For hosted sandbox verification configure, locally only:

- `PAYMENT_DEFAULT_PROVIDER`: `paystack`
- `PAYMENT_PAYSTACK_MODE`: `test`
- `PAYMENT_PAYSTACK_TEST_SECRET_KEY`: the merchant's test key, entered securely

Never copy a live key from another site. A public webhook requires an approved
reachable test endpoint; the local `.test` hostname is not reachable by Paystack.
Browser returns alone do not confirm payments; server verification is required.

Verified locally in PHP 8.5: contribution success, duplicate events, refund,
no course access effects, exact prices, recurring-contribution rejection,
Paystack signature/amount/domain checks and recurring mapping, payment/catalogue
contracts, and repeatable import. Chrome verified all five totals and a R57.50
fake-provider success with the contribution-specific confirmation. The synthetic
payment record remains in local payment history for audit (no money moved).

No Paystack test key was installed during these checks. Real hosted sandbox
checkout and provider-delivered renewals/webhooks remain unverified. Local default
remains `fake`; no live site or provider account was changed.
