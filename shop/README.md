# Shop

Physical and virtual product sales for Chisimba. Shop 1.014 adds virtual fulfilment;
Payment Service 1.037 and File Manager 1.100 provide the coordinated catalogue and protected delivery changes.
Author: Derek Keats.

## Scope

- Public book catalogue, draft/published/archived books, ISBNs, public cover images,
  editable selling prices and physical saleable stock.
- Session cart with multiple titles/copies, guest delivery details, immutable
  order review, and hosted Paystack checkout. No collection option.
- South Africa only, ZAR, up to 100 copies per order (a site may set a lower limit).
- Editable quantity thresholds for the **whole-order delivery charge**. The highest
  applicable threshold wins. Charges cannot increase as quantity increases; zero
  means free delivery. The last band applies through the maximum order quantity.
- Separate payment and physical fulfilment states, dispatch courier/tracking,
  confirmation/dispatch emails, private signed order links and an operation history.
- Administrators and the canonical `shop/manage` capability control all administration.
  Being signed in alone confers no access. Public visitors see only published books.

No real shipping rates, books, merchant credentials or sales terms are seeded.
Checkout starts disabled. Example figures appear only in disposable tests.

## Installation and activation

1. Back up the site's database and configuration; stage the coordinated shared
   source including Payment Service 1.036. Preserve/reconcile any other changes
   on the source branch, including the current Events integration.
2. Install/update Payment Service, then Shop through Module Catalogue. The shop
   hook verifies InnoDB and unique indexes, registers management permission,
   preserves the signing key and creates disabled settings on first installation.
   The tables are `tbl_shop_books`, `tbl_shop_settings`, `tbl_shop_orders` and
   `tbl_shop_history`. No ordinary request creates schema.
3. Create books as drafts, choose existing public File Manager covers, enter prices
   and stock, and publish the reviewed entries. Prices are final selling prices,
   including any applicable tax. Version 1 does not generate tax invoices or
   calculate a separate VAT component; confirm the required invoice workflow
   before live trading. Existing order prices never change on a book edit.
4. Configure Payment Service's Paystack **test** credential and mode and the exact
   signed webhook endpoint: `index.php?module=payment-service&action=paystackwebhook`.
   Shop always uses Paystack; there is no automatic switch to Yoco or fake payments.
5. Enter the actual decreasing shipping bands, maximum quantity and sales/delivery/
   returns terms. Configure Communications and its per-site worker. In staging,
   verify a real provider test checkout, signed callbacks, private order status,
   confirmed email delivery and order operations before enabling live checkout.
6. Publish the Shop navigation through the existing toolbar/page editor. A module
   installation does not change the public home page automatically.

Book cover URLs must be HTTPS on the same site. File Manager remains responsible
for access to the selected file; use public cover assets, never private course media.
Back up the signing key with the database. Rotating it invalidates private order links.

## Payment, reservations and recovery

All inventory/order writes lock the singleton settings row within a database
transaction. This serializes writes for a small shop, including multiple-book
orders, price edits and shipping changes. Stock is reserved for 15 minutes; expired
unpaid holds stop reducing availability without needing a cleanup worker.
Confirmed payment deducts stock once. A late payment is fulfilled only if stock
is still available; otherwise the paid order enters review for allocation or refund.

Orders snapshot quantities, descriptions, ISBNs, unit prices, line totals, shipping
charge and revision, currency, delivery address and accepted terms. A changed quote
must be reviewed again. Session-owned request keys prevent duplicate review submits.
The server validates shop intents against the snapshot, including the total,
provider, currency and order reference. Browser returns grant no payment status.

Checkout claims the order before the remote call and stores Paystack's immutable
reference before contacting the provider. Successful repeat clicks reuse the URL.
An uncertain request is not silently repeated; use Check payment status to reconcile
or review it operationally. Do not create another order just because a callback
has not arrived. A late charge for a cancelled order goes to paid review.

Verified refunds/reversals/disputes retain the payment ledger and stop pending
fulfilment. Dispatch history is retained. Physical goods are never automatically
restocked on a financial event. Managers may explicitly restore refunded, unsent
copies once; shipped returns require physical inspection and a deliberate stock
adjustment. Partial refunds remain a payment-operations review, not automatic
partial stock movement. Refund initiation itself is in Paystack.

Mail is queued idempotently after durable payment/dispatch changes. A queue failure
cannot undo payment or stock. Duplicate verified callbacks or Retry confirmation
emails repair queueing without producing another notice. Provider delivery failures
are inspected in Communications. No marketing subscription is created.

## Validation

Run from the modules repository:

