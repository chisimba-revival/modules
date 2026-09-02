<?php
/** Learner recovery when Essay is opened without a usable course scope. @author Derek Keats */
$e=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
$icons=$this->getObject('iconservice','ui');
?>
<section class="dashboard-panel essay-context-recovery" role="alert" aria-labelledby="essay-context-title">
    <header class="dashboard-panel__header"><div><p class="dashboard-eyebrow">Course required</p>
        <?php if(!empty($targetCourse)): ?>
        <h1 id="essay-context-title">You are not in this course</h1>
        <p>Would you like to enter <?php echo $e($targetCourse['title']); ?> now?</p>
        <?php else: ?>
        <h1 id="essay-context-title">You are not currently in a course</h1>
        <p>Choose a course below to continue with Essays. Your course will be established before the Essay page opens.</p>
        <?php endif; ?></div>
        <?php echo $icons->render('map-pinned',array('decorative'=>true)); ?>
    </header>
    <?php if(!empty($targetCourse)): ?>
    <div class="chisimba-actions">
        <a class="button" href="<?php echo $e($targetCourse['url']); ?>"><?php echo $icons->render('log-in',array('decorative'=>true)); ?> Enter course</a>
        <a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(array(),'mylearning')); ?>"><?php echo $icons->render('arrow-left',array('decorative'=>true)); ?> Return to My Learning</a>
    </div>
    <?php else: ?>
    <div class="essay-context-recovery__courses">
    <?php if(empty($recoveryCourses)): ?>
        <div class="dashboard-empty-state"><span class="dashboard-empty-state__icon"><?php echo $icons->render('book-open',array('decorative'=>true)); ?></span><div><h2>No enrolled courses</h2><p>Open the course catalogue to find an available course.</p></div></div>
    <?php else: foreach($recoveryCourses as $course): ?>
        <a class="chisimba-card essay-context-choice" href="<?php echo $e($course['url']); ?>">
            <?php echo $icons->render('graduation-cap',array('decorative'=>true)); ?><span><?php echo $e($course['title']); ?></span><?php echo $icons->render('arrow-right',array('decorative'=>true)); ?>
        </a>
    <?php endforeach; endif; ?>
    </div>
    <div class="chisimba-actions"><a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(array(),'mylearning')); ?>"><?php echo $icons->render('arrow-left',array('decorative'=>true)); ?> Return to My Learning</a>
    <a class="button" href="<?php echo $e($this->uri(array('action'=>'catalogue'),'context')); ?>"><?php echo $icons->render('library-big',array('decorative'=>true)); ?> Course catalogue</a></div>
    <?php endif; ?>
</section>
