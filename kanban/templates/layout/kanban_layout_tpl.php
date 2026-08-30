<?php
$this->appendArrayVar('headerParams','<link rel="stylesheet" type="text/css" href="'.$this->getResourceUri('kanban.css').'?v=0107" />');
$this->appendArrayVar('headerParams','<script defer type="text/javascript" src="'.$this->getResourceUri('kanban.js').'?v=0107"></script>');
echo $this->getContent();
?>
