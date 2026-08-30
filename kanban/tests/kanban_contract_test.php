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
 'task movement has keyboard controls'=>str_contains($template,"array('left'=>")&&str_contains($template,'>Move <?php echo $e($direction); ?></button>')&&str_contains($controller,"param('response')==='json'"),
 'workspace opts into full width'=>str_contains($template,'chisimba-structural-main--full')&&str_contains($template,'max-width:none'),
 'workspace has accessible fullscreen control'=>str_contains($template,'data-kanban-fullscreen')&&str_contains($template,'>Full screen</button>'),
 'board scope is an explicit selector'=>str_contains($template,'for="kanban-scope">Board scope')&&str_contains($template,'Personal — only your boards')&&str_contains($template,'Course — boards for this course')&&str_contains($template,'Site — organisation-wide boards')&&str_contains($template,"render('eye'"),
 'columns use semantic skin colours'=>str_contains($css,'var(--chisimba-danger)')&&str_contains($css,'var(--chisimba-warning)')&&str_contains($css,'var(--chisimba-success)'),
 'board status uses shared pills'=>substr_count($template,'chisimba-pill')>=4&&str_contains($template,'Board task status'),
 'project summary includes progress and recency'=>str_contains($template,"'% complete'")&&str_contains($template,"'Updated '")&&str_contains($template,'kanban-column__heading'),
 'boards collapse accessibly'=>str_contains($template,'data-board-toggle aria-expanded="true"')&&str_contains($read('resources/kanban.js'),'is-collapsed'),
 'boards reorder within their stored scope'=>str_contains($controller,'inScope($board[\'scopetype\'],$board[\'scopeid\'],true)')&&str_contains($read('classes/dbkanbanboards_class_inc.php'),'setSortOrder'),
 'module CSS does not redefine primitives'=>!str_contains($css,'.button{')&&!str_contains($css,'.chisimba-card{')&&!str_contains($css,'.chisimba-form-field{'),
 'students are not enabled prematurely'=>!str_contains($read('register.conf'),'Students')
);
foreach($checks as $name=>$ok){if(!$ok){fwrite(STDERR,"FAIL: $name\n");exit(1);}echo "PASS: $name\n";}
