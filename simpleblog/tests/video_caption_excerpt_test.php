<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject{}
require __DIR__.'/../classes/publishingrenderer_class_inc.php';
function excerpt($blocks){return publishingrenderer::excerpt(['composition_json'=>json_encode(['version'=>1,'blocks'=>$blocks]),'post_content'=>'Player controls must not leak']);}
function check($condition,$message){if(!$condition)throw new RuntimeException($message);}
$v=['type'=>'video','caption'=>'A woodpecker &amp; its neighbours.'];
check(excerpt([$v])==='A woodpecker & its neighbours.','Video caption fallback');
check(excerpt([['type'=>'text','text'=>'<p>Authored body</p>'],$v])==='Authored body','Body text takes precedence');
check(excerpt([['type'=>'text','text'=>'<p>&nbsp;</p>'],$v])==='A woodpecker & its neighbours.','Empty editor markup falls back');
check(excerpt([['type'=>'video','caption'=>'']])==='','Empty captions omit excerpt');
check(excerpt([['type'=>'video','caption'=>str_repeat('Birds ',80)]])!==''&&mb_strlen(excerpt([['type'=>'video','caption'=>str_repeat('Birds ',80)]]))<=241,'Long captions use normal truncation');
check(publishingrenderer::excerpt(['post_content'=>'<p>Legacy prose</p>'])==='Legacy prose','Legacy imports unchanged');
echo "PASS video-caption fallback, body precedence, empty markup, truncation and legacy content.\n";
