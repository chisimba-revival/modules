<?php
/** Browser practice authoring and delivery. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class sightsandsounds extends controller
{
    public $access, $bank, $runs, $language;
    public function init()
    {
        $this->access=$this->getObject('practiceaccess','sightsandsounds');
        $this->bank=$this->getObject('practicebank','sightsandsounds');
        $this->runs=$this->getObject('practiceruns','sightsandsounds');
        $this->language=$this->getObject('language','language');
        $this->getObject('practicerules','sightsandsounds');
    }
    public function requiresLogin($action=null) { return true; }
    public function text($key) { return html_entity_decode($this->language->code2Txt('mod_sightsandsounds_'.$key,'sightsandsounds'),ENT_QUOTES,'UTF-8'); }
    private function input(string $key,int $max=160): string
    {
        $value=$this->getParam($key,'');
        if (!is_string($value) || !mb_check_encoding($value,'UTF-8') || mb_strlen($value)>$max) throw new DomainException('invalid');
        return $value;
    }
    public function dispatch()
    {
        $action='home'; $input=[]; $scope='';
        try {
            $scope=$this->access->scope();
            $uid=(string)$this->getObject('user','security')->userId();
            $action=$this->input('action',30) ?: 'home';
            if (!in_array($action,['home','bank','add','publish','start','play','check','media'],true)) throw new DomainException('invalid');
            if (in_array($action,['bank','add','publish'],true)) $this->access->requireManager();
            $id=$this->input('id',32);
            $input=['answer'=>$this->input('answer'),'alternatives'=>$this->input('alternatives',4800),'feedback'=>$this->input('feedback',2000),
                'credit'=>$this->input('credit',500),'title'=>$this->input('title',200),'instructions'=>$this->input('instructions',2000),'questioncount'=>$this->input('questioncount',3)];
            if (in_array($action,['add','publish','start','check'],true)) $this->access->mutation($_SERVER['REQUEST_METHOD']??'', $this->input('csrf_token',256));
            if ($action==='add') {
                if (trim($input['answer'])==='') throw new DomainException('invalid');
                practicerules::aliases($input['answer'],$input['alternatives']);
                $file=$_FILES['media']??null;
                if (!is_array($file) || !is_string($file['tmp_name']??null) || ($file['error']??-1)!==UPLOAD_ERR_OK
                    || !is_uploaded_file($file['tmp_name']) || filesize($file['tmp_name'])>10485760) throw new DomainException('media');
                $bytes=file_get_contents($file['tmp_name']);
                $store=$this->getObject('modulefilestore','filemanager');
                try { $type=$store::mediaType($bytes); } catch (InvalidArgumentException $e) { throw new DomainException('media'); }
                $fileid=$store->storeMedia('sightsandsounds','practiceaccess',$bytes);
                try { $this->bank->addItem($scope,$uid,$input,$fileid,$type['category']==='images'?'image':'audio'); }
                catch(Throwable $e) { $store->discard('sightsandsounds','practiceaccess',$fileid); throw $e; }
                return $this->nextAction('bank',['saved'=>'1']);
            }
            if ($action==='publish') { $this->bank->publish($scope,$uid,$input); return $this->nextAction('home',['saved'=>'1']); }
            if ($action==='start') {
                $activity=$this->bank->activity($scope,$id);
                if (!$activity) throw new DomainException('missing');
                $run=$this->runs->start($scope,$uid,$activity,$this->input('fresh',1)==='1');
                return $this->nextAction('play',['id'=>$run]);
            }
            if ($action==='check') {
                $revision=$this->input('revision',10);
                if (!ctype_digit($revision)) throw new DomainException('invalid');
                $this->runs->checkAnswer($scope,$uid,$id,$this->input('itemid',32),$input['answer'],(int)$revision);
                return $this->nextAction('play',['id'=>$id,'itemid'=>$this->input('itemid',32)]);
            }
            if ($action==='media') {
                $item=$this->bank->item($scope,$id);
                if (!$item) throw new DomainException('missing');
                $this->getObject('filedelivery','filemanager')->sendOwnedFile($item['fileid'],'sightsandsounds','practiceaccess',$id);
                return null;
            }
            if ($action==='play') $this->setVar('ssRun',$this->runs->owned($scope,$uid,$id));
        } catch (Throwable $e) {
            $key=$e instanceof DomainException?$e->getMessage():'failed';
            if (!in_array($key,['denied','invalid','method','csrf','coverage','missing','stale','media'],true)) $key='failed';
            http_response_code(match($key){'denied','csrf'=>403,'method'=>405,'missing'=>404,'stale'=>409,default=>422});
            $this->setVar('ssError',$this->text('error_'.$key));
            // Failed authoring retains text; no private listing on an access failure.
            if ($scope==='' || in_array($key,['denied','method','missing'],true)) return 'error_tpl.php';
            if (in_array($action,['add','publish'],true)) $action='bank';
            elseif ($action==='check') {
                $this->setVar('ssDraft',$input['answer']??'');
                $this->setVar('ssRun',$this->runs->owned($scope,$uid,$id)); $action='play';
            } else return 'error_tpl.php';
        }
        $this->setVar('ssScope',$scope);
        $this->setVar('ssInput',$input);
        $this->setVar('ssManager',$this->access->manager());
        $this->setVar('ssActivities',$this->bank->activities($scope));
        if ($action==='bank') $this->setVar('ssItems',$this->bank->items($scope));
        $this->appendArrayVar('headerParams',$this->getJavaScriptFile('practice.js','sightsandsounds'));
        return $action==='play'?'play_tpl.php':($action==='bank'?'bank_tpl.php':'home_tpl.php');
    }
}
