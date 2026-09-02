<?php
/** Long-form Essay writing surface with recoverable background drafts. @author Derek Keats */
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$icons=$this->getObject('iconservice','ui');
$bookId=(string)$writtenBooking['id'];
$saveUrl=$this->uri(array('action'=>'savedraft','bookid'=>$bookId));
$submitUrl=$this->uri(array('action'=>'submitwritten','bookid'=>$bookId));
$uploadUrl=$this->uri(array('action'=>'uploadessay','bookid'=>$bookId));
$this->setVar('heading','Write Essay');
?>
<section class="chisimba-workspace chisimba-writing-workspace" data-essay-writer>
<header class="chisimba-writing-header"><div><p class="chisimba-eyebrow">Your Essay</p><h2><?php echo $e($writtenEssayTitle); ?></h2></div><div class="chisimba-save-state" role="status" aria-live="polite" data-save-state>Saved</div></header>
<div class="chisimba-editor-toolbar" role="toolbar" aria-label="Essay formatting">
<button type="button" class="chisimba-icon-button" data-command="bold" title="Bold" aria-label="Bold"><?php echo $icons->render('bold',array('decorative'=>true)); ?></button>
<button type="button" class="chisimba-icon-button" data-command="italic" title="Italic" aria-label="Italic"><?php echo $icons->render('italic',array('decorative'=>true)); ?></button>
<button type="button" class="chisimba-icon-button" data-command="formatBlock" data-value="h2" title="Heading" aria-label="Heading"><?php echo $icons->render('heading-2',array('decorative'=>true)); ?></button>
<button type="button" class="chisimba-icon-button" data-command="insertUnorderedList" title="Bulleted list" aria-label="Bulleted list"><?php echo $icons->render('list',array('decorative'=>true)); ?></button>
<button type="button" class="chisimba-icon-button" data-command="insertOrderedList" title="Numbered list" aria-label="Numbered list"><?php echo $icons->render('list-ordered',array('decorative'=>true)); ?></button>
</div>
<div class="chisimba-longform-editor chisimba-prose" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Essay text" data-editor><?php echo $writtenEssayBody; ?></div>
<footer class="chisimba-writing-footer"><span data-word-count>0 words</span><span>Drafts save automatically</span></footer>
<form method="post" action="<?php echo $e($submitUrl); ?>" data-submit-form>
<input type="hidden" name="csrf_token" value="<?php echo $e($writtenSubmitToken); ?>"><input type="hidden" name="body_html" value="" data-submit-body>
<div class="chisimba-form-actions"><button class="button" type="submit"><?php echo $icons->render('send',array('decorative'=>true)); ?> Submit for marking</button><a class="button chisimba-button-secondary" href="<?php echo $e($uploadUrl); ?>"><?php echo $icons->render('upload',array('decorative'=>true)); ?> Upload document instead</a><a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(array('action'=>'viewallessays'))); ?>"><?php echo $icons->render('arrow-left',array('decorative'=>true)); ?> My Essays</a></div>
</form>
</section>
<script>
(function(){
const root=document.querySelector('[data-essay-writer]');if(!root)return;
const editor=root.querySelector('[data-editor]'),state=root.querySelector('[data-save-state]'),count=root.querySelector('[data-word-count]'),form=root.querySelector('[data-submit-form]');
let dirty=false,saving=false,timer=null,revision=0,token=<?php echo json_encode($writtenDraftToken); ?>;
const saveUrl=<?php echo json_encode($saveUrl); ?>;
function words(){const value=(editor.innerText||'').trim();count.textContent=(value?value.split(/\s+/).length:0)+' words';}
function changed(){revision++;dirty=true;state.textContent='Unsaved changes';words();clearTimeout(timer);timer=setTimeout(save,2000);}
async function save(){if(!dirty||saving)return true;saving=true;const savingRevision=revision;state.textContent='Saving…';const body=new URLSearchParams({csrf_token:token,body_html:editor.innerHTML});try{const response=await fetch(saveUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:body.toString(),credentials:'same-origin'});const data=await response.json();token=data.csrf||token;if(!data.ok)throw new Error('save failed');dirty=revision!==savingRevision;state.textContent=dirty?'Unsaved changes':'Saved';if(dirty){clearTimeout(timer);timer=setTimeout(save,500);}return true;}catch(error){dirty=true;state.textContent='Could not save — retrying';return false;}finally{saving=false;}}
editor.addEventListener('input',changed);editor.addEventListener('blur',save);
root.querySelectorAll('[data-command]').forEach(function(button){button.addEventListener('click',function(){editor.focus();document.execCommand(button.dataset.command,false,button.dataset.value||null);changed();});});
form.addEventListener('submit',function(event){if(!confirm('Submit this Essay for marking? You can revise it until it has been marked.')){event.preventDefault();return;}form.querySelector('[data-submit-body]').value=editor.innerHTML;dirty=false;});
window.addEventListener('beforeunload',function(event){if(dirty){event.preventDefault();event.returnValue='';}});
setInterval(save,15000);words();
})();
</script>
