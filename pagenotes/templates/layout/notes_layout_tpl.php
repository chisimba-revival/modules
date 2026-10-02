<?php
/**
 * Shared layout for Notes screens.
 *
 * @author Derek Keats
 * @package pagenotes
 */
$this->appendArrayVar('headerParams','<link rel="stylesheet" type="text/css" href="'.$this->getResourceUri('notes.css').'?v=1" />');
$this->appendArrayVar('headerParams','<script defer type="text/javascript" src="'.$this->getResourceUri('notes.js').'?v=1"></script>');
echo $this->getContent();
?>
