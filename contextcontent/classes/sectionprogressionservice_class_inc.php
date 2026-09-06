<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

/** Sections are informational stops placed by chapter membership. */
class sectionprogressionservice extends controller
{
    public function init()
    {
        $this->objContext=$this->getObject('dbcontext','context');
        $this->objSections=$this->getObject('db_contextcontent_sections','contextcontent');
        $this->objContextChapters=$this->getObject('db_contextcontent_contextchapter','contextcontent');
        $this->objGates=$this->getObject('chapterstagegateservice','contextcontent');
    }

    public function enabled($contextCode)
    {
        return (int)$this->objContext->getField('use_sections',$contextCode)===1;
    }

    public function isManager($contextCode)
    {
        return $this->objGates->isCourseManager($contextCode);
    }

    /** Group chapters without changing their canonical chapter order. */
    public function sections($contextCode)
    {
        $manager=$this->isManager($contextCode);
        $stored=$this->objSections->forContext($contextCode,$manager);
        $byId=array();
        foreach ($stored as $section) {
            $section['chapters']=array();
            $byId[$section['id']]=$section;
        }
        $ordered=array();
        $seen=array();
        $chapters=$this->objContextChapters->getContextChapters($contextCode);
        if (!is_array($chapters)) { $chapters=array(); }
        foreach ($chapters as $chapter) {
            $sectionId=(string)($chapter['sectionid'] ?? '');
            if ($sectionId==='' || !isset($byId[$sectionId])) { continue; }
            if (!$manager && ($chapter['visibility'] ?? 'Y')==='N') { continue; }
            if (!isset($seen[$sectionId])) {
                $ordered[]=$byId[$sectionId];
                $seen[$sectionId]=count($ordered)-1;
            }
            $ordered[$seen[$sectionId]]['chapters'][]=$chapter;
        }
        if ($manager) {
            foreach ($stored as $section) {
                if (!isset($seen[$section['id']])) {
                    $section['chapters']=array();
                    $ordered[]=$section;
                }
            }
        }
        return $ordered;
    }

    public function section($contextCode,$sectionId)
    {
        foreach ($this->sections($contextCode) as $section) {
            if ($section['id']===$sectionId) { return $section; }
        }
        return FALSE;
    }

    public function firstSection($contextCode)
    {
        $sections=$this->sections($contextCode);
        return empty($sections)?FALSE:$sections[0];
    }

    /** Follow canonical chapter order, inserting a section when membership changes. */
    public function nextStop($contextCode,$chapterId)
    {
        $chapters=$this->objContextChapters->getContextChapters($contextCode);
        if (!is_array($chapters)) { return array('type'=>'complete','id'=>''); }
        foreach ($chapters as $index=>$chapter) {
            if ($chapter['chapterid']!==$chapterId) { continue; }
            if (!isset($chapters[$index+1])) { return array('type'=>'complete','id'=>''); }
            $next=$chapters[$index+1];
            $currentSection=(string)($chapter['sectionid'] ?? '');
            $nextSection=(string)($next['sectionid'] ?? '');
            if ($nextSection!=='' && $nextSection!==$currentSection) {
                return array('type'=>'section','id'=>$nextSection);
            }
            return array('type'=>'chapter','id'=>$next['chapterid']);
        }
        return array('type'=>'complete','id'=>'');
    }
}
?>
