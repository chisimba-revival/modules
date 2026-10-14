<?php
$escape=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
echo '<p class="error" role="alert">'.$escape($this->objLanguage->languageText('mod_calendar_'.$calendarError,'calendar')).'</p>';
?>
