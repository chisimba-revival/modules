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
        'Structure'=>array('folder','list-tree','layers','network','link-2','workflow','tags','boxes'),
        'Learning and work'=>array('graduation-cap','school','target','presentation','clipboard-check','calendar-days','clock','flag'),
        'People and places'=>array('user','users','messages-square','hand-helping','globe-2','map-pin','building-2','home'),
        'Research and subjects'=>array('flask-conical','microscope','atom','calculator','chart-line','palette','music','image'),
        'Meaning and status'=>array('star','bookmark','circle-check','triangle-alert','eye','lock','heart','sparkles'),
        'Nature'=>array('leaf','sprout','trees','tree-deciduous','tree-pine','shrub','flower-2','wheat','bird','paw-print','fish','bug','rabbit','turtle','earth','mountain','waves','droplet','sun','cloud-rain','wind','snowflake','flame','rainbow')
    );}

    /** Return each curated icon name once. */
    public function names(){return array_values(array_unique(array_merge(...array_values($this->groups()))));}
}
?>
