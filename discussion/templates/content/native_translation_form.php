<?php
/** Render only after block expansion, preserving the editor's configuration JSON. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$t=fn($key)=>$this->objLanguage->languageText($key,str_starts_with($key,'word_')?'system':'discussion');
$post=$translationPostData;
$draft=is_array($translationDraft)?$translationDraft:array('title'=>'','message'=>'','language'=>'');
$languages=$this->getObject('languagecode','language')->iso_639_2_tags->codes;
foreach ($this->getObject('dbposttext','discussion')->getPostLanguages($post['post_id']) as $existing) unset($languages[$existing['language']]);
asort($languages);
$editor=$this->newObject('htmlarea','htmlelements');$editor->name='message';$editor->setContent($draft['message']);
echo '<main class="chisimba-workspace"><section class="chisimba-form-card chisimba-form-card--wide"><h1>'.$e($t('mod_discussion_translateposttitle')).'</h1>';
echo $this->getObject('contextualhelp','help')->show('discussion','translate');
echo '<details><summary>'.$e($t('mod_discussion_translation_original')).'</summary><h2>'.$e($post['post_title']).'</h2>'.$this->getObject('script','utilities')->removeScript($post['post_text']).'</details>';
if ($this->getParam('message')==='editretry') echo '<p class="chisimba-notice" role="status">'.$e($t('mod_discussion_translation_recovery')).'</p>';
echo '<form method="post" action="'.$e($this->uri(array('action'=>'savetranslation'),'discussion')).'"><input type="hidden" name="post" value="'.$e($post['post_id']).'"><input type="hidden" name="csrf_token" value="'.$e($translationToken).'">';
echo '<div class="chisimba-form-field"><label for="translation-language">'.$e($t('word_language')).'</label><select id="translation-language" name="language" required>';
foreach($languages as $code=>$label) if(strlen($code)===2) echo '<option value="'.$e($code).'"'.($draft['language']===$code?' selected':'').'>'.$e($label).'</option>';
echo '</select></div><div class="chisimba-form-field"><label for="translation-title">'.$e($t('word_subject')).'</label><input id="translation-title" name="title" maxlength="160" required value="'.$e($draft['title']).'"></div><div class="chisimba-form-field">'.$editor->show().'</div><div class="chisimba-form-actions"><button class="button" type="submit">'.$e($t('word_save')).'</button><a class="button chisimba-button-secondary" href="'.$e($this->uri(array('action'=>'viewtopic','id'=>$post['topic_id']),'discussion')).'">'.$e($t('word_cancel')).'</a></div></form></section></main>';
