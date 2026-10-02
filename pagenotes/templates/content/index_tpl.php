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
?>
<main class="chisimba-workspace chisimba-flow chisimba-structural-main chisimba-structural-main--full notes-library">
 <header class="chisimba-page-header"><div><p class="chisimba-eyebrow">NOTES · <?php echo $e(strtoupper($notesScope['type'])); ?></p><h1><?php echo $e($notesScope['label']); ?></h1><p>Keep one note and connect it to related work across Chisimba.</p></div></header>
 <?php if($notesMessage!==''):?><p class="success" role="status"><?php echo $e($notesMessage);?></p><?php endif;?>
 <?php if($notesError!==''):?><p class="error" role="alert"><?php echo $e($notesError);?></p><?php endif;?>
 <section class="dashboard-panel notes-scope"><form method="get" action="<?php echo $e($url());?>"><input type="hidden" name="module" value="pagenotes"/><label for="notes-scope">Note scope</label><select id="notes-scope" name="scope"><option value="personal"<?php echo $notesScope['type']==='personal'?' selected':'';?>>Personal — my notes</option><option value="context"<?php echo $notesScope['type']==='context'?' selected':'';?>>Course — current context</option><option value="site"<?php echo $notesScope['type']==='site'?' selected':'';?>>Site — organisation-wide</option></select><button type="submit" class="button">View notes</button></form></section>
 <?php if($notesCanCreate):?><details class="dashboard-panel notes-new"><summary class="button">Add note</summary><form method="post" action="<?php echo $e($url(array('action'=>'create')));?>" class="chisimba-flow"><?php echo $hidden;?><label>Title<input type="text" name="title" maxlength="255" required/></label><label>Note<textarea name="body" rows="7" maxlength="50000"></textarea></label><button type="submit" class="button">Create note</button></form></details><?php endif;?>
 <section class="notes-grid" aria-label="Notes">
 <?php if(!$notesRows):?><div class="dashboard-panel notes-empty"><h2>No notes here yet</h2><p>Create the first note in this scope, or choose another scope.</p></div><?php endif;?>
 <?php foreach($notesRows as $note):?><article class="dashboard-panel note-card"><p class="chisimba-eyebrow"><?php echo $e(strtoupper($note['scopetype']));?> · <?php echo (int)$note['linkcount'];?> <?php echo (int)$note['linkcount']===1?'LINK':'LINKS';?></p><h2><a href="<?php echo $e($url(array('action'=>'view','noteid'=>$note['id'])));?>"><?php echo $e($note['title']);?></a></h2><p><?php echo $e(mb_strimwidth(preg_replace('/\s+/u',' ',strip_tags((string)$note['body'])),0,240,'…','UTF-8'));?></p><p class="chisimba-muted">Updated <?php echo $e($note['datemodified']);?></p></article><?php endforeach;?>
 </section>
</main>
