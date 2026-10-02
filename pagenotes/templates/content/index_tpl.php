<?php
/**
 * Scoped Notes library.
 *
 * @author Derek Keats
 * @package pagenotes
 */
$e=fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
$url=fn($params=array())=>html_entity_decode($this->uri($params,'pagenotes'),ENT_QUOTES,'UTF-8');
$hidden='<input type="hidden" name="csrf_token" value="'.$e($notesCsrf).'"/><input type="hidden" name="actor" value="'.$e($notesActor).'"/><input type="hidden" name="scope" value="'.$e($notesScope['type']).'"/><input type="hidden" name="scopeid" value="'.$e($notesScope['id']).'"/>';
$icons=$this->getObject('iconservice','ui');
?>
<main class="chisimba-workspace chisimba-flow chisimba-structural-main chisimba-structural-main--full notes-library">
 <header class="chisimba-page-header notes-hero"><div><p class="chisimba-eyebrow">NOTES · <?php echo $e(strtoupper($notesScope['type'])); ?></p><h1><?php echo $e($notesScope['label']); ?></h1><p>Capture ideas once, then connect them to the work they support.</p></div><div class="notes-hero-icon" aria-hidden="true"><?php echo $icons->render('notebook-pen',array('decorative'=>true));?></div></header>
 <?php if($notesMessage!==''):?><p class="success" role="status"><?php echo $e($notesMessage);?></p><?php endif;?>
 <?php if($notesError!==''):?><p class="error" role="alert"><?php echo $e($notesError);?></p><?php endif;?>
 <section class="dashboard-panel notes-scope"><form method="get" action="<?php echo $e($url());?>"><input type="hidden" name="module" value="pagenotes"/><label for="notes-scope">Note scope</label><select id="notes-scope" name="scope"><option value="personal"<?php echo $notesScope['type']==='personal'?' selected':'';?>>Personal — my notes</option><option value="context"<?php echo $notesScope['type']==='context'?' selected':'';?>>Course — current context</option><option value="site"<?php echo $notesScope['type']==='site'?' selected':'';?>>Site — organisation-wide</option></select><button type="submit" class="button">View notes</button></form></section>
 <?php if($notesCanCreate):?><details class="dashboard-panel notes-new"><summary class="button"><?php echo $icons->render('plus',array('decorative'=>true));?> Add note</summary><form method="post" action="<?php echo $e($url(array('action'=>'create')));?>" class="chisimba-flow"><?php echo $hidden;?><label>Title<input type="text" name="title" maxlength="255" required placeholder="What is this note about?"/></label><label>Start writing<textarea name="body" rows="6" maxlength="50000" placeholder="Capture the thought now; format and connect it after creating the note."></textarea></label><div class="chisimba-form-actions"><button type="submit" class="button"><?php echo $icons->render('arrow-right',array('decorative'=>true));?> Create and open</button></div></form></details><?php endif;?>
 <section class="notes-grid" aria-label="Notes">
 <?php if(!$notesRows):?><div class="dashboard-panel notes-empty"><h2>No notes here yet</h2><p>Create the first note in this scope, or choose another scope.</p></div><?php endif;?>
 <?php foreach($notesRows as $note):?><article class="dashboard-panel note-card"><div class="note-card-top"><span class="chisimba-pill"><?php echo $e(ucfirst($note['scopetype']));?></span><span class="chisimba-pill"><?php echo $icons->render('link-2',array('decorative'=>true));?> <?php echo (int)$note['linkcount'];?></span></div><h2><a href="<?php echo $e($url(array('action'=>'view','noteid'=>$note['id'])));?>"><?php echo $e($note['title']);?></a></h2><p class="note-card-excerpt"><?php echo $e(mb_strimwidth(preg_replace('/\s+/u',' ',strip_tags((string)$note['body'])),0,240,'…','UTF-8'));?></p><footer><span class="chisimba-muted">Updated <?php echo $e($note['datemodified']);?></span><a class="button chisimba-button-secondary" href="<?php echo $e($url(array('action'=>'view','noteid'=>$note['id'])));?>">Open note <?php echo $icons->render('arrow-right',array('decorative'=>true));?></a></footer></article><?php endforeach;?>
 </section>
</main>
