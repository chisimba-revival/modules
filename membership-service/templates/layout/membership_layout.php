<?php
/** Standard wide workspace with the canonical role-aware account/admin block. */
$layout = $this->newObject('csslayout', 'htmlelements');
$layout->setNumColumns(2);
$layout->setLeftColumnContent(
    '<aside class="chisimba-structural-sidebar membership-admin-sidebar" aria-label="Administration navigation">'
    . $this->getObject('postloginmenu', 'toolbar')->show()
    . '</aside>'
);
$layout->setMiddleColumnContent(
    '<div class="chisimba-structural-main membership-wide-column">'
    . $this->getContent() . '</div>'
);
echo $layout->show();
?>
