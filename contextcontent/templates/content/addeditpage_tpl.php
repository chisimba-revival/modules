<?php
//$this->setVar('pageSuppressXML',true);
$this->loadClass('htmlheading', 'htmlelements');
$this->loadClass('form', 'htmlelements');
$this->loadClass('textinput', 'htmlelements');
$this->loadClass('textarea', 'htmlelements');
$this->loadClass('radio', 'htmlelements');
$this->loadClass('hiddeninput', 'htmlelements');
$this->loadClass('label', 'htmlelements');
$this->loadClass('dropdown', 'htmlelements');
$this->loadClass('button', 'htmlelements');

$header = new htmlheading();
if ($mode=='edit') {
    $header->str = $this->objLanguage->languageText('mod_contextcontent_editcontextpages','contextcontent').': '.htmlentities($page['menutitle']);
    $this->setVar('pageTitle', htmlentities($this->objContext->getTitle().' - '.$this->objLanguage->languageText('mod_contextcontent_editcontextpages','contextcontent').': '.$page['menutitle']));
} else {
    $header->str = $this->objLanguage->languageText('mod_contextcontent_addnewcontextpages','contextcontent');
    $this->setVar('pageTitle', htmlentities($this->objContext->getTitle().' - '.$this->objLanguage->languageText('mod_contextcontent_addnewcontextpages','contextcontent')));
}
$header->type = 1;
echo $header->show();

$preserveSubmittedPageForm = !empty($preserveSubmittedPageForm);
if ($preserveSubmittedPageForm && !empty($pageFormError)) {
    echo '<div class="error contextcontent-form-error" role="alert">'
        . htmlentities($pageFormError, ENT_QUOTES, 'UTF-8') . '</div>';
}

$form = new form('addpage', $this->uri(array('action'=>$formaction)));
$csrfInput = new hiddeninput('csrf_token', $contextContentCsrf);
$form->addToForm($csrfInput->show());
$typeInput = new hiddeninput('contenttype', $contentType);
$form->addToForm($typeInput->show());
$formTable = $this->newObject('htmltable', 'htmlelements');
$formTable->cssClass = 'ctxtcnt-add-table';
$this->appendArrayVar('headerParams', '<style type="text/css">.ctxtcnt-add-table{box-sizing:border-box;width:100%;max-width:100%;table-layout:fixed}.ctxtcnt-add-table td:first-child{width:240px}.ctxtcnt-add-table td{box-sizing:border-box;min-width:0}.contextcontent-page-title-input{box-sizing:border-box;width:100%!important;max-width:100%!important}@media(max-width:700px){.ctxtcnt-add-table,.ctxtcnt-add-table tbody,.ctxtcnt-add-table tr,.ctxtcnt-add-table td{display:block;width:100%!important}.ctxtcnt-add-table td:first-child{width:100%!important}}</style>');

$chapterLabel = $this->objLanguage->languageText('mod_contextcontent_chapter', 'contextcontent');
$chapterValue = isset($chapterTitle) && trim((string) $chapterTitle) !== ''
    ? $chapterTitle
    : $this->objLanguage->languageText('mod_contextcontent_currentchapter', 'contextcontent');
$formTable->startRow();
$formTable->addCell('<strong>' . htmlentities($chapterLabel, ENT_QUOTES, 'UTF-8') . '</strong>');
$formTable->addCell('<p class="contextcontent-chapter-context">'
    . htmlentities($chapterValue, ENT_QUOTES, 'UTF-8') . '<br /><span>'
    . htmlentities($this->objLanguage->languageText('mod_contextcontent_pageaddedtochapter', 'contextcontent'), ENT_QUOTES, 'UTF-8')
    . '</span></p>' . (new hiddeninput('parentnode', 'root'))->show()
    . (new hiddeninput('insert_after', isset($parent) ? $parent : ''))->show());
$formTable->endRow();
$menuTitle = new textinput('menutitle');
$menuTitle->size = 60;
$menuTitle->extra = 'required="required" aria-required="true" class="contextcontent-page-title-input"';

