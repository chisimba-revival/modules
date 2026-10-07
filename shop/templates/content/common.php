<?php
$esc = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$t = fn($key) => $esc($shopService->text($key));
$url = fn($action, $params = []) => $esc($shopService->url($action, $params));
$actionIcon = function ($key) {
    $icons = ['remove_item'=>'trash-2','clear_cart'=>'trash-2','sales'=>'tag','stop_sale'=>'square','cart'=>'shopping-cart','manage'=>'settings','add_to_cart'=>'shopping-cart','update_cart'=>'refresh-cw','review_order'=>'clipboard-check','save'=>'save','cancel'=>'x','add_band'=>'plus','choose_image'=>'image','add_book'=>'plus','shipping_settings'=>'truck','orders'=>'package','shop'=>'book-open','edit'=>'pencil','continue_shopping'=>'arrow-left','pay'=>'credit-card','check_payment'=>'refresh-cw','mark_dispatched'=>'truck','retry_notice'=>'mail','cancel_order'=>'x','mark_delivered'=>'package-check','allocate_stock'=>'package-plus','restock_unsent'=>'archive-restore'];
    return $this->getObject('iconservice', 'ui')->render($icons[$key] ?? 'arrow-right', ['decorative'=>true]);
};
$money = fn($value) => $esc($shopService->money($value));
$hidden = static function ($name, $value) use ($esc) { echo '<input type="hidden" name="'.$esc($name).'" value="'.$esc($value).'">'; };
$csrf = function () use ($hidden, $shopCsrf) { $hidden('csrf_token', $shopCsrf); };
$field = function ($name, $value = '', $type = 'text', $required = true) use ($esc, $t) {
    echo '<div class="chisimba-form-field"><label for="shop-'.$esc($name).'">'.$t($name === 'name' ? 'customer_name' : $name).'</label>';
    if ($type === 'textarea') echo '<textarea id="shop-'.$esc($name).'" name="'.$esc($name).'" rows="5"'.($required?' required':'').'>'.$esc($value).'</textarea>';
    else echo '<input id="shop-'.$esc($name).'" name="'.$esc($name).'" type="'.$esc($type).'" value="'.$esc($value).'"'.($required?' required':'').'>';
    echo '</div>';
};
?>
<?php if (!empty($shopError)): ?><p class="error chisimba-form-notice" role="alert"><?=$t($shopError)?></p><?php endif; ?>
<?php
$bookPrice = function ($book) use ($money, $t) {
    if (($book['sale_price_minor'] ?? null) !== null) {
        echo '<span>'.$t('regular_price').' <del>'.$money($book['price_minor']).'</del></span> <strong>'.$t('sale_price').' '.$money($book['sale_price_minor']).'</strong>';
    } else echo '<strong>'.$money($book['price_minor']).'</strong>';
    if (!empty($book['saving_minor'])) echo ' <span class="chisimba-status-badge">'.$t('save_amount').' '.$money($book['saving_minor']).'</span>';
};
?>
