<?php
/** Contextual event guidance through the shared Help service.
 * @author Derek Keats <derek@dkeats.com>
 */
if(empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($id)
    { return in_array($id,['booking','tickets','arrival'],true)||($id==='organising'&&$this->getObject('eventpolicy','events')->canCreate()); }
    public function getTopic($id)
    {
        if(!$this->mayViewTopic($id)) return null;
        $s=$this->getObject('eventservice','events');
        return ['title'=>$s->text('help_'.$id.'_title'),'summary'=>$s->text('help_'.$id.'_summary'),'steps'=>$id==='organising'?[$s->text('editor_flow_help'),$s->text('help_'.$id.'_steps')]:($id==='arrival'?[$s->text('checkins_intro'),$s->text('team_step_scan'),$s->text('checkin_window_hint')]:($id==='booking'?array_map(fn($key)=>$s->text($key),['booking_help_choose','booking_help_details','booking_help_checkout','booking_help_other']):[$s->text('help_'.$id.'_steps')])),'sections'=>$id==='organising'?[['heading'=>$s->text('arrival_team'),'body'=>$s->text('arrival_team_hint').' '.$s->text('team_step_assign').' '.$s->text('team_step_open').' '.$s->text('team_step_scan')],['heading'=>$s->text('help_editor_title'),'body'=>$s->text('help_editor_body')],['heading'=>$s->text('help_sharing_title'),'body'=>$s->text('help_sharing_body')]]:[]];
    }
}
