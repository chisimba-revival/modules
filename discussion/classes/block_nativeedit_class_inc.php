<?php
/** Render editor JSON only after the block parser has resolved the page. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class block_nativeedit extends ChisimbaObject {
    public $configData;

    public $objLanguage;
    public function show() {
        $editPostData=$this->getVar('editPostData');
        $editPostDraft=$this->getVar('editPostDraft');
        $editPostToken=$this->getVar('editPostToken');
        if (!is_array($editPostData) || !$editPostToken) return '';
        $this->objLanguage=$this->getObject('language','language');
        ob_start(); include dirname(__DIR__) . '/templates/content/native_edit_form.php'; return ob_get_clean();
    }
}
