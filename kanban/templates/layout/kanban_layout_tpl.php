<?php
$this->appendArrayVar('headerParams','<script defer src="'.$this->getResourceUri('formdrafts.js','htmlelements').'?v=1"></script>');
$this->appendArrayVar('headerParams','<link rel="stylesheet" type="text/css" href="'.$this->getResourceUri('kanban.css').'?v=0124" />');
$this->appendArrayVar('headerParams','<script defer type="text/javascript" src="'.$this->getResourceUri('kanban.js').'?v=0124"></script>');
echo $this->getContent();
?>
