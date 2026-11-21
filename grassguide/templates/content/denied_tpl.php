<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
$r=$this->getObject('grassguiderenderer','grassguide');
echo '<p role="alert">'.$r::escape($r->text('unavailable')).'</p>';
