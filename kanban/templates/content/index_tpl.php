<?php
$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$url=fn($params=array())=>html_entity_decode($this->uri($params,'kanban'),ENT_QUOTES,'UTF-8');
$scopeType=$kanbanScope['type'];
$hidden=function($boardId='')use($e,$kanbanCsrf,$scopeType){return '<input type="hidden" name="csrf_token" value="'.$e($kanbanCsrf).'"/><input type="hidden" name="scope" value="'.$e($scopeType).'"/>'.($boardId!==''?'<input type="hidden" name="boardid" value="'.$e($boardId).'"/>':'');};
$labels=array('not_started'=>'Not started','in_progress'=>'In progress','completed'=>'Completed');
$icons=$this->getObject('iconservice','ui');
?>
<main class="chisimba-workspace chisimba-flow kanban-workspace" data-kanban data-move-url="<?php echo $e($url(array('action'=>'movetask'))); ?>" data-csrf="<?php echo $e($kanbanCsrf); ?>" data-scope="<?php echo $e($scopeType); ?>">
    <header class="chisimba-page-header">
        <div><p class="chisimba-eyebrow"><?php echo $e($kanbanScope['label']); ?></p><h1>Kanban boards</h1><p>Plan work in personal, course or site scope.</p></div>
        <nav class="chisimba-cluster" aria-label="Board scopes">
            <a class="button chisimba-button-secondary" href="<?php echo $e($url(array('scope'=>'personal'))); ?>">Personal</a>
            <?php if((string)$this->getObject('dbcontext','context')->getContextCode()!=='root'&&(string)$this->getObject('dbcontext','context')->getContextCode()!==''): ?><a class="button chisimba-button-secondary" href="<?php echo $e($url(array('scope'=>'context'))); ?>">Course</a><?php endif; ?>
            <?php if($this->getObject('user','security')->isAdmin()): ?><a class="button chisimba-button-secondary" href="<?php echo $e($url(array('scope'=>'site'))); ?>">Site</a><?php endif; ?>
        </nav>
    </header>
    <?php if($kanbanMessage!==''): ?><div class="chisimba-notice chisimba-notice--success" role="status"><?php echo $e($kanbanMessage); ?></div><?php endif; ?>
    <?php if($kanbanError!==''): ?><div class="chisimba-notice chisimba-notice--error" role="alert"><?php echo $e($kanbanError); ?></div><?php endif; ?>
    <form class="chisimba-cluster" method="get" action="<?php echo $e($url()); ?>"><input type="hidden" name="module" value="kanban"/><input type="hidden" name="scope" value="<?php echo $e($scopeType); ?>"/><label><input type="checkbox" name="archived" value="1" <?php echo !empty($_GET['archived'])?'checked':''; ?> onchange="this.form.submit()"/> Include archived boards</label></form>
    <?php if($kanbanCanCreate): ?><details class="chisimba-form-card kanban-create">
        <summary>Create a board</summary>
        <form class="chisimba-form" method="post" action="<?php echo $e($url(array('action'=>'saveproject'))); ?>">
            <?php echo $hidden(); ?>
            <div class="chisimba-form-field"><label for="kanban-new-title">Board title</label><input id="kanban-new-title" name="title" required maxlength="255"/></div>
            <div class="chisimba-form-field"><label for="kanban-new-description">Description</label><textarea id="kanban-new-description" name="description"></textarea></div>
            <div class="chisimba-form-actions"><button class="button" type="submit"><?php echo $icons->render('circle-plus',array('decorative'=>true)); ?> Create board</button></div>
        </form>
    </details><?php endif; ?>
    <?php if(!$kanbanBoards): ?><div class="chisimba-card"><h2>No boards yet</h2><p>Create the first board in this scope.</p></div><?php endif; ?>
    <?php foreach($kanbanBoards as $board): $manage=$board['permission']==='manage';$edit=$board['permission']!=='view'; ?>
    <section class="chisimba-card kanban-board" data-board-id="<?php echo $e($board['id']); ?>">
        <header class="kanban-board__header">
            <div><p class="chisimba-eyebrow"><?php echo $e(ucfirst($board['scopetype'])); ?> · <?php echo $e(ucfirst($board['permission'])); ?></p><h2><?php echo $e($board['title']); ?></h2><?php if($board['description']!==''): ?><p><?php echo nl2br($e($board['description'])); ?></p><?php endif; ?></div>
            <?php if($manage): ?><div class="chisimba-cluster">
                <details><summary class="button chisimba-button-secondary">Edit</summary><form class="chisimba-form kanban-popover" method="post" action="<?php echo $e($url(array('action'=>'saveproject'))); ?>"><?php echo $hidden($board['id']); ?><div class="chisimba-form-field"><label>Title<input name="title" value="<?php echo $e($board['title']); ?>" required/></label></div><div class="chisimba-form-field"><label>Description<textarea name="description"><?php echo $e($board['description']); ?></textarea></label></div><button class="button" type="submit">Save</button></form></details>
                <form method="post" action="<?php echo $e($url(array('action'=>'archiveproject','archived'=>'1'))); ?>"><?php echo $hidden($board['id']); ?><button class="button chisimba-button-secondary" type="submit"><?php echo empty($board['isarchived'])?'Archive':'Restore'; ?></button></form>
                <form method="post" action="<?php echo $e($url(array('action'=>'deleteproject'))); ?>" data-confirm="Delete this board and all its tasks?"><?php echo $hidden($board['id']); ?><button class="button chisimba-button-danger" type="submit">Delete</button></form>
            </div><?php endif; ?>
        </header>
        <?php if($manage): ?><details class="kanban-sharing"><summary>Sharing and permissions</summary><p>Enter one username per line followed by <code>:view</code>, <code>:edit</code>, or <code>:manage</code>. Saving replaces current direct-user grants. Group and course-role grants will use this same permission model when those resolvers are enabled.</p><form class="chisimba-form" method="post" action="<?php echo $e($url(array('action'=>'saveaccess'))); ?>"><?php echo $hidden($board['id']); ?><div class="chisimba-form-field"><label>Direct user grants<textarea name="grants" placeholder="jane:edit&#10;john:view"><?php foreach($this->getObject('dbkanbanaccess')->grants($board['id']) as $grant){if($grant['principaltype']==='user'){$details=$this->getObject('user','security')->getUserDetails($grant['principalid']);echo $e(($details['username']??$grant['principalid']).':'.$grant['permission'])."\n";}} ?></textarea></label></div><button class="button" type="submit">Save sharing</button></form></details><?php endif; ?>
        <?php if($edit): ?><details class="kanban-add-task"><summary class="button chisimba-button-secondary">Add task</summary><form class="chisimba-form kanban-popover" method="post" action="<?php echo $e($url(array('action'=>'savetask'))); ?>"><?php echo $hidden($board['id']); ?><div class="chisimba-form-field"><label>Task title<input name="title" required/></label></div><div class="chisimba-form-field"><label>Description<textarea name="description"></textarea></label></div><div class="chisimba-form-field"><label>Notes<textarea name="notes"></textarea></label></div><button class="button" type="submit">Save task</button></form></details><?php endif; ?>
        <div class="kanban-columns">
        <?php foreach($labels as $status=>$label): ?>
            <section class="kanban-column" data-status="<?php echo $e($status); ?>"><h3><?php echo $e($label); ?></h3><div class="kanban-task-list">
            <?php foreach($board['tasks'] as $task): if($task['status']!==$status)continue; ?>
                <article class="kanban-task" data-task-id="<?php echo $e($task['id']); ?>" draggable="<?php echo $edit?'true':'false'; ?>">
                    <h4><?php echo $e($task['title']); ?></h4><?php if($task['description']!==''): ?><p><?php echo nl2br($e($task['description'])); ?></p><?php endif; ?>
                    <?php if($task['notes']!==''): ?><details><summary>Notes</summary><p><?php echo nl2br($e($task['notes'])); ?></p></details><?php endif; ?>
                    <?php if($task['subtasks']||$edit): ?><div class="kanban-subtasks"><strong>Subtasks</strong><?php foreach($task['subtasks'] as $sub): ?><label class="kanban-subtask"><input type="checkbox" data-subtask-id="<?php echo $e($sub['id']); ?>" <?php echo !empty($sub['iscompleted'])?'checked':''; ?> <?php echo $edit?'':'disabled'; ?>/><span><?php echo $e($sub['title']); ?></span></label><?php endforeach; ?><?php if($edit): ?><form method="post" action="<?php echo $e($url(array('action'=>'savesubtask'))); ?>"><?php echo $hidden($board['id']); ?><input type="hidden" name="taskid" value="<?php echo $e($task['id']); ?>"/><div class="chisimba-cluster"><input name="title" aria-label="New subtask" required/><button class="button chisimba-button-secondary" type="submit">Add</button></div></form><?php endif; ?></div><?php endif; ?>
                    <?php if($edit): ?><div class="chisimba-cluster kanban-task__actions"><details><summary class="chisimba-icon-button" title="Edit task" aria-label="Edit task"><?php echo $icons->render('pencil',array('decorative'=>true)); ?></summary><form class="chisimba-form kanban-popover" method="post" action="<?php echo $e($url(array('action'=>'savetask'))); ?>"><?php echo $hidden($board['id']); ?><input type="hidden" name="taskid" value="<?php echo $e($task['id']); ?>"/><div class="chisimba-form-field"><label>Title<input name="title" value="<?php echo $e($task['title']); ?>" required/></label></div><div class="chisimba-form-field"><label>Description<textarea name="description"><?php echo $e($task['description']); ?></textarea></label></div><div class="chisimba-form-field"><label>Notes<textarea name="notes"><?php echo $e($task['notes']); ?></textarea></label></div><button class="button" type="submit">Save</button></form></details><form method="post" action="<?php echo $e($url(array('action'=>'deletetask'))); ?>" data-confirm="Delete this task?"><?php echo $hidden($board['id']); ?><input type="hidden" name="taskid" value="<?php echo $e($task['id']); ?>"/><button class="chisimba-icon-button chisimba-button-danger" title="Delete task" aria-label="Delete task" type="submit"><?php echo $icons->render('trash-2',array('decorative'=>true)); ?></button></form></div><?php endif; ?>
                </article>
            <?php endforeach; ?>
            </div></section>
        <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
</main>
