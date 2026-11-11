<?php
/** Immutable bank items and published activity selections. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class practicebank extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorCallback') { parent::init('tbl_ss_items',$pearDb,$errorCallback); }
    public function items(string $scope): array { return $this->selectPrepared('SELECT * FROM tbl_ss_items WHERE contextcode = ? ORDER BY createdat DESC, id',[$scope]); }
    public function item(string $scope,string $id): ?array { return $this->selectPrepared('SELECT * FROM tbl_ss_items WHERE contextcode = ? AND id = ?',[$scope,$id])[0]??null; }
    public function activities(string $scope): array { return $this->selectPrepared('SELECT * FROM tbl_ss_activities WHERE contextcode = ? ORDER BY createdat DESC, id',[$scope]); }
    public function activity(string $scope,string $id): ?array { return $this->selectPrepared('SELECT * FROM tbl_ss_activities WHERE contextcode = ? AND id = ?',[$scope,$id])[0]??null; }
    public function itemInPublishedActivity(string $scope,string $id): bool
    {
        foreach ($this->activities($scope) as $activity) if (in_array($id,json_decode($activity['itemids'],true,512,JSON_THROW_ON_ERROR),true)) return true;
        return false;
    }
    public function addItem(string $scope,string $uid,array $input,string $fileid,string $kind): string
    {
        $aliases=practicerules::aliases($input['answer'],$input['alternatives']);
        $id=$this->insert(['contextcode'=>$scope,'fileid'=>$fileid,'kind'=>$kind,'answer'=>trim($input['answer']),
            'aliases'=>json_encode($aliases,JSON_THROW_ON_ERROR),'feedback'=>$input['feedback'],'credit'=>$input['credit'],'createdby'=>$uid,'createdat'=>gmdate('c')]);
        if (!$id) throw new RuntimeException('Item save failed');
        return $id;
    }
    public function publish(string $scope,string $uid,array $input): string
    {
        $items=$this->items($scope);
        if (!$items || count($items)>1000) throw new DomainException('coverage');
        $count=filter_var($input['questioncount'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>50]]);
        if (!$count || $count>count($items)) throw new DomainException('coverage');
        if (trim($input['title'])==='') throw new DomainException('invalid');
        $id=bin2hex(random_bytes(16));
        if ($this->executePrepared('INSERT INTO tbl_ss_activities (id, contextcode, title, instructions, questioncount, itemids, createdby, createdat) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$id,$scope,trim($input['title']),$input['instructions'],$count,json_encode(array_column($items,'id'),JSON_THROW_ON_ERROR),$uid,gmdate('c')])===false) throw new RuntimeException('Activity save failed');
        return $id;
    }
}
