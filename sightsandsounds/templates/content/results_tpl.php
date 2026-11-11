<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
$e=fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');$t=fn($k)=>$this->text($k);
$url=fn($p)=>html_entity_decode($this->uri($p,'sightsandsounds'),ENT_QUOTES,'UTF-8');
$rulesService=$this->getObject('assignmentrules','sightsandsounds');$rules=$rulesService->forActivity($ssScope,$ssActivity['id']);
$members=$this->getObject('usercontext','context');$people=array_merge($members->getContextStudents($ssScope),$members->getContextLecturers($ssScope),$members->getContextGuests($ssScope));
$names=[];foreach($people as $person)$names[(string)($person['userid']??$person['userId'])]=trim($person['firstname'].' '.$person['surname']);
$draft=$this->getVar('ssGrantDraft',[]);
?>
<h1><?= $e($ssActivity['title']) ?> — <?= $e($t('results')) ?></h1>
<div class="chisimba-form-actions"><a class="button" href="<?= $e($url(['action'=>'activity','id'=>$ssActivity['id']])) ?>"><?= $e($t('open_activity')) ?></a><?= $this->getObject('contextualhelp','help')->show('sightsandsounds','authoring') ?></div>
<?php if($this->getVar('ssError')): ?><p role="alert"><?= $e($this->getVar('ssError')) ?></p><?php endif ?>
<?php if($this->getParam('saved')==='1'): ?><p role="status"><?= $e($t('saved')) ?></p><?php endif ?>
<p><?= $e($t('permissions_note')) ?></p>
<table><thead><tr><?php foreach(['member','started','received','progress'] as $key): ?><th scope="col"><?= $e($t($key)) ?></th><?php endforeach ?></tr></thead><tbody>
<?php foreach($rulesService->results($ssScope,$ssActivity['id']) as $row): ?><tr><td><?= $e($names[$row['userid']]??$row['userid']) ?></td><td><?= $e($rulesService->time()->formatDateTime($row['started'])) ?></td><td><?= $row['received']?$e($rulesService->time()->formatDateTime($row['received'])):'—' ?> <?= $row['received']&&$rulesService->late($ssScope,$ssActivity['id'],$row['received'])?$e($t('late')):'' ?></td><td><?= $row['received']?(int)$row['correct'].' / '.(int)$row['total']:$e($t('in_progress')) ?></td></tr><?php endforeach ?>
</tbody></table>
<?php if($rules['mode']==='formative'): ?>
<form method="post" action="<?= $e($url(['action'=>'grant'])) ?>" class="chisimba-form-card">
<h2><?= $e($t('grant')) ?></h2><?= $this->access->tokenField() ?><input type="hidden" name="id" value="<?= $e($ssActivity['id']) ?>"><input type="hidden" name="requestid" value="<?= $e($draft['requestid']??bin2hex(random_bytes(16))) ?>">
<div class="chisimba-form-field"><label for="ss-member"><?= $e($t('member')) ?></label><select id="ss-member" name="userid" required><option value="">—</option><?php foreach($names as $uid=>$name): ?><option value="<?= $e($uid) ?>" <?= ($draft['userid']??'')===$uid?'selected':'' ?>><?= $e($name) ?></option><?php endforeach ?></select></div>
<div class="chisimba-form-field"><label for="ss-reason"><?= $e($t('reason')) ?></label><textarea id="ss-reason" name="reason" maxlength="1000" required><?= $e($draft['reason']??'') ?></textarea></div><button type="submit"><?= $e($t('grant')) ?></button></form>
<h2><?= $e($t('grant_history')) ?></h2><ul><?php foreach($rulesService->grants($ssScope,$ssActivity['id']) as $grant): ?><li><?= $e($names[$grant['userid']]??$grant['userid']) ?> — <?= $e($grant['reason']) ?> · <?= $e($names[$grant['actor']]??$grant['actor']) ?> · <?= $e($rulesService->time()->formatDateTime($grant['createdat'])) ?></li><?php endforeach ?></ul>
<?php endif ?>
