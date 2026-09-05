<?php
// Keep authoring within the standard context shell: the editor is the main
// column and the role-aware context navigation occupies the supplementary
// column, which the active canvas places on the right.
$toolbar = $this->getObject('contextsidebar', 'context');
$cssLayout = $this->newObject('csslayout', 'htmlelements');
$cssLayout->setNumColumns(2);
$cssLayout->setLeftColumnContent($toolbar->show());
$cssLayout->setMiddleColumnContent('<main class="chisimba-structural-main contextcontent-authoring-main">' . $this->getContent() . '</main>');
echo $cssLayout->show();
?>