```sh
php shop/tests/rules_test.php
php shop/tests/service_test.php
php shop/tests/template_test.php
php payment-service/tests/shop_payment_test.php
php payment-service/tests/event_payment_test.php
php payment-service/tests/contribution_service_test.php
php payment-service/tests/paystack_payment_provider_test.php
php payment-service/tests/yoco_payment_provider_test.php
php payment-service/tests/payment_service_contract_test.php
php payment-service/tests/payment_catalogue_contract_test.php
```

The rules fixtures test intermediate thresholds, free delivery, mixed titles,
unsupported destinations, exact cents and malformed input. Service fixtures test
permissions, changed quotes, reservations, late payment, uncertain checkout,
duplicate fulfilment, mail failure/retry, refund and explicit stock restoration.
All payment and email boundaries are fake in these tests.

`tests/install_local.php` and `tests/runtime_test.php` require
`SHOP_TEST_SITE=/var/www/html/ch` and verify a local development hostname. The runtime
test requires an empty shop, creates synthetic records, checks concurrent last-copy
reservation and transaction rollback, then removes those records and restores
settings. It never contacts a payment provider or sends mail.

On 7 October 2026 these checks passed locally, including Module Catalogue installation
and repeat installation, real database concurrency, and browser book creation,
quantity-band editing, five-/ten-copy cart totals, shared Help/Escape and a 390px
layout without horizontal overflow. Hosted Paystack test checkout and real shop
emails remain launch checks; no shop was deployed or activated on production.

## Later scope

Shipping settings are stored by country zone for future extension, but v1 explicitly
rejects every country except ZA on the server. International rates/quotations need
an intentional extension, not just a configuration toggle. Also deferred: courier
rate/label APIs, wholesale pricing, coupons, digital downloads, automatic returns,
partial-refund allocation, tax invoices and catalogue/order search beyond the small
initial catalogue (500 books and latest 200 orders in administration).

## KengaPub catalogue import at deployment

Use the retained WooCommerce products and original media as the source for the
initial catalogue. The WordPress conversion handover records that the original
database and file trees were retained; verify their current location and contents
on the server before importing. This import has not yet run.

Preview the mapping of book titles, descriptions, ISBNs (where recorded), prices,
stock and cover images. Preserve source product IDs in an import manifest so a
repeat run cannot create duplicates. Flag variations, missing ISBNs, unmanaged
stock, old sale prices and unsupported products for review. Import as drafts;
confirm current selling prices and physical stock before publishing. Copy covers
through the shared file/media service and retain their source mapping. Keep
historic customer and order records in the archive; catalogue import does not
create new orders, payments or customer accounts. Verify counts and representative
books, then record the import result alongside the deployment backup.


## Percentage sales

Shop managers can prepare one non-stacking sale through Manage shop → Sales.
It selects book IDs, a 1–99 percentage reduction, start/end timestamps, title,
description, optional image and CTA text. Form dates use Africa/Johannesburg;
storage uses Unix timestamps. The start is inclusive and the end exclusive.
Save disabled to prepare; enable to schedule/start; Stop sale disables immediately.
Regular book prices are never rewritten. Only selected published books receive a
calculated sale price; cent rounding is half-up and prices cannot reach zero.

The campaign uses the existing settings gateway under ID `sale`, with optimistic
revision checks and the same transaction lock as checkout. No new schema is
required. Catalogue, cart and quotes use the same rule. Quote hashes detect price
changes; order snapshots retain regular/charged unit prices and sale revision.
Existing reviewed orders honour their 15-minute reservation price.

Pages offers a Sale promotion block when Shop is installed. The promotion is
rendered from current sale settings and is absent outside its active dates or
when no selected published book is in stock. Placement does not enable the sale,
checkout, or any book. Its CTA points to the shop catalogue. The editor explains
inactive previews. Empty live-only pages retain a harmless comment in legacy
body storage while composition metadata remains authoritative.

Cart item removal and Clear cart use POST and the same CSRF boundary as quantity
updates. Clearing also invalidates the session order-request key.

Checks: `php shop/tests/sales_test.php`, `php shop/tests/promotion_test.php`, plus
existing rules, service and template tests. No test contacts a payment provider.


## Combo products and optional checkout offers

Add combo creates a normal catalogue product with a manually set price and 2–20
unique individual book IDs (one copy each). Product type is fixed after creation;
combos cannot contain other combos. The definition uses the existing settings table
under the product's 32-character ID. Book revision protects both edits in one
transaction. There is no new schema or automatic production combo creation.

Stock is derived from published constituents and their reservations. Shipping uses
physical copy count, including mixed combo/individual baskets. Order lines snapshot
contents; reservation, payment allocation and refund restocking aggregate that
snapshot, preserving older orders and definitions edited after order review.
Catalogue SAVE compares current individual selling prices and never shows a
negative saving. General sales apply only to explicitly selected product IDs.