if ($preserveSubmittedPageForm) {
    $menuTitle->value = htmlentities((string) $this->getParam('menutitle', ''), ENT_QUOTES, 'UTF-8');
} elseif ($mode=='edit') {
    $menuTitle->value = htmlentities($page['menutitle']);
}

$label = new label ($this->objLanguage->languageText('mod_contextcontent_pagetitle','contextcontent'), 'input_menutitle');

$formTable->startRow();
$formTable->addCell($label->show());
$formTable->addCell($menuTitle->show());
$formTable->endRow();

$htmlarea = $this->newObject('htmlarea', 'htmlelements');
$htmlarea->setName('pagecontent');
$htmlarea->context = TRUE;
$contenttitleheader = new htmlheading();
$contenttitleheader->type=1;
$contenttitleheader->str=$this->objLanguage->languageText('mod_contextcontent_addtitle','contextcontent');
if ($preserveSubmittedPageForm) {
    $htmlarea->setContent((string) $this->getParam('pagecontent', ''));
} elseif ($mode == 'add') {
    $htmlarea->setContent('');
} else {
    $htmlarea->setContent($page['pagecontent']);
}

$label = new label ($this->objLanguage->languageText('mod_contextcontent_pagecontent','contextcontent'), 'input_htmlarea');

if ($contentType === 'short_text') {
    $shortTextGuidance = $this->objLanguage->languageText('mod_contextcontent_shorttext_guidance', 'contextcontent');
    $shortTextCount = $this->objLanguage->languageText('mod_contextcontent_shorttext_count', 'contextcontent');
    $shortTextLimit = $this->objLanguage->languageText('mod_contextcontent_shorttext_limit', 'contextcontent');
    $formTable->startRow();
    $formTable->addCell('');
    $formTable->addCell('<p class="contextcontent-authoring-guidance">'
        . htmlentities($shortTextGuidance, ENT_QUOTES, 'UTF-8') . '</p>');
    $formTable->endRow();
}

