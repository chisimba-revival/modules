<?php
/**
 * Content blocks management template.
 *
 * PHP version 8
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * @category  Chisimba
 * @package   contentblocks
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/modules
 */
$e = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$l = $contentblocksLabels;
$moduleUrl = function (array $params, $module = 'contentblocks') {
    return html_entity_decode($this->uri($params, $module), ENT_QUOTES, 'UTF-8');
};
$scopeUrl = function ($scope) use ($moduleUrl) {
    return $moduleUrl(array('action' => 'manage', 'scope' => $scope));
};
$saveUrl = $moduleUrl(array('action' => 'save', 'scope' => $contentblocksScope));
$edit = is_array($contentblocksEdit) ? $contentblocksEdit : array();
$imageValue = (string)($edit['image_url'] ?? '');
$imagePickerUrl = $moduleUrl(array(
    'action' => 'filepicker',
    'policy' => 'image',
    'target' => 'contentblocks-image-url',
), 'filemanager');
$videoPickerUrl = $moduleUrl(array(
    'action' => 'filepicker',
    'policy' => 'video',
    'target' => 'contentblocks-video-url',
), 'filemanager');
$pickerScript = '<script type="text/javascript">(function(){"use strict";'
    . 'function previewImage(url){var image=document.getElementById("contentblocks-image-preview");if(!image){return;}image.src=url||"";image.hidden=!url;}'
    . 'function previewVideo(url){var video=document.getElementById("contentblocks-video-preview");if(!video){return;}video.src=url||"";video.hidden=!url;if(!url){video.removeAttribute("src");video.load();}}'
    . 'window.ChisimbaFilePickerReceive=function(target,file){var field=document.getElementById(target);if(!field||!file||!file.url){return;}field.value=file.url;if(target==="contentblocks-image-url"){previewImage(file.url);}if(target==="contentblocks-video-url"){previewVideo(file.url);}field.dispatchEvent(new Event("change",{bubbles:true}));};'
    . 'document.addEventListener("DOMContentLoaded",function(){var choose=document.getElementById("contentblocks-choose-image"),remove=document.getElementById("contentblocks-remove-image"),field=document.getElementById("contentblocks-image-url"),chooseVideo=document.getElementById("contentblocks-choose-video"),removeVideo=document.getElementById("contentblocks-remove-video"),videoField=document.getElementById("contentblocks-video-url"),type=document.getElementById("contentblocks-blocktype"),width=document.getElementById("contentblocks-blockwidth"),help=document.getElementById("contentblocks-type-help"),titleLabel=document.getElementById("contentblocks-title-label"),titleHelp=document.getElementById("contentblocks-title-help");'
    . 'if(choose){choose.addEventListener("click",function(){window.open(' . json_encode($imagePickerUrl) . ',"chisimbaContentblocksImagePicker","width=920,height=720,resizable=yes,scrollbars=yes");});}'
    . 'if(remove&&field){remove.addEventListener("click",function(){field.value="";previewImage("");field.dispatchEvent(new Event("change",{bubbles:true}));});}'
    . 'if(chooseVideo){chooseVideo.addEventListener("click",function(){window.open(' . json_encode($videoPickerUrl) . ',"chisimbaContentblocksVideoPicker","width=920,height=720,resizable=yes,scrollbars=yes");});}'
    . 'if(removeVideo&&videoField){removeVideo.addEventListener("click",function(){videoField.value="";previewVideo("");videoField.dispatchEvent(new Event("change",{bubbles:true}));});}'
    . 'function updateType(){if(!type){return;}var hero=type.value==="hero",video=type.value==="videohero";Array.prototype.forEach.call(document.querySelectorAll(".contentblocks-hero-only"),function(element){element.hidden=!hero;});Array.prototype.forEach.call(document.querySelectorAll(".contentblocks-video-hero-only"),function(element){element.hidden=!video;});Array.prototype.forEach.call(document.querySelectorAll(".contentblocks-text-only"),function(element){element.hidden=video;});if(videoField){videoField.required=video;}if(width){var side=width.querySelector("option[value=normal]");if(side){side.disabled=hero||video;}if(hero||video){width.value="wide";}}if(help){help.textContent=video?help.getAttribute("data-videohero"):(hero?help.getAttribute("data-hero"):help.getAttribute("data-information"));}if(titleLabel){titleLabel.textContent=video?titleLabel.getAttribute("data-video"):titleLabel.getAttribute("data-standard");}if(titleHelp){titleHelp.hidden=!video;}}'
    . 'if(type){type.addEventListener("change",updateType);updateType();}});'
    . '}());</script>';
