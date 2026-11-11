<?php
/** Shared readable paths. Owners retain permission checks and content transactions. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class contentpaths extends ChisimbaObject
{
 public function init() {}
 public function enabled(): bool {return $this->getObject('dbsysconfig','sysconfig')->getValue('content_paths_enabled','shorturl','FALSE')==='TRUE';}
 public function store() {return $this->getObject('contentpathstore','shorturl');}
 public function locked(callable $operation) {return $this->enabled()?$this->store()->locked($operation):$operation();}
 public static function slug(string $title): string {
  $title=mb_strtolower(trim($title));
  if(function_exists('iconv')){$ascii=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$title);if($ascii!==false)$title=strtolower($ascii);}
  $slug=trim(preg_replace('/[^a-z0-9]+/','-',$title),'-');
  return rtrim(substr($slug,0,170),'-')?:'page';
 }
 public static function valid(string $slug): bool {
  return strlen($slug)<=190 && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug)
   && !in_array($slug,['api','go','index','gateway','admin','login','logout','config','installer','classes','core-modules','packages','skins','lib','cron','usrfiles','user-images','robots','favicon','wp-admin','wp-content','wp-includes','wp-json','blog','podcast','.well-known'],true);
 }
 public function reserved(string $slug): bool {
  $names=$this->getObject('dbsysconfig','sysconfig')->getValue('content_paths_reserved','shorturl','');
  return in_array($slug,array_map('trim',explode(',',strtolower((string)$names))),true);
 }
 /** Operator-only backfill. It never edits content, authors, dates or status. */
 public function backfill(bool $apply=false): array {
  if(!$this->enabled())throw new RuntimeException('Enable content paths after configuring the server route');
  $result=[];
  try {$this->locked(function()use($apply,&$result){$this->store()->transaction(function()use($apply,&$result){
   foreach(['sitepages','simpleblog','webinar'] as $module) {
    if(!$this->getObject('modules','modulecatalogue')->checkIfRegistered($module))continue;
    foreach($this->store()->candidates($module) as $row) {
     if($this->current($module,$row['id'])!=='')continue;
     $requested=$row['slug']??'';
     if($module==='sitepages') {
      $this->store()->retainPageAlias($row['id'],$requested);
      if(!self::valid($requested) || $this->reserved($requested) || $this->store()->legacyPathExists($requested))$requested='';
     }
     $slug=$this->save($module,$row['id'],html_entity_decode($row['title'],ENT_QUOTES|ENT_HTML5,'UTF-8'),$requested);
     $result[]=['module'=>$module,'id'=>$row['id'],'slug'=>$slug];
    }
   }
   if(!$apply)throw new ContentPathsDryRun();
  });});}catch(ContentPathsDryRun $dryRun){}
  return $result;
 }
 public function current(string $module,string $id): string {return $this->enabled()&&$id!==''?($this->store()->owner($module,$id)['slug']??''):'';}
 public function choose(string $module,string $id,string $title,string $requested=''): string {
  if(!in_array($module,['simpleblog','sitepages','webinar'],true))throw new DomainException('invalid');
  $requested=strtolower(trim($requested));$existing=$this->current($module,$id);
  if($requested==='') {if($existing!=='')return $existing;$requested=self::slug($title);$automatic=true;}else $automatic=false;
  if(!self::valid($requested) || $this->reserved($requested)){if(!$automatic)throw new DomainException('slug_invalid');$requested='page-'.$requested;}
  $base=$requested;
  for($suffix=2;;$suffix++) {
   $row=$this->store()->path($requested);
   $page=$this->getObject('modules','modulecatalogue')->checkIfRegistered('sitepages') ? $this->getObject('dbsitepages','sitepages')->findBySlug($requested) : null;
   $pageConflict=$page && !($module==='sitepages' && (string)$page['id']===$id);
   if(!$this->reserved($requested) && (!$row||($row['module_id']===$module&&$row['content_id']===$id))&&!$pageConflict&&!$this->store()->legacyPathExists($requested))return $requested;
   if(!$automatic)throw new DomainException('slug_taken');
   $requested=$base.'-'.$suffix;
  }
 }
 public function save(string $module,string $id,string $title,string $requested=''): string {
  if(!$this->enabled())return '';
  $slug=$this->choose($module,$id,$title,$requested);$this->store()->claim($module,$id,$slug);return $slug;
 }
 public function url(string $module,string $id,string $fallback): string {
  $slug=$this->current($module,$id);return $slug===''?$fallback:$this->root().$slug;
 }
 public function root(): string {return rtrim($this->getObject('altconfig','config')->getSiteRoot(),'/').'/';}
 public function resolveRequest(string $request): ?array {
  if(!$this->enabled())return null;
  $base=(string)(parse_url($this->root(),PHP_URL_PATH)??'/');$path=parse_url($request,PHP_URL_PATH);
  if(!is_string($path)||!str_starts_with($path,$base))return null;
  $slug=strtolower(trim(substr($path,strlen($base)),'/'));
  if(!self::valid($slug))return null;
  $row=$this->store()->path($slug);
  if(!$row||!in_array($row['module_id'],['simpleblog','sitepages','webinar'],true))return null;
  return ['module'=>$row['module_id'],'action'=>'view','id'=>$row['content_id']];
 }
}

/** Private rollback signal: dry runs exercise the real collision rules. */
class ContentPathsDryRun extends RuntimeException {}
