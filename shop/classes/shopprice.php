<?php
/** Shared price presentation for the catalogue and linked page products. */
final class ShopPrice
{
    public static function render(array $book, callable $money, callable $text)
    {
        $escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        $regular = $escape($money($book['price_minor']));
        if (($book['sale_price_minor'] ?? null) !== null) {
            $html = '<span>'.$escape($text('regular_price')).' <del>'.$regular.'</del></span> <strong>'.$escape($text('sale_price')).' '.$escape($money($book['sale_price_minor'])).'</strong>';
        } else $html = '<strong>'.$regular.'</strong>';
        if (!empty($book['saving_minor'])) $html .= ' <span class="chisimba-status-badge">'.$escape($text('save_amount')).' '.$escape($money($book['saving_minor'])).'</span>';
        return $html;
    }
}