$this->appendArrayVar('headerParams', $pickerScript);
?>
<main class="contentblocks-admin">
  <h1><?= $e($l['title']) ?></h1>
  <p><?= $e($l['intro']) ?></p>
  <?php if ($contentblocksFlash !== ''): ?><p class="contentblocks-notice"><?= $e($contentblocksFlash) ?></p><?php endif; ?>
  <nav class="contentblocks-tabs">
    <?php if ($this->getObject('user', 'security')->isAdmin()): ?><a href="<?= $e($scopeUrl('site')) ?>"><?= $e($l['siteblocks']) ?></a><?php endif; ?>
    <?php if ($contentblocksContextCode !== ''): ?><a href="<?= $e($scopeUrl('context')) ?>"><?= $e($l['contextblocks']) ?></a><?php endif; ?>
  </nav>
  <?php if (!empty($contentblocksDenied)): ?>
    <p class="contentblocks-error"><?= $e($l['forbidden']) ?></p>
  <?php else: ?>
    <section class="contentblocks-list">
      <h2><?= $e($contentblocksScope === 'context' ? $l['contextblocks'] : $l['siteblocks']) ?></h2>
      <?php if (!$contentblocksRows): ?><p><?= $e($l['empty']) ?></p><?php endif; ?>
      <?php foreach ($contentblocksRows as $row): ?>
        <article class="contentblocks-row">
          <div><strong><?= $e($row['title']) ?></strong><br><small><?= $e($l['key']) ?>: <code><?= $e($row['blockkey']) ?></code></small></div>
          <div class="contentblocks-actions">
            <a href="<?= $e($moduleUrl(array('action'=>'manage','scope'=>$contentblocksScope,'id'=>$row['id']))) ?>"><?= $e($l['edit']) ?></a>
            <form method="post" action="<?= $e($moduleUrl(array())) ?>" onsubmit="return confirm(<?= $e(json_encode($l['confirmdelete'])) ?>)">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="scope" value="<?= $e($contentblocksScope) ?>">
              <input type="hidden" name="csrf_token" value="<?= $e($contentblocksCsrf) ?>">
              <input type="hidden" name="id" value="<?= $e($row['id']) ?>">
              <button type="submit"><?= $e($l['delete']) ?></button>
            </form>          </div>
        </article>
      <?php endforeach; ?>
    </section>
    <section class="contentblocks-form">
      <h2><?= $e($edit ? $l['edit'] : $l['new']) ?></h2>
      <form method="post" action="<?= $e($saveUrl) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="scope" value="<?= $e($contentblocksScope) ?>">
        <input type="hidden" name="csrf_token" value="<?= $e($contentblocksCsrf) ?>">
        <input type="hidden" name="id" value="<?= $e($edit['id'] ?? '') ?>">
        <div class="contentblocks-grid">
          <label><?= $e($l['type']) ?><select id="contentblocks-blocktype" name="blocktype" <?= $edit ? 'disabled' : '' ?>><option value="hero" <?= ($edit['blocktype'] ?? '') === 'hero' ? 'selected' : '' ?>><?= $e($l['hero']) ?></option><option value="videohero" <?= ($edit['blocktype'] ?? '') === 'videohero' ? 'selected' : '' ?>><?= $e($l['videohero']) ?></option><option value="information" <?= ($edit['blocktype'] ?? 'information') === 'information' ? 'selected' : '' ?>><?= $e($l['information']) ?></option></select></label>
          <label><?= $e($l['width']) ?><select id="contentblocks-blockwidth" name="blockwidth" <?= $edit ? 'disabled' : '' ?>><option value="wide" <?= ($edit['blockwidth'] ?? 'wide') === 'wide' ? 'selected' : '' ?>><?= $e($l['wide']) ?></option><option value="normal" <?= ($edit['blockwidth'] ?? '') === 'normal' ? 'selected' : '' ?>><?= $e($l['normal']) ?></option></select></label>
        </div>
        <p id="contentblocks-type-help" class="contentblocks-type-help" data-hero="<?= $e($l['herodesc']) ?>" data-videohero="<?= $e($l['videoherodesc']) ?>" data-information="<?= $e($l['informationdesc']) ?>"></p>
        <?php if ($edit): ?><input type="hidden" name="blocktype" value="<?= $e($edit['blocktype']) ?>"><input type="hidden" name="blockwidth" value="<?= $e($edit['blockwidth']) ?>"><?php endif; ?>
        <label><span id="contentblocks-title-label" data-standard="<?= $e($l['blocktitle']) ?>" data-video="<?= $e($l['videoname']) ?>"><?= $e(($edit['blocktype'] ?? '') === 'videohero' ? $l['videoname'] : $l['blocktitle']) ?></span><input required maxlength="250" name="title" value="<?= $e($edit['title'] ?? '') ?>"></label>
        <p id="contentblocks-title-help" class="contentblocks-field-help" <?= ($edit['blocktype'] ?? '') !== 'videohero' ? 'hidden' : '' ?>><?= $e($l['videonamehelp']) ?></p>
        <label class="contentblocks-check contentblocks-text-only"><input type="checkbox" name="show_title" value="1" <?= ($edit['show_title'] ?? '1') === '1' ? 'checked' : '' ?>> <?= $e($l['showtitle']) ?></label>
        <div class="contentblocks-text-only"><label><?= $e($l['body']) ?></label>
        <?php $editor = $this->newObject('htmlarea', 'htmlelements'); $editor->name = 'body_html'; $editor->height = '300px'; $editor->width = '100%'; $editor->setContent($edit['body_html'] ?? ''); echo $editor->show(); ?></div>
        <fieldset class="contentblocks-image-field contentblocks-hero-only">
          <legend><?= $e($l['imageurl']) ?></legend>
          <p><?= $e($l['imagehelp']) ?></p>
          <input type="text" readonly id="contentblocks-image-url" name="image_url" value="<?= $e($imageValue) ?>">
          <div class="contentblocks-actions"><button type="button" id="contentblocks-choose-image"><?= $e($l['chooseimage']) ?></button><button type="button" id="contentblocks-remove-image"><?= $e($l['removeimage']) ?></button></div>
          <img id="contentblocks-image-preview" class="contentblocks-image-preview" src="<?= $e($imageValue) ?>" alt="" <?= $imageValue === '' ? 'hidden' : '' ?>>
        </fieldset>
        <fieldset class="contentblocks-image-field contentblocks-video-hero-only">
          <legend><?= $e($l['videourl']) ?></legend>
          <p><?= $e($l['videohelp']) ?></p>
          <input type="url" id="contentblocks-video-url" name="video_url" value="<?= $e($imageValue) ?>" <?= ($edit['blocktype'] ?? '') === 'videohero' ? 'required' : '' ?>>
          <div class="contentblocks-actions"><button type="button" id="contentblocks-choose-video"><?= $e($l['choosevideo']) ?></button><button type="button" id="contentblocks-remove-video"><?= $e($l['removevideo']) ?></button></div>
          <video id="contentblocks-video-preview" class="contentblocks-video-preview" src="<?= $e($imageValue) ?>" controls playsinline preload="metadata" <?= $imageValue === '' ? 'hidden' : '' ?>></video>
        </fieldset>
        <div class="contentblocks-grid contentblocks-hero-only"><label><?= $e($l['actionlabel']) ?><input name="action_label" value="<?= $e($edit['action_label'] ?? '') ?>"></label><label><?= $e($l['actionurl']) ?><input name="action_url" value="<?= $e($edit['action_url'] ?? '') ?>"></label></div>
        <div class="contentblocks-actions"><button type="submit"><?= $e($l['save']) ?></button><?php if ($edit): ?><a href="<?= $e($scopeUrl($contentblocksScope)) ?>"><?= $e($l['cancel']) ?></a><?php endif; ?></div>
      </form>
    </section>
  <?php endif; ?>
</main>
