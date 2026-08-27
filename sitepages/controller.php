<?php
/** Controller for ordinary site pages. */
class sitepages extends controller
{
    private $db;
    private $user;
    private $cleaner;
    public function init()
    {
        $this->db=$this->getObject('dbsitepages','sitepages');
        $this->user=$this->getObject('user','security');
        $this->cleaner=$this->getObject('htmlcleaner','utilities');
        $this->ensureRefundPolicy();
        $this->setLayoutTemplate('layout_tpl.php');
    }
    /** Supply the compliance page on every installation without a legacy CMS. */
    private function ensureRefundPolicy()
    {
        if($this->db->findBySlug('refund-and-cancellation-policy')) return;
        $body=<<<'HTML'
<p>This policy explains how cancellations and refunds are handled for online courses, memberships and related learning services purchased through this platform.</p>
<h2>Before you pay</h2>
<p>The checkout page identifies what you are buying, the price, the billing period and whether access begins immediately. Please review these details before completing payment.</p>
<h2>One-off course purchases</h2>
<p>Please contact us promptly if you paid twice, paid the wrong amount, bought the wrong course by mistake, or did not receive the access described at checkout. Valid duplicate and incorrect charges will be refunded.</p>
<p>Where immediate online access begins at your request, the service has started. A statutory cooling-off right may not apply once a digital service has begun with your consent. We will nevertheless consider a cancellation request fairly, taking account of when it was made, how much of the course was accessed or completed, and any costs already incurred.</p>
<h2>Memberships and recurring payments</h2>
<p>You may cancel a recurring membership at any time to prevent its next renewal. Unless the law or the checkout terms require otherwise, access continues until the end of the period already paid for and no further payment will be collected. We do not normally provide a partial refund for an already-started membership period, but we will review service failures, duplicate charges and other exceptional circumstances fairly.</p>
<h2>Courses requiring manual admission</h2>
<p>If admission is declined, or a paid course cannot be provided, the applicable course payment will be refunded. Any separate application, assessment or third-party fees will be treated according to the terms displayed before they are paid.</p>
<h2>If we cancel or cannot provide a service</h2>
<p>If we cancel a paid course or cannot provide the purchased service, you may choose an appropriate replacement where one is available or receive a refund for the part not provided.</p>
<h2>How to request a cancellation or refund</h2>
<p>Use the customer-service contact details published on this site. Include your full name, account email address, the course or membership, payment date, payment reference and a short explanation. Do not send card details.</p>
<p>We will acknowledge the request, review the account and payment record, and tell you the outcome. Approved refunds are returned through the original payment method where possible. Banks and payment providers may take additional time to show the credit. Where legislation sets a refund deadline, that deadline applies.</p>
<h2>Payment disputes and unauthorised transactions</h2>
<p>Tell us immediately if you believe a transaction was unauthorised. We may suspend the related access while the payment provider investigates. This does not prevent you from contacting your bank or exercising any chargeback or statutory right available to you.</p>
<h2>Your legal rights</h2>
<p>This policy does not exclude or limit rights that cannot lawfully be excluded, including applicable rights under South African consumer and electronic-transactions legislation. If a course or service is defective, materially different from its description, or not supplied with reasonable care, the remedies required by law remain available.</p>
<p><strong>Last updated:</strong> 27 August 2026.</p>
HTML;
        $this->db->savePage(array('slug'=>'refund-and-cancellation-policy','title'=>'Refund and cancellation policy','body_html'=>$body,'status'=>'published'),'system');
    }
    public function requiresLogin($action)
    {
        return in_array((string)$action,array('manage','save','archive'),true);
    }
    public function dispatch($action)
    {
        $action=(string)$action;
        if($action===''&&$this->canManage())$action='manage';
        return match($action){'manage'=>$this->manage(),'save'=>$this->save(),'archive'=>$this->archive(),default=>$this->view()};
    }
    private function canManage(){return $this->user->isAdmin();}
    private function text($key){return $this->getObject('language','language')->languageText('mod_sitepages_'.$key,'sitepages');}
    private function token()
    {
        $token=(string)$this->getSession('sitepages_csrf');
        if($token===''){$token=bin2hex(random_bytes(24));$this->setSession('sitepages_csrf',$token);}
        return $token;
    }
    private function validPost()
    {
        $expected=(string)$this->getSession('sitepages_csrf');$actual=(string)$this->getParam('csrf_token','');
        return ($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&$expected!==''&&hash_equals($expected,$actual);
    }
    private function flash($message){$this->setSession('sitepages_flash',$message);}
    private function labels()
    {
        $labels=array();foreach(array('title','intro','new','edit','archive','preview','save','cancel','pagetitle','slug','slughelp','content','status','draft','published','empty','forbidden','confirmarchive') as $key)$labels[$key]=$this->text($key);return $labels;
    }
    private function manage()
    {
        if(!$this->canManage())$this->setVar('sitepagesDenied',true);
        $edit=false;$id=(string)$this->getParam('id','');if($id!==''&&$this->canManage())$edit=$this->db->find($id);
        $this->setVar('sitepagesLabels',$this->labels());$this->setVar('sitepagesRows',$this->canManage()?$this->db->activeRows():array());$this->setVar('sitepagesEdit',$edit);$this->setVar('sitepagesCsrf',$this->token());$this->setVar('sitepagesFlash',(string)$this->getSession('sitepages_flash'));$this->setSession('sitepages_flash','');return 'manage_tpl.php';
    }
    private function save()
    {
        if(!$this->canManage()||!$this->validPost()){$this->flash($this->text('forbidden'));return $this->nextAction('manage');}
        $id=(string)$this->getParam('id','');$slug=strtolower(trim((string)$this->getParam('slug','')));$title=trim((string)$this->getParam('title',''));$body=trim((string)$this->getParam('body_html',''));$status=(string)$this->getParam('status','draft')==='published'?'published':'draft';$collision=$this->db->findBySlug($slug);
        if($title===''||$body===''||!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$slug)||($collision&&(string)$collision['id']!==$id)){$this->flash($this->text('invalid'));return $this->nextAction('manage',$id!==''?array('id'=>$id):array());}
        $saved=$this->db->savePage(array('slug'=>$slug,'title'=>$title,'body_html'=>$this->cleaner->cleanHtml($body),'status'=>$status),$this->user->userId(),$id);$this->flash($saved?$this->text('saved'):$this->text('invalid'));return $this->nextAction('manage');
    }
    private function archive()
    {
        if($this->canManage()&&$this->validPost()){$this->db->archive((string)$this->getParam('id',''),$this->user->userId());$this->flash($this->text('archived'));}else{$this->flash($this->text('forbidden'));}return $this->nextAction('manage');
    }
    private function view()
    {
        $page=$this->db->findBySlug((string)$this->getParam('slug',''),!$this->canManage());if(!$page||(!$this->canManage()&&($page['status']??'')!=='published'))$this->setVar('sitepagesMissing',$this->text('notfound'));else$this->setVar('sitepagesPage',$page);return 'view_tpl.php';
    }
}
?>
