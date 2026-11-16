<?php
/** Administrator-only, read-only views of the canonical mail outbox. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class maildeliveryreport extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler')
    {
        parent::init($tableName ?: 'tbl_communications_outbox',$pearDb,$errorCallback);
    }

    public function report($input=[], $now=null)
    {
        if (!$this->getObject('user','security')->isAdmin()) throw new DomainException('forbidden');
        // Communications currently writes date(), i.e. runtime local wall time, not UTC.
        // Preserve that storage contract; do not reinterpret old rows as UTC.
        $now=$now ?: new DateTimeImmutable('now');
        $today=$now->setTime(0,0);$tomorrow=$today->modify('+1 day');
        $start=$today->modify('-6 days');$days=[];
        for($day=$start;$day<$tomorrow;$day=$day->modify('+1 day'))$days[$day->format('Y-m-d')]=0;
        foreach($this->selectPrepared("SELECT DATE(sent_at) AS day,COUNT(*) AS total FROM tbl_communications_outbox WHERE channel='email' AND status='sent' AND sent_at>=? AND sent_at<? GROUP BY DATE(sent_at)",[$start->format('Y-m-d H:i:s'),$tomorrow->format('Y-m-d H:i:s')]) as $row)$days[$row['day']]=(int)$row['total'];
        $subjects=$this->selectPrepared("SELECT subject,status,COUNT(*) AS total FROM tbl_communications_outbox WHERE channel='email' AND date_created>=? AND date_created<? GROUP BY subject,status ORDER BY subject,status LIMIT 501",[$today->format('Y-m-d H:i:s'),$tomorrow->format('Y-m-d H:i:s')]);
        $truncated=count($subjects)>500;$subjects=array_slice($subjects,0,500);
        $date=is_string($input['date']??null)?$input['date']:$today->format('Y-m-d');
        $parsed=preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date)?DateTimeImmutable::createFromFormat('!Y-m-d',$date):false;
        if($date!==''&&(!$parsed||$parsed->format('Y-m-d')!==$date))$date=$today->format('Y-m-d');
        $search=is_string($input['search']??null)?mb_substr(trim($input['search']),0,200):'';
        $status=is_string($input['status']??null)?$input['status']:'';
        if(!in_array($status,['queued','sending','sent','failed','cancelled'],true))$status='';
        $where="channel='email'";$values=[];
        if($date!==''){$where.=' AND date_created>=? AND date_created<?';$values[]=$date.' 00:00:00';$values[]=(new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d H:i:s');}
        if($search!==''){$where.=" AND (recipient LIKE ? ESCAPE '!' OR subject LIKE ? ESCAPE '!')";$term='%'.strtr($search,['!'=>'!!','%'=>'!%','_'=>'!_']).'%';$values[]=$term;$values[]=$term;}
        if($status!==''){$where.=' AND status=?';$values[]=$status;}
        $total=(int)($this->selectPrepared('SELECT COUNT(*) AS total FROM tbl_communications_outbox WHERE '.$where,$values)[0]['total']??0);
        $pages=max(1,(int)ceil($total/50));$page=max(1,min($pages,(int)($input['page']??1)));
        $rows=$this->selectPrepared('SELECT id,recipient,subject,status,transport,attempts,date_created,sent_at,(SELECT a.response_code FROM tbl_communications_attempts a WHERE a.outbox_id=tbl_communications_outbox.id ORDER BY a.attempt_number DESC,a.id DESC LIMIT 1) AS response_code FROM tbl_communications_outbox WHERE '.$where.' ORDER BY date_created DESC,id DESC LIMIT 50 OFFSET '.(($page-1)*50),$values);
        return compact('days','subjects','truncated','rows','total','page','pages','date','search','status')+['timezone'=>$now->getTimezone()->getName()];
    }
}
