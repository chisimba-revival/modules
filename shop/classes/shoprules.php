<?php
/** Pure money, cart and delivery rules. Amounts are integer cents.
 * @author Derek Keats <derek@dkeats.com>
 */
final class ShopRules
{
    public const MAX_QUANTITY = 100;
    public const MAX_MONEY = 100000000;

    /** Return only to a local absolute path, never to a supplied host or scheme. */
    public static function returnPath($value): string
    {
        if (!is_string($value) || !str_starts_with($value,'/') || str_starts_with($value,'//') || preg_match('/[\\x00-\\x20\\x7f\\\\]/',$value)) return '/';
        return explode('#',$value,2)[0];
    }
    public static function integer($value, int $min, int $max): int
    {
        if (!is_int($value) && (!is_string($value) || !preg_match('/^(0|[1-9][0-9]*)$/D', $value))) {
            throw new DomainException('invalid_number');
        }
        $number = filter_var($value, FILTER_VALIDATE_INT);
        if ($number === false || $number < $min || $number > $max) throw new DomainException('invalid_number');
        return $number;
    }

    public static function money($value): int
    {
        if (!is_string($value) || !preg_match('/^(0|[1-9][0-9]{0,6})(?:\.([0-9]{1,2}))?$/D', trim($value), $m)) {
            throw new DomainException('invalid_money');
        }
        $cents = (int)$m[1] * 100 + (int)str_pad($m[2] ?? '', 2, '0');
        return self::integer($cents, 0, self::MAX_MONEY);
    }

    /** Thresholds apply to the total delivery charge, never each book. */
    public static function bands(array $rows): array
    {
        if (!$rows || count($rows) > 20) throw new DomainException('invalid_shipping');
        $bands = [];
        foreach ($rows as $row) {
            if (!is_array($row)) throw new DomainException('invalid_shipping');
            $from = self::integer($row['from'] ?? null, 1, self::MAX_QUANTITY);
            if (isset($bands[$from])) throw new DomainException('invalid_shipping');
            $bands[$from] = self::integer($row['amount_minor'] ?? null, 0, self::MAX_MONEY);
        }
        ksort($bands, SORT_NUMERIC);
        if (array_key_first($bands) !== 1) throw new DomainException('invalid_shipping');
        $previous = self::MAX_MONEY;
        $result = [];
        foreach ($bands as $from => $amount) {
            if ($amount > $previous) throw new DomainException('shipping_must_decrease');
            $result[] = ['from' => $from, 'amount_minor' => $amount];
            $previous = $amount;
        }
        return $result;
    }

    public static function shipping(string $country, int $quantity, array $policy): int
    {
        self::integer($quantity, 1, self::MAX_QUANTITY);
        // Zone-shaped storage permits future international rates; v1 enables ZA only.
        if ($country !== 'ZA') throw new DomainException('country_unavailable');
        $zone = $policy['zones'][$country] ?? null;
        if (!is_array($zone) || empty($zone['enabled'])) throw new DomainException('shipping_unconfigured');
        if ($quantity > self::integer($zone['max_quantity'] ?? null, 1, self::MAX_QUANTITY)) {
            throw new DomainException('quantity_unavailable');
        }
        $amount = null;
        foreach (self::bands($zone['bands'] ?? []) as $band) {
            if ($band['from'] <= $quantity) $amount = $band['amount_minor'];
        }
        if ($amount === null) throw new DomainException('shipping_unconfigured');
        return $amount;
    }

    public static function cart(array $cart): array
    {
        if (count($cart) > 50) throw new DomainException('quantity_unavailable');
        $result = [];
        foreach ($cart as $id => $quantity) {
            if (!is_string($id) || !preg_match('/^[a-f0-9]{32}$/D', $id)) throw new DomainException('invalid_cart');
            $quantity = self::integer($quantity, 0, self::MAX_QUANTITY);
            if ($quantity) $result[$id] = $quantity;
        }
        if (array_sum($result) > self::MAX_QUANTITY) throw new DomainException('quantity_unavailable');
        ksort($result);
        return $result;
    }

    /** Snapshot only server-owned book values. Shipping is a separate total. */
    public static function quote(array $cart, array $books, string $country, array $policy): array
    {
        $cart = self::cart($cart);
        if (!$cart) throw new DomainException('empty_cart');
        $lines = []; $subtotal = 0; $physicalQuantity=0;
        foreach ($cart as $id => $quantity) {
            $book = $books[$id] ?? null;
            if (!$book || $book['status'] !== 'published') throw new DomainException('book_unavailable');
            $regular = self::integer($book['price_minor'], 1, self::MAX_MONEY);
            $price = self::integer($book['sale_price_minor'] ?? $regular, 1, $regular);
            $components=$book['components']??[];
            if (($book['kind']??'')==='combo' && (count($components)!==count($book['book_ids']) || !$components)) throw new DomainException('book_unavailable');
            $physicalQuantity+=$quantity*max(1,count($components));
            $total = $price * $quantity;
            $subtotal += $total;
            if ($subtotal > self::MAX_MONEY) throw new DomainException('invalid_money');
            $lines[] = ['book_id' => $id, 'title' => $book['title'], 'isbn' => $book['isbn'],
                'revision' => (int)$book['revision'], 'quantity' => $quantity,
                'unit_minor' => $price, 'regular_unit_minor' => $regular,
                'sale_revision' => (int)($book['sale_revision'] ?? 0), 'total_minor' => $total] + ($components ? ['components'=>$components] : []);
        }
        $shipping = self::shipping($country, $physicalQuantity, $policy);
        $total = self::integer($subtotal + $shipping, 1, self::MAX_MONEY);
        return ['lines' => $lines, 'quantity' => $physicalQuantity, 'subtotal_minor' => $subtotal,
            'shipping_minor' => $shipping, 'amount_minor' => $total, 'currency' => 'ZAR',
            'shipping_revision' => (int)($policy['revision'] ?? 0), 'country' => $country];
    }
    /** Aggregate physical copies, including legacy orders without component snapshots. */
    public static function inventory(array $quote): array
    {
        $items=[];
        foreach ($quote['lines'] as $line) {
            foreach ($line['components']??[['book_id'=>$line['book_id'],'title'=>$line['title'],'isbn'=>$line['isbn']??'','quantity'=>1]] as $child) {
                $id=$child['book_id'];
                if (!isset($items[$id])) $items[$id]=['book_id'=>$id,'title'=>$child['title'],'isbn'=>$child['isbn']??'','quantity'=>0];
                $items[$id]['quantity']+=(int)$line['quantity']*(int)$child['quantity'];
            }
        }
        return array_values($items);
    }
}