$formTable->startRow();
$formTable->addCell($label->show());
if ($contentType === 'image_audio') {
    $values = array('image_url'=>'', 'audio_url'=>'', 'image_alt'=>'', 'media_caption'=>'', 'audio_transcript'=>'');
    if ($mode === 'edit' && !empty($page['pagecontent'])) {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8" ?>' . $page['pagecontent']);
        libxml_clear_errors();
        $images = $doc->getElementsByTagName('img');
        $audios = $doc->getElementsByTagName('audio');
        $captions = $doc->getElementsByTagName('figcaption');
        $details = $doc->getElementsByTagName('details');
        if ($images->length) { $values['image_url']=$images->item(0)->getAttribute('src'); $values['image_alt']=$images->item(0)->getAttribute('alt'); }
        if ($audios->length) { $values['audio_url']=$audios->item(0)->getAttribute('src'); }
        if ($captions->length) { $values['media_caption']=$captions->item(0)->textContent; }
        if ($details->length) { $values['audio_transcript']=preg_replace('/^\s*Transcript \(optional\)\s*/u', '', trim($details->item(0)->textContent)); }
    }
    if ($preserveSubmittedPageForm) { $values['image_url'] = (string) $this->getParam('image_url', $values['image_url']); }
    if ($preserveSubmittedPageForm) { $values['audio_url'] = (string) $this->getParam('audio_url', $values['audio_url']); }
    if ($preserveSubmittedPageForm) { $values['image_alt'] = (string) $this->getParam('image_alt', $values['image_alt']); }
    if ($preserveSubmittedPageForm) { $values['media_caption'] = (string) $this->getParam('media_caption', $values['media_caption']); }
    if ($preserveSubmittedPageForm) { $values['audio_transcript'] = (string) $this->getParam('audio_transcript', $values['audio_transcript']); }
    $field = function ($name, $labelKey, $value, $textarea = false) {
        $labelText = $this->objLanguage->languageText($labelKey, 'contextcontent');
        $inputType = in_array($name, array('image_url', 'audio_url'), true) ? 'url' : 'text';
        $control = $textarea
            ? '<textarea name="'.$name.'" id="input_'.$name.'" rows="5">'.htmlentities($value, ENT_QUOTES, 'UTF-8').'</textarea>'
            : '<input type="'.$inputType.'" name="'.$name.'" id="input_'.$name.'" value="'.htmlentities($value, ENT_QUOTES, 'UTF-8').'"'.($name==='image_url'?' required="required"':'').' />';
        return '<div class="contextcontent-media-field"><label for="input_'.$name.'">'.htmlentities($labelText, ENT_QUOTES, 'UTF-8').'</label>'.$control.'</div>';
    };
    $editorMarkup = '<section class="contextcontent-image-audio-authoring"><p>'
        . htmlentities($this->objLanguage->languageText('mod_contextcontent_imageaudio_guidance', 'contextcontent'), ENT_QUOTES, 'UTF-8') . '</p>'
        . $field('image_url','mod_contextcontent_image_url',$values['image_url'])
        . '<p><button type="button" class="button contextcontent-media-picker" data-policy="image" data-target="input_image_url">'
        . htmlentities($this->objLanguage->languageText('mod_contextcontent_choose_image', 'contextcontent'), ENT_QUOTES, 'UTF-8') . '</button></p>'
        . $field('image_alt','mod_contextcontent_image_alt',$values['image_alt'])
        . $field('media_caption','mod_contextcontent_media_caption',$values['media_caption'],true)
        . $field('audio_url','mod_contextcontent_audio_url',$values['audio_url'])
        . '<p><button type="button" class="button contextcontent-media-picker" data-policy="audio" data-target="input_audio_url">'
        . htmlentities($this->objLanguage->languageText('mod_contextcontent_choose_audio', 'contextcontent'), ENT_QUOTES, 'UTF-8') . '</button></p>'
        . $field('audio_transcript','mod_contextcontent_audio_transcript',$values['audio_transcript'],true) . '</section>';
    $pickerBaseUrl = html_entity_decode($this->uri(array('action'=>'filepicker'),'filemanager'), ENT_QUOTES, 'UTF-8');
    $this->appendArrayVar('headerParams', '<script type="text/javascript">(function(){"use strict";window.ChisimbaFilePickerReceive=function(target,file){var field=document.getElementById(target);if(field&&file&&file.url){field.value=file.url;field.dispatchEvent(new Event("change",{bubbles:true}));}};document.addEventListener("DOMContentLoaded",function(){Array.prototype.forEach.call(document.querySelectorAll(".contextcontent-media-picker"),function(button){button.addEventListener("click",function(){var separator='.json_encode(strpos($pickerBaseUrl, '?') === false ? '?' : '&').' ,url='.json_encode($pickerBaseUrl).'+separator+"policy="+encodeURIComponent(button.getAttribute("data-policy"))+"&target="+encodeURIComponent(button.getAttribute("data-target"));window.open(url,"chisimbaFilePicker","width=920,height=720,resizable=yes,scrollbars=yes");});});});}());</script>');
    $this->appendArrayVar('headerParams', '<style type="text/css">.contextcontent-image-audio-authoring{max-width:720px;padding:1.25rem;border:1px solid #cfd8dc;border-radius:12px;background:#f7fafb}.contextcontent-media-field{margin:1rem 0}.contextcontent-media-field label{display:block;margin-bottom:.35rem;font-weight:700}.contextcontent-media-field input,.contextcontent-media-field textarea{box-sizing:border-box;width:100%;max-width:100%}.contextcontent-media-picker{margin-top:.15rem}</style>');
} elseif ($contentType === 'tiktok_video') {
    $values = array('tiktok_url'=>'', 'tiktok_caption'=>'', 'tiktok_transcript'=>'');
    if ($mode === 'edit' && !empty($page['pagecontent'])) {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8" ?>' . $page['pagecontent']);
        libxml_clear_errors();
        $iframes = $doc->getElementsByTagName('iframe');
        $captions = $doc->getElementsByTagName('figcaption');
        $details = $doc->getElementsByTagName('details');
        if ($iframes->length) { $values['tiktok_url']=$iframes->item(0)->getAttribute('src'); }
        if ($captions->length) { $values['tiktok_caption']=$captions->item(0)->textContent; }
        if ($details->length) { $values['tiktok_transcript']=trim(preg_replace('/^\s*[^\n]+\s*/u', '', trim($details->item(0)->textContent))); }
    }
    if ($preserveSubmittedPageForm) { $values['tiktok_url'] = (string) $this->getParam('tiktok_url', $values['tiktok_url']); }
    if ($preserveSubmittedPageForm) { $values['tiktok_caption'] = (string) $this->getParam('tiktok_caption', $values['tiktok_caption']); }
    if ($preserveSubmittedPageForm) { $values['tiktok_transcript'] = (string) $this->getParam('tiktok_transcript', $values['tiktok_transcript']); }
    $tiktokLabel = function ($key) { return htmlentities($this->objLanguage->languageText($key, 'contextcontent'), ENT_QUOTES, 'UTF-8'); };
    $tiktokField = function ($name, $key, $value, $textarea = false, $required = false) use ($tiktokLabel) {
        $escaped = htmlentities($value, ENT_QUOTES, 'UTF-8');
        $control = $textarea ? '<textarea name="'.$name.'" id="input_'.$name.'" rows="6">'.$escaped.'</textarea>'
            : '<input type="url" name="'.$name.'" id="input_'.$name.'" value="'.$escaped.'"'.($required?' required="required" aria-required="true"':'').' />';
        return '<div class="contextcontent-media-field"><label for="input_'.$name.'">'.$tiktokLabel($key).'</label>'.$control.'</div>';
    };
    $editorMarkup = '<section class="contextcontent-tiktok-authoring"><p>'.$tiktokLabel('mod_contextcontent_tiktok_guidance').'</p>'
        . $tiktokField('tiktok_url','mod_contextcontent_tiktok_url',$values['tiktok_url'],false,true)
        . $tiktokField('tiktok_caption','mod_contextcontent_tiktok_caption',$values['tiktok_caption'],true)
        . $tiktokField('tiktok_transcript','mod_contextcontent_tiktok_transcript',$values['tiktok_transcript'],true) . '</section>';
    $this->appendArrayVar('headerParams', '<style type="text/css">.contextcontent-tiktok-authoring{max-width:720px;padding:1.25rem;border:1px solid #cfd8dc;border-radius:12px;background:#f7fafb}.contextcontent-tiktok-authoring .contextcontent-media-field{margin:1rem 0}.contextcontent-tiktok-authoring label{font-weight:700}.contextcontent-tiktok-authoring .contextcontent-media-field label{display:block;margin-bottom:.35rem}.contextcontent-tiktok-authoring input[type=url],.contextcontent-tiktok-authoring textarea{box-sizing:border-box;width:100%;max-width:100%}</style>');
} elseif ($contentType === 'video') {
    $values = array('video_url'=>'', 'video_caption'=>'', 'video_transcript'=>'', 'video_orientation'=>'portrait');
    if ($mode === 'edit' && !empty($page['pagecontent'])) {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8" ?>' . $page['pagecontent']);
        libxml_clear_errors();
        $videos = $doc->getElementsByTagName('video');
        $iframes = $doc->getElementsByTagName('iframe');
        $captions = $doc->getElementsByTagName('figcaption');
        $details = $doc->getElementsByTagName('details');
        if ($videos->length) { $values['video_url']=$videos->item(0)->getAttribute('src'); }
        if ($iframes->length) { $values['video_url']=$iframes->item(0)->getAttribute('src'); }
        if ($captions->length) { $values['video_caption']=$captions->item(0)->textContent; }
        if ($details->length) { $values['video_transcript']=trim(preg_replace('/^\s*[^\n]+\s*/u', '', trim($details->item(0)->textContent))); }
        if (strpos($page['pagecontent'], 'contextcontent-video-landscape') !== false) { $values['video_orientation']='landscape'; }
    }
    if ($preserveSubmittedPageForm) { $values['video_url'] = (string) $this->getParam('video_url', $values['video_url']); }
    if ($preserveSubmittedPageForm) { $values['video_caption'] = (string) $this->getParam('video_caption', $values['video_caption']); }
    if ($preserveSubmittedPageForm) { $values['video_transcript'] = (string) $this->getParam('video_transcript', $values['video_transcript']); }
    if ($preserveSubmittedPageForm) { $values['video_orientation'] = (string) $this->getParam('video_orientation', $values['video_orientation']); }
    $videoLabel = function ($key) { return htmlentities($this->objLanguage->languageText($key, 'contextcontent'), ENT_QUOTES, 'UTF-8'); };
    $videoField = function ($name, $key, $value, $textarea = false, $required = false) use ($videoLabel) {
        $escaped = htmlentities($value, ENT_QUOTES, 'UTF-8');
        $control = $textarea ? '<textarea name="'.$name.'" id="input_'.$name.'" rows="6">'.$escaped.'</textarea>'
            : '<input type="url" name="'.$name.'" id="input_'.$name.'" value="'.$escaped.'"'.($required?' required="required" aria-required="true"':'').' />';
        return '<div class="contextcontent-media-field"><label for="input_'.$name.'">'.$videoLabel($key).'</label>'.$control.'</div>';
    };
    $portraitChecked = $values['video_orientation'] === 'portrait' ? ' checked="checked"' : '';
    $landscapeChecked = $values['video_orientation'] === 'landscape' ? ' checked="checked"' : '';
    $orientation = '<fieldset class="contextcontent-video-orientation"><legend>'.$videoLabel('mod_contextcontent_video_orientation').'</legend>'
        . '<label><input type="radio" name="video_orientation" value="portrait"'.$portraitChecked.' /> '.$videoLabel('mod_contextcontent_video_portrait').'</label> '
        . '<label><input type="radio" name="video_orientation" value="landscape"'.$landscapeChecked.' /> '.$videoLabel('mod_contextcontent_video_landscape').'</label></fieldset>';
    $editorMarkup = '<section class="contextcontent-video-authoring"><p>'.$videoLabel('mod_contextcontent_video_guidance').'</p>'
        . $videoField('video_url','mod_contextcontent_video_url',$values['video_url'],false,true)
        . $orientation
        . $videoField('video_caption','mod_contextcontent_video_caption',$values['video_caption'],true)
        . $videoField('video_transcript','mod_contextcontent_video_transcript',$values['video_transcript'],true) . '</section>';
    $this->appendArrayVar('headerParams', '<style type="text/css">.contextcontent-video-authoring{max-width:720px;padding:1.25rem;border:1px solid #cfd8dc;border-radius:12px;background:#f7fafb}.contextcontent-video-authoring .contextcontent-media-field{margin:1rem 0}.contextcontent-video-authoring label{font-weight:700}.contextcontent-video-authoring .contextcontent-media-field label{display:block;margin-bottom:.35rem}.contextcontent-video-authoring input[type=url],.contextcontent-video-authoring textarea{box-sizing:border-box;width:100%;max-width:100%}.contextcontent-video-orientation{margin:1rem 0;padding:.75rem 1rem}.contextcontent-video-orientation label{display:inline-block;margin:.25rem 1.5rem .25rem 0}</style>');
} elseif (in_array($contentType, array('pdf', 'zip_bundle', 'external_reading'), true)) {
    $values = array('resource_url'=>'', 'resource_filepreview'=>'', 'resource_description'=>'', 'resource_source'=>'');
    if ($mode === 'edit' && !empty($page['pagecontent'])) {
        if ($contentType === 'zip_bundle' && preg_match('/\[FILEPREVIEW\s+id="[A-Za-z0-9_-]+"\s+comment="[^"\r\n]+\.zip"\s*\/\]/i', $page['pagecontent'], $tokenMatch)) {
            $values['resource_filepreview'] = $tokenMatch[0];
        }
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8" ?>' . $page['pagecontent']);
        libxml_clear_errors();
        $links = $doc->getElementsByTagName('a');
        if ($links->length) { $values['resource_url'] = $links->item(0)->getAttribute('href'); }
        $finder = new DOMXPath($doc);
        foreach (array('resource_description'=>'contextcontent-resource-description', 'resource_source'=>'contextcontent-resource-source') as $key=>$class) {
            $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' ".$class." ')]");
            if ($nodes->length) { $values[$key] = trim($nodes->item(0)->textContent); }
        }
    }
    if ($preserveSubmittedPageForm) {
        foreach (array('resource_url','resource_filepreview','resource_description','resource_source') as $fieldName) {
            $values[$fieldName] = (string) $this->getParam($fieldName, $values[$fieldName]);
        }
    }
    $resourceLabel = function ($key) { return htmlentities($this->objLanguage->languageText($key, 'contextcontent'), ENT_QUOTES, 'UTF-8'); };
    $guidanceKey = $contentType === 'pdf' ? 'mod_contextcontent_pdf_guidance'
        : ($contentType === 'zip_bundle' ? 'mod_contextcontent_zip_guidance' : 'mod_contextcontent_external_guidance');
    $resourceField = function ($name, $key, $value, $textarea = false, $required = false) use ($resourceLabel) {
        $escaped = htmlentities($value, ENT_QUOTES, 'UTF-8');
        $control = $textarea ? '<textarea name="'.$name.'" id="input_'.$name.'" rows="5">'.$escaped.'</textarea>'
            : '<input type="url" name="'.$name.'" id="input_'.$name.'" value="'.$escaped.'"'.($required?' required="required" aria-required="true"':'').' />';
        return '<div class="contextcontent-media-field"><label for="input_'.$name.'">'.$resourceLabel($key).'</label>'.$control.'</div>';
    };
    if ($contentType === 'zip_bundle') {
        $zipSelectedName = '';
        if (preg_match('/^\[FILEPREVIEW\s+id="[A-Za-z0-9_-]+"\s+comment="([^"\r\n]+\.zip)"\s*\/\]$/i', $values['resource_filepreview'], $zipNameMatch)) {
            $zipSelectedName = $zipNameMatch[1];
        }
        $primaryField = '<input type="hidden" name="resource_filepreview" id="input_resource_filepreview" value="'
            . htmlentities($values['resource_filepreview'], ENT_QUOTES, 'UTF-8') . '" />'
            . '<div class="contextcontent-zip-selection"><strong>' . $resourceLabel('mod_contextcontent_zip_selected') . ':</strong> '
            . '<span id="contextcontent-zip-selected-name">'
            . ($zipSelectedName !== '' ? htmlentities($zipSelectedName, ENT_QUOTES, 'UTF-8') : $resourceLabel('mod_contextcontent_zip_none'))
            . '</span></div>';
        $pickerUrl = html_entity_decode($this->uri(array('action'=>'filepicker','policy'=>'zip','target'=>'input_resource_filepreview'),'filemanager'), ENT_QUOTES, 'UTF-8');
        $primaryField .= '<p><button type="button" class="button" id="contextcontent-choose-zip">'.$resourceLabel('mod_contextcontent_choose_zip').'</button></p>';
        $this->appendArrayVar('headerParams', '<script type="text/javascript">window.ChisimbaFilePickerReceive=function(target,file){var field=document.getElementById(target),name=document.getElementById("contextcontent-zip-selected-name");if(field&&file&&file.id&&file.name&&/\.zip$/i.test(file.name)&&/^[A-Za-z0-9_-]+$/.test(file.id)&&file.name.indexOf(String.fromCharCode(34))===-1){field.value="[FILEPREVIEW id="+String.fromCharCode(34)+file.id+String.fromCharCode(34)+" comment="+String.fromCharCode(34)+file.name+String.fromCharCode(34)+" /]";if(name){name.textContent=file.name;}field.dispatchEvent(new Event("change",{bubbles:true}));}};document.addEventListener("DOMContentLoaded",function(){var b=document.getElementById("contextcontent-choose-zip");if(b){b.addEventListener("click",function(){window.open('.json_encode($pickerUrl).',"chisimbaFilePicker","width=920,height=720,resizable=yes,scrollbars=yes");});}});</script>');
    } else {
        $primaryField = $resourceField('resource_url','mod_contextcontent_resource_url',$values['resource_url'],false,true);
    }
    if ($contentType === 'pdf') {
        $pickerUrl = html_entity_decode($this->uri(array('action'=>'filepicker','policy'=>'pdf','target'=>'input_resource_url'),'filemanager'), ENT_QUOTES, 'UTF-8');
        $primaryField .= '<p><button type="button" class="button" id="contextcontent-choose-pdf">'.$resourceLabel('mod_contextcontent_choose_pdf').'</button></p>';
        $this->appendArrayVar('headerParams', '<script type="text/javascript">window.ChisimbaFilePickerReceive=function(target,file){var field=document.getElementById(target);if(field&&file&&file.url){field.value=file.url;field.dispatchEvent(new Event("change",{bubbles:true}));}};document.addEventListener("DOMContentLoaded",function(){var b=document.getElementById("contextcontent-choose-pdf");if(b){b.addEventListener("click",function(){window.open('.json_encode($pickerUrl).',"chisimbaFilePicker","width=920,height=720,resizable=yes,scrollbars=yes");});}});</script>');
    }
    $editorMarkup = '<section class="contextcontent-resource-authoring"><p>'.$resourceLabel($guidanceKey).'</p>'
        . $primaryField
        . $resourceField('resource_description','mod_contextcontent_resource_description',$values['resource_description'],true)
        . $resourceField('resource_source','mod_contextcontent_resource_source',$values['resource_source'],true) . '</section>';
    $this->appendArrayVar('headerParams', '<style type="text/css">.contextcontent-resource-authoring{max-width:720px;padding:1.25rem;border:1px solid #cfd8dc;border-radius:12px;background:#f7fafb}.contextcontent-resource-authoring .contextcontent-media-field{margin:1rem 0}.contextcontent-resource-authoring .contextcontent-media-field label{display:block;margin-bottom:.35rem;font-weight:700}.contextcontent-resource-authoring input[type=url],.contextcontent-resource-authoring textarea{box-sizing:border-box;width:100%;max-width:100%}.contextcontent-zip-selection{margin:.75rem 0;padding:.7rem .85rem;border:1px solid #cfd8dc;border-radius:6px;background:#fff;overflow-wrap:anywhere}</style>');
} else {
    $editorMarkup = $htmlarea->show();
}
if ($contentType === 'short_text') {
    $editorMarkup = '<section class="contextcontent-phone-authoring">'
        . '<div class="contextcontent-phone-speaker" aria-hidden="true"></div>'
        . '<div class="contextcontent-phone-screen">' . $editorMarkup . '</div>'
        . '<p class="contextcontent-short-text-counter" aria-live="polite">'
        . '<strong id="contextcontent-short-text-count">0</strong> '
        . htmlentities($shortTextCount, ENT_QUOTES, 'UTF-8') . ' &middot; '
        . htmlentities($shortTextLimit, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<div class="contextcontent-phone-gesture" aria-hidden="true"></div></section>';
}
$formTable->addCell($editorMarkup);
$formTable->endRow();

if ($contentType === 'short_text') {
    $this->appendArrayVar('headerParams', '<style type="text/css">'
        . '.contextcontent-phone-authoring{box-sizing:border-box;width:360px;max-width:100%;margin:1rem auto;padding:12px 10px 10px;border:5px solid #263238;border-radius:38px;background:#263238;box-shadow:0 14px 34px rgba(0,0,0,.22)}'
        . '.contextcontent-phone-speaker{width:54px;height:5px;margin:0 auto 10px;border-radius:4px;background:#78909c}'
        . '.contextcontent-phone-screen{overflow:hidden;min-height:500px;padding:8px;border-radius:25px;background:#fff}'
        . '.contextcontent-phone-screen .tox-tinymce{border:0!important;border-radius:17px!important;min-height:480px!important}'
        . '.contextcontent-phone-screen .tox-edit-area iframe{min-height:390px!important}'
        . '.contextcontent-short-text-counter{margin:.65rem .25rem .45rem;color:#eceff1;text-align:center;font-size:.86rem}'
        . '.contextcontent-phone-gesture{width:92px;height:4px;margin:0 auto 2px;border-radius:4px;background:#b0bec5}'
        . '.contextcontent-short-text-counter.is-over-limit{color:#ffab91;font-weight:700}'
        . '@media(max-width:560px){.ctxtcnt-add-table,.ctxtcnt-add-table tbody,.ctxtcnt-add-table tr,.ctxtcnt-add-table td{display:block;width:100%!important}.contextcontent-phone-authoring{width:100%;border-width:3px;border-radius:28px}.contextcontent-phone-screen{min-height:420px}.contextcontent-phone-screen .tox-tinymce{min-height:400px!important}.contextcontent-phone-screen .tox-edit-area iframe{min-height:310px!important}}'
        . '</style>');
    $this->appendArrayVar('headerParams', '<script type="text/javascript">'
        . '(function(){function visibleLength(value){var node=document.createElement("div");node.innerHTML=value||"";return (node.textContent||node.innerText||"").trim().length;}'
        . 'function update(value){var output=document.getElementById("contextcontent-short-text-count");if(!output){return;}var count=visibleLength(value);output.textContent=count;var status=output.parentNode;status.className="contextcontent-short-text-counter"+(count>1200?" is-over-limit":"");}'
        . 'function connect(){var field=document.querySelector("textarea[name=pagecontent]");if(!field){return;}field.setAttribute("maxlength","1600");field.addEventListener("input",function(){update(field.value);});update(field.value);'
        . 'if(window.tinymce){var attach=function(editor){if(editor.targetElm===field){editor.on("init input change keyup undo redo",function(){update(editor.getContent());});update(editor.getContent());}};tinymce.on("AddEditor",function(event){attach(event.editor);});tinymce.editors.forEach(attach);}}'
        . 'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",connect);}else{connect();}}());'
        . '</script>');
}

/* CHISIMBA_CONTEXTCONTENT_HEADER_SCRIPT_AUTHORING_RETIRED
 * Course authors may no longer attach page-specific JavaScript or CSS.
 */
$saveButtonText = $this->objLanguage->languageText('mod_contextcontent_savepage','contextcontent');
$button = new button('submitform', $saveButtonText);
$button->cssId = 'ctxtcnt-add-submit';
$button->cssClass = 'contextcontent-primary-action';
$button->sexyButtons = FALSE;
$button->setToSubmit();

$formTable->startRow();
$formTable->addCell('', '240');
$formTable->addCell($button->show());
$formTable->endRow();

$form->addToForm($formTable->show());

if ($mode == 'edit') {
    $hiddeninput = new hiddeninput('id', $page['id']);
    $form->addToForm($hiddeninput->show());
    $hiddeninput = new hiddeninput('context', $this->contextCode);
    $form->addToForm($hiddeninput->show());
} else {
    $hiddeninput = new hiddeninput('chapter', $chapter);
    $form->addToForm($hiddeninput->show());
}

// Rules

$form->addRule('menutitle', $this->objLanguage->languageText('mod_contextcontent_pleaseenterpagetitle','contextcontent'), 'required');

echo $form->show();
?>
