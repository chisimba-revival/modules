<?php
/** Shop-owned purchase controls attached to page text/image blocks. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
require_once __DIR__.'/shopprice.php';
class productbuttons extends ChisimbaObject
{
    private $token;
    private $assets=false;
    public function validateBlock($data)
    {
        if (!is_array($data)) throw new DomainException('invalid');
        $id=$data['product_id']??'';
        if ($id==='') return [];
        if (!is_string($id)||!preg_match('/^[a-f0-9]{32}$/D',$id)) throw new DomainException('invalid');
        return ['product_id'=>$id];
    }
    public function previewBlock($data)
    {
        if (empty($data['product_id'])) return '';
        $shop=$this->getObject('shopservice','shop');$book=$shop->book($data['product_id']);
        return '<p>'.htmlspecialchars($shop->text('purchase_buttons').': '.($book['title']??$shop->text('book_unavailable')),ENT_QUOTES,'UTF-8').'</p>';
    }
    public function editBlock($prefix,$data)
    {
        $shop=$this->getObject('shopservice','shop');$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
        $id='shop_link_'.preg_replace('/[^a-z0-9]/i','_',$prefix);
        $html='<div class="chisimba-form-field"><label for="'.$id.'">'.$e($shop->text('purchase_buttons')).'</label><select id="'.$id.'" name="'.$e($prefix.'[product_id]').'"><option value="">'.$e($shop->text('no_product')).'</option>';
        $selected=$data['product_id']??'';$found=false;
        foreach ($shop->books($shop->canManage()) as $book) {
            $found=$found||$selected===$book['id'];
            $html.='<option value="'.$e($book['id']).'"'.($selected===$book['id']?' selected':'').'>'.$e($book['title']).'</option>';
        }
        if ($selected!==''&&!$found) $html.='<option selected value="'.$e($selected).'">'.$e($shop->text('book_unavailable')).'</option>';
        return $html.'</select><p>'.$e($shop->text('purchase_buttons_help')).'</p></div>';
    }
    /** Plain text for the composition service's shared image badge. */
    public function mediaBadge($data)
    {
        $shop = $this->getObject('shopservice', 'shop');
        $book = empty($data['product_id']) ? null : $shop->book($data['product_id']);
        return $book && $book['sale_price_minor'] !== null ? $shop->text('sale_badge') : '';
    }
    public function renderBlock($data)
    {
        if (empty($data['product_id'])) return '';
        $shop=$this->getObject('shopservice','shop');$book=$shop->book($data['product_id']);
        $e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
        if (!$book) return '<p>'.$e($shop->text('book_unavailable')).'</p>';
        if (ShopRules::membership($book)) return '<div class="chisimba-stack"><p>'.ShopPrice::render($book,[$shop,'money'],[$shop,'text']).' · '.$e($shop->text($book['virtual']['billing_period'])).'</p><a class="button" href="'.$e($shop->membershipUrl($book)).'">'.$e($shop->text('subscribe')).'</a></div>';
        if (!$this->assets) {
            $this->appendArrayVar('headerParams','<script defer src="'.$e($this->getResourceUri('pagecart.js','shop').'?v=1.004').'"></script>');$this->assets=true;
        }
        header('Cache-Control: private, no-store');
        $this->token??=$this->getObject('nativeauthwebcomposition','security')->build()['csrf']->issue('shop');
        $icon=$this->getObject('iconservice','ui');
        $html='<div id="shop-product-'.$e($book['id']).'" class="chisimba-stack" data-shop-purchase><p>'.ShopPrice::render($book, [$shop,'money'], [$shop,'text']).'</p>';
        if ((!ShopRules::physical($book)||(int)$book['stock']>0)) {
            $return=$_SERVER['REQUEST_URI']??'/';
            $html.='<form method="post" action="'.$e($shop->url('pageadd')).'" class="chisimba-form-actions" data-shop-page-add data-pending="'.$e($shop->text('adding')).'" data-uncertain="'.$e($shop->text('add_uncertain')).'">';
            foreach (['csrf_token'=>$this->token,'id'=>$book['id'],'request_key'=>bin2hex(random_bytes(16)),'return_url'=>$return] as $name=>$value) $html.='<input type="hidden" name="'.$name.'" value="'.$e($value).'">';
            $html.='<button class="button" type="submit" name="intent" value="buy">'.$icon->render('shopping-bag',['decorative'=>true]).' '.$e($shop->text('buy_now')).'</button><button class="button chisimba-button-secondary" type="submit" name="intent" value="stay">'.$icon->render('plus',['decorative'=>true]).' '.$e($shop->text('add_to_cart')).'</button>';
        } else $html.='<p>'.$e($shop->text('out_of_stock')).'</p><div class="chisimba-form-actions">';
        $html.='<a class="button chisimba-button-secondary" href="'.$e($shop->url('cart')).'">'.$icon->render('shopping-cart',['decorative'=>true]).' '.$e($shop->text('go_to_cart')).'</a>'.((!ShopRules::physical($book)||(int)$book['stock']>0)?'</form>':'</div>');
        $notice=!empty($_SESSION['shop_page_added'][$book['id']])?$shop->text('added_to_cart'):'';
        unset($_SESSION['shop_page_added'][$book['id']]);
        return $html.'<p role="status" aria-live="polite" data-shop-add-status>'.$e($notice).'</p></div>';
    }
}
