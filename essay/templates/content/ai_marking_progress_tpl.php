<?php /** Essay AI-marking queue progress. @package essay @author Derek Keats */
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');$icons=$this->getObject('iconservice','ui');$refresh=$this->uri(array('action'=>'aimarkingjob','id'=>$aiMarkingJob['id'])); ?>
<section class="chisimba-workspace"><div class="chisimba-summary-card"><h2><?php echo $icons->render('sparkles',array('decorative'=>true)); ?> Preparing marking suggestions</h2>
<?php if($aiMarkingJob['status']==='failed'): ?><p>The AI suggestions could not be generated. You can continue marking manually.</p>
<?php else: ?><p>The Essay is in the marking queue. This page will refresh automatically.</p><meta http-equiv="refresh" content="4;url=<?php echo $e($refresh); ?>">
<?php endif; ?></div></section>
