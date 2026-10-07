<?php
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$t=fn($key)=>html_entity_decode($this->getObject('language','language')->code2Txt('mod_contactform_'.$key,'contactform'),ENT_QUOTES,'UTF-8');
$url=fn($params=[])=>html_entity_decode($this->uri($params,'contactform'),ENT_QUOTES,'UTF-8');
$icon=fn($name)=>$this->getObject('iconservice','ui')->render($name,['decorative'=>true]);
$service=$this->getObject('contactservice','contactform');
