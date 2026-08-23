<?php
if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}
$this->loadClass('htmlheading', 'htmlelements');
$chapters = isset($courseCompletionChapters) && is_array($courseCompletionChapters)
    ? $courseCompletionChapters : array();
$heading = new htmlheading();
$heading->type = 1;
$heading->str = $this->objLanguage->languageText('mod_contextcontent_course_completion_heading', 'contextcontent');
echo $heading->show();
?>
<style>
.course-completion{max-width:54rem;margin:1.5rem auto 2rem}.course-completion-intro{margin:0 0 1.25rem;color:#444}.course-completion-panel{margin:1rem 0;border:1px solid #d7dce1;border-radius:.65rem;background:#fff;overflow:hidden}.course-completion-panel h2{margin:0;padding:.8rem 1rem;background:#f6f8fa;border-bottom:1px solid #d7dce1;font-size:1.05rem}.course-completion-list{list-style:none;margin:0;padding:0}.course-completion-list li{display:flex;justify-content:space-between;gap:1rem;padding:.8rem 1rem;border-top:1px solid #edf0f2}.course-completion-list li:first-child{border-top:0}.course-completion-title{font-weight:600}.course-completion-status{white-space:nowrap;text-align:right}.course-completion-status.passed{color:#157347}.course-completion-status.no-gate{color:#5f6770}.course-completion-placeholder{margin:0;padding:1rem;color:#5f6770}@media(max-width:36rem){.course-completion-list li{display:block}.course-completion-status{margin-top:.25rem;text-align:left}}
</style>
<div class="course-completion">
<p class="course-completion-intro"><?php echo $this->objLanguage->languageText('mod_contextcontent_course_completion_intro', 'contextcontent'); ?></p>
<section class="course-completion-panel">
<h2><?php echo $this->objLanguage->languageText('mod_contextcontent_course_completion_chapters', 'contextcontent'); ?></h2>
<ul class="course-completion-list">
<?php foreach ($chapters as $chapter): ?>
<li>
<span class="course-completion-title"><?php echo htmlspecialchars($chapter['chaptertitle'], ENT_QUOTES, 'UTF-8'); ?></span>
<?php if ($chapter['gate']): ?>
<span class="course-completion-status passed"><?php echo $this->objLanguage->languageText('mod_contextcontent_course_completion_passed', 'contextcontent'); ?> — <?php echo number_format((float) $chapter['bestpercentage'], 1); ?>%</span>
<?php else: ?>
<span class="course-completion-status no-gate"><?php echo $this->objLanguage->languageText('mod_contextcontent_course_completion_no_gate', 'contextcontent'); ?></span>
<?php endif; ?>
</li>
<?php endforeach; ?>
</ul>
</section>
<section class="course-completion-panel">
<h2><?php echo $this->objLanguage->languageText('mod_contextcontent_course_completion_requirements', 'contextcontent'); ?></h2>
<p class="course-completion-placeholder"><?php echo $this->objLanguage->languageText('mod_contextcontent_course_completion_no_requirements', 'contextcontent'); ?></p>
</section>
<?php
$certificateModules=$this->getObject('modules','modulecatalogue');
if($certificateModules->checkIfRegistered('certificate-service')){
    $certificateService=$this->getObject('certificateservice','certificate-service');
    if($certificateService->assignmentFor('course',$this->contextCode)){
        echo '<p class="course-completion-certificate"><a class="button" href="'.$this->uri(array('action'=>'downloadcourse'),'certificate-service').'">'.$this->objLanguage->languageText('mod_certificate_service_download','certificate-service').'</a></p>';
    }
}
?>
</div>
