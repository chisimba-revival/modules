<?php
/** Verify Announcements no longer requires retired delivery services to open. */
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$register = file_get_contents($root . '/register.conf');
$form = file_get_contents($root . '/templates/content/addedit_tpl.php');
$schema = file_get_contents($root . '/sql/tbl_announcements.sql');
$publisher = file_get_contents($root . '/classes/announcementnotificationpublisher_class_inc.php');
$block = file_get_contents($root . '/classes/block_whatsnewauthors_class_inc.php');
$archive = file_get_contents($root . '/templates/content/home_tpl.php');
$detail = file_get_contents($root . '/templates/content/view_tpl.php');
$checks = array(
    'Feed is not loaded during initialisation' => strpos(
        substr($controller, 0, strpos($controller, 'public function __feed()')),
        "getObject('feeder', 'feed')"
    ) === false,
    'Feed access is optional' => strpos($controller, "checkIfRegistered('feed')") !== false,
    'Legacy Mail is not a dependency' => strpos($register, 'DEPENDS:mail') === false,
    'Legacy email control is absent' => strpos($form, "new radio ('email')") === false,
    'Publishing cannot invoke legacy email' => substr_count($controller, '$email = FALSE;') === 2,
    'Description covers site and context audiences' => strpos($register, 'selected site or [-context-] audiences') !== false,
    'Publication dimensions are stored independently' => strpos($schema, "'announcement_type'") !== false && strpos($schema, "'audience'") !== false,
    'Content model has one optional resource URL' => strpos($schema, "'resource_url'") !== false && strpos($schema, "'summary'") === false && strpos($schema, "'download_url'") === false,
    'Sidebar excerpt comes from the message' => strpos($block, "strip_tags((string)\$row['message'])") !== false,
    'Author audience alone controls the instructor block' => strpos($controller, "'show_in_latest'=>\$audience==='authors'") !== false && strpos($form, 'name="show_in_latest"') === false && strpos(file_get_contents($root . '/classes/dbannouncements_class_inc.php'), "audience='authors'") !== false,
    'Scope uses one selector' => strpos($form, 'id="announcement-scope"') !== false && strpos($form, 'type="radio" name="recipienttarget"') === false,
    'Introduction preserves context terminology' => strpos($register, 'selected groups or [-contexts-]') !== false,
    'Updates delivery is explicit and idempotent' => strpos($publisher, "'idempotencyKey'=>'announcement:'") !== false,
    'Instructor product update block is registered' => strpos($register, 'BLOCK: whatsnewauthors') !== false && strpos($block, 'getLatestAuthorUpdates(4)') !== false,
    'Instructor notices use configured dates and bounded recency' => strpos($block, "timeanddateservice") !== false && strpos($block, '<ul class="announcement-latest">') !== false && strpos(file_get_contents($root . '/classes/dbannouncements_class_inc.php'), '7*86400') !== false,
    'Archive is not a top-level menu item' => strpos($register, 'MENU_CATEGORY:') === false && strpos($register, 'SIDEMENU:') === false,
    'Archive has one publishing action' => substr_count($archive, "'action' => 'add'") === 1,
    'Archive uses shared icons and ordinary pagination' => strpos($archive, "getObject('iconservice', 'ui')") !== false && strpos($archive, "'page' => \$page + 1") !== false,
    'Obsolete AJAX viewer is gone' => !file_exists($root . '/resources/announceview.js') && strpos($controller, '__getajax') === false && strpos($archive, "newObject('pagination'") === false,
    'Detail uses shared skin primitives' => strpos($detail, 'chisimba-page-header chisimba-card') !== false && strpos($detail, "getObject('iconservice','ui')") !== false && strpos($detail, 'linkwrapper') === false && strpos($detail, 'modulehome') === false,
);
foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}
fwrite(STDOUT, "Announcements revival contract: PASS\n");
