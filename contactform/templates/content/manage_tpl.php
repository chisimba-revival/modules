<?php require __DIR__.'/common.php'; ?>
<main class="chisimba-workspace chisimba-stack">
<header class="chisimba-cluster"><h1><?= $e($t('manage')) ?></h1><a class="button" href="<?= $e($url(['action'=>'view'])) ?>"><?= $icon('eye') ?> <?= $e($t('title')) ?></a><?= $this->getObject('contextualhelp','help')->show('contactform','managing',true) ?></header>
<p><?= $e($t('private_notice')) ?></p>
<?php if($contactChanged): ?><p class="chisimba-form-notice" role="status"><?= $e($t('changed')) ?></p><?php endif; ?>
<nav class="chisimba-cluster" aria-label="<?= $e($t('folders')) ?>"><?php foreach(['inbox'=>'inbox','spam'=>'shield-alert','trash'=>'trash-2'] as $folder=>$glyph): ?><a class="button chisimba-button-secondary" <?= $folder===$contactFolder?'aria-current="page"':'' ?> href="<?= $e($url(['action'=>'manage','folder'=>$folder])) ?>"><?= $icon($glyph) ?> <?= $e($t($folder)) ?></a><?php endforeach; ?></nav>
<?php if(!$contactRows): ?><p><?= $e($t('empty')) ?></p><?php else: ?>
<?php if($contactFolder==='inbox'): ?><form method="post" action="<?= $e($url(['action'=>'review'])) ?>" class="chisimba-cluster"><input type="hidden" name="csrf_token" value="<?= $e($contactManageToken) ?>"><input type="hidden" name="page" value="<?= $e($contactPage) ?>"><button class="button chisimba-button-secondary" type="submit"><?= $icon('scan-search') ?> <?= $e($t('review_page')) ?></button><span><?= $e($t('review_hint')) ?></span></form><?php endif; ?>
<form method="post" action="<?= $e($url(['action'=>'moderate'])) ?>" class="chisimba-stack">
<input type="hidden" name="csrf_token" value="<?= $e($contactManageToken) ?>"><input type="hidden" name="folder" value="<?= $e($contactFolder) ?>">
<div class="chisimba-cluster">
<?php foreach(['spam'=>'shield-alert','trash'=>'trash-2','restore'=>'rotate-ccw','block'=>'ban','unblock'=>'shield-check'] as $op=>$glyph): ?>
<?php if(($op==='spam'&&$contactFolder==='spam')||($op==='trash'&&$contactFolder==='trash')||($op==='restore'&&$contactFolder==='inbox'))continue; ?>
<button type="submit" class="button chisimba-button-secondary" name="operation" value="<?= $op ?>"><?= $icon($glyph) ?> <?= $e($t('action_'.$op)) ?></button><?php endforeach; ?></div>
<p><?= $e($t('selection_hint')) ?></p>
<?php foreach($contactRows as $row): ?><article class="chisimba-form-card chisimba-form-card--wide chisimba-flow chisimba-wrap-anywhere">
<div class="chisimba-cluster"><input type="checkbox" name="ids[]" value="<?= $e($row['id']) ?>" id="select-<?= $e($row['id']) ?>"><h2><label for="select-<?= $e($row['id']) ?>"><?= $e($row['subject']) ?></label></h2></div>
<p><?= $e($row['name']) ?> · <a href="mailto:<?= $e($row['email']) ?>"><?= $e($row['email']) ?></a></p>
<p><?= $e($row['datecreated']) ?> UTC · <?= $e($t('state_'.$row['status'])) ?></p>
<?php if(!empty($row['reason'])&&$row['reason']!=='manual'): ?><p class="chisimba-form-notice"><?= $e($t('reason_'.$row['reason'])) ?></p><?php endif; ?>
<details><summary><?= $e($t('read_message')) ?></summary><div class="chisimba-richtext"><?= nl2br($e($row['message'])) ?></div><p><?= $e($t('reference')) ?>: <?= $e($row['id']) ?></p></details>
</article><?php endforeach; ?></form><?php endif; ?>
<nav class="chisimba-cluster"><?php if($contactPage>1): ?><a class="button" href="<?= $e($url(['action'=>'manage','folder'=>$contactFolder,'page'=>$contactPage-1])) ?>"><?= $icon('arrow-left') ?> <?= $e($t('previous')) ?></a><?php endif; ?><?php if($contactMore): ?><a class="button" href="<?= $e($url(['action'=>'manage','folder'=>$contactFolder,'page'=>$contactPage+1])) ?>"><?= $icon('arrow-right') ?> <?= $e($t('next')) ?></a><?php endif; ?></nav>
</main>
