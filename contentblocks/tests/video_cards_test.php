<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject{public function getObject($n,$m=null){return new class{public function render($icon,$options){return '<svg aria-hidden="true"></svg>';}};}}
require dirname(__DIR__).'/classes/videocardrenderer_class_inc.php';require dirname(__DIR__).'/classes/contentmediaservice_class_inc.php';
$r=new videocardrenderer();$html=$r->cards([['video_id'=>'7lRvYUfMwDs','title'=>'Birds & <script>bad()</script>','duration'=>'1:11:39']],'player','Watch video');
if(str_contains($html,'<iframe')||str_contains($html,'<script>')||!str_contains($html,'loading="lazy"')||!str_contains($html,'width="480"')||!str_contains($html,'target="_blank"')||!str_contains($html,'data-ui-open="player"'))throw new RuntimeException('Video card contract failed');
foreach(['<script>','shorts/7lRvYUfMwDs','123','7lRvYUfMwDs?x=1'] as $id)if(videocardrenderer::videoId($id))throw new RuntimeException('Unsafe identity');
$m=new contentmediaservice();foreach(['https://xeno-canto.org/395736','https://www.xeno-canto.org/395736/embed'] as $url)if($m->videoEmbed($url)!=='https://xeno-canto.org/395736/embed')throw new RuntimeException('Bird audio lost');
if($m->videoEmbed('https://xeno-canto.org.evil.test/395736')!==null)throw new RuntimeException('Embed host check');
echo "PASS: escaped cards, lazy thumbnails, no initial player, native fallback and allowlisted bird-sound embeds\n";
