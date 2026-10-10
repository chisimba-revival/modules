<?php require __DIR__.'/common.php'; ?>
<h1><?=$t('unable')?></h1><p><?=$t('recover_hint')?></p>
<?php if(!empty($eventDraft)): ?><details open><summary><?=$t('submitted_values')?></summary><dl><?php foreach($eventDraft as $key=>$value): if(!in_array($key,['name','email','quantity','attendees','title','summary','description','attendee','rating','comment','display_name','code'],true))continue; ?><dt><?=$t($key)?></dt><dd><?=nl2br($esc($value))?></dd><?php endforeach; ?></dl></details><?php endif; ?>
<a href="<?=$url('')?>"><?=$t('heading')?></a>
