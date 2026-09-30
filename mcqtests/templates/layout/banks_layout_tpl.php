<?php
/** Shared wide workspace; standalone banks do not show an empty course menu. */
$layout=$this->newObject('csslayout','htmlelements');
$layout->setNumColumns(3);$layout->layoutType='canvas_stacked31';
$layout->setMiddleColumnContent('<main class="chisimba-structural-main">'.$this->getContent().'</main>');
$layout->setRightColumnContent('<aside class="chisimba-structural-sidebar">'.$this->getObject('postloginmenu','toolbar')->show().'</aside>');
echo $layout->show();
