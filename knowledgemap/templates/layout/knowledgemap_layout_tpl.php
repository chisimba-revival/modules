<?php
/**
 * Shared layout for Active Knowledge Map screens.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
$this->appendArrayVar('headerParams','<link rel="stylesheet" type="text/css" href="'.$this->getResourceUri('knowledgemap.css').'?v=09" />');
$this->appendArrayVar('headerParams','<script defer type="text/javascript" src="'.$this->getResourceUri('knowledgemap.js').'?v=10"></script>');
echo $this->getContent();
?>
