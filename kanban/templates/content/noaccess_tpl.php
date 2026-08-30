<?php
$home=$this->uri(array(),'kanban');
?>
<main class="chisimba-workspace chisimba-flow">
    <div class="chisimba-notice chisimba-notice--error" role="alert">You do not have access to this Kanban scope.</div>
    <a class="button chisimba-button-secondary" href="<?php echo htmlspecialchars($home,ENT_QUOTES,'UTF-8'); ?>">Open my personal boards</a>
</main>
