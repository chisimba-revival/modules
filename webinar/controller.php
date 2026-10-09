<?php
/** Public catalogue and explicit, CSRF-protected registration actions. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinar extends controller
{
    public function init(){}
    public function requiresLogin($action=null)
    {return !in_array($this->getParam('action','upcoming'),['booking_status','upcoming','archive','recordings','view','speakers','register','confirm','unsubscribe','notice'],true);}
    private function param($key){$v=$this->getParam($key,'');return is_string($v)?trim($v):'';}
    private function csrf(){return $this->getObject('nativeauthwebcomposition','security')->build()['csrf'];}
    private function redirectNotice($message)
    {header('Location: '.html_entity_decode($this->uri(['action'=>'notice','message'=>$message],'webinar'),ENT_QUOTES,'UTF-8'),true,303);exit;}
    /** Return to the booking section with a one-use, session-owned result. */
    private function redirectBooking($message,$id)
    {
        $_SESSION['webinar_booking_notice'][$id]=$message;
        header('Location: '.html_entity_decode($this->uri(['action'=>'view','id'=>$id],'webinar'),ENT_QUOTES,'UTF-8').'#booking',true,303);exit;
    }
    public function dispatch($action=null)
    {
        if($this->param('action')===''&&($_SERVER['REQUEST_METHOD']??'GET')==='GET')return $this->nextAction('upcoming');
        $action=$this->param('action')?:'upcoming';
        if(in_array($action,['manage','edit','save','preview','tag_suggestions','editor_aux','trash','restore'],true))return $this->editorAction($action);
        if($action==='booking_status')return $this->bookingStatus();
        if(in_array($action,['register','confirm','unsubscribe'],true))return $this->formAction($action);
        if($action==='recordings')return $this->recordings();
        if($action==='notice'){
            $message=$this->param('message');if(!in_array($message,['pending','confirmed','unsubscribed'],true))$message='invalid';
            $this->setVar('webinarNotice',$message);return 'registration_tpl.php';
        }
        $record=$action==='view'?$this->getObject('webinarstore')->one($this->param('id')):null;
        if(!in_array($action,['booking_status','upcoming','archive','recordings','view','speakers'],true)||($action==='view'&&!$record)){http_response_code(404);$this->setVar('webinarMissing',true);}
        if($record&&$record['kind']==='webinar'){
            $this->loadClass('webinarschedule','webinar');$start=webinarschedule::start($record);
            if(($start&&$start->getTimestamp()>time())||isset($_SESSION['webinar_booking_notice'][$record['id']]))return $this->formAction('register');
        }
        $this->setVar('webinarRecord',$record);$this->setVar('webinarAction',$action);
        return 'archive_tpl.php';
    }
    /** Authenticated editor, with identical permission/CSRF checks for native and Ajax forms. */
    private function editorAction($action)
    {
        header('Cache-Control: private, no-store');
        $service=$this->getObject('webinareditservice','webinar');
        if(!$service->allowed()){http_response_code(404);$this->setVar('webinarMissing',true);return 'archive_tpl.php';}
        $id=$this->param('id');$record=$id!==''?$service->read($id):null;
        if($id!==''&&!$record){http_response_code(404);$this->setVar('webinarMissing',true);return 'archive_tpl.php';}
        $kind=$record['kind']??($this->param('kind')==='speaker'?'speaker':'webinar');
        $this->setVar('webinarEditKind',$kind);$this->setVar('webinarEditRecord',$record);
        if(in_array($action,['trash','restore'],true)){
            $error='';
            if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'||!$this->csrf()->consume('webinar-editor',$this->param('csrf_token')))$error='session_expired';
            else try{$service->setTrashed($id,$this->param('version'),$action==='restore');}
            catch(DomainException $failure){$error=in_array($failure->getMessage(),['editor_conflict','editor_forbidden','editor_invalid'],true)?$failure->getMessage():'editor_save_failed';}
            catch(Throwable $failure){$error='editor_save_failed';}
            if($error!==''){http_response_code(422);$this->setVar('webinarRemovalError',$error);}
            else return $this->nextAction('manage',['kind'=>'webinar','removed'=>$action==='trash'?'1':'0']);
            $this->setVar('webinarEditCsrf',$this->csrf()->issue('webinar-editor'));return 'manage_tpl.php';
        }
        if($action==='manage'){$this->setVar('webinarEditCsrf',$this->csrf()->issue('webinar-editor'));return 'manage_tpl.php';}
        if($record&&$record['status']==='trashed'){http_response_code(404);$this->setVar('webinarMissing',true);return 'archive_tpl.php';}
        if($action==='preview'){
            if(!$record){http_response_code(404);$this->setVar('webinarMissing',true);return 'archive_tpl.php';}
            return 'preview_tpl.php';
        }
        if($action==='tag_suggestions'){
            $terms=$service->classification()->creationChoices('webinar','site','site','tag');$q=mb_strtolower($this->param('q'));
            $names=array_column(array_slice(array_values(array_filter($terms,fn($t)=>$q===''||str_contains(mb_strtolower($t['name']),$q))),0,20),'name');
            header('Content-Type: application/json; charset=UTF-8');echo json_encode(['tags'=>$names],JSON_THROW_ON_ERROR);exit;
        }
        $error='';$aux=null;$saved=null;
        if(in_array($action,['save','editor_aux'],true)){
            $input=[];foreach(['title','description','image','image_alt','status','starts_at','ends_at','duration_minutes','timezone','recording','joining_url','tags','version','create_id','registration_open','cancelled'] as $key)$input[$key]=$this->param($key);
            $input['status']=$this->param('publish_action')?:$input['status'];
            $input['categories']=$this->getParam('categories',[]);$input['speakers']=$this->getParam('speakers',[]);
            $this->setVar('webinarEditInput',$input);
            if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST')$error='editor_invalid';
            elseif($this->param('editor_complete')!=='1')$error='editor_invalid';
            elseif(!$this->csrf()->consume('webinar-editor',$this->param('csrf_token')))$error='session_expired';
            else try{
                if($action==='save'){$saved=$service->save($id,$kind,$input);$record=$saved;$this->setVar('webinarEditRecord',$record);$this->setVar('webinarEditInput',null);}
                elseif($this->param('operation')==='category_add'){
                    $term=$service->classification()->saveTerm('site','site','category',$this->param('category_name'),'',$this->param('category_parent'));
                    $aux=['kind'=>'category','id'=>$term['id'],'name'=>$term['name']];$input['categories']=is_array($input['categories'])?$input['categories']:[];$input['categories'][]=$term['id'];
                }elseif($this->param('operation')==='speaker_add'){
                    $speaker=$service->save('','speaker',['title'=>$this->param('speaker_name'),'description'=>$this->param('speaker_bio'),'image'=>'','image_alt'=>'','status'=>'published','create_id'=>$this->param('aux_create_id')]);
                    $aux=['kind'=>'speaker','id'=>$speaker['id'],'name'=>$speaker['title']];$input['speakers']=is_array($input['speakers'])?$input['speakers']:[];$input['speakers'][]=$speaker['id'];
                }else throw new DomainException('editor_invalid');
                if($aux){$aux['next_id']=bin2hex(random_bytes(16));$this->setVar('webinarEditInput',$input);}
            }catch(DomainException $e){$error=$e->getMessage();}
            catch(Throwable $e){$error='editor_save_failed';}
        }
        $allowed=['editor_invalid','editor_forbidden','editor_conflict','editor_date','editor_duration','editor_end','editor_url','editor_recording','editor_speaker','editor_description','editor_save_failed','session_expired'];
        if($error!==''&&!in_array($error,$allowed,true))$error=str_starts_with($error,'classification_')?'editor_classification':'editor_invalid';
        $csrf=$this->csrf()->issue('webinar-editor');$this->setVar('webinarEditCsrf',$csrf);$this->setVar('webinarEditError',$error);
        if($this->param('ajax')==='1'&&in_array($action,['save','editor_aux'],true)){
            $r=$this->getObject('webinarrenderer','webinar');header('Content-Type: application/json; charset=UTF-8');
            if($error!=='')http_response_code($error==='editor_conflict'?409:422);
            $result=['ok'=>$error==='','csrf'=>$csrf,'message'=>$r->text($error?:($aux?'editor_added':($saved&&$saved['status']==='draft'?'editor_draft_saved':'editor_saved'))),'aux'=>$aux];
            if($saved)$result+=['id'=>$saved['id'],'version'=>webinareditservice::version($saved),'edit'=>html_entity_decode($this->uri(['action'=>'edit','id'=>$saved['id']],'webinar'),ENT_QUOTES,'UTF-8'),'preview'=>html_entity_decode($this->uri(['action'=>'preview','id'=>$saved['id']],'webinar'),ENT_QUOTES,'UTF-8')];
            echo json_encode($result,JSON_THROW_ON_ERROR);exit;
        }
        if($saved)return $this->nextAction('edit',['id'=>$saved['id'],'saved'=>'1']);
        return 'editor_tpl.php';
    }
    /** JSON is a public read-only enhancement of the same paginated page. */
    private function recordings()
    {
        $store=$this->getObject('webinarvideos','webinar');$rows=$store->catalogue();
        try{$page=webinarvideos::page($this->getParam('page',1),count($rows));}
        catch(InvalidArgumentException $error){http_response_code(400);$this->setVar('webinarMissing',true);return 'archive_tpl.php';}
        $slice=array_slice($rows,($page-1)*6,6);$r=$this->getObject('webinarrenderer','webinar');
        $data=['html'=>$this->getObject('videocardrenderer','contentblocks')->cards($slice,'webinar-video-player',$r->text('watch')),
            'count'=>count($slice),'total'=>count($rows),'channel'=>$store->channel(),
            'next'=>$page*6<count($rows)?html_entity_decode($this->uri(['action'=>'recordings','page'=>$page+1],'webinar'),ENT_QUOTES,'UTF-8'):null];
        if($this->param('fragment')==='1'){header('Content-Type: application/json; charset=UTF-8');header('X-Content-Type-Options: nosniff');echo json_encode($data,JSON_THROW_ON_ERROR);exit;}
        $this->setVar('videoGallery',$data);return 'recordings_tpl.php';
    }
    private function bookingStatus(){
        header('Cache-Control: no-store');header('Content-Type: application/json; charset=UTF-8');
        $ok=false;$registered=false;
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&$this->csrf()->consume('webinar-booking-check',$this->param('check_token'))){
            $rate=$_SESSION['webinar_booking_checks']??['time'=>time(),'count'=>0];
            if(time()-$rate['time']>60)$rate=['time'=>time(),'count'=>0];
            $rate['count']++;$_SESSION['webinar_booking_checks']=$rate;
            if($rate['count']<=30)try{$record=$this->getObject('webinarstore')->one($this->param('id'));if($record&&$record['kind']==='webinar'){$registered=$this->getObject('webinarregistrationservice')->alreadyRegistered($record,$this->param('email'));$ok=true;}}catch(DomainException $e){}
        }
        if(!$ok)http_response_code(422);
        echo json_encode(['ok'=>$ok,'registered'=>(bool)$registered,'token'=>$this->csrf()->issue('webinar-booking-check'),'message'=>$registered?$this->getObject('webinarrenderer')->text('already_registered'):'']);exit;
    }
    private function formAction($action)
    {
        header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');
        $this->loadClass('webinarschedule','webinar');
        $token=$this->param('token');$record=null;$error='';
        if($action==='register'){
            $record=$this->getObject('webinarstore')->one($this->param('id'));
            if(!$record||!webinarschedule::canRegister($record))$error='closed';
        }elseif($action==='confirm'){
            $registration=$this->getObject('webinarregistrations')->token($token);
            if(!$registration)$error='expired';
            else $record=$this->getObject('webinarstore')->one($registration['webinar_id']);
        }elseif(!$this->getObject('audienceservice','audience')->contactForToken($token))$error='expired';
        if(strtoupper($_SERVER['REQUEST_METHOD']??'GET')==='POST' && $error===''){
            if(!$this->csrf()->consume('webinar-'.$action,$this->param('csrf_token')))$error='session_expired';
            else try {
                $service=$this->getObject('webinarregistrationservice');
                if($action==='register'){
                    if($this->param('website')!=='')throw new DomainException('invalid');
                    $result=$service->register($record,$this->param('name'),$this->param('email'),$this->param('consent')==='1',$_SERVER['REMOTE_ADDR']??'unknown');
                    $this->redirectBooking($result,$record['id']);
                }elseif($action==='confirm'){$confirmed=$service->confirm($token);$this->redirectBooking('confirmed',$confirmed['id']);}
                else {
                    if(!$this->getObject('audienceservice','audience')->unsubscribe($token))throw new DomainException('expired');
                    $this->redirectNotice('unsubscribed');
                }
            }catch(DomainException $e){$error=in_array($e->getMessage(),['invalid','closed','expired','try_later'],true)?$e->getMessage():'invalid';}
            catch(Throwable $e){$error='unavailable';}
        }
        $this->setVar('webinarFormAction',$action);$this->setVar('webinarRecord',$record);
        $this->setVar('webinarToken',$token);$this->setVar('webinarError',$error);
        $this->setVar('webinarCsrf',$this->csrf()->issue('webinar-'.$action));
        $input=['name'=>$this->param('name'),'email'=>$this->param('email'),'consent'=>$this->param('consent')];
        $user=$this->getObject('user','security');
        if($action==='register'&&($_SERVER['REQUEST_METHOD']??'GET')==='GET'&&$user->isLoggedIn()){$input['name']=$user->fullname($user->userId());$savedEmail=$user->email($user->userId());$input['email']=filter_var($savedEmail,FILTER_VALIDATE_EMAIL)?$savedEmail:'';}
        $this->setVar('webinarInput',$input);
        $this->setVar('webinarCheckToken',$this->csrf()->issue('webinar-booking-check'));
        if($action==='register'&&$record){
            if(($_SERVER['REQUEST_METHOD']??'GET')==='GET'&&$this->param('action')==='register'){header('Location: '.html_entity_decode($this->uri(['action'=>'view','id'=>$record['id']],'webinar'),ENT_QUOTES,'UTF-8').'#booking',true,303);exit;}
            $notice=$_SESSION['webinar_booking_notice'][$record['id']]??'';unset($_SESSION['webinar_booking_notice'][$record['id']]);
            $this->setVar('webinarBookingNotice',in_array($notice,['pending','confirmed','already_registered'],true)?$notice:'');
            $this->setVar('webinarInlineBooking',true);$this->setVar('webinarAction','view');return 'archive_tpl.php';
        }
        return 'registration_tpl.php';
    }
}
