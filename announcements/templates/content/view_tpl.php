<?php
$e=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
$txt=fn($key,$fallback)=>$this->objLanguage->languageText($key,'announcements',$fallback);
$systxt=fn($key,$fallback)=>$this->objLanguage->code2Txt($key,'announcements',NULL,$fallback);
$icons=$this->getObject('iconservice','ui');
$dateTime=$this->getObject('timeanddateservice','timeanddate-service');
$washout=$this->getObject('washout','utilities');
$canManage=$this->checkPermission($announcement['id']);
$typeLabels=array(
    'whats_new'=>$txt('mod_announcements_whatsnew','What’s new'),
    'general'=>$txt('mod_announcements_general','General announcement'),
    'service'=>$txt('mod_announcements_service','Service notice')
);
$type=(string)($announcement['announcement_type']??'general');
$published=$announcement['publish_at']?:$announcement['createdon'];
$scope=$announcement['contextid']==='site'
    ?$txt('mod_announcements_siteword','Site')
    :ucfirst($systxt('mod_announcements_contextscope','[-context-]'));
$scopeDetails=array();
if($announcement['contextid']==='context'){
    foreach((array)$this->objAnnouncements->getMessageContexts($announcement['id']) as $context){
        $scopeDetails[]=$this->objContext->getTitle($context);
    }
}
?>
<main class="chisimba-workspace chisimba-flow announcements-detail">
    <header class="chisimba-page-header chisimba-card announcements-detail__header">
        <div>
            <p class="chisimba-eyebrow"><?php echo $e($typeLabels[$type]??$typeLabels['general']);?></p>
            <h1><?php echo $e($announcement['title']);?></h1>
            <p><?php echo $e($txt('mod_announcements_detailhelp','An announcement shared with you.'));?></p>
        </div>
        <?php if($canManage):?>
            <div class="chisimba-form-actions">
                <a class="button chisimba-button-secondary chisimba-button-compact" href="<?php echo $e($this->uri(array('action'=>'edit','id'=>$announcement['id'])));?>"><?php echo $icons->render('pencil',array('decorative'=>true));?><span><?php echo $e($this->objLanguage->languageText('word_edit','system','Edit'));?></span></a>
                <a class="button chisimba-button-danger chisimba-button-compact" href="<?php echo $e($this->uri(array('action'=>'delete','id'=>$announcement['id'])));?>" onclick="<?php echo $e('return confirm('.json_encode($txt('mod_announcements_deleteconfirm','Delete this announcement?')).');');?>"><?php echo $icons->render('trash-2',array('decorative'=>true));?><span><?php echo $e($this->objLanguage->languageText('word_delete','system','Delete'));?></span></a>
            </div>
        <?php endif;?>
    </header>

    <article class="chisimba-card announcements-detail__card">
        <dl class="announcements-detail__meta">
            <div><dt><?php echo $e($txt('mod_announcements_published','Published'));?></dt><dd><?php echo $e($dateTime->formatDateTime($published));?></dd></div>
            <div><dt><?php echo $e($this->objLanguage->languageText('word_by','system','By'));?></dt><dd><?php echo $e($this->objUser->fullName($announcement['createdby']));?></dd></div>
            <div><dt><?php echo $e($txt('mod_announcements_scope','Scope'));?></dt><dd><?php echo $e($scope);?><?php echo $scopeDetails?' · '.$e(implode(', ',$scopeDetails)):'';?></dd></div>
        </dl>
        <div class="announcements-detail__message"><?php echo $washout->parseText($announcement['message']);?></div>
        <?php if(!empty($announcement['resource_url'])):?>
            <p><a class="button chisimba-button-secondary" href="<?php echo $e($announcement['resource_url']);?>"><?php echo $icons->render('external-link',array('decorative'=>true));?><span><?php echo $e($txt('mod_announcements_userguide','Further information'));?></span></a></p>
        <?php endif;?>
    </article>

    <nav class="chisimba-form-actions announcements-detail__navigation" aria-label="<?php echo $e($txt('mod_announcements_actions','Announcement actions'));?>">
        <a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(NULL));?>"><?php echo $icons->render('list',array('decorative'=>true));?><span><?php echo $e($txt('mod_announcements_allannouncements','All announcements'));?></span></a>
        <?php if($isAdmin||(is_countable($lecturerContext)?count($lecturerContext):0)>0):?>
            <a class="button" href="<?php echo $e($this->uri(array('action'=>'add')));?>"><?php echo $icons->render('plus',array('decorative'=>true));?><span><?php echo $e($txt('mod_announcements_postannouncement','Publish announcement'));?></span></a>
        <?php endif;?>
    </nav>
</main>
