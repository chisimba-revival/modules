<?php
$this->appendArrayVar('headerParams','<script defer src="'.$this->getResourceUri('formdrafts.js','htmlelements').'?v=1"></script>');
$this->appendArrayVar('headerParams','<link rel="stylesheet" type="text/css" href="'.$this->getResourceUri('kanban.css').'?v=0131" />');
$this->appendArrayVar('headerParams','<script defer type="text/javascript" src="'.$this->getResourceUri('kanban.js').'?v=0131"></script>');
echo $this->getContent();
?>
