<?php
/** Bounded queue processing. Never logs content; abandoned claims require explicit recovery. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class spokenworker extends ChisimbaObject
{
    public function runOne()
    {
        $store=$this->getObject('spokenstore'); $row=$store->claim(); if (!$row) return ['selected'=>0];
        $transcribing=$row['state']==='transcribing';
        $values=['state'=>$transcribing?'transcription_failed':'feedback_failed','error_code'=>'processing_failed'];
        try {
            if (!$this->getObject('spokenpolicy')->member($row['userid'],$row['contextcode'])) {
                $values['error_code']='access_changed';
            } elseif (!$this->getObject('spokenservice')->automaticEnabled()) {
                $values['error_code']=$transcribing?'transcription_unavailable':'feedback_unavailable';
            } elseif ($transcribing) {
                $result=$this->getObject('aiservice','ai')->transcribe('spokenassessment',$this->getObject('spokenfiles')->path($row['id']));
                if (!empty($result['ok'])) $values=['state'=>'transcript_ready','original_transcript'=>$result['text'],'transcription_model'=>$result['model']??'','error_code'=>''];
                else $values['error_code']='transcription_unavailable';
            } else {
                $result=$this->getObject('spokenfeedback')->suggest($row);
                if (!empty($result['ok'])) $values=['state'=>'feedback_ready','feedback_json'=>json_encode($result['feedback'],JSON_THROW_ON_ERROR),'feedback_model'=>$result['model'],'error_code'=>''];
                else $values['error_code']='feedback_unavailable';
            }
        } catch (Throwable $e) { /* Deliberately omit provider bodies and private content. */ }
        $saved=$store->finish($row,$values);
        return ['selected'=>1,'completed'=>$saved?1:0,'state'=>$saved?$values['state']:'superseded'];
    }
}
