<?php
/**
 * Shared layout for Notes screens.
 *
 * @author Derek Keats
 * @package pagenotes
 */
$this->appendArrayVar('headerParams','<link rel="stylesheet" type="text/css" href="'.$this->getResourceUri('notes.css').'?v=5" />');
$this->appendArrayVar('headerParams','<script defer type="text/javascript" src="'.$this->getResourceUri('notes.js').'?v=2"></script>');
echo $this->getContent();
?>
