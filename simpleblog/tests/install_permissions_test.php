<?php
$GLOBALS['kewl_entry_point_run']=true;
class dbtable {
    public function getObject($name,$module) {
        if($name!=='permissionservice' || $module!=='security')throw new RuntimeException('Unexpected permission mutation');
        return $GLOBALS['permissions'];
    }
}
$GLOBALS['permissions']=new class {
    public $rights=[];
    public function ensureArea($system,$module){if($system!=='chisimba'||$module!=='simpleblog')throw new RuntimeException('Incorrect area');return 'area';}
    public function ensureRight($area,$name){if($area!=='area')throw new RuntimeException('Incorrect right area');$this->rights[$name]=true;return true;}
};
require dirname(__DIR__).'/patches/installscripts_class_inc.php';
$hook=new simpleblog_installscripts();$hook->postinstall();$hook->postinstall('0.069');
if(array_keys($GLOBALS['permissions']->rights)!==['personal_publish','site_publish','site_manage'])throw new RuntimeException('Incorrect capability definitions');
echo "PASS: fresh and repeated installation define capabilities without group membership or grants\n";
