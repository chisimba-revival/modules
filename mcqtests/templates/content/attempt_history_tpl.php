<?php
$this->setLayoutTemplate('mcqtests_layout_tpl.php');
$objLink = $this->loadClass('link', 'htmlelements');
$objTable = new htmltable(); $objTable->cellpadding=5; $objTable->cellspacing=2; $objTable->width='99%';
$attempt=$this->objLanguage->languageText('mod_mcqtests_attempt','mcqtests');
$mark=$this->objLanguage->languageText('mod_mcqtests_mark','mcqtests');
$taken=$this->objLanguage->languageText('mod_mcqtests_datetaken','mcqtests');
$view=$this->objLanguage->languageText('word_view');
$this->setVar('heading',$this->objLanguage->languageText('mod_mcqtests_attempthistory','mcqtests'));
$objTable->addHeader(array($attempt,$mark.' (%)',$taken,''),'heading');
$totalmark=$this->dbQuestions->sumTotalmark($test['id']); $count=count($data); $i=0;
foreach($data as $line){ $i++; $pct=($totalmark!=0 && intval($line['mark'])!=-1)?round($line['mark']/$totalmark*100,2).'%' : $this->objLanguage->languageText('mod_mcqtests_notcompleted','mcqtests'); $date=!empty($line['endtime'])?$this->formatDate($line['endtime']):$this->formatDate($line['starttime']); $link=new link($this->uri(array('action'=>'showtest','id'=>$test['id'],'studentId'=>$studentId,'resultId'=>$line['id']))); $link->link=$view; $objTable->addRow(array($attempt.' '.$i.' / '.$count,$pct,$date,$link->show()),($i%2)?'odd':'even'); }
echo '<p><strong>'.htmlspecialchars($test['name'],ENT_QUOTES,'UTF-8').'</strong><br />'.htmlspecialchars($studentName,ENT_QUOTES,'UTF-8').'</p>'.$objTable->show();
?>
