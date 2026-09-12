<?php $r=$this->getObject('publishingrenderer','simpleblog'); echo '<p role="alert">'.publishingrenderer::escape($r->text($this->getVar('blogError'))).'</p>'; ?>
