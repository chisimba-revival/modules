<?php
$root=dirname(__DIR__);
$controller=file_get_contents($root.'/controller.php');
$service=file_get_contents($root.'/classes/sectionprogressionservice_class_inc.php');
$chapters=file_get_contents($root.'/classes/db_contextcontent_contextchapter_class_inc.php');
$manager=file_get_contents($root.'/templates/content/managesections_tpl.php');
$overview=file_get_contents($root.'/templates/content/listsections_tpl.php');
$sectionPage=file_get_contents($root.'/templates/content/sectionpage_tpl.php');
$register=file_get_contents($root.'/register.conf');
$checks=array(
    'chapter placement supports optional section'=>str_contains($chapters,"'sectionid'"),
    'section is an informational route'=>str_contains($controller,"case 'viewsection':")
        &&str_contains($sectionPage,'contextcontent-section-page__introduction'),
    'section inserts into canonical journey'=>str_contains($service,'public function nextStop')
        &&str_contains($service,"array('type'=>'section'"),
    'assignment does not change chapter order'=>str_contains(
        $chapters,"array('sectionid' => \$sectionId)"
    )&&!str_contains($chapters,"'sectionid' => \$sectionId, 'chapterorder'"),
    'section manager has no order controls'=>!str_contains($manager,'movesectionup')
        &&!str_contains($manager,'movesectionchapterup'),
    'delivery has no acknowledgement gate'=>!str_contains($service,'objAcknowledgements')
        &&!str_contains($controller,'acknowledgesection')
        &&!str_contains($register,'tbl_contextcontent_section_acknowledgements'),
    'overview opens informational section'=>str_contains($overview,"'action'=>'viewsection'"),
    'managers retain authoring access'=>str_contains($service,'$this->isManager($contextCode)')
);
foreach($checks as $name=>$ok){echo ($ok?'PASS':'FAIL').': '.$name.PHP_EOL;if(!$ok){exit(1);}}
?>
