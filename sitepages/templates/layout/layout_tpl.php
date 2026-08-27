<?php
$css=htmlspecialchars($this->getResourceUri('sitepages.css','sitepages'),ENT_QUOTES,'UTF-8');
$this->appendArrayVar('headerParams','<link rel="stylesheet" href="'.$css.'">');
echo $this->getContent();
?>
