<?php
/** Publishing capability definitions for fresh installs and upgrades. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class simpleblog_installscripts extends dbtable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler') {}

    public function postinstall($version = null)
    {
        // Canonical permissions replace the retired SimpleBloggers group. Define
        // capabilities only: never add memberships or grant publishing access.
        $permissions = $this->getObject('permissionservice', 'security');
        $area = $permissions->ensureArea('chisimba', 'simpleblog');
        foreach (['personal_publish', 'site_publish', 'site_manage'] as $name) {
            if (!$permissions->ensureRight($area, $name)) {
                throw new RuntimeException('Publishing permission definition failed');
            }
        }
    }
}
