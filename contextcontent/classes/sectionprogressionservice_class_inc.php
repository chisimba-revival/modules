<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }
class sectionprogressionservice extends controller {
    public function init() {
        $this->objContext=$this->getObject('dbcontext','context');
        $this->objSections=$this->getObject('db_contextcontent_sections','contextcontent');
        $this->objAcknowledgements=$this->getObject('db_contextcontent_section_acknowledgements','contextcontent');
        $this->objContextChapters=$this->getObject('db_contextcontent_contextchapter','contextcontent');
        $this->objGates=$this->getObject('chapterstagegateservice','contextcontent');
        $this->objUser=$this->getObject('user','security');
    }
    public function enabled($contextCode) { return (int)$this->objContext->getField('use_sections',$contextCode)===1; }
    public function isManager($contextCode) { return $this->objGates->isCourseManager($contextCode); }
    public function sections($contextCode) {
        $manager=$this->isManager($contextCode); $sections=$this->objSections->forContext($contextCode,$manager);
        $chapters=$this->objContextChapters->getContextChapters($contextCode); if (!is_array($chapters)) { $chapters=array(); }
        foreach ($sections as $index=>$section) {
            $group=array(); foreach ($chapters as $chapter) {
                if (($chapter['sectionid'] ?? '')===$section['id'] && ($manager || ($chapter['visibility'] ?? 'Y')!=='N')) { $group[]=$chapter; }
            }
            $available=$manager || $this->priorSectionComplete($contextCode,$sections,$index);
            $acknowledged=$manager || ($this->objUser->isLoggedIn() && $this->objAcknowledgements->has($contextCode,$section['id'],$this->objUser->userId()));
            $sections[$index]['chapters']=$group; $sections[$index]['available']=$available; $sections[$index]['acknowledged']=$acknowledged;
        }
        return $sections;
    }
    public function chapterDecision($contextCode,$chapterId) {
        if (!$this->enabled($contextCode) || $this->isManager($contextCode)) { return array('allowed'=>TRUE); }
        foreach ($this->sections($contextCode) as $section) {
            foreach ($section['chapters'] as $chapter) {
                if ($chapter['chapterid']===$chapterId) {
                    return array('allowed'=>!empty($section['available'])&&!empty($section['acknowledged']),'section'=>$section);
                }
            }
        }
        return array('allowed'=>FALSE,'section'=>FALSE);
    }
    public function acknowledge($contextCode,$sectionId) {
        if (!$this->objUser->isLoggedIn()) { return FALSE; }
        foreach ($this->sections($contextCode) as $section) {
            if ($section['id']===$sectionId && !empty($section['available'])) {
                return $this->objAcknowledgements->acknowledge($contextCode,$sectionId,$this->objUser->userId());
            }
        }
        return FALSE;
    }
    private function priorSectionComplete($contextCode,$sections,$index) {
        if ($index===0) { return TRUE; }
        $previous=$sections[$index-1]; $chapters=$this->objContextChapters->getContextChapters($contextCode);
        $last=FALSE; foreach ($chapters as $chapter) { if (($chapter['sectionid'] ?? '')===$previous['id']) { $last=$chapter; } }
        if ($last===FALSE) { return FALSE; }
        $gate=$this->objGates->chapterGate($contextCode,$last['chapterid']);
        return $gate!==FALSE && $this->objGates->hasPassed($gate);
    }
}
?>
