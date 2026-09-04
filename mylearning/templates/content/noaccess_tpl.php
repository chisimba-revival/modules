<?php
echo '<p class="chisimba-notification chisimba-notification--warning">'
    . htmlspecialchars($this->objLanguage->code2Txt('mod_mylearning_noaccess', 'mylearning', null, 'My Learning is available when you are enrolled as a student in at least one [-context-].'), ENT_QUOTES, 'UTF-8') . '</p>';
?>
