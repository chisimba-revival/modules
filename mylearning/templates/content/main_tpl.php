<?php
$layout = $this->newObject('csslayout', 'htmlelements');
$layout->setNumColumns(2);
$accountMenu = $this->getObject('postloginmenu', 'toolbar')->show();
$layout->setLeftColumnContent(
    '<aside class="mylearning-sidebar" aria-label="Student navigation">'
    . $accountMenu
    . '<div class="mylearning-sidebar__blocks">' . $sidebarBlocks . '</div>'
    . '</aside>'
);
$layout->setMiddleColumnContent(
    '<main class="mylearning-page">' . $learningOverview . '</main>'
);
echo $layout->show();
?>
