<?php
/** Semantic mail report presentation, using shared skin primitives. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class mailreportview extends ChisimbaObject
{
    public function text($key){return $this->getObject('language','language')->code2Txt('mod_myadmin_'.$key,'myadmin');}
    public static function escape($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
    public function summary($data)
    {
        $e=[self::class,'escape'];$max=max(1,max($data['days']));
        $html='<section class="chisimba-form-card chisimba-form-card--wide"><h2>'.$e($this->text('sent_week')).'</h2><p>'.$e($data['timezone']).'</p><table class="chisimba-metric-bars"><tbody>';
        foreach($data['days'] as $day=>$count)$html.='<tr><th scope="row">'.$e($day).'</th><td><meter min="0" max="'.$max.'" value="'.$count.'" aria-label="'.$e($day).'">'.$count.'</meter></td><td>'.$count.'</td></tr>';
        $html.='</tbody></table><h3>'.$e($this->text('subjects_today')).'</h3><div class="chisimba-table-scroll"><table><thead><tr><th>'.$e($this->text('mail_subject')).'</th><th>'.$e($this->text('mail_status')).'</th><th>'.$e($this->text('mail_attempts_total')).'</th></tr></thead><tbody>';
        foreach($data['subjects'] as $row)$html.='<tr><td>'.$e($row['subject']).'</td><td>'.$e($this->status($row['status'])).'</td><td>'.(int)$row['total'].'</td></tr>';
        $html.='</tbody></table></div>';
        if(!$data['subjects'])$html.='<p>'.$e($this->text('mail_empty')).'</p>';
        if($data['truncated'])$html.='<p>'.$e($this->text('mail_summary_limit')).'</p>';
        return $html.'<p>'.$e($this->text('mail_report_help')).'</p><div class="chisimba-form-actions"><a class="button" href="'.$e($this->uri(['action'=>'maillog'],'myadmin')).'">'.$this->getObject('iconservice','ui')->render('mail',['decorative'=>true]).' '.$e($this->text('delivery_log')).'</a>'.$this->getObject('contextualhelp','help')->show('myadmin','mail-report',true).'</div></section>';
    }
    public function status($status)
    {
        return in_array($status,['queued','sending','sent','failed','cancelled'],true)?$this->text('mail_'.$status.'_status'):$status;
    }
}
