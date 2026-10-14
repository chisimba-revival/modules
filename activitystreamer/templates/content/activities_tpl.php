<?php
/** Native activity report. @author Derek Keats */
$view=$this->getObject('activityview','activitystreamer');
$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$t=fn($key)=>$e($view->text($key));
if($denied){echo '<p class="chisimba-notice" role="alert">'.$t('denied').'</p>';return;}
$browser=$this->getObject('activitybrowser','activitystreamer');
$url=fn($params)=>$e($this->uri($params,'activitystreamer'));
echo '<section class="chisimba-form-card chisimba-form-card--wide"><h1>'.$t($data['mode']).'</h1><p>'.$t('intro').'</p><div class="chisimba-form-actions chisimba-form-actions--equal">';
foreach(['site','context','pages'] as $mode) if($browser->mayView($mode)) echo '<a class="button chisimba-button-secondary"'.($data['mode']===$mode?' aria-current="page"':'').' href="'.$url(['mode'=>$mode]).'">'.$t($mode).'</a>';
echo $this->getObject('contextualhelp','help')->show('activitystreamer','browse',true).'</div>';
echo '<form method="get" class="chisimba-form-grid" action="'.$url([]).'"><input type="hidden" name="module" value="activitystreamer"><input type="hidden" name="mode" value="'.$e($data['mode']).'">';
echo '<div class="chisimba-form-field"><label for="activity-search">'.$t($data['mode']==='pages'?'searchtitles':'search').'</label><input type="text" id="activity-search" name="q" maxlength="100" value="'.$e($data['q']).'"></div>';
echo '<div class="chisimba-form-field"><label for="activity-sort">'.$t('sort').'</label><select id="activity-sort" name="sort">';
foreach(['recent','oldest','title'] as $sort)echo '<option value="'.$sort.'"'.($sort===$data['sort']?' selected':'').'>'.$t($sort).'</option>';
echo '</select></div><div class="chisimba-form-actions"><button type="submit">'.$t('filter').'</button><a class="button chisimba-button-secondary" href="'.$url(['mode'=>$data['mode']]).'">'.$t('reset').'</a></div></form>';
echo '<p>'.$t('count').': '.$e($data['total']).'</p>'.$view->table($data);
echo '<nav class="chisimba-form-actions" aria-label="'.$t('pagination').'">';
$params=['mode'=>$data['mode'],'q'=>$data['q'],'sort'=>$data['sort']];
if($data['page']>1)echo '<a class="button chisimba-button-secondary" href="'.$url($params+['page'=>$data['page']-1]).'">'.$t('previous').'</a>';
echo '<span>'.$t('page').' '.$e($data['page']).'</span>';
if($data['page']*$data['size']<$data['total'])echo '<a class="button chisimba-button-secondary" href="'.$url($params+['page'=>$data['page']+1]).'">'.$t('next').'</a>';
echo '</nav></section>';
