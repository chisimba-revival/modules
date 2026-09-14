<?php
/** Booking lives alongside its event; feedback and validation preserve context. */
$action='register';$error=$this->getVar('webinarError');$input=$this->getVar('webinarInput');$notice=$this->getVar('webinarBookingNotice');
echo '<section id="booking" class="chisimba-form-section chisimba-form-card" aria-labelledby="webinar-booking-title"><h2 id="webinar-booking-title">'.$e($r->text('booking')).'</h2>';
if($notice){echo '<p role="status">'.$e($r->text($notice.'_body')).'</p>';}else{
 if($error)echo '<p role="alert">'.$e($r->text($error)).'</p>';
 if($error!=='closed')include __DIR__.'/registration_form_tpl.php';
}
echo '</section>';
