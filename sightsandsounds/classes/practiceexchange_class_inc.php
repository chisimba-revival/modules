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
                    'instructions'=>$activity['instructions'],'count'=>(int)$activity['questioncount'],'mode'=>'practice'];
            }
        }
        return ['activities'=>$rows];
    }
    public function package(string $scope,string $id): array {
        $this->scope($scope);$this->getObject('practicerules','sightsandsounds');
        $bank=$this->getObject('practicebank','sightsandsounds');$runs=$this->getObject('practiceruns','sightsandsounds');
        $activity=$bank->activity($scope,$id);if(!$activity)throw new DomainException('missing');
        $run=$runs->owned($scope,$this->uid(),$runs->start($scope,$this->uid(),$activity));$items=[];$size=0;
        foreach(json_decode($run['state'],true,512,JSON_THROW_ON_ERROR) as $qid=>$state) {
            $item=$bank->item($scope,$qid);$media=$this->media($scope,$run['id'],$qid);$size+=$media['size'];
            if($size>104857600)throw new DomainException('coverage');
            unset($media['base64']);
            $items[]=['id'=>$qid,'kind'=>$item['kind'],'answer'=>$item['answer'],'aliases'=>json_decode($item['aliases'],true,512,JSON_THROW_ON_ERROR),
                'feedback'=>$item['feedback'],'credit'=>$item['credit'],'media'=>$media,'state'=>$state];
        }
        return ['version'=>1,'mode'=>'practice','id'=>$run['id'],'activity'=>$id,'context'=>$scope,'owner'=>$this->uid(),
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
        return ['id'=>$receipt['id'],'run'=>$receipt['runid'],'correct'=>(int)$receipt['correct'],'total'=>(int)$receipt['total']];
    }
}
