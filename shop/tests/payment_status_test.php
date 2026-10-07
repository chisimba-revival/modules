<?php
/** Exercise stale public status submissions without a payment-provider call. */
if (!isset($argv[1])) {
    foreach (['paid-stale','unpaid-stale','unpaid-valid','invalid-link'] as $case) {
        passthru(PHP_BINARY.' '.escapeshellarg(__FILE__).' '.escapeshellarg($case), $status);
        if ($status) exit($status);
    }
    exit;
}
$GLOBALS['kewl_entry_point_run']=true;
class controller {
    public function getParam($key,$default=''){return $_POST[$key]??$default;}
    public function setVar($key,$value){}
}
require dirname(__DIR__).'/controller.php';
$case=$argv[1];
$service=new class($case) {
    public $calls=0; private $case;
    public function __construct($case){$this->case=$case;}
    public function order($token){if($this->case==='invalid-link')throw new DomainException('invalid_order');return ['payment_state'=>$this->case==='paid-stale'?'paid':'unpaid'];}
    public function reconcile($token){++$this->calls;}
    public function url($action,$params){return '/order';}
};
$csrf=new class($case) {
    private $case;public function __construct($case){$this->case=$case;}
    public function consume($context,$token){return $this->case==='unpaid-valid';}
};
$shop=new shop;
foreach(['service'=>$service,'csrf'=>$csrf] as $name=>$value){$p=new ReflectionProperty(shop::class,$name);$p->setValue($shop,$value);}
$_POST=['token'=>'fixture','csrf_token'=>'fixture'];$_SERVER['REQUEST_METHOD']='POST';
$invalid=false;
register_shutdown_function(function()use($service,$case,&$invalid){
    $expected=$case==='unpaid-valid'?1:0;
    if($service->calls!==$expected||($case==='invalid-link'&&!$invalid)){fwrite(STDERR,"FAIL: $case\n");exit(1);}
    echo "PASS: $case\n";
});
try{(new ReflectionMethod(shop::class,'route'))->invoke($shop,'reconcile');}
catch(DomainException $e){if($case!=='invalid-link')throw $e;$invalid=true;}
