<?php
/** One non-stacking sale, calculated in cents against the current regular price. */
final class ShopSaleRules
{
    public static function active(array $sale, int $now): bool
    {
        return !empty($sale['enabled']) && $now >= (int)($sale['starts_at'] ?? PHP_INT_MAX)
            && $now < (int)($sale['ends_at'] ?? 0);
    }
    public static function price(array $book, array $sale, int $now): ?int
    {
        if (!self::active($sale, $now) || $book['status'] !== 'published'
            || !in_array($book['id'], $sale['book_ids'] ?? [], true)) return null;
        $regular = ShopRules::integer($book['price_minor'], 1, ShopRules::MAX_MONEY);
        $percent = ShopRules::integer($sale['percent'] ?? null, 1, 99);
        return max(1, intdiv($regular * (100 - $percent) + 50, 100));
    }
    public static function timestamp($input): int
    {
        if (!is_string($input)) throw new DomainException('invalid_sale_dates');
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $input, new DateTimeZone('Africa/Johannesburg'));
        if (!$date || $date->format('Y-m-d\\TH:i') !== $input) throw new DomainException('invalid_sale_dates');
        return $date->getTimestamp();
    }
}
