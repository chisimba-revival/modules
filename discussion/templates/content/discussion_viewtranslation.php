<?php
//Sending display to 1 column layout


$this->loadClass('htmlheading', 'htmlelements');
$this->loadClass('link', 'htmlelements');

$header = new htmlheading();
$header->type=1;

$languageCodes = $this->newObject('languagecode','language');

$link = new link($this->uri(array('action'=>'discussion', 'id'=>$post['discussion_id'])));
$link->link = htmlspecialchars($discussion['discussion_name'],ENT_QUOTES,'UTF-8');
$headerString = $link->show().' &gt; '.htmlspecialchars(stripslashes($post['post_title']),ENT_QUOTES,'UTF-8');
$headerString .= ' - <em>'.$languageCodes->getLanguage($post['language']).'</em>';

$header->str=$headerString;

echo $header->show();

if ($this->getParam('message') == 'translationsaved') {
    $timeoutMessage = $this->getObject('timeoutmessage', 'htmlelements');
    $timeoutMessage->setMessage($this->objLanguage->languageText('mod_discussion_translationsaved', 'discussion'));
    $timeoutMessage->setTimeout(10000);
    echo ('<p>'.$timeoutMessage->show().'</p>');
}

echo $postDisplay;

$link = new link ($this->uri(array('action'=>'viewtopic', 'id'=>$post['topic_id'], 'post'=>$post['post_id'])));
$link->link = 'Topic: '.htmlspecialchars(stripslashes($post['post_title']),ENT_QUOTES,'UTF-8');

echo '<p align="center">'.$link->show().'</p>';


?>
