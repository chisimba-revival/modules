<?php
/** Account-scoped mobile packages. All access is rechecked on every exchange. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class practiceexchange extends ChisimbaObject
{
    private function scope(string $scope): string { return $this->getObject('practiceaccess','sightsandsounds')->scopeFor($scope); }
    private function uid(): string { return (string)$this->getObject('user','security')->userId(); }
    public function catalogue(): array {
        $user=$this->getObject('user','security');$contexts=$this->getObject('dbcontext','context');
        $codes=$user->lookupAdmin($this->uid())?array_column($contexts->getAll(),'contextcode'):$this->getObject('usercontext','context')->getUserContext($this->uid());
        $rows=[];
        foreach(array_unique($codes) as $code) {
            try {$this->scope($code);} catch(DomainException $e) {continue;}
            foreach($this->getObject('practicebank','sightsandsounds')->activities($code) as $activity) {
                $rows[]=['id'=>$activity['id'],'context'=>$code,'contextTitle'=>$contexts->getContextDetails($code)['title'],'title'=>$activity['title'],
                    'instructions'=>$activity['instructions'],'count'=>(int)$activity['questioncount'],'mode'=>$this->getObject('assignmentrules','sightsandsounds')->forActivity($code,$activity['id'])['mode'],
                    'rules'=>$this->getObject('assignmentrules','sightsandsounds')->forActivity($code,$activity['id']),
                    'budget'=>$this->getObject('assignmentrules','sightsandsounds')->budget($code,$activity['id'],$this->uid())];
            }
        }
        return ['activities'=>$rows];
    }
    public function package(string $scope,string $id,int $protocol=1): array {
        $this->scope($scope);$this->getObject('practicerules','sightsandsounds');
        $bank=$this->getObject('practicebank','sightsandsounds');$runs=$this->getObject('practiceruns','sightsandsounds');
        $activity=$bank->activity($scope,$id);if(!$activity)throw new DomainException('missing');
        $rules=$this->getObject('assignmentrules','sightsandsounds')->forActivity($scope,$id);
        if($rules['mode']==='formative'&&$protocol<2)throw new DomainException('upgrade');
        $run=$runs->owned($scope,$this->uid(),$runs->start($scope,$this->uid(),$activity));$items=[];$size=0;
        foreach(json_decode($run['state'],true,512,JSON_THROW_ON_ERROR) as $qid=>$state) {
            $item=$bank->item($scope,$qid);$media=$this->media($scope,$run['id'],$qid);$size+=$media['size'];
            if($size>104857600)throw new DomainException('coverage');
            unset($media['base64']);
            $entry=['id'=>$qid,'kind'=>$item['kind'],'answer'=>$item['answer'],'aliases'=>json_decode($item['aliases'],true,512,JSON_THROW_ON_ERROR),
                'feedback'=>$item['feedback'],'credit'=>$item['credit'],'media'=>$media,'state'=>$state];
            if($rules['mode']==='formative'){unset($entry['answer'],$entry['aliases'],$entry['feedback'],$entry['state']['correct'],$entry['state']['firstCorrect']);}
            $items[]=$entry;
        }
        return ['version'=>$rules['mode']==='formative'?2:1,'mode'=>$rules['mode'],'rules'=>$rules,'id'=>$run['id'],'activity'=>$id,'context'=>$scope,'owner'=>$this->uid(),
            'revision'=>(int)$run['revision'],'title'=>$activity['title'],'instructions'=>$activity['instructions'],'items'=>$items];
    }
    public function media(string $scope,string $runId,string $qid): array {
        $this->scope($scope);$run=$this->getObject('practiceruns','sightsandsounds')->owned($scope,$this->uid(),$runId);
        if(!isset(json_decode($run['state'],true,512,JSON_THROW_ON_ERROR)[$qid]))throw new DomainException('missing');
        $item=$this->getObject('practicebank','sightsandsounds')->item($scope,$qid);
        if(!$item)throw new DomainException('missing');
        $store=$this->getObject('modulefilestore','filemanager');
        $bytes=$store->readMedia('sightsandsounds','practiceaccess',['context'=>$scope,'id'=>$qid],$item['fileid']);
        return ['base64'=>base64_encode($bytes),'size'=>strlen($bytes),'sha256'=>hash('sha256',$bytes),'mime'=>(new finfo(FILEINFO_MIME_TYPE))->buffer($bytes)];
    }
    public function submit(string $scope,string $run,int $revision,array $answers): array {
        $this->scope($scope);$this->getObject('practicerules','sightsandsounds');
        $receipt=$this->getObject('practicecompletions','sightsandsounds')->finish($scope,$this->uid(),$run,$revision,$answers);
        return ['id'=>$receipt['id'],'run'=>$receipt['runid'],'correct'=>(int)$receipt['correct'],'total'=>(int)$receipt['total'],'late'=>$this->getObject('assignmentrules','sightsandsounds')->late($scope,$receipt['activityid'],$receipt['createdat']),'received'=>$receipt['createdat']];
    }
}
