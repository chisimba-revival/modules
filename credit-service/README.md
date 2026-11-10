# Credit service

Reusable integer credits for server-side consumers. No client grant endpoint, provider credentials, pricing UI or payment processor. The consumer owns permissions and offer policy. All persistence uses dbTable and composable checked transactions/savepoints. Module Catalogue post-install enforces unique credit identifiers, including installations where Chisimba adds its own `puid` primary key.

## Contract

- `grant(scope, owner, source, units, kind, plan, validUntil)` records an immutable, idempotent grant. `source` must be globally unique within a scope, including across users. Reusing it with different terms or an owner raises a conflict. Kinds are trial, monthly, topup and adjustment. Monthly grants require a plan and verified UTC paid-through time.
- `reserve(scope, owner, request)` holds one credit. Requests serialize on a durable account row; concurrent requests cannot overspend. Call inside the same transaction as the owning domain's job insertion.
- `settle(scope, owner, request, success)` spends or releases the hold exactly once. Missing holds are permitted for grandfathered jobs. Terminal settlement cannot be reversed by deletion.
- `reverse(scope, owner, source)` removes the original grant once. A spent grant can leave debt: future grants first cover that debt. A reversal revokes that grant's plan period. Process an out-of-order refund by atomically recording the original verified grant and reversing it, so a late success cannot resurrect it.
- `snapshot` returns ledger balance, held and available credits, debt and current paid-through plan. Monthly credits currently carry forward; expiry of the paid period removes plan eligibility, not the remaining credits. Consumption is fungible; entries retain the source category.

## Payment integration boundary

These methods are trusted **server APIs**, not payment verification. No app-store verifier or checkout is provided by this module. Integrate with Chisimba `payment-service`, which owns verified provider events, canonical immutable prices, transaction ownership and subscription state. Do not call grant on a browser return, a phone's success flag, an unverified receipt, or a client-supplied quantity.

The future Google Play/Apple adapters must verify application/product, purchaser binding, amount/currency, environment, transaction/cycle identity, paid period and refund state. Resolve units and plan from the immutable server-owned offer version purchased, not current mutable configuration or client data. Use `store:transaction-or-cycle` as the source. Renewals grant only for verified paid cycles; renewal cancellation alone leaves already-paid coverage until its paid-through time. Refunds/revocations reverse the corresponding source. Partial refunds, grace periods, overlapping upgrade/downgrade cycles and rollover/expiry rules must be explicitly resolved before selling subscriptions. No automatic monthly grant scheduler is needed or supplied.

Tests in the Hair Planner consumer exercise the real PDO ledger, concurrency, rollback and job settlement with synthetic grants; they do not constitute testing a store verification adapter.
