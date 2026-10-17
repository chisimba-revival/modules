<?php
if(PHP_SAPI!=='cli'||!in_array($argv[1]??'', ['textblock','slate'],true))exit(64);
$GLOBALS['kewl_entry_point_run']=true;
set_error_handler(static function($n,$s){throw new ErrorException($s,0,$n);});
class ChisimbaObject {public static $user,$language;function getObject($name,$module=null){return $name==='user'?self::$user:self::$language;}}
ChisimbaObject::$user=new class {public bool $logged=false,$admin=false;function isLoggedIn(){return $this->logged;}function isAdmin(){return $this->admin;}};
$module=$argv[1];$terms=[];
foreach(file(dirname(__DIR__,2).'/'.$module.'/register.conf') as $line)if(str_starts_with($line,'TEXT: ')){[$key,$description,$text]=explode('|',substr(trim($line),6),3);$terms[$key]=$text;}
ChisimbaObject::$language=new class($terms){function __construct(private $terms){}function languageText($key,$module=null){return $this->terms[$key];}};
require dirname(__DIR__,2).'/'.$module.'/classes/helpcontent_class_inc.php';$help=new helpcontent();
if($help->mayViewTopic('editing'))throw new RuntimeException('Anonymous help access');
ChisimbaObject::$user->logged=true;if($help->mayViewTopic('editing'))throw new RuntimeException('Non-admin help access');
ChisimbaObject::$user->admin=true;
if(!$help->mayViewTopic('editing')||$help->mayViewTopic('unknown')||$help->getTopic('unknown')!==null)throw new RuntimeException('Help permission contract');
$topic=$help->getTopic('editing');if(!$topic['title']||count($topic['steps'])!==5 || !in_array($terms['mod_'.$module.'_help_delete_recovery'], $topic['steps'], true))throw new RuntimeException('Help incomplete');
echo "PASS $module: admin-only help, complete registered recovery/delete guidance\n";
