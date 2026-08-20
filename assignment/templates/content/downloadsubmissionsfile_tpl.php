<?php
 $objAltConfig = $this->getObject('altconfig','config');
 
$backLink = new link ($this->uri(array("action"=>"view","id"=>$filename)));
$backLink->link = $this->objLanguage->languageText('mod_assignment_backtolist', 'assignment', 'Back to List of Assignments');

echo $backLink->show();


?>