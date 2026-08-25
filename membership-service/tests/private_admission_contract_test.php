<?php
$root=dirname(__DIR__);
$service=file_get_contents($root.'/classes/privateadmissionservice_class_inc.php');
$controller=file_get_contents($root.'/controller.php');
$template=file_get_contents($root.'/templates/content/admissions_tpl.php');
$schema=file_get_contents($root.'/sql/tbl_membership_service_admissions.sql');
$register=file_get_contents($root.'/register.conf');
$checks=array(
 'admission ledger registered'=>str_contains($register,'TABLE: tbl_membership_service_admissions'),
 'course and user indexed'=>str_contains($schema,"'membership_admission_course_user'"),
 'private course required'=>str_contains($service,"access_policy']??''))==='private'"),
 'canonical entitlement granted'=>str_contains($service,"'entitlementType' => 'resource_access'")&&str_contains($service,"'resourceType' => 'course'"),
 'canonical Students group used'=>str_contains($service,"course_code'] . '^Students'"),
 'pre-existing membership retained'=>str_contains($service,"'student_membership_added' => \$wasMember ? 0 : 1"),
 'owned membership reversed'=>str_contains($service,"student_membership_added'] === 1"),
 'grant and revoke audited'=>str_contains($service,'private_admission.admitted')&&str_contains($service,'private_admission.revoked'),
 'manager capability enforced'=>substr_count($controller,"can('private_admission.manage')")>=6,
 'CSV must preview'=>str_contains($controller,"case 'previewcsv'")&&str_contains($controller,"case 'importcsv'"),
 'CSV does not create users'=>!str_contains($controller,'createUser('),
 'payment evidence is distinct'=>str_contains($template,'Payment information is optional supporting evidence')
    && str_contains($template,'Optional payment information'),
 'revocation requires reason'=>str_contains($template,'Reason for revocation'),
 'verified payment bridge is bounded'=>str_contains($service,'admitConfirmedPayment')&&str_contains($service,"private_admission_mode'] ?? '') !== 'automatic_payment'"),
 'automatic actions use service actor'=>str_contains($service,"array('type' => 'service', 'id' => 'payment-service')")&&str_contains($service,"'actorType' => 'service'"),
 'pending reviews are auditable edits'=>str_contains($service,'function updateReview')
    && str_contains($service,'private_admission.review_updated')
    && str_contains($template,'Edit review'),
);
$failed=array_keys(array_filter($checks,fn($ok)=>!$ok));
if($failed){fwrite(STDERR,'FAILED: '.implode(', ',$failed).PHP_EOL);exit(1);}
echo 'OK: '.count($checks).' private admission contract checks'.PHP_EOL;
