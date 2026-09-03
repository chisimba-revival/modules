<?php
/** Notification centre and versioned JSON API. @author Derek Keats @package notifications */
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
class notifications extends controller
{
 private const CSRF='notifications_api_v1';private $user;private $service;private $csrf;
 /** Load authentication, domain and CSRF services. */
 public function init(){$this->user=$this->getObject('user','security');$this->service=$this->getObject('notificationservice');$this->csrf=$this->getObject('nativeauthwebcomposition','security')->build()['csrf'];$this->appendArrayVar('headerParams','<link rel="stylesheet" href="'.$this->getResourceUri('notifications.css').'?v='.filemtime(__DIR__.'/resources/notifications.css').'">');$this->appendArrayVar('headerParams','<script defer src="'.$this->getResourceUri('notifications.js').'?v='.filemtime(__DIR__.'/resources/notifications.js').'"></script>');}
 /** Notifications always belong to an authenticated identity. */
 public function requiresLogin($action){return true;}
 /** Restrict public routing to the documented notification surface. */
 public function isValid($action,$default=true){return in_array((string)$action,array('','default','api_v1_feed','api_v1_unread_count','api_v1_mark_read'),true);}
 /** Dispatch the centre or a v1 API resource. */
 public function dispatch($action){$action=(string)$action;if($action==='api_v1_feed')return $this->feed();if($action==='api_v1_unread_count')return $this->unreadCount();if($action==='api_v1_mark_read')return $this->markRead();$this->setVar('notificationFeedUrl',$this->url('api_v1_feed'));$this->setVar('notificationCountUrl',$this->url('api_v1_unread_count'));$this->setVar('notificationReadUrl',$this->url('api_v1_mark_read'));$this->setVar('notificationCsrf',$this->csrf->issue(self::CSRF));return 'centre_tpl.php';}
 private function feed(){if($this->method()!=='GET')return $this->json(array('ok'=>false,'error'=>array('code'=>'method_not_allowed')),405);$limit=(int)$this->getParam('limit',25);$cursor=trim((string)$this->getParam('cursor',''));$unread=(string)$this->getParam('unread','')==='1';$result=$this->service->feed($this->user->userId(),$limit,$cursor,$unread);return $this->json($result,!empty($result['ok'])?200:400);}
 private function unreadCount(){if($this->method()!=='GET')return $this->json(array('ok'=>false,'error'=>array('code'=>'method_not_allowed')),405);return $this->json(array('ok'=>true,'unreadCount'=>$this->service->unreadCount($this->user->userId())));}
 private function markRead(){if($this->method()!=='POST')return $this->json(array('ok'=>false,'error'=>array('code'=>'method_not_allowed')),405);if(!$this->csrf->consume(self::CSRF,(string)$this->getParam('csrf_token','')))return $this->json(array('ok'=>false,'csrfToken'=>$this->csrf->issue(self::CSRF),'error'=>array('code'=>'invalid_csrf')),403);$ok=$this->service->markRead(trim((string)$this->getParam('notification_id','')),$this->user->userId());return $this->json(array('ok'=>$ok,'csrfToken'=>$this->csrf->issue(self::CSRF),'code'=>$ok?'marked_read':'invalid_notification'),$ok?200:400);}
 private function json(array $body,$status=200){http_response_code($status);header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: private, no-store');echo json_encode($body,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);exit;}
 private function method(){return strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET'));}
 private function url($action){return html_entity_decode($this->uri(array('action'=>$action),'notifications'),ENT_QUOTES,'UTF-8');}
}
?>
