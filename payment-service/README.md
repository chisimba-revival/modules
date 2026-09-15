# Payment Service

The payment service owns provider-neutral payment intents, verified events,
products, immutable prices, fulfilment and recurring membership mappings.
Browser returns never grant access.

## Paystack setup

1. Install or update `payment-service` through Module Catalogue. Version 1.016
   adds the provider-plan and recurring-subscription tables.
2. In System Configuration set:
   - `PAYMENT_DEFAULT_PROVIDER` to `paystack`;
   - `PAYMENT_PAYSTACK_MODE` to `test` while testing;
   - `PAYMENT_PAYSTACK_SECRET_KEY` to the server-side `sk_test_...` key.
3. In Paystack Dashboard, under **Settings → API Keys & Webhooks**, set the
   webhook URL to:

   `https://YOUR-SITE/index.php?module=payment-service&action=paystackwebhook`

4. Keep live keys out of source control. Change the mode to `live` only when a
   matching `sk_live_...` key is installed and the merchant is approved.

The public key is not required for the hosted redirect checkout. Chisimba sends
the amount, currency, signed-in learner email and an immutable local reference
from the server. Paystack hosts the card interface and returns the learner to
the payment status page.

## Recurring memberships

Membership products with a `monthly` or `annual` billing period use Paystack
plans. Chisimba creates a plan for each immutable product price version and
retains the returned plan code. A new price version therefore cannot silently
change an existing subscriber's agreed charge.

The first verified charge creates the initial membership period. Each later
verified recurring charge creates a separate idempotent renewal intent and
extends membership from the latest existing coverage end. Failed invoices are
recorded without granting access. Subscription cancellation events mark the
mapping non-renewing or disabled; already-paid membership remains valid until
its recorded period ends.

## Verification and recovery

- Paystack webhook bodies are verified with the `x-paystack-signature` HMAC
  before any state change.
- Amount, currency and test/live domain must match the server-owned intent.
- The return page also calls Paystack's Verify Transaction API to provide prompt
  feedback, but the browser URL itself remains non-authoritative.
- Provider event identities and renewal references are idempotent. Duplicate or
  out-of-order deliveries cannot create duplicate membership periods.
- Full refunds and disputes are retained in payment operations; refunds reverse
  fulfilment through the existing membership or private-admission service.

Yoco and the deterministic fake provider remain available adapters. If the
configured default is unavailable, the service falls back only to another
configured provider or the explicit development test checkout.

## Guest contributions (1.032)

The public `action=contributions` journey supports once-off Yoco payments without
creating a user, membership or audience subscription. Contribution products have
purpose `contribution`, billing period `one_off`, and an immutable gross price
with explicit VAT in cents. The order snapshots that breakdown. `user_id` is
nullable only for validated guest contribution intents; account-based purchase
validation remains unchanged. Orders are accessible only through a random,
hashed capability retained in the originating browser session. Query parameters
never prove payment or expose the donor's email address.

For an **existing** payment installation, take a backup, update the module and run
`scripts/upgrade-contribution-schema.php --check`, then `--apply` if changes are
reported. The legacy module updater does not alter existing columns. This step
makes the payment user ID nullable and adds the VAT snapshot field to prices.
It does not invent VAT amounts for historical prices. New installations receive
these columns from the schema definitions. Do not deploy this release to an
existing payment installation without that schema step.

`scripts/setup-ltb-contributions.php` creates the approved staging products and
prices idempotently: R115 (R15 VAT), R230 (R30 VAT) and R575 (R75 VAT). It does not
activate checkout. Existing differing price versions cause a review error.

Keep `PAYMENT_CONTRIBUTIONS_ENABLED=FALSE` until test checkout and callbacks pass.
Use a Yoco **test** Checkout key with `PAYMENT_YOCO_MODE=sandbox` and its matching
webhook signing secret. Staging's outer access protection must allow only the
exact signed Yoco webhook endpoint; keep the rest protected. Guest checkout never
falls back to the fake payment provider. Live activation is a separate step.

Verified success queues one receipt through Communications, with an idempotency
key per order. The browser return does not send or confirm a receipt. Confirm
Communications worker/delivery configuration before launch. Receipt sending is
transactional and does not subscribe the donor to a newsletter. Payment failures,
refunds and late callbacks retain the shared payment ledger behaviour.

The contribution page includes public contextual Help. No new Support navigation
item is published automatically; add it using the site's existing toolbar editor
when the journey is ready for public use.
