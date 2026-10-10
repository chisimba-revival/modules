<?php
/** Local editor and shared host contracts, with synthetic data and no external calls.
 * @author Derek Keats <derek@dkeats.com>
 */
$argv[1]='--library'; require __DIR__.'/runtime_test.php';
$engine->loadClass('hostservice','host-service');
/** Synthetic identity through the canonical host permission boundary.
 * @author Derek Keats <derek@dkeats.com>
 */
class EditorHostFixture extends hostservice {
    public $identity;
    public function __construct($engine,$identity){$this->objEngine=$engine;$this->moduleName='host-service';$this->identity=$identity;}
    public function getObject($name,$moduleName=''){return $name==='user'?$this->identity:parent::getObject($name,$moduleName);}
}
/** Default terms isolated from the site's actual configuration.
 * @author Derek Keats <derek@dkeats.com>
 */
class EditorConfigFixture {
    public $real; public $terms='Synthetic default terms';
    public function getValue($name,$module){return $name==='EVENTS_BOOKING_TERMS'?$this->terms:$this->real->getValue($name,$module);}
}
$hosts=new EditorHostFixture($engine,$user); $hostId=bin2hex(random_bytes(16)); $eventId=null;
$config=new EditorConfigFixture(); $config->real=$engine->getObject('dbsysconfig','sysconfig');
$s->overrides['hostservice']=$hosts; $s->overrides['dbsysconfig']=$config;
try {
    $user->logged=false; $reject(fn()=>$hosts->create(['create_id'=>$hostId,'name'=>'Synthetic host']),'forbidden'); $user->logged=true;
    $reference=$hosts->create(['create_id'=>$hostId,'name'=>'Synthetic editor host','biography'=>'Synthetic biography']);
    $assert($hosts->create(['create_id'=>$hostId,'name'=>'Changed retry'])===$reference,'Host retry identity');
    $assert($hosts->profile($reference)['name']==='Synthetic editor host','Retry preserves first profile');
    $e=$s->saveEvent(['title'=>'Disposable Events editor tests','summary'=>'Synthetic summary','description'=>'Synthetic description','status'=>'published','host_reference'=>$reference,'host_user_id'=>'obsolete-input','difficulty'=>'moderate']); $eventId=$e['id'];
    $public=$s->publicEvent($eventId); $assert($public['details']['host']==='Synthetic editor host','Selected host replaces obsolete ID');
    $assert($public['details']['host_biography']==='Synthetic biography','Shared biography resolves');
    $assert($public['details']['terms']==='Synthetic default terms','Configured terms applied');
    $assert(!isset($public['details']['host_reference']),'Internal host reference not public');
    $reject(fn()=>$s->saveEvent(['title'=>'Test','summary'=>'Test','description'=>'Test','gallery'=>'not-a-url']),'field:gallery:gallery_invalid');
    $reject(fn()=>$s->saveEvent(['title'=>'','summary'=>'Test','description'=>'Test']),'field:title:field_required');
    $assert($s->bookingTerms(['details'=>json_encode(['terms'=>'Event-specific terms'])])==='Event-specific terms','Event override preserved');
    echo "PASS: host authorisation and retry, shared biography, legacy ID replacement, configured and overridden terms, field-specific validation.\n";
} finally {
    if($eventId)$db->exec('DELETE FROM tbl_events_events WHERE id='.$db->quote($eventId));
    $db->exec('DELETE FROM tbl_host_service_profiles WHERE id='.$db->quote($hostId).' AND owner_id='.$db->quote('events-test-user'));
}
