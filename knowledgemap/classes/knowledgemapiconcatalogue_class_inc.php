<?php
/**
 * Curated node-icon catalogue for Active Knowledge Maps.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');

/** Exposes a deliberately bounded semantic subset of the skin's Lucide icons. */
class knowledgemapiconcatalogue extends controller
{
    /** Return icons grouped for fast visual selection. */
    public function groups(){return array(
        'Knowledge'=>array('lightbulb','book-open-text','library','notebook-pen','file-text','quote','circle-help','info'),
        'Media'=>array('video','clapperboard','file-text','notebook-pen','image','music'),
        'Structure'=>array('folder','list-tree','layers','network','link-2','workflow','tags','boxes'),
        'Learning and work'=>array('graduation-cap','school','target','presentation','calendar-days','clock','flag'),
        'Projects'=>array('clipboard-list','clipboard-check','circle-play','chart-no-axes-combined','list-checks','circle-check-big','rocket','circle-pause','circle-x','octagon-alert','hourglass','calendar-clock','milestone','archive'),
        'People and places'=>array('user','users','messages-square','hand-helping','globe-2','map-pin','building-2','home'),
        'Research and subjects'=>array('flask-conical','microscope','atom','calculator','chart-line','palette','music','image'),
        'Meaning and status'=>array('star','bookmark','circle-check','triangle-alert','eye','lock','heart','sparkles'),
        'Nature'=>array('leaf','sprout','trees','tree-deciduous','tree-pine','shrub','flower-2','wheat','bird','paw-print','fish','bug','rabbit','turtle','earth','mountain','waves','droplet','sun','cloud-rain','wind','snowflake','flame','rainbow')
    );}

    /** Resolve semantic media labels through the shared language and system-text service. */
    public function mediaLabels(){
        $language=$this->getObject('language','language');$labels=array();
        foreach(array('create-video','use-video','create-text','use-text') as $intent)
            $labels[$intent]=$language->languageText('mod_knowledgemap_'.str_replace('-','_',$intent),'knowledgemap');
        return $labels;
    }

    /** Project meanings are labels only; stored Lucide identifiers remain stable. */
    public function projectLabels(){
        $language=$this->getObject('language','language');
        return array(
            'clipboard-list'=>$language->languageText('mod_knowledgemap_project_planning','knowledgemap','Planning'),
            'clipboard-check'=>$language->languageText('mod_knowledgemap_project_ready_to_start','knowledgemap','Ready to start'),
            'circle-play'=>$language->languageText('mod_knowledgemap_project_started','knowledgemap','Started'),
            'chart-no-axes-combined'=>$language->languageText('mod_knowledgemap_project_good_progress','knowledgemap','Good progress'),
            'list-checks'=>$language->languageText('mod_knowledgemap_project_almost_done','knowledgemap','Almost done'),
            'circle-check-big'=>$language->languageText('mod_knowledgemap_project_completed','knowledgemap','Completed'),
            'rocket'=>$language->languageText('mod_knowledgemap_project_deployed','knowledgemap','Deployed'),
            'circle-pause'=>$language->languageText('mod_knowledgemap_project_stalled','knowledgemap','Stalled'),
            'circle-x'=>$language->languageText('mod_knowledgemap_project_cancelled','knowledgemap','Cancelled'),
            'octagon-alert'=>$language->languageText('mod_knowledgemap_project_blocked','knowledgemap','Blocked'),
            'hourglass'=>$language->languageText('mod_knowledgemap_project_awaiting_approval','knowledgemap','Awaiting approval'),
            'calendar-clock'=>$language->languageText('mod_knowledgemap_project_scheduled','knowledgemap','Scheduled'),
            'milestone'=>$language->languageText('mod_knowledgemap_project_milestone','knowledgemap','Milestone'),
            'archive'=>$language->languageText('mod_knowledgemap_project_archived','knowledgemap','Archived')
        );
    }

    /** Return each curated icon name once. */
    public function names(){return array_values(array_unique(array_merge(...array_values($this->groups()))));}
}
?>
