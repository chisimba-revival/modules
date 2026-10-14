<?php
$e = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
echo '<p role="alert" class="chisimba-notice">'.$e($this->objLanguage->languageText('mod_triplestore_'.$notice,'triplestore')).'</p>';
