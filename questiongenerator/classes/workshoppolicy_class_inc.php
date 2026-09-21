<?php
/** Standalone authoring access; saved sets remain private to their owner. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class workshoppolicy extends ChisimbaObject
{
    public function allowed()
    {
        $user=$this->getObject('user','security');
        return $user->isLoggedIn() && ($user->isAdmin() || $user->isLecturer());
    }
    public function owner(array $row)
    {
        return $this->allowed() && hash_equals((string)$row['ownerid'],(string)$this->getObject('user','security')->userId());
    }
}
