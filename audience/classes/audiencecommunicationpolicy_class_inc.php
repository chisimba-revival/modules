<?php
/** Consent and cancellation are checked again immediately before each email leaves. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class audiencecommunicationpolicy extends ChisimbaObject {
 public function init(){}
 public function allows(array $m){$r=$this->getObject('audiencecampaigns','audience')->one($m['campaign_id']??'');if(!$r||!in_array($r['state'],['queued','dispatched'],true))return false;$p=json_decode($r['payload'],true);if(isset($p['expires_at'])&&!$this->getObject('webinarannouncements','webinar')->stillCurrent($p))return false;$c=$this->getObject('audienceservice','audience')->one($m['contact_id']??'');return audiencecampaigns::eligible($c,['revision'=>$m['revision']??-1]);}
}
