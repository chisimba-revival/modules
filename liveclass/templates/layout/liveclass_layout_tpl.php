<?php
$layout=$this->newObject('csslayout','htmlelements');$layout->setNumColumns(2);
$context=$this->getObject('dbcontext','context');$side=$context->getContextCode()!==''?$this->getObject('contextsidebar','context'):$this->getObject('postloginmenu_elearn','toolbar');
$layout->setLeftColumnContent($side->show());$layout->setMiddleColumnContent($this->getContent());echo $layout->show();
?>
