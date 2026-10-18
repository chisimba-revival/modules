<?php
/** Temporary toolbar/buddies adapter; remove when callers migrate. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class chatdisplay extends ChisimbaObject {
 public function init() {}
 public function imParams() {return ['headerParams'=>'','bodyOnLoad'=>''];}
 public function divShowIM() {return $this->getObject('conversationview')->entryLink();}
}
