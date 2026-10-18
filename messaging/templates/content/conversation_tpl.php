<?php
/** Native shared-skin conversation and one-way announcement views. @author Derek Keats */
$v=$this->getObject('conversationview');$t=fn($key)=>$v->text($key);$e=fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
extract($conversation,EXTR_OVERWRITE);$icon=$this->getObject('iconservice','ui');
$url=fn($params=[])=>$this->uri($params+['action'=>'view'],'messaging');
$hidden=function($name,$value)use($e){echo '<input type="hidden" name="'.$e($name).'" value="'.$e($value).'">';};
?>
<section class="chisimba-form-card chisimba-form-card--wide">
<h1><?php echo $e($t('title')); ?></h1>
<p><?php echo $e($t('intro')); ?></p>
<?php if($contextTitle!==''): ?><h2><?php echo $e($contextTitle); ?></h2><?php endif; ?>
<nav class="chisimba-form-actions chisimba-form-actions--equal" aria-label="<?php echo $e($t('channels')); ?>">
<?php foreach(['discussion'=>'message-circle','announcements'=>'megaphone','site'=>'bell']as$name=>$symbol): ?>
<a class="button" <?php if($tab===$name)echo 'aria-current="page"'; ?> href="<?php echo $e($url(['tab'=>$name])); ?>"><?php echo $icon->render($symbol,['decorative'=>true]).'<span>'.$e($t($name)).'</span>'; ?></a>
<?php endforeach;echo $this->getObject('contextualhelp','help')->show('messaging','conversations'); ?>
</nav>
<?php if($error): ?><p role="alert"><?php echo $e($t(in_array($error,['csrf','method','conflict','invalid','failed','scope_changed'],true)?$error:'unavailable')); ?></p><?php endif; ?>
<?php if($error&&!$allowed&&$body!==''): ?><label for="unsent-message"><?php echo $e($t('recover')); ?></label><textarea id="unsent-message" readonly rows="5"><?php echo $e($body); ?></textarea><?php endif; ?>
<?php if($tab!=='site'&&!$allowed): ?><p><?php echo $e($t('unavailable')); ?></p>
<?php elseif($tab==='discussion'): ?>
<h2><?php echo $e($t('discussion')); ?></h2>
<?php if(!$channel): ?><p><?php echo $e($t('not_started')); ?></p>
<?php if($this->getObject('conversationpolicy')->mayModerate($context)): ?>
<form method="post" action="<?php echo $e($url(['action'=>'create'])); ?>"><?php $hidden('csrf_token',$conversationToken);$hidden('conversation_context',$context); ?><button type="submit" class="button"><?php echo $icon->render('plus',['decorative'=>true]).'<span>'.$e($t('start')).'</span>'; ?></button></form>

<?php endif;else: ?>

<p data-chat-unread><?php echo $e($t('unread')).': '.(int)$data['unread']; ?></p>
<div class="chisimba-form-actions">
<a class="button" href="<?php echo $e($url()); ?>"><?php echo $icon->render('refresh-cw',['decorative'=>true]).'<span>'.$e($t('refresh')).'</span>'; ?></a>
<?php if($data['older']): ?><a class="button" href="<?php echo $e($url(['before'=>$data['older']])); ?>"><?php echo $icon->render('arrow-left',['decorative'=>true]).'<span>'.$e($t('older')).'</span>'; ?></a><?php endif; ?>
</div>
<?php if(!$data['messages']): ?><p><?php echo $e($t('empty')); ?></p><?php endif; ?>
<div class="chisimba-chat" aria-label="<?php echo $e($t('discussion')); ?>">
<?php foreach($data['messages']as$post): ?><article class="chisimba-chat__message<?php if((string)$post['sender_id']===$this->getObject('conversationpolicy')->actor())echo ' chisimba-chat__message--own'; ?>"><header class="chisimba-chat__meta"><strong><?php echo $e($this->getObject('user','security')->fullName($post['sender_id'])); ?></strong><time datetime="<?php echo $e(str_replace(' ','T',$post['created_at']).'Z'); ?>"><?php echo $e($post['created_at']).' UTC'; ?></time></header><p><?php echo nl2br($e($post['body']),false); ?></p></article><?php endforeach; ?>
</div>
<?php if($data['messages']): $last=end($data['messages']); ?>
<form method="post" action="<?php echo $e($url(['action'=>'read'])); ?>"><?php $hidden('csrf_token',$conversationToken);$hidden('conversation_context',$context);$hidden('channel',$channel['id']);$hidden('sequence',$last['sequence_no']); ?><button class="button" type="submit"><?php echo $icon->render('check',['decorative'=>true]).'<span>'.$e($t('read')).'</span>'; ?></button></form>
<?php endif; ?>

<p data-chat-status role="status" aria-live="polite" <?php foreach(['connected','offline','sending','sent','retry']as$status): ?>data-<?php echo $status; ?>="<?php echo $e($t('chat_'.$status)); ?>" <?php endforeach; ?>></p>
<form data-conversation-compose method="post" action="<?php echo $e($url(['action'=>'send'])); ?>">
<?php $hidden('csrf_token',$conversationToken);$hidden('conversation_context',$context);$hidden('channel',$channel['id']);$hidden('submission_id',$key); ?>
<div class="chisimba-form-field"><label for="conversation-body"><?php echo $e($t('message')); ?></label><textarea id="conversation-body" name="body" rows="3" maxlength="20000" required aria-describedby="conversation-send-help"><?php echo $e($body); ?></textarea></div>
<p id="conversation-send-help"><?php echo $e($t('send_help')); ?></p>
<button class="button" type="submit"><?php echo $icon->render('send',['decorative'=>true]).'<span>'.$e($t('send')).'</span>'; ?></button>
</form>
<?php endif;else: ?>
<h2><?php echo $e($t($tab)); ?></h2><p><?php echo $e($t('oneway')); ?></p>
<?php if(!$announcements): ?><p><?php echo $e($t('no_announcements')); ?></p><?php endif; ?>
<?php foreach($announcements as $item): ?><article class="chisimba-flow"><h3><?php echo $e($item['title']); ?></h3><p><?php echo nl2br($e(html_entity_decode(strip_tags($item['message']),ENT_QUOTES,'UTF-8')),false); ?></p></article><?php endforeach; ?>
<a class="button" href="<?php echo $e($this->uri([],'announcements')); ?>"><?php echo $icon->render('megaphone',['decorative'=>true]).'<span>'.$e($t('manage_announcements')).'</span>'; ?></a>
<?php endif; ?>
</section>
