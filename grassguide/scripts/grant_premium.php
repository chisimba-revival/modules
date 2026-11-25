<?php
/** Trusted operator only: a named account receives an audited, non-expiring app grant. */
if(PHP_SAPI!=='cli')exit(64);
$options=getopt('',['username:','reason:']);
if(empty($options['username'])||empty($options['reason'])){fwrite(STDERR,"Use --username=NAME --reason=REASON\n");exit(64);}
$root=dirname(__DIR__,3);chdir($root);$GLOBALS['kewl_entry_point_run']=true;
$_SERVER+=['REQUEST_METHOD'=>'CLI','HTTP_HOST'=>'localhost','SCRIPT_NAME'=>'/index.php','QUERY_STRING'=>''];
require_once $root.'/classes/core/engine_class_inc.php';$engine=new engine();
$user=$engine->getObject('userservice','security')->findByUsername($options['username']);
if(!$user||(int)($user['isactive']??0)!==1)throw new RuntimeException('Active account not found');
$grant=$engine->getObject('entitlementservice','entitlement-service')->grant([
    'userId'=>(string)$user['userid'],'entitlementType'=>'premium','resourceType'=>'app','resourceId'=>'lowveld-grasses',
    'sourceType'=>'manual','sourceReference'=>'founder-lifetime','idempotencyKey'=>'grassguide:founder-lifetime:'.$user['userid'],
    'correlationId'=>'grassguide-premium-'.bin2hex(random_bytes(8)),'grantedByType'=>'system','grantedById'=>'grassguide-operator',
    'expiresAt'=>null,'metadata'=>['reason'=>$options['reason']]
]);
if(empty($grant['ok']))throw new RuntimeException('Premium grant failed');
$status=$engine->getObject('grassguidepremium','grassguide')->entitlement((string)$user['userid']);
if(!$status['lifetime'])throw new RuntimeException('Lifetime grant could not be verified');
echo 'Lifetime premium verified for '.$user['username'].PHP_EOL;
