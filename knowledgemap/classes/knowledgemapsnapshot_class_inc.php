<?php
/** Lossless maintenance snapshots; no web restore route. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class knowledgemapsnapshot extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_knowledgemap_maps',$pearDb,$errorCallback);}
    private function maintenance(){if(PHP_SAPI!=='cli'||getenv('CHISIMBA_MAP_MAINTENANCE')!=='1')throw new DomainException('Maintenance only');}
    public function capture($id)
    {
        $this->maintenance();return $this->transactionWithSavepoint(function()use($id){
            $map=$this->selectPrepared('SELECT * FROM tbl_knowledgemap_maps WHERE id=? FOR UPDATE',[$id])[0]??null;
            if(!$map)throw new DomainException('Map missing');$data=['format'=>'chisimba-map-snapshot-v1','map'=>$map,'tables'=>[]];
            foreach(['nodes','relationships','access'] as $name)$data['tables'][$name]=$this->selectPrepared('SELECT * FROM tbl_knowledgemap_'.$name.' WHERE mapid=? ORDER BY id',[$id]);
            return $data;
        });
    }
    public function restore(array $data,$id)
    {
        $this->maintenance();
        if(($data['format']??'')!=='chisimba-map-snapshot-v1'||($data['map']['id']??'')!==$id)throw new DomainException('Wrong snapshot');
        return $this->transactionWithSavepoint(function()use($data,$id){
            $current=$this->selectPrepared('SELECT * FROM tbl_knowledgemap_maps WHERE id=? FOR UPDATE',[$id])[0]??null;
            if(!$current||$current['ownerid']!==$data['map']['ownerid'])throw new DomainException('Map missing or ownership changed');
            $metadata=json_decode((string)$current['sourcemetadata'],true);$code=$metadata['courseBlueprint']['contextCode']??'';
            if($code&&$this->getObject('dbcontext','context')->getContext($code))throw new DomainException('Delete the generated test course before restoring its source map');
            foreach(['relationships','nodes','access'] as $name){
                if(!isset($data['tables'][$name])||!is_array($data['tables'][$name]))throw new DomainException('Incomplete snapshot');
                $this->executePrepared('DELETE FROM tbl_knowledgemap_'.$name.' WHERE mapid=?',[$id]);
                foreach($data['tables'][$name] as $row){if(($row['mapid']??'')!==$id)throw new DomainException('Foreign snapshot row');if(!$this->insert($row,'tbl_knowledgemap_'.$name))throw new RuntimeException('Snapshot insert failed');}
            }
            $map=$data['map'];unset($map['id']);$map['revision']=max((int)$current['revision'],(int)$map['revision'])+1;$map['datemodified']=date('Y-m-d H:i:s');
            if(!$this->update('id',$id,$map))throw new RuntimeException('Snapshot restore failed');return $map['revision'];
        });
    }
}