Managers opt a combo into checkout offers. A partially represented combo with a
positive saving and sufficient stock can be offered; a combo already present,
all its books already present, or any overlapping combo suppresses the offer.
Acceptance replaces one matching individual copy of each included book and adds
one combo, retaining extra copies. Both old and new quote hashes are checked
server-side under the shop lock. Nothing is purchased by accepting the offer.

The native non-modal popover opens at most once per shopping session, resetting
only when the cart is emptied. Close, No thanks, Escape and click-away dismiss it
without a request or loss of checkout input. It never opens on validation errors.
Unsupported browsers keep checkout available without a popup. Savings on shipping
and the new basket total are computed from actual shipping bands.

Check `php shop/tests/combos_test.php` plus existing service/rules/sales/template
checks. Browser verification covers dismissal, repeat visits, acceptance, Help,
keyboard focus and narrow screens with synthetic local data only.

## Purchase buttons on Pages

Pages text/image/hero blocks can link a shop product through the Shop product for
purchase buttons selector. Contentblocks owns the generic attachment API; shop
owns product selection, price/availability and the three purchase controls.
Public cards render interactive attachments. Editor/storage rendering shows a
non-interactive summary, avoiding nested forms and saved session tokens.

Buy now POSTs one copy then opens the cart. Add to cart uses POST/CSRF and Ajax,
renews all displayed purchase tokens and announces completion without navigation.
Without JavaScript it returns to the same local page section. Go to cart is a
normal link. Add requests have a session-scoped idempotency key; uncertain Ajax
responses are not replayed. Public forms disable shared caching. Current product
visibility, component inventory and quantity limits are checked server-side;
linking a draft never exposes its details or permits purchase.

## Physical and virtual products

Existing products and historic order snapshots default to **Physical**. Their stock,
combo composition, delivery bands and dispatch workflow are retained. New products
also start Physical. Use **Add virtual product**, or change Product type in the editor,
for the following fulfilment types:

- **Contribution:** one-off payment, guest checkout, no stock or delivery fields.
- **Membership:** choose an enabled paid membership tier and monthly or annual
  billing. Save creates an immutable offer and price through Payment Service.
  The Subscribe button uses its existing account-based checkout, verified payment,
  renewal and reversal handling. Subscriptions are purchased separately; the book
  cart is retained. Old product revisions cannot start new purchases, but existing
  subscriptions retain their original price and continue through the canonical
  renewal path. Memberships are excluded from percentage sales.
- **Download:** one-off payment, one copy per product per order, signed-in purchaser
  required. Select personal files through File Manager after setting Private selected
  access. The files must exist exclusively in its configured secure storage;
  public copies, course files and assignment files are rejected. Originals are not
  republished or copied into the shop. The shared download picker accepts the existing
  document, media, image and ZIP allowlists.

Mixed carts calculate shipping bands and reserve/deduct stock only for physical
items. Virtual-only checkout collects a name and email without postal-address fields
or shipping configuration. Empty shipping bands permit a virtual-only shop; physical
checkout remains blocked until bands are configured. Virtual-only paid orders become
Complete; mixed orders retain the existing packing/dispatch workflow.

**My purchases** lists orders associated with the current authenticated account.
Paid file links check current account ownership, the immutable purchased file IDs,
the order payment state and the canonical payment intent on every request, including
HEAD/ranges. The shared File Manager streams bytes as attachments from secure storage.
Anonymous users, other accounts, unpaid orders and recorded refunds/reversals/disputes
are denied. Changing or archiving a product does not rewrite old purchase snapshots.
Already downloaded files cannot be recalled.

## Upgrading to virtual fulfilment

Update File Manager, Toolbar (1.811), Payment Service and Shop through Module Catalogue, including
Shop's guarded installation hook. The repeatable upgrade adds product type/options
and an optional purchasing-account ID. It preserves existing book prices, stock,
revisions, order snapshots and signing keys; no products or subscriptions are seeded.
File Manager's secure-storage path must be configured before publishing downloads.
Confirm the existing web-server private-file routing as part of site deployment.

Focused checks (run from the modules repository):

```sh
php shop/tests/virtual_products_test.php
php shop/tests/service_test.php
php shop/tests/template_test.php
php payment-service/tests/shop_payment_test.php
```

`shop/tests/upgrade_test.php` is an opt-in MariaDB test using
`SHOP_UPGRADE_TEST_HOST`, optional `SHOP_UPGRADE_TEST_USER` and
`SHOP_UPGRADE_TEST_PASSWORD`. It creates and removes a random `shop_test_*` database;
use only a disposable test server. It checks both fresh and legacy installation and
repeated upgrades. No test sends mail or contacts a payment provider.
