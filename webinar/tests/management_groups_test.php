<?php
$GLOBALS['kewl_entry_point_run']=true;class ChisimbaObject{}
require __DIR__.'/../classes/webinarschedule_class_inc.php';require __DIR__.'/../classes/webinarmanagement_class_inc.php';
function row($id,$date,$status='published',$cancelled=false){return ['id'=>$id,'title'=>$id,'kind'=>'webinar','status'=>$status,'presented_at'=>$date,'payload'=>json_encode(['timezone'=>'Africa/Johannesburg','ends_at'=>$date,'cancelled'=>$cancelled])];}
function check($v,$m){if(!$v)throw new RuntimeException($m);}
$now=(new DateTimeImmutable('2026-10-09 12:00:00+02:00'))->getTimestamp();
$rows=[row('future','2027-01-01'),row('next','2026-10-15'),row('recent','2026-09-01'),row('boundary','2026-04-09 12:00:00'),row('old','2026-04-09 11:59:59'),row('undated','','draft'),row('past-draft','2026-09-02','draft'),row('cancelled','2026-09-03','published',true),row('just-ended','2026-10-09 12:00:00')];
$g=webinarmanagement::groups($rows,$now);
check(array_column($g['upcoming'],'id')===['next','future'],'Upcoming order');
check(array_column($g['recent'],'id')===['just-ended','cancelled','past-draft','recent','boundary'],'Recent reverse order and six month boundary');
check(array_column($g['older'],'id')===['old'],'Older cutoff');check(array_column($g['unscheduled'],'id')===['undated'],'Undated retained');
check(webinarmanagement::status($rows[2],$now)==='completed','Published past completed');check(webinarmanagement::status($rows[6],$now)==='editor_draft','Past draft stays draft');check(webinarmanagement::status($rows[7],$now)==='cancelled','Cancelled not completed');check(webinarmanagement::status($rows[5],$now)==='editor_draft','Undated draft');
echo "PASS: staff date groups, ascending upcoming, descending history, exact end/six-month boundaries, drafts, cancellation, undated retention.\n";
