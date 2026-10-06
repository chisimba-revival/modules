<?php
/** Task controls compose shared field, flow and expandable-action primitives. */
$kanbanText=fn($key)=>html_entity_decode($this->getObject('language','language')->languageText('mod_kanban_'.$key,'kanban'),ENT_QUOTES|ENT_HTML5,'UTF-8');
?>
<article class="kanban-task" data-task-id="<?php echo $e($task['id']); ?>" draggable="<?php echo $edit?'true':'false'; ?>">
    <div class="kanban-task__header"><h4><?php echo $e($task['title']); ?></h4><button class="button chisimba-button-secondary" type="button" data-task-toggle aria-expanded="true" aria-controls="task-body-<?php echo $e($task['id']); ?>" aria-label="<?php echo $e('Collapse task: '.$task['title']); ?>">Collapse</button></div>
    <div class="kanban-task__body" id="task-body-<?php echo $e($task['id']); ?>">
        <div data-task-summary><?php if($task['description']!==''): ?><p><?php echo nl2br($e($task['description'])); ?></p><?php endif; ?></div>
        <?php $targetType='kanban_task';$targetId=$task['id'];$targetLabel=$task['title'];$targetLinkedNotes=$task['linkednotes']??array();$taskNoteText=$task['notes'];include __DIR__.'/note_connections_tpl.php'; ?>
        <?php if($task['subtasks']||$edit): ?><div class="kanban-subtasks"><strong>Subtasks</strong>
            <?php foreach($task['subtasks'] as $sub)include __DIR__.'/subtask_tpl.php'; ?>
            <?php if($edit): ?>
            <template data-subtask-row-template><?php $sub=array('id'=>'__SUBTASK__','title'=>'','iscompleted'=>0);include __DIR__.'/subtask_tpl.php'; ?></template>
            <form method="post" action="<?php echo $e($url(array('action'=>'savesubtask'))); ?>" data-subtask-create>
                <?php echo $hidden($board['id']); ?><input type="hidden" name="taskid" value="<?php echo $e($task['id']); ?>"/>
                <div class="chisimba-cluster kanban-compact-actions"><input name="title" aria-label="New subtask" maxlength="255" required/><button class="button chisimba-button-secondary" type="submit">Add</button></div>
                <p data-subtask-feedback role="status" aria-live="polite"></p>
            </form>
            <?php endif; ?>
        </div><?php endif; ?>
        <?php if($edit): ?><div class="chisimba-cluster kanban-task__actions kanban-compact-actions">
            <details class="chisimba-action-disclosure" data-task-editor><summary class="button chisimba-button-secondary"><?php echo $e($kanbanText('edit')); ?></summary>
                <form class="chisimba-form chisimba-flow kanban-popover" method="post" action="<?php echo $e($url(array('action'=>'savetask'))); ?>">
                    <?php echo $hidden($board['id']); ?><input type="hidden" name="taskid" value="<?php echo $e($task['id']); ?>"/>
                    <div class="chisimba-form-field"><label for="task-title-<?php echo $e($task['id']); ?>"><?php echo $e($kanbanText('title_field')); ?></label><input type="text" id="task-title-<?php echo $e($task['id']); ?>" name="title" maxlength="255" value="<?php echo $e($task['title']); ?>" required/></div>
                    <div class="chisimba-form-field"><label for="task-description-<?php echo $e($task['id']); ?>"><?php echo $e($kanbanText('description_field')); ?></label><textarea id="task-description-<?php echo $e($task['id']); ?>" name="description"><?php echo $e($task['description']); ?></textarea></div>
                    <div class="chisimba-form-field"><label for="task-notes-<?php echo $e($task['id']); ?>"><?php echo $e($kanbanText('notes_field')); ?></label><textarea id="task-notes-<?php echo $e($task['id']); ?>" name="notes"><?php echo $e($task['notes']); ?></textarea></div>
                    <button class="button" type="submit"><?php echo $e($kanbanText('save')); ?></button>
                </form>
            </details>
            <?php $statusKeys=array_keys($labels);$statusIndex=array_search($status,$statusKeys,true);foreach(array('left'=>$statusIndex-1,'right'=>$statusIndex+1) as $direction=>$targetIndex):if(isset($statusKeys[$targetIndex])): ?>
            <form method="post" action="<?php echo $e($url(array('action'=>'movetask'))); ?>" data-task-move><?php echo $hidden($board['id']); ?><input type="hidden" name="taskid" value="<?php echo $e($task['id']); ?>"/><input type="hidden" name="status" value="<?php echo $e($statusKeys[$targetIndex]); ?>"/><input type="hidden" name="sortorder" value="<?php echo time(); ?>"/><button class="button chisimba-button-secondary" type="submit">Move <?php echo $e($direction); ?></button></form>
            <?php endif;endforeach; ?>
            <form method="post" action="<?php echo $e($url(array('action'=>'deletetask'))); ?>" data-task-delete data-confirm="Delete this task?"><?php echo $hidden($board['id']); ?><input type="hidden" name="taskid" value="<?php echo $e($task['id']); ?>"/><button class="button chisimba-button-danger" type="submit">Delete</button></form>
        </div><?php endif; ?>
    </div>
</article>
