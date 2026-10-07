<?php
/** Browser cart ownership: only a verified purchase from this cart may consume it. */
final class ShopCartSession
{
    public static function remember(array &$session, array $order, array $cart): void
    {
        // A repeated prepare must not attach an old receipt to newly added copies.
        $id = $order['id'];
        if (isset($session['shop_checkouts'][$id])) return;
        $session['shop_checkouts'][$id] = ['cart' => $cart];
        $session['shop_checkouts'] = array_slice($session['shop_checkouts'], -50, null, true);
    }

    /** Explicit replacement/removal makes the changed line a new shopping choice. */
    public static function edited(array &$session, array $before, array $after): void
    {
        foreach ($session['shop_checkouts'] ?? [] as $id => $checkout) {
            foreach ($checkout['cart'] as $book => $quantity) {
                if (($before[$book] ?? 0) !== ($after[$book] ?? 0)) unset($session['shop_checkouts'][$id]['cart'][$book]);
            }
        }
    }

    public static function paid(array &$session, array $order): void
    {
        if (($order['payment_state'] ?? '') !== 'paid') return;
        $id = $order['id'] ?? '';
        if (!isset($session['shop_checkouts'][$id])) return;
        $purchased = $session['shop_checkouts'][$id]['cart'];
        $session['shop_checkouts'][$id]['cart'] = [];
        foreach ($purchased as $book => $quantity) {
            $remaining = max(0, ($session['shop_cart'][$book] ?? 0) - $quantity);
            if ($remaining) $session['shop_cart'][$book] = $remaining;
            else unset($session['shop_cart'][$book]);
            // Overlapping checkouts must not consume these same cart copies twice.
            foreach ($session['shop_checkouts'] as &$checkout) {
                if (isset($checkout['cart'][$book])) $checkout['cart'][$book] = max(0, $checkout['cart'][$book] - $quantity);
            }
            unset($checkout);
        }
        if ($purchased) unset($session['shop_request']);
        if (empty($session['shop_cart'])) unset($session['shop_offer_seen']);
    }
}
