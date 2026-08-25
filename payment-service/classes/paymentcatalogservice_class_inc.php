<?php
/** Server-owned catalogue. Historic price rows are never edited. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class paymentcatalogservice extends ChisimbaObject
{
    private const PURPOSES=array('membership','private_course');
    private const PERIODS=array('monthly','annual','one_off');
    public function init() { $this->products=$this->getObject('dbpaymentproducts'); $this->prices=$this->getObject('dbpaymentprices'); $this->contexts=$this->getObject('dbcontext','context'); }
    public function listProducts($activeOnly=false) {
        $rows=$activeOnly?$this->products->activeProducts():$this->products->allProducts();
        foreach($rows as &$row){ $row['current_price']=$this->prices->currentForProduct($row['id']); $row['prices']=$this->prices->forProduct($row['id']); } unset($row);
        return $rows;
    }
    public function purchasable($code,$version=null) {
        $code=$this->identifier($code,96); $product=$code===null?null:$this->products->byCode($code);
        if(!is_array($product)||empty($product['active'])) return null;
        $price=$version===null?$this->prices->currentForProduct($product['id']):$this->prices->byVersion($product['id'],$this->identifier($version,64));
        if(!is_array($price)) return null;
        $now=date('Y-m-d H:i:s');
        if($price['effective_from']>$now||(!empty($price['effective_until'])&&$price['effective_until']<=$now)) return null;
        $product['price']=$price; return $product;
    }
    public function productVersion($code,$version) {
        $code=$this->identifier($code,96); $version=$this->identifier($version,64);
        $product=$code===null?null:$this->products->byCode($code);
        $price=(!$product||$version===null)?null:$this->prices->byVersion($product['id'],$version);
        if(!$product||!$price) return null; $product['price']=$price; return $product;
    }
    public function createProduct(array $input) {
        $purpose=$this->enum($input['purposeType']??null,self::PURPOSES); $period=$this->enum($input['billingPeriod']??null,self::PERIODS);
        $duration=filter_var($input['durationMonths']??null,FILTER_VALIDATE_INT,array('options'=>array('min_range'=>1,'max_range'=>120)));
        if($period==='one_off') $duration=null;
        $values=array('code'=>$this->identifier($input['code']??null,96),'name'=>$this->text($input['name']??null,191),'purpose_type'=>$purpose,'purpose_id'=>$this->text($input['purposeId']??null,191),'billing_period'=>$period,'duration_months'=>$duration===false?null:$duration,'active'=>1);
        if(in_array(null,$values,true)||($purpose==='membership'&&($duration===null||!in_array($values['purpose_id'],array('tier_1','tier_2'),true)))) return array('ok'=>false,'code'=>'invalid_product');
        if($purpose==='private_course') {
            $course=$this->contexts->getContext($values['purpose_id']);
            if(!is_array($course)||strtolower((string)($course['access_policy']??''))!=='private') return array('ok'=>false,'code'=>'private_course_required');
        }
        $existing=$this->products->byCode($values['code']); if($existing) return array('ok'=>true,'code'=>'already_created','productId'=>$existing['id']);
        $values['id']=bin2hex(random_bytes(16)); $values['created_at']=$values['updated_at']=date('Y-m-d H:i:s');
        return $this->products->insert($values)===false?array('ok'=>false,'code'=>'product_failed'):array('ok'=>true,'code'=>'product_created','productId'=>$values['id']);
    }
    public function addPrice($productId,array $input) {
        $product=$this->products->byId($this->hexId($productId)); $amount=filter_var($input['amountMinor']??null,FILTER_VALIDATE_INT,array('options'=>array('min_range'=>1)));
        $version=$this->identifier($input['versionCode']??null,64); $currency=strtoupper(trim((string)($input['currency']??''))); $from=$this->timestamp($input['effectiveFrom']??date('Y-m-d H:i:s')); $until=trim((string)($input['effectiveUntil']??'')); $until=$until===''?null:$this->timestamp($until);
        if(!$product||$amount===false||$version===null||!preg_match('/^[A-Z]{3}$/',$currency)||$from===null||($until!==null&&$until<=$from)) return array('ok'=>false,'code'=>'invalid_price');
        $existing=$this->prices->byVersion($product['id'],$version); if($existing) return array('ok'=>true,'code'=>'already_created','priceId'=>$existing['id']);
        $values=array('id'=>bin2hex(random_bytes(16)),'product_id'=>$product['id'],'version_code'=>$version,'amount_minor'=>$amount,'currency'=>$currency,'effective_from'=>$from,'effective_until'=>$until,'created_at'=>date('Y-m-d H:i:s'));
        return $this->prices->insert($values)===false?array('ok'=>false,'code'=>'price_failed'):array('ok'=>true,'code'=>'price_created','priceId'=>$values['id']);
    }
    private function enum($v,array $a){$v=is_scalar($v)?strtolower(trim((string)$v)):'';return in_array($v,$a,true)?$v:null;}
    private function identifier($v,$m){$v=is_scalar($v)?trim((string)$v):'';return $v!==''&&strlen($v)<=$m&&preg_match('/^[A-Za-z0-9][A-Za-z0-9_.:-]*$/',$v)?$v:null;}
    private function text($v,$m){$v=is_scalar($v)?trim((string)$v):'';return $v!==''&&strlen($v)<=$m&&!preg_match('/[\x00-\x1F\x7F]/',$v)?$v:null;}
    private function hexId($v){$v=is_scalar($v)?strtolower(trim((string)$v)):'';return preg_match('/^[a-f0-9]{32}$/',$v)?$v:null;}
    private function timestamp($v){$d=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',(string)$v);return $d&&$d->format('Y-m-d H:i:s')===(string)$v?(string)$v:null;}
}
?>
