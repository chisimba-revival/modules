<?php
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$t=fn($key)=>$this->getObject('language','language')->code2Txt('mod_curatedfaq_'.$key,'curatedfaq');
$url=fn($params=[])=>html_entity_decode($this->uri($params,'curatedfaq'),ENT_QUOTES,'UTF-8');
$icon=fn($name)=>$this->getObject('iconservice','ui')->render($name,['decorative'=>true]);
$service=$this->getObject('faqservice','curatedfaq');
$button=fn($label,$name,$params)=>'<a class="button" href="'.$e($url($params)).'">'.$icon($name).'<span>'.$e($t($label)).'</span></a>';
