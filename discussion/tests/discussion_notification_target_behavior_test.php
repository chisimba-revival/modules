<?php
/** Course entry and lobby notification link regression. @author Derek Keats */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
 public function getObject($name,$module){if($name==='courseawarelaunchservice'&&$module==='context')return new courseawarelaunchservice();throw new RuntimeException('Unexpected dependency');}
 public function uri($params,$module){return '/index.php?'.http_build_query(array_merge(array('module'=>$module),$params),'','&amp;');}
}
require dirname(__DIR__,3).'/framework/app/core_modules/context/classes/courseawarelaunchservice_class_inc.php';
require dirname(__DIR__).'/classes/discussionnotificationpublisher_class_inc.php';
$publisher=new discussionnotificationpublisher();
parse_str(parse_url($publisher->notificationTarget('testing106','topic-1','post-2','/broken'),PHP_URL_QUERY),$query);
if($query['module']!=='context'||$query['action']!=='launchcourseactivity'||$query['coursecode']!=='testing106')throw new RuntimeException('Missing course-entry route');
$request=(new courseawarelaunchservice())->request($query['coursecode'],$query['targetmodule'],$query['targetaction'],$query['targetparams']);
if($request['module']!=='discussion'||$request['action']!=='viewtopic'||$request['params']!==array('id'=>'topic-1','post'=>'post-2','type'=>'context'))throw new RuntimeException('Destination changed');
if($publisher->notificationTarget('root','topic-1','post-2','/index.php?module=discussion&amp;id=topic-1')!=='/index.php?module=discussion&id=topic-1')throw new RuntimeException('Lobby link changed');
echo "PASS: course notifications retain topic/post through canonical entry; Lobby links remain direct.\n";
