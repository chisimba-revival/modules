<?php
/** Stable page identity, independent of hostname, query ordering and tracking parameters. */
$pages = $this->getObject('dbslatepages','slate');
try {
    $key = $pages->pageKey($this->getParam('page', ''));
} catch (DomainException $error) {
    http_response_code(404);
    echo htmlspecialchars($this->getObject('language','language')->languageText('mod_slate_editor_missing','slate'), ENT_QUOTES, 'UTF-8');
    return;
}
$blocks = $this->getObject('dbslateblocks','slate');
$revision = $blocks->capture($key);
$settings = $this->getObject('slateblockeditor','slate')->settings($key, $revision);
if ($this->objUser->isLoggedIn() && $this->objUser->isAdmin()) {
    echo $this->getObject('contextualhelp','help')->show('slate','editing');
}
echo $this->getObject('buildcanvas','canvas')->show('page', $key, $blocks, $settings);
