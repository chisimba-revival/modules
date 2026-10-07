<?php
/** Live shop-owned promotion for the shared page composition editor. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class salepromotion extends ChisimbaObject
{
    public function label() { return $this->getObject('shopservice','shop')->text('sale_promotion'); }
    public function validateBlock($data) { return []; }
    public function renderBlock($data)
    {
        $shop=$this->getObject('shopservice','shop'); $sale=$shop->sale();
        if (!ShopSaleRules::active($sale,time())) return '';
        $eligible=false;
        foreach ($shop->books() as $book) if ($book['sale_price_minor']!==null && (int)$book['stock']>0) { $eligible=true; break; }
        if (!$eligible) return '';
        $builder=$this->getObject('compositionservice','contentblocks');$block=$builder->emptyBlock('hero');
        $block['title']=$sale['title'];$block['text']='<p><strong>'.htmlspecialchars($shop->text('sale_active').' · '.$sale['percent'].'% '.$shop->text('off'),ENT_QUOTES,'UTF-8').'</strong></p><p>'.nl2br(htmlspecialchars($sale['description'],ENT_QUOTES,'UTF-8')).'</p>';
        $block['url']=$sale['image_url'];$block['alt']=$sale['title'];$block['button_label']=$sale['button_label'];$block['button_url']=$shop->url('catalogue');
        return $builder->render([$block]);
    }
    public function previewBlock($data)
    {
        return '<p>'.htmlspecialchars($this->getObject('shopservice','shop')->text('sale_preview_inactive'),ENT_QUOTES,'UTF-8').'</p>';
    }
    public function editBlock($prefix,$data)
    {
        $shop=$this->getObject('shopservice','shop');$e=static fn($v)=>htmlspecialchars($v,ENT_QUOTES,'UTF-8');
        $html='<p>'.$e($shop->text('sale_promotion_help')).'</p>';
        if ($shop->canManage()) $html.='<a class="button chisimba-button-secondary" href="'.$e($shop->url('sales')).'">'.$this->getObject('iconservice','ui')->render('tag',['decorative'=>true]).' '.$e($shop->text('sales')).'</a>';
        return $html;
    }
}
