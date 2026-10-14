<?php
if(PHP_SAPI!=='cli'||getenv('CHISIMBA_PERSONALSPACE_TEST')!=='1')exit("Set CHISIMBA_PERSONALSPACE_TEST=1 locally.\n");
$GLOBALS['kewl_entry_point_run']=true;
$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test:8445';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';
require 'classes/core/engine_class_inc.php';$engine=new engine();
set_error_handler(static function($n,$s){if(error_reporting()&$n)throw new RuntimeException($s);return false;});
$repo=$engine->getObject('dbpersonalspaceblocks','personalspace');$code='qa-space-'.bin2hex(random_bytes(6));
$actor=new class($code){function __construct(public string $id){}function isLoggedIn(){return true;}function userId(){return $this->id;}};
$repo->objUser=$actor;
class FailingBlockOrder extends dbpersonalspaceblocks {
    private int $writes=0;
    public function update($pkfield,$pkvalue,$fields,$tablename='') {
        if(++$this->writes===2)throw new RuntimeException('injected-second-write');
        return parent::update($pkfield,$pkvalue,$fields,$tablename);
    }
}
$failing=new FailingBlockOrder($engine,'personalspace');$failing->objUser=$actor;
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
try{$repo->transaction(function()use($repo,$code,$failing){
    foreach(['A','B','C'] as $i=>$name)$repo->insert(['userid'=>$code,'block'=>'block|'.$name.'|context','side'=>'middle','module'=>'context','position'=>$i+1]);
    check(substr_count($repo->getUserBlocks($code,'middle'),'class="block"')===3,'unavailable placements retained without rendering source blocks');
    $rows=$repo->editingRows($code);$initial=$repo->editingRevision($code);
    try{$failing->editPlacement($code,$initial,'moveblock',$rows[1]['id'],null,'up');throw new LogicException('failure not injected');}catch(RuntimeException $e){check($e->getMessage()==='injected-second-write','second-write error');}
    check($repo->editingRevision($code)===$initial,'partial move rolled back');
    $revision=$repo->editPlacement($code,$initial,'moveblock',$rows[1]['id'],null,'up');
    check(array_column($repo->editingRows($code),'id')===[$rows[1]['id'],$rows[0]['id'],$rows[2]['id']],'move up');
    try{$repo->editPlacement($code,$initial,'removeblock',$rows[0]['id'],null,null);throw new LogicException('stale accepted');}catch(RuntimeException $e){check($e->getMessage()==='block_stale','stale rejection');}
    check($repo->editingRevision($code)===$revision,'stale request unchanged');
    try{$repo->editPlacement($code.'-other',$repo->editingRevision($code.'-other'),'removeblock',$rows[0]['id'],null,null);throw new LogicException('cross owner accepted');}catch(RuntimeException $e){check($e->getMessage()==='block_denied','cross-owner rejection');}
    $revision=$repo->editPlacement($code,$revision,'moveblock',$rows[1]['id'],null,'down');
    check(array_column($repo->editingRows($code),'id')===array_column($rows,'id'),'move down');
    $revision=$repo->editPlacement($code,$revision,'removeblock',$rows[1]['id'],null,null);
    $repo->editPlacement($code,$revision,'addblock','block|aboutcontext|context','middle',null);
    check(count($repo->editingRows($code))===3,'add/remove');
    // A foreign placement must also be rejected when using the correct owner revision.
    $foreign=$repo->insert(['userid'=>$code.'x','block'=>'block|foreign|context','side'=>'middle','module'=>'context','position'=>1]);
    try{$repo->editPlacement($code,$repo->editingRevision($code),'removeblock',$foreign,null,null);throw new LogicException('foreign placement accepted');}catch(RuntimeException $e){check($e->getMessage()==='block_denied','foreign ID denied');}
    check((bool)$repo->getRow('id',$foreign),'foreign row retained');
    $before=$repo->editingRevision($code);
    try{$repo->transactionWithSavepoint(function()use($repo,$code,$before){$repo->editPlacement($code,$before,'removeblock',$repo->editingRows($code)[0]['id'],null,null);throw new LogicException('injected-failure');});}catch(LogicException $e){check($e->getMessage()==='injected-failure','injected error');}
    check($repo->editingRevision($code)===$before,'nested rollback preserves layout');
    throw new LogicException('rollback-fixtures');
});}catch(LogicException $e){if($e->getMessage()!=='rollback-fixtures')throw $e;}
check($repo->editingRows($code)===[],'outer rollback');
echo "PASS real PDO move/add/remove, stale revision, cross-owner denial, nested and outer rollback\n";
