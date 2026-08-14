<?php
$css = htmlspecialchars($this->getResourceUri('contentblocks.css', 'contentblocks'), ENT_QUOTES, 'UTF-8');
$this->appendArrayVar('headerParams', '<link rel="stylesheet" href="' . $css . '">');
echo $this->getContent();
?>
