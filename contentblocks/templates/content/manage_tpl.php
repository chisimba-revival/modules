<?php
$e = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$l = $contentblocksLabels;
$scopeUrl = function ($scope) {
    return $this->uri(array('action' => 'manage', 'scope' => $scope), 'contentblocks');
};
$saveUrl = $this->uri(array('action' => 'save', 'scope' => $contentblocksScope), 'contentblocks');
$edit = is_array($contentblocksEdit) ? $contentblocksEdit : array();
?>
<main class="contentblocks-admin">
  <h1><?= $e($l['title']) ?></h1>
  <p><?= $e($l['intro']) ?></p>
  <?php if ($contentblocksFlash !== ''): ?><p class="contentblocks-notice"><?= $e($contentblocksFlash) ?></p><?php endif; ?>
  <nav class="contentblocks-tabs">
    <?php if ($this->getObject('user', 'security')->isAdmin()): ?><a href="<?= $e($scopeUrl('site')) ?>"><?= $e($l['siteblocks']) ?></a><?php endif; ?>
    <?php if ($contentblocksContextCode !== ''): ?><a href="<?= $e($scopeUrl('context')) ?>"><?= $e($l['contextblocks']) ?></a><?php endif; ?>
  </nav>
  <?php if (!empty($contentblocksDenied)): ?>
    <p class="contentblocks-error"><?= $e($l['forbidden']) ?></p>
  <?php else: ?>
    <section class="contentblocks-list">
      <h2><?= $e($contentblocksScope === 'context' ? $l['contextblocks'] : $l['siteblocks']) ?></h2>
      <?php if (!$contentblocksRows): ?><p><?= $e($l['empty']) ?></p><?php endif; ?>
      <?php foreach ($contentblocksRows as $row): ?>
        <article class="contentblocks-row">
          <div><strong><?= $e($row['title']) ?></strong><br><small><?= $e($l['key']) ?>: <code><?= $e($row['blockkey']) ?></code></small></div>
          <div class="contentblocks-actions">
            <a href="<?= $e($this->uri(array('action'=>'manage','scope'=>$contentblocksScope,'id'=>$row['id']), 'contentblocks')) ?>"><?= $e($l['edit']) ?></a>
            <form method="post" action="<?= $e($this->uri(array('action'=>'delete','scope'=>$contentblocksScope), 'contentblocks')) ?>" onsubmit="return confirm(<?= json_encode($l['confirmdelete']) ?>)">
              <input type="hidden" name="csrf_token" value="<?= $e($contentblocksCsrf) ?>">
              <input type="hidden" name="id" value="<?= $e($row['id']) ?>">
              <button type="submit"><?= $e($l['delete']) ?></button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
    <section class="contentblocks-form">
      <h2><?= $e($edit ? $l['edit'] : $l['new']) ?></h2>
      <form method="post" action="<?= $e($saveUrl) ?>">
        <input type="hidden" name="csrf_token" value="<?= $e($contentblocksCsrf) ?>">
        <input type="hidden" name="id" value="<?= $e($edit['id'] ?? '') ?>">
        <div class="contentblocks-grid">
          <label><?= $e($l['type']) ?><select name="blocktype" <?= $edit ? 'disabled' : '' ?>><option value="hero" <?= ($edit['blocktype'] ?? '') === 'hero' ? 'selected' : '' ?>><?= $e($l['hero']) ?></option><option value="information" <?= ($edit['blocktype'] ?? 'information') === 'information' ? 'selected' : '' ?>><?= $e($l['information']) ?></option></select></label>
          <label><?= $e($l['width']) ?><select name="blockwidth" <?= $edit ? 'disabled' : '' ?>><option value="wide" <?= ($edit['blockwidth'] ?? 'wide') === 'wide' ? 'selected' : '' ?>><?= $e($l['wide']) ?></option><option value="normal" <?= ($edit['blockwidth'] ?? '') === 'normal' ? 'selected' : '' ?>><?= $e($l['normal']) ?></option></select></label>
        </div>
        <?php if ($edit): ?><input type="hidden" name="blocktype" value="<?= $e($edit['blocktype']) ?>"><input type="hidden" name="blockwidth" value="<?= $e($edit['blockwidth']) ?>"><?php endif; ?>
        <label><?= $e($l['blocktitle']) ?><input required maxlength="250" name="title" value="<?= $e($edit['title'] ?? '') ?>"></label>
        <label class="contentblocks-check"><input type="checkbox" name="show_title" value="1" <?= ($edit['show_title'] ?? '1') === '1' ? 'checked' : '' ?>> <?= $e($l['showtitle']) ?></label>
        <label><?= $e($l['body']) ?></label>
        <?php $editor = $this->newObject('htmlarea', 'htmlelements'); $editor->name = 'body_html'; $editor->height = '300px'; $editor->width = '100%'; $editor->setContent($edit['body_html'] ?? ''); echo $editor->show(); ?>
        <label><?= $e($l['imageurl']) ?><input name="image_url" value="<?= $e($edit['image_url'] ?? '') ?>"></label>
        <div class="contentblocks-grid"><label><?= $e($l['actionlabel']) ?><input name="action_label" value="<?= $e($edit['action_label'] ?? '') ?>"></label><label><?= $e($l['actionurl']) ?><input name="action_url" value="<?= $e($edit['action_url'] ?? '') ?>"></label></div>
        <div class="contentblocks-actions"><button type="submit"><?= $e($l['save']) ?></button><?php if ($edit): ?><a href="<?= $e($scopeUrl($contentblocksScope)) ?>"><?= $e($l['cancel']) ?></a><?php endif; ?></div>
      </form>
    </section>
  <?php endif; ?>
</main>
