<?php
/** Standard wide workspace with the shared role-aware account navigation. */
$layout=$this->newObject('csslayout','htmlelements');
$layout->setNumColumns(3);
$layout->layoutType='canvas_stacked31';
$layout->setMiddleColumnContent('<main class="chisimba-structural-main">'.$this->getContent().'</main>');
$layout->setRightColumnContent('<aside class="chisimba-structural-sidebar">'.$this->getObject('postloginmenu','toolbar')->show().'</aside>');
echo $layout->show();
