<?php
/** Exercise stale public status submissions without a payment-provider call. */
if (!isset($argv[1])) {
    foreach (['paid-stale','unpaid-stale','unpaid-valid','invalid-link','return-paid','return-pending','return-error','already-paid'] as $case) {
        passthru(PHP_BINARY.' '.escapeshellarg(__FILE__).' '.escapeshellarg($case), $status);
        if ($status) exit($status);
    }
    exit;
}
$GLOBALS['kewl_entry_point_run']=true;
class controller {
    public function getParam($key,$default=''){return $_POST[$key]??$default;}
    public $vars=[]; public function setVar($key,$value){$this->vars[$key]=$value;}
}
require dirname(__DIR__).'/controller.php';
$case=$argv[1];
$service=new class($case) {
    public $calls=0; private $case;
    public function __construct($case){$this->case=$case;}
    public function order($token){if($this->case==='invalid-link')throw new DomainException('invalid_order');return ['intent_id'=>'stored-intent','payment_state'=>in_array($this->case,['paid-stale','already-paid'],true)?'paid':'unpaid'];}
    public function reconcile($token){++$this->calls;if($this->case==='return-error')throw new RuntimeException('Provider timeout');return ['intent_id'=>'stored-intent','payment_state'=>$this->case==='return-paid'?'paid':'unpaid'];}
    public function url($action,$params){return '/order';}
};
$csrf=new class($case) {
    private $case;public function __construct($case){$this->case=$case;}
    public function consume($context,$token){return $this->case==='unpaid-valid';}
};
$shop=new shop;
foreach(['service'=>$service,'csrf'=>$csrf] as $name=>$value){$p=new ReflectionProperty(shop::class,$name);$p->setValue($shop,$value);}
$_SESSION=[];
$_POST=['token'=>'fixture','csrf_token'=>'fixture'];$_SERVER['REQUEST_METHOD']='POST';
$invalid=false;
register_shutdown_function(function()use($service,$case,&$invalid,$shop){
    $expected=in_array($case,['unpaid-valid','return-paid','return-pending','return-error'],true)?1:0;
    if(str_starts_with($case,'return-')||$case==='already-paid'){ $state=in_array($case,['return-paid','already-paid'],true)?'paid':'unpaid';if(($shop->vars['shopOrder']['payment_state']??null)!==$state)exit(1); }
    if($service->calls!==$expected||($case==='invalid-link'&&!$invalid)){fwrite(STDERR,"FAIL: $case\n");exit(1);}
    echo "PASS: $case\n";
});
try{(new ReflectionMethod(shop::class,'route'))->invoke($shop,str_starts_with($case,'return-')||$case==='already-paid'?'order':'reconcile');}
catch(DomainException $e){if($case!=='invalid-link')throw $e;$invalid=true;}
