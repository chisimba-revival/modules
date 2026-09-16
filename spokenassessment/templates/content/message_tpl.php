<?php
$r=$this->getObject('spokenrenderer'); $e=['spokenrenderer','escape'];
echo '<section class="chisimba-form-card"><h1>'.$e($r->text('title')).'</h1><p role="alert">'.$e($r->text($spokenError)).'</p>';
if (!empty($spokenInput)) {
    echo '<h2>'.$e($r->text('retained_input')).'</h2>';
    foreach (array_keys($spokenInput) as $key) echo '<p><strong>'.$e($r->text($key)).'</strong></p><pre>'.$e($spokenInput[$key]??'').'</pre>';
}
echo $r->link('all_activities','arrow-left',['action'=>'list']).'</section>';
