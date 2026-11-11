<?php
/** Shared saved-address display for content editors. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class contentpathrenderer extends ChisimbaObject
{
    public function init() {}
    public function show(string $url, bool $webinar = false): string
    {
        $escape = fn($value) => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $language = $this->getObject('language', 'language');
        $text = fn($key) => $escape($language->languageText('mod_shorturl_copy_'.$key, 'shorturl'));
        $this->appendArrayVar('headerParams', '<script defer src="'.$escape($this->getResourceUri('copy-link.js', 'shorturl')).'"></script>');
        return '<div data-content-address'.($url === '' ? ' hidden' : '').'><div class="chisimba-cluster chisimba-wrap-anywhere">'
            .'<a data-content-address-link'.($webinar ? ' data-webinar-public' : '').' href="'.$escape($url).'">'.$escape($url).'</a>'
            .'<button type="button" class="chisimba-icon-button" data-content-address-copy title="'.$text('label').'" aria-label="'.$text('label').'" hidden>'
            .$this->getObject('iconservice', 'ui')->render('copy', ['decorative'=>true]).'</button></div>'
            .'<span role="status" aria-live="polite" data-content-address-status data-success="'.$text('success').'" data-failure="'.$text('failure').'"></span></div>';
    }
}
