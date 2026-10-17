<?php
//Sending display to 1 column layout
ob_start();

$this->loadClass('form', 'htmlelements');
$this->loadClass('button', 'htmlelements');
$this->loadClass('hiddeninput', 'htmlelements');

$objIcon = $this->newObject('geticon', 'htmlelements');

$form = new form('saveattachment', $this->uri(array('action'=>'saveattachment')));

// Todo Implement WorkGroup
// Todo Implement Context
$objSelectFile = $this->newObject('selectfile', 'filemanager');
$objSelectFile->name = 'attachment';
$form->addToForm($objSelectFile->show());

$button = new button('save', 'Attach File');
$button->cssClass = 'save';
$button->setToSubmit();
$form->addToForm(' &nbsp; &nbsp; '.$button->show());

$hiddeninput = new hiddeninput('id', $id);
$form->addToForm($hiddeninput->show());
$form->addToForm('<input type="hidden" name="csrf_token" value="'.htmlspecialchars($attachmentToken,ENT_QUOTES,'UTF-8').'">');

echo $form->show();

if ((is_countable($files) ? count($files) : 0) > 0) {

    echo '<ul>';
    
    foreach ($files AS $file)
    {
        $e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
        echo '<li>'.$e($file['filename']).'<form method="post" action="'.$e($this->uri(array('action'=>'deleteattachment'))).'"><input type="hidden" name="attachmentwindow" value="'.$e($id).'"><input type="hidden" name="id" value="'.$e($file['id']).'"><input type="hidden" name="csrf_token" value="'.$e($attachmentToken).'"><button class="button chisimba-button-secondary" type="submit">'.$e($this->objLanguage->languageText('word_remove','system')).'</button></form></li>';

    }
    
    echo '</ul>';
} 

$display = ob_get_contents();
ob_end_clean();

$this->setVar('middleColumn', $display);

?>