-- Membership Service 1.003 data migration (MariaDB/MySQL).
-- Safe to rerun after the module catalogue has added the nullable column.
UPDATE tbl_membership_service_periods AS p
SET p.current_entitlement_key = COALESCE(
    (
        SELECT g.idempotency_key
        FROM tbl_entitlement_service_grants AS g
        LEFT JOIN tbl_entitlement_service_revocations AS r
            ON r.grant_id = g.id
        WHERE r.id IS NULL
          AND (
              g.idempotency_key = CONCAT('membership-period:', p.id)
              OR g.idempotency_key LIKE CONCAT(
                  'membership-period:', p.id, ':amend:%'
              )
          )
        ORDER BY g.granted_at DESC, g.id DESC
        LIMIT 1
    ),
    CONCAT('membership-period:', p.id)
)
WHERE p.current_entitlement_key IS NULL
   OR p.current_entitlement_key = '';
