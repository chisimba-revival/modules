<?php
/** Evidence-linked AI marking suggestions for Discussion. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class discussionaimarker extends ChisimbaObject
{
    private $aiService=null;
    public function isAvailable(){return $this->ensureAiAvailable();}
    public function suggest(array $discussion,array $evidence,array $rubric)
    {
        if(!$this->ensureAiAvailable()){return array('ok'=>false,'error'=>'ai_unavailable');}
        if($evidence===array()){return array('ok'=>false,'error'=>'insufficient_evidence');}
        if(count($evidence)>500){return array('ok'=>false,'error'=>'evidence_limit_exceeded_manual_review_required');}
        $criteria=array();foreach((array)$rubric['criteria'] as $index=>$criterion){$criteria[]=array('criterionId'=>'criterion-'.($index+1),'objective'=>(string)$criterion['objective'],'maximumMark'=>(int)$criterion['maximumMark'],'levels'=>$criterion['levels']);}
        $schema=array('type'=>'object','properties'=>array('criteria'=>array('type'=>'array','items'=>array('type'=>'object','properties'=>array('criterionId'=>array('type'=>'string'),'score'=>array('type'=>'integer','minimum'=>0),'rationale'=>array('type'=>'string'),'evidencePostIds'=>array('type'=>'array','items'=>array('type'=>'string'))),'required'=>array('criterionId','score','rationale','evidencePostIds'),'additionalProperties'=>false)),'feedback'=>array('type'=>'string'),'insufficientEvidence'=>array('type'=>'boolean')),'required'=>array('criteria','feedback','insufficientEvidence'),'additionalProperties'=>false);
        $instructions='Create an editable marking suggestion for a human lecturer using only the supplied learner posts and rubric. Score each criterion independently, cite the exact post IDs supporting every judgement, and distinguish frequency from quality. Improvement or thoughtful response to feedback over time may support a stronger judgement, but improvement is never required: do not penalise a learner whose work is consistently strong from the outset, and do not reward mere change without improved quality. Do not infer participation outside the evidence, personality, identity, intent, protected characteristics, or whether text was AI-written. If evidence is inadequate, say so and avoid invented support. Never publish or describe the mark as final.';
        $result=$this->aiService->execute(array('consumer'=>'discussion','task'=>'suggest_discussion_mark','instructions'=>$instructions,'input'=>json_encode(array('discussion'=>array('name'=>$discussion['discussion_name'],'description'=>strip_tags($discussion['discussion_description'])),'rubric'=>$criteria,'learnerPosts'=>$evidence)),'schemaName'=>'discussion_marking_suggestion_v1','schema'=>$schema));
        if(empty($result['ok'])||!is_array($result['data']??null)){return array('ok'=>false,'error'=>(string)($result['error']??'provider_failed'));}
        $validIds=array_fill_keys(array_column($evidence,'postId'),true);$byId=array();foreach($criteria as $criterion){$byId[$criterion['criterionId']]=$criterion;}$scored=array();$total=0;
        foreach((array)($result['data']['criteria']??array()) as $item){$id=(string)($item['criterionId']??'');if(!isset($byId[$id])||isset($scored[$id]))continue;$score=min($byId[$id]['maximumMark'],max(0,(int)($item['score']??0)));$cited=array_values(array_filter(array_unique(array_map('strval',(array)($item['evidencePostIds']??array()))),fn($postId)=>isset($validIds[$postId])));$scored[$id]=array('criterionId'=>$id,'objective'=>$byId[$id]['objective'],'score'=>$score,'maximumMark'=>$byId[$id]['maximumMark'],'rationale'=>trim((string)($item['rationale']??'')),'evidencePostIds'=>$cited);$total+=$score;}
        if(count($scored)!==count($criteria)){return array('ok'=>false,'error'=>'incomplete_suggestion');}
        return array('ok'=>true,'suggestion'=>array('mark'=>$total,'criteria'=>array_values($scored),'feedback'=>trim((string)($result['data']['feedback']??'')),'insufficientEvidence'=>!empty($result['data']['insufficientEvidence'])));
    }
    private function ensureAiAvailable(){if($this->aiService!==null)return method_exists($this->aiService,'isAvailable')&&$this->aiService->isAvailable();try{if(!$this->getObject('modules','modulecatalogue')->checkIfRegistered('ai'))return false;$this->aiService=$this->getObject('aiservice','ai');return method_exists($this->aiService,'isAvailable')&&$this->aiService->isAvailable();}catch(Throwable $error){$this->aiService=null;return false;}}
}
