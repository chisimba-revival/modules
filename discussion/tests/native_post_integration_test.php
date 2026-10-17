<?php
/** Disposable PDO regression: original-language edits and stale revisions. */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_DISPOSABLE_TEST') !== '1') exit(64);
chdir('/var/www/html/ch');
if (!str_contains(file_get_contents('config/dbdetails_inc.php'), '@editor-db/editor_fixture')) exit(64);
$GLOBALS['kewl_entry_point_run']=true;
$_SERVER+=array('REQUEST_METHOD'=>'CLI','HTTP_HOST'=>'127.0.0.1:8088','SCRIPT_NAME'=>'/ch/index.php','QUERY_STRING'=>'');
require 'classes/core/engine_class_inc.php'; $engine=new engine();
function check($ok,$label) { if (!$ok) throw new RuntimeException($label); echo 'PASS '.$label."\n"; }
$posts=$engine->getObject('dbpost','discussion');
$topics=$engine->getObject('dbtopic','discussion');
$text=$engine->getObject('dbposttext','discussion');
$topic=$topics->insertSingle('nativeqa','init_1',0,'topic-author','Native test');
$post=$posts->insertSingle('0','0','nativeqa',$topic,'reply-author');
check((bool)$topic && (bool)$post,'synthetic post created');
try {
    $translation=$text->insertSingle($post,'Translation','Translation body','af',0,'translator');
    $original=$text->insertSingle($post,'Original','Original body','en',1,'reply-author');
    $loaded=$posts->getPostWithText($post,true);
    check($loaded['post_title']==='Original','edit reads original even when translation was inserted first');
    check($loaded['userid']==='reply-author','topic and translation authors cannot replace post ownership');
    $revision=hash('sha256',$loaded['post_title']."\0".$loaded['post_text']);
    check($text->updateRevision($post,$revision,'Edited','<p>Edited body</p>'),'matching revision saves');
    check($text->getRow('id',$translation)['post_title']==='Translation','edit leaves translation intact');
    check(!$text->updateRevision($post,$revision,'Stale','Stale body'),'stale revision is rejected');
    check($text->getRow('id',$original)['post_title']==='Edited','stale edit preserves saved text');
    check($posts->getPostWithText("' OR 1=1 --",true)===null,'post identifier stays bound');
} finally {
    $text->delete('post_id',$post); $posts->delete('id',$post); $topics->delete('id',$topic);
}
