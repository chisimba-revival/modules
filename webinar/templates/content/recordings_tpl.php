<?php
/** Dedicated recordings page using shared cards, dialog and contextual Help. */
$r=$this->getObject('webinarrenderer','webinar');$e=['webinarrenderer','escape'];$data=$this->getVar('videoGallery');
echo '<section data-video-gallery data-loading="'.$e($r->text('videos_loading')).'" data-loaded="'.$e($r->text('videos_loaded')).'" data-error="'.$e($r->text('videos_error')).'"><header class="chisimba-form-actions"><h1>'.$e($r->text('recordings')).'</h1>'.$this->getObject('contextualhelp','help')->show('webinar','recordings',true).'</header>';
echo '<div class="chisimba-publication-card-grid" data-video-grid>'.$data['html'].'</div>';
if(!$data['total'])echo '<p>'.$e($r->text('videos_empty')).'</p>';
echo '<div class="chisimba-form-actions">';
if($data['next'])echo '<a class="button" data-video-more href="'.$e($data['next']).'">'.$this->getObject('iconservice','ui')->render('plus',['decorative'=>true]).'<span>'.$e($r->text('videos_more')).'</span></a>';
if($data['channel'])echo '<a class="button chisimba-button-primary" href="https://www.youtube.com/channel/'.$e($data['channel']).'?sub_confirmation=1" target="_blank" rel="noopener noreferrer">'.$this->getObject('iconservice','ui')->render('bell-plus',['decorative'=>true]).'<span>'.$e($r->text('videos_subscribe')).'</span></a>';
echo '</div><p role="status" aria-live="polite" data-video-status></p></section>';
echo $this->getObject('videocardrenderer','contentblocks')->player('webinar-video-player',$r->text('watch'));
