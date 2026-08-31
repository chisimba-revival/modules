<?php
//var_dump($worksheets);


$this->loadClass('htmlheading', 'htmlelements');
$this->loadClass('link', 'htmlelements');
$this->loadClass('confirm', 'utilities');


$iconBase = $this->getResourceUri('icons/lucide/', 'ui');
$addLabel = $this->objLanguage->languageText('mod_worksheet_createnewworksheet', 'worksheet');
$viewLabel = $this->objLanguage->languageText('word_view', 'system');
$editLabel = $this->objLanguage->languageText('mod_worksheet_editworksheet', 'worksheet', 'Edit worksheet information');
$addLink = new link($this->uri(array('action'=>'add')));
$addLink->title = $addLabel;
$addLink->link = '<span class="worksheet-heading-action"><img src="'.$iconBase.'circle-plus.svg" width="20" height="20" alt="" aria-hidden="true" /></span>';


$header = new htmlheading();
$header->type = 1;
$header->str = $this->objLanguage->languageText('mod_worksheet_name', 'worksheet'); //$this->objContext->getTitle().': '.

if ($this->isValid('add')) {
    $header->str .= ' '.$addLink->show();
}

echo $header->show();

if ((is_countable($worksheets) ? count($worksheets) : 0) == 0) {
    echo '<div class="noRecordsMessage">No Worksheets at present</div>';
} else {
    $table = $this->newObject('htmltable', 'htmlelements');
    $table->cssClass = 'worksheet-list';


    if ($this->isValid('worksheetinfo')) {
        $table->startHeaderRow();
            $table->addHeaderCell($this->objLanguage->languageText('mod_worksheet_worksheetname', 'worksheet', 'Worksheet Name'));
            $table->addHeaderCell($this->objLanguage->languageText('mod_worksheet_questions', 'worksheet', 'Questions'));
            $table->addHeaderCell($this->objLanguage->languageText('mod_worksheet_activitystatus', 'worksheet', 'Activity Status'));
            $table->addHeaderCell($this->objLanguage->languageText('mod_worksheet_totalmark', 'worksheet', 'Total Mark'));
            $table->addHeaderCell($this->objLanguage->languageText('mod_worksheet_closingdate', 'worksheet', 'Closing Date'));
            $table->addHeaderCell("&nbsp;");
        $table->endHeaderRow();

        foreach ($worksheets as $worksheet)
        {
            $table->startRow();
                $link = new link ($this->uri(array('action'=>'worksheetinfo', 'id'=>$worksheet['id'])));
                $link->title = $this->objLanguage->languageText('mod_worksheet_openworksheet', 'worksheet', 'Open worksheet overview');
                $link->link = $worksheet['name'];
                $table->addCell($link->show());
                $table->addCell($worksheet['questions']);
                $table->addCell($this->objWorksheet->getStatusText($worksheet['activity_status']));
                $table->addCell($worksheet['total_mark']);
                $table->addCell($worksheet['closing_date']);

                $viewLink = new link($this->uri(array('action'=>'preview', 'id'=>$worksheet['id'])));
                $viewLink->title = $viewLabel;
                $viewLink->link = '<img src="'.$iconBase.'eye.svg" width="18" height="18" alt="" aria-hidden="true" />';

                $editLink = new link($this->uri(array('action'=>'edit', 'id'=>$worksheet['id'])));
                $editLink->title = $editLabel;
                $editLink->link = '<img src="'.$iconBase.'pencil.svg" width="18" height="18" alt="" aria-hidden="true" />';

                $deleteConfirm = new confirm();
                $deleteConfirm->setConfirm(
                    '<img src="'.$iconBase.'trash-2.svg" width="18" height="18" alt="" aria-hidden="true" />',
                    $this->uri(array('action'=>'deleteworksheet', 'id'=>$worksheet['id'])),
                    $this->objLanguage->languageText('mod_worksheet_confirmdeleteworksheet', 'worksheet')
                );

                $table->addCell('<span class="worksheet-action-group"><span class="worksheet-icon-action">'.$viewLink->show().'</span><span class="worksheet-icon-action">'.$editLink->show().'</span><span class="worksheet-icon-action">'.$deleteConfirm->show().'</span></span>');

				$viewLink = null;
                $editLink = null;
                $deleteIcon = null;
            $table->endRow();
        }
    } else {
        $table->startHeaderRow();
            $table->addHeaderCell($this->objLanguage->languageText('mod_worksheet_worksheetname', 'worksheet', 'Worksheet Name'));
            $table->addHeaderCell($this->objLanguage->languageText('mod_worksheet_questions', 'worksheet', 'Questions'));
            $table->addHeaderCell($this->objLanguage->languageText('mod_worksheet_activitystatus', 'worksheet', 'Activity Status'));
            $table->addHeaderCell($this->objLanguage->languageText('mod_worksheet_totalmark', 'worksheet', 'Total Mark'));
            $table->addHeaderCell($this->objLanguage->languageText('mod_worksheet_closingdate', 'worksheet', 'Closing Date'));
        $table->endHeaderRow();

        $counter = 0;
        $studentViewStatus = array('open', 'closed', 'marked');

        foreach ($worksheets as $worksheet)
        {
            if (in_array($worksheet['activity_status'], $studentViewStatus)) {
                $counter++;
                $table->startRow();
                    switch($worksheet['activity_status'])
                    {
                        case 'marked':
                            $link = new link ($this->uri(array('action'=>'viewworksheet', 'id'=>$worksheet['id'])));
                            $link->link = $worksheet['name'];
                            $link = $link->show();
                            break;
                        case 'open':

                            // Fix automatic closure
                            /*if (strtotime(date('Y-m-d  H:i:s')) > strtotime($worksheet['closing_date'])) {
                                $worksheet['activity_status'] = 'closed';
                                $link = $worksheet['name'];
                            } else {*/
                                $link = new link ($this->uri(array('action'=>'viewworksheet', 'id'=>$worksheet['id'])));
                                $link->link = $worksheet['name'];
                                $link = $link->show();
                            //}

                            break;
                        default:
                            $link = $worksheet['name'];
                            break;
                    }

                    $table->addCell($link);
                    $table->addCell($worksheet['questions']);
                    $table->addCell($this->objWorksheet->getStatusText($worksheet['activity_status']));
                        $table->addCell($worksheet['total_mark']);
                    $table->addCell($worksheet['closing_date']);
                $table->endRow();
            }
        }

        if ($counter == 0) {

        }
    }

    echo $table->show();
}

?>
