<?php if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access'); ?>
<h1><?= htmlspecialchars($this->text('title'),ENT_QUOTES,'UTF-8') ?></h1>
<p role="alert"><?= htmlspecialchars($this->getVar('ssError',''),ENT_QUOTES,'UTF-8') ?></p>
