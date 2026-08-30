<?php
$this->appendArrayVar('headerParams','<link rel="stylesheet" type="text/css" href="'.$this->getResourceUri('kanban.css').'?v=0105" />');
$this->appendArrayVar('headerParams','<script defer type="text/javascript" src="'.$this->getResourceUri('kanban.js').'?v=0105"></script>');
echo $this->getContent();
?>
