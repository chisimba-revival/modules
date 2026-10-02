<?php
/**
 * Compact board or task connections to canonical Notes.
 *
 * @author Derek Keats
 * @package kanban
 */
$linkedNotes=$targetLinkedNotes??array();
$noteChoices=$board['availablenotes']??array();
$isTask=$targetType==='kanban_task';
$taskNote=$isTask?(string)($taskNoteText??''):'';
$noteCount=count($linkedNotes)+($taskNote!==''?1:0);
$returnParams=array('scope'=>$board['scopetype'],'openboard'=>$board['id']);
if($board['scopetype']==='context')$returnParams['scopeid']=$board['scopeid'];
if($targetType==='kanban_task')$returnParams['opentask']=$targetId;
$kanbanReturn=$url($returnParams).'#board-'.$board['id'];
?>
<details class="kanban-note-connections" data-note-connections data-local-note="<?php echo $taskNote!==''?'1':'0'; ?>" data-target-type="<?php echo $e($targetType); ?>" data-target-id="<?php echo $e($targetId); ?>">
    <summary class="button chisimba-button-secondary"><?php if(isset($icons))echo $icons->render('notebook-pen',array('decorative'=>true)); ?><span><?php echo $isTask?'Notes':'Connected notes'; ?></span><?php if($noteCount): ?><span class="chisimba-pill" data-note-count><?php echo $noteCount; ?></span><?php endif; ?></summary>
    <div class="kanban-note-connections__panel">
        <?php if($taskNote!==''): ?><section><strong>Task note</strong><p><?php echo nl2br($e($taskNote)); ?></p></section><?php endif; ?>
        <ul class="kanban-note-links" data-note-links><?php foreach($linkedNotes as $linkedNote): ?><li><a data-note-open data-note-id="<?php echo $e($linkedNote['id']); ?>" data-editor-url="<?php echo $e($this->uri(array('action'=>'modaldata','noteid'=>$linkedNote['id']),'pagenotes')); ?>" href="<?php echo $e($this->uri(array('action'=>'view','noteid'=>$linkedNote['id'],'return'=>$kanbanReturn),'pagenotes')); ?>"><?php echo $e($linkedNote['title']); ?></a></li><?php endforeach; ?></ul>
        <?php if(!$linkedNotes): ?><p class="chisimba-muted" data-note-empty>No connected notes yet.</p><?php endif; ?>
        <?php if($edit): ?>
        <form method="post" action="<?php echo $e($url(array('action'=>'connectnote'))); ?>" data-note-connect>
            <?php echo $hidden($board['id']); ?><input type="hidden" name="targettype" value="<?php echo $e($targetType); ?>"/><input type="hidden" name="targetid" value="<?php echo $e($targetId); ?>"/>
            <input type="hidden" name="targetlabel" value="<?php echo $e($targetLabel); ?>"/>
            <label class="chisimba-form-field"><span>Link an existing note</span><select name="noteid"><option value="">Choose a note</option><?php foreach($noteChoices as $choice): ?><option value="<?php echo $e($choice['id']); ?>"><?php echo $e($choice['title']); ?></option><?php endforeach; ?></select></label>
            <button class="button chisimba-button-secondary" type="submit" name="connection" value="existing" <?php echo $noteChoices?'':'disabled'; ?>>Link note</button>
        </form>
        <form method="post" action="<?php echo $e($url(array('action'=>'connectnote'))); ?>" data-note-connect>
            <?php echo $hidden($board['id']); ?><input type="hidden" name="targettype" value="<?php echo $e($targetType); ?>"/><input type="hidden" name="targetid" value="<?php echo $e($targetId); ?>"/>
            <input type="hidden" name="targetlabel" value="<?php echo $e($targetLabel); ?>"/>
            <label class="chisimba-form-field"><span>New connected note</span><input name="title" maxlength="255" placeholder="Note title" required/></label>
            <button class="button" type="submit" name="connection" value="new">Create note</button>
        </form>
        <p data-note-feedback role="status" aria-live="polite"></p>
        <?php endif; ?>
    </div>
</details>
