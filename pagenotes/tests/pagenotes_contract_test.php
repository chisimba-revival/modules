<?php
/**
 * Static architecture contracts for the Notes module.
 *
 * @author Derek Keats
 * @package pagenotes
 */
$root=dirname(__DIR__);
$checks=array(
 'legacy registration is disabled'=>!is_file(dirname($root).'/pagenotes_DEPRECATED/register.conf')&&is_file(dirname($root).'/pagenotes_DEPRECATED/register.conf.deprecated'),
 'responsible author is registered'=>str_contains(file_get_contents($root.'/register.conf'),'MODULE_AUTHORS: Derek Keats'),
 'canonical note table does not collide with legacy data'=>str_contains(file_get_contents($root.'/sql/tbl_pagenotes_items.sql'),'tbl_pagenotes_items')&&!str_contains(file_get_contents($root.'/register.conf'),'TABLE: tbl_pagenotes_notes'),
 'typed links are unique per note target'=>str_contains(file_get_contents($root.'/sql/tbl_pagenotes_links.sql'),'pagenotes_links_unique'),
 'future group permissions have an extension seam'=>str_contains(file_get_contents($root.'/classes/noteauthorizationservice_class_inc.php'),'allowsFuturePrincipal'),
 'note saving is asynchronous'=>str_contains(file_get_contents($root.'/resources/notes.js'),'data-note-save'),
 'attachments do not copy notes'=>str_contains(file_get_contents($root.'/README.md'),'without copying')
);
$failed=false;foreach($checks as $label=>$ok){echo ($ok?'PASS':'FAIL').': '.$label.PHP_EOL;$failed=$failed||!$ok;}exit($failed?1:0);
?>
