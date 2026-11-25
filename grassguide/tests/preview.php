<?php
/** Fixture-only router, never deploy to a Chisimba site. */
if (getenv('GRASSGUIDE_TEST')!=='1') {http_response_code(404);exit;}
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$roots=['/skin/'=>'/framework/app/skins/chisimba-reborn/','/assets/grassguide/'=>dirname(__DIR__).'/resources/','/assets/help/'=>'/framework/app/core_modules/help/resources/'];
foreach($roots as $prefix=>$root)if(str_starts_with($path,$prefix)){
    $real=realpath($root.substr($path,strlen($prefix)));
    if(!$real||!str_starts_with($real,realpath($root).'/')||!in_array(pathinfo($real,PATHINFO_EXTENSION),['css','js','png','woff2','svg'],true)){http_response_code(404);exit;}
    header('Content-Type: '.match(pathinfo($real,PATHINFO_EXTENSION)){'css'=>'text/css','js'=>'text/javascript','png'=>'image/png','svg'=>'image/svg+xml',default=>'font/woff2'});readfile($real);exit;
}
session_save_path(sys_get_temp_dir()); session_start(); require __DIR__.'/bootstrap.php';
if(($_SERVER['HTTP_X_FIXTURE_ROLE']??'')==='outsider')$user->admin=false;
if(($_SERVER['HTTP_X_FIXTURE_ROLE']??'')==='anonymous')$user->logged=false;
$c=new grassguide(); $template=$c->dispatch();
if($template){echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Grass guide isolated fixture</title><link rel="stylesheet" href="/skin/stylesheet.css"><link rel="stylesheet" href="/skin/canvases/_default/stylesheet.css"><link rel="stylesheet" href="/skin/components/editorial.css"><link rel="stylesheet" href="/skin/canvases/chisimba/stylesheet.css"></head><body><div id="Canvas_Content_Body_Region2">';$c->render($template);echo '</div></body></html>';}
