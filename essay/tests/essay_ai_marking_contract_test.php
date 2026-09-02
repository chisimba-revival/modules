<?php
/** Source contract for safe, retained Essay AI marking. @author Derek Keats */
$root=dirname(__DIR__);$rubric=file_get_contents($root.'/classes/essaydefaultrubric_class_inc.php');$marker=file_get_contents($root.'/classes/essayaimarker_class_inc.php');$jobs=file_get_contents($root.'/classes/dbessayaimarkingjobs_class_inc.php');$controller=file_get_contents($root.'/classes/essaymanagementbase_class_inc.php');$form=file_get_contents($root.'/templates/content/manage_upload_tpl.php');$register=file_get_contents($root.'/register.conf');
$checks=array(
 'Essay rubric has a new immutable version'=>str_contains($rubric,"essay-default-rubric-v2"),
 'weighted criteria total 100'=>array_sum(array_map('intval',array_slice(array_map(fn($line)=>preg_match("/'maximumMark'=>([0-9]+)/",$line,$m)?$m[1]:0,explode("\n",$rubric)),0)))===100,
 'model Essay is supplied to marker'=>str_contains($marker,"'modelEssay'"),
 'criterion marks are bounded server side'=>str_contains($marker,'min($byId[$id]'),
 'authorship review cannot alter marking'=>str_contains($marker,'never alter scores because of it'),
 'marker forbids AI authorship classification'=>str_contains($marker,'never classify the author as AI or human'),
 'completed drafts remain recoverable'=>str_contains($jobs,'getLatestCompleted'),
 'batch and single actions use CSRF'=>str_contains($controller,"consume('essay_ai_marking'")&&str_contains($controller,"consume('essay_ai_batch_marking'"),
 'signed lecturer adjustment requires a reason'=>str_contains($controller,"lecturerAdjustment !== 0 && \$integrityReason === ''"),
 'adjustment can add or deduct points'=>str_contains($form,'positive number to add points or a negative number to deduct points')&&str_contains($form,'Calculated final mark'),
 'AI base mark is recovered server side'=>str_contains($controller,'getLatestCompleted($book')&&str_contains($controller,'$aiBaseMark'),
 'queue table is installer managed'=>str_contains($register,'TABLE: tbl_essay_ai_marking_jobs'),
);
$failed=array_keys(array_filter($checks,fn($passed)=>!$passed));if($failed){fwrite(STDERR,"Failed: ".implode('; ',$failed).PHP_EOL);exit(1);}echo "Essay AI marking contract passed (".count($checks)." checks).\n";
?>
