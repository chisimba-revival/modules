<?php
/** One subtask with an optional inline, progressively enhanced title editor. */
$subId=$sub['id'];
?>
<div class="chisimba-cluster kanban-compact-actions" data-subtask-row>
    <label class="kanban-subtask"><input type="checkbox" data-subtask-id="<?php echo $e($subId); ?>" <?php echo !empty($sub['iscompleted'])?'checked':''; ?> <?php echo $edit?'':'disabled'; ?>/><span data-subtask-title><?php echo $e($sub['title']); ?></span></label>
    <?php if($edit): ?>
    <details class="chisimba-action-disclosure" data-subtask-editor>
        <summary class="button chisimba-button-secondary" aria-label="<?php echo $e($kanbanText('subtask_edit').': '.$sub['title']); ?>" data-edit-label="<?php echo $e($kanbanText('subtask_edit')); ?>"><?php echo $e($kanbanText('edit')); ?></summary>
        <form class="chisimba-form chisimba-flow kanban-popover" method="post" action="<?php echo $e($url(array('action'=>'updatesubtask'))); ?>" data-subtask-edit>
            <?php echo $hidden($board['id']); ?>
            <input type="hidden" name="taskid" value="<?php echo $e($task['id']); ?>"/>
            <input type="hidden" name="subtaskid" value="<?php echo $e($subId); ?>"/>
            <input type="hidden" name="original_title" value="<?php echo $e($sub['title']); ?>"/>
            <div class="chisimba-form-field"><label for="subtask-title-<?php echo $e($subId); ?>"><?php echo $e($kanbanText('subtask_title')); ?></label><input type="text" id="subtask-title-<?php echo $e($subId); ?>" name="title" value="<?php echo $e($sub['title']); ?>" maxlength="255" required/></div>
            <div class="chisimba-cluster"><button class="button" type="submit"><?php echo $e($kanbanText('save')); ?></button><button class="button chisimba-button-secondary" type="button" data-subtask-cancel><?php echo $e($kanbanText('cancel')); ?></button></div>
            <p data-subtask-edit-feedback role="status" aria-live="polite" hidden></p>
        </form>
    </details>
    <?php endif; ?>
</div>
