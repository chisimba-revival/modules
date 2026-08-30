<?php
$root=dirname(__DIR__);$read=fn($file)=>file_get_contents($root.'/'.$file);
$required=array('controller.php','register.conf','classes/kanbanauthorizationservice_class_inc.php','classes/dbkanbanaccess_class_inc.php','sql/tbl_kanban_boards.sql','sql/tbl_kanban_access.sql','templates/content/index_tpl.php','resources/kanban.css','resources/kanban.js');
foreach($required as $file)if(!is_file($root.'/'.$file)){fwrite(STDERR,"FAIL: missing $file\n");exit(1);}
$controller=$read('controller.php');$auth=$read('classes/kanbanauthorizationservice_class_inc.php');$access=$read('sql/tbl_kanban_access.sql');$template=$read('templates/content/index_tpl.php');$css=$read('resources/kanban.css');
$checks=array(
 'services use the active framework base'=>str_contains($auth,'extends controller')&&str_contains($read('classes/kanbanservice_class_inc.php'),'extends controller'),
 'three board scopes'=>str_contains($controller,"'site'")&&str_contains($controller,"'context'")&&str_contains($controller,"'personal'"),
 'all mutations require native CSRF'=>str_contains($controller,'csrf->consume(self::CSRF'),
 'permission levels are ordered'=>str_contains($auth,"'view'=>1")&&str_contains($auth,"'edit'=>2")&&str_contains($auth,"'manage'=>3"),
 'future group resolver seam'=>str_contains($auth,'allowsFuturePrincipal')&&str_contains($auth,'context_role and group'),
 'direct grants remain lecturer or admin only'=>str_contains($auth,'eligibleDirectUser')&&str_contains($auth,'isContextLecturer')&&str_contains($auth,'inAdminGroup'),
 'generic principals stored'=>str_contains($access,"'principaltype'")&&str_contains($access,"'principalid'")&&str_contains($access,"'permission'"),
 'shared skin primitives composed'=>str_contains($template,'chisimba-card')&&str_contains($template,'chisimba-form-field')&&str_contains($template,'chisimba-button-danger'),
 'module CSS does not redefine primitives'=>!str_contains($css,'.button{')&&!str_contains($css,'.chisimba-card{')&&!str_contains($css,'.chisimba-form-field{'),
 'students are not enabled prematurely'=>!str_contains($read('register.conf'),'Students')
);
foreach($checks as $name=>$ok){if(!$ok){fwrite(STDERR,"FAIL: $name\n");exit(1);}echo "PASS: $name\n";}
