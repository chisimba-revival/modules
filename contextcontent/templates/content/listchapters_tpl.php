<?php

$this->loadClass('link', 'htmlelements');
$this->loadClass('htmlheading', 'htmlelements');
$this->loadClass('form', 'htmlelements');
$this->loadClass('button', 'htmlelements');
$this->loadClass('dropdown', 'htmlelements');
$this->loadClass('hiddeninput', 'htmlelements');
$this->loadClass('label', 'htmlelements');

$objModules = $this->getObject('modules', 'modulecatalogue');
$pdfHtmlDoc = $objModules->checkIfRegistered('htmldoc');



// CHISIMBA NATIVE CHAPTER UI
$objIconService = $this->getObject('iconservice', 'ui');
$editLabel = $this->objLanguage->languageText(
    'mod_contextcontent_editchapter',
    'contextcontent',
    'Edit Chapter'
);
$deleteLabel = $this->objLanguage->languageText(
    'mod_contextcontent_deletechapter',
    'contextcontent',
    'Delete Chapter'
);
$addChapterLabel = $this->objLanguage->languageText(
    'mod_contextcontent_addanewchapter',
    'contextcontent',
    'Add a New Chapter'
);
$addPageLabel = $this->objLanguage->languageText(
    'mod_contextcontent_addapagetothischapter',
    'contextcontent',
    'Add a Page to this Chapter'
);
$reorderPagesLabel = $this->objLanguage->languageText(
    'mod_contextcontent_reorderchapterpages',
    'contextcontent',
    'Reorder chapter pages'
);

$editIcon = $objIconService->render('pencil', array('decorative' => TRUE));
$deleteIcon = $objIconService->render('trash-2', array('decorative' => TRUE));
$addIcon = $objIconService->render('plus', array('decorative' => TRUE));
$addPageIcon = $objIconService->render('plus', array('decorative' => TRUE));
$reorderPagesIcon = $objIconService->render('list-ordered', array('decorative' => TRUE));
$chapterIcon = $objIconService->render('book-open', array('decorative' => TRUE));
$contextContentCsrf = isset($contextContentCsrf) ? (string) $contextContentCsrf : '';
$moveUpIcon = $objIconService->render('chevron-left', array('decorative' => TRUE));
$moveDownIcon = $objIconService->render('chevron-right', array('decorative' => TRUE));
$moveUpLabel = $this->objLanguage->languageText(
    'mod_contextcontent_movechapterup',
    'contextcontent',
    'Move Chapter Up'
);
$moveDownLabel = $this->objLanguage->languageText(
    'mod_contextcontent_movechapterdown',
    'contextcontent',
    'Move Chapter Down'
);
$chapterOrderForm = function ($action, $chapterId, $label, $icon) use ($contextContentCsrf) {
    $escape = function ($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    };
    return '<form method="post" class="chisimba-chapter-order-form" action="'
        . $this->uri(array('action' => $action)) . '">'
        . '<input type="hidden" name="csrf_token" value="'
        . $escape($contextContentCsrf) . '" />'
        . '<input type="hidden" name="id" value="' . $escape($chapterId) . '" />'
        . '<button type="submit" class="chisimba-chapter-order-button" aria-label="'
        . $escape($label) . '" title="' . $escape($label) . '">'
        . $icon . '</button></form>';
};

// Retain the legacy icon object only for auxiliary actions not yet migrated.
$objIcon = $this->newObject('geticon', 'htmlelements');
$objIcon->align = 'absmiddle';

$objIcon->setIcon('add_multiple');
$objIcon->alt = $this->objLanguage->languageText('mod_contextcontent_createpagefromfile', 'contextcontent', 'Create page from file');
$objIcon->title = $this->objLanguage->languageText('mod_contextcontent_createpagefromfile', 'contextcontent', 'Create page from file');
$addPageFromFileIcon = $objIcon->show();

if (false) {
    $objIcon->setIcon('scm');
    $objIcon->alt = $this->objLanguage->languageText('mod_scorm_addscormchapter', 'scorm');
    $objIcon->title = $this->objLanguage->languageText('mod_scorm_addscormchapter', 'scorm');
    $addScormIcon = $objIcon->show();
}

$objIcon->setIcon('pdf');
$objIcon->alt = $this->objLanguage->languageText('mod_contextcontent_downloadchapterinpdfformat', 'contextcontent');
$objIcon->title = $this->objLanguage->languageText('mod_contextcontent_downloadchapterinpdfformat', 'contextcontent');
$pdfIcon = $objIcon->show();
//The Activity Streamer Icon
$this->objAltConfig = $this->getObject('altconfig', 'config');
$siteRoot = $this->objAltConfig->getsiteRoot();
$moduleUri = $this->objAltConfig->getModuleURI();
$imgPath = $siteRoot . "/" . $moduleUri . '/contextcontent/resources/img/new.png';
$streamerimg = '<img  class="newcontentimg" src="' . $imgPath . '">';


if ($this->isValid('addchapter')) {
    $link = new link($this->uri(array('action' => 'addchapter')));
    $link->link = $addIcon;
    $link->cssClass = 'chisimba-chapter-action chisimba-chapter-action-add';
    $link->extra = ' aria-label="' . htmlspecialchars($addChapterLabel, ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($addChapterLabel, ENT_QUOTES, 'UTF-8') . '"';

    $addChapter = $link->show();
} else {
    $addChapter = '';
}
if (false) {
    $link = new link($this->uri(array('action' => 'addscorm')));
    $link->link = $addScormIcon;
    $objSysConfig = $this->getObject('dbsysconfig', 'sysconfig');
    $enableScorm = $objSysConfig->getValue('ENABLE_SCORM', 'contextcontent');

    $addScormChapter = $enableScorm == 'true' ? $link->show() : '';
} else {
    $addScormChapter = '';
}
echo '<div class="chisimba-page-title-row"><h1>' . $this->objContext->getTitle() . '</h1>' . '<div class="chisimba-page-title-actions">' . $addChapter . ' ' . $addScormChapter . '</div></div>';

$counter = 1;
$notVisibleCounter = 0;
$addedCounter = 0;

// Form for Quick Jump to a Chapter
/**
$form = new form($this->uri(array('action' => 'viewchapter')));
$form->method = 'GET';

$module = new hiddeninput('module', 'contextcontent');
$form->addToForm($module->show());

$action = new hiddeninput('action', 'viewchapter');
$form->addToForm($action->show());

$label = new label($this->objLanguage->languageText('mod_contextcontent_jumptochapter', 'contextcontent') . ': ', 'input_id');
$form->addToForm($label->show());

$dropdown = new dropdown('id');

// End Form
**/
$chapterList = '<div id="allchapters" data-toggle-storage-key="'
    . htmlspecialchars('chisimba-contextcontent-open-' . (string) $this->contextCode, ENT_QUOTES, 'UTF-8')
    . '">';

$objWashout = $this->getObject('washout', 'utilities');
$todays_date = date('Y-m-d H:i');
$today = strtotime($todays_date);

$stageGateOverviewActive = $this->objChapterStageGates->isGatedProgression($this->contextCode)
    && !$this->objChapterStageGates->isCourseManager($this->contextCode);
$stageGateLockedLabel = $this->objLanguage->languageText(
    'mod_contextcontent_stagegatelockedchapter',
    'contextcontent',
    'Complete the previous stage gate assessment to unlock this [-chapter-].'
);

echo '<style>'
    . '.chisimba-stage-gate-locked-heading{color:#777;opacity:.65;cursor:not-allowed;}'
    . '.chisimba-stage-gate-locked-pages{opacity:.48;}'
    . '.chisimba-stage-gate-locked-pages a{pointer-events:none;cursor:not-allowed;text-decoration:none;color:inherit;}'
    . '.chisimba-stage-gate-locked-page{color:#777;cursor:not-allowed;}'
    . '.chisimba-stage-gate-lock-note{font-size:.9em;color:#666;margin:.35rem 0 .75rem;}'
    . '</style>';

foreach ($chapters as $chapter) {
    $stageGateEntry = $stageGateOverviewActive
        ? $this->objChapterStageGates->entryDecision($this->contextCode, $chapter['chapterid'])
        : array('allowed' => TRUE);
    $stageGateLocked = empty($stageGateEntry['allowed']);
    $showChapter = TRUE;

    if ($chapter['visibility'] == 'N') {
        $showChapter = FALSE;
    }

    if ($this->isValid('viewhiddencontent')) {
        $showChapter = TRUE;
    }


    $releasedate = strtotime($chapter['releasedate']);
    $enddate = strtotime($chapter['enddate']);

    //compate dates here, then decide on visibility
    if (!empty($releasedate) && !empty($enddate)) {
        if (($today <= $releasedate)) {
            $showChapter = FALSE;
            if ($this->isValid('addchapter')) {
                $showChapter = TRUE;
                $chapter['chaptertitle'] = $chapter['chaptertitle'] . '&nbsp;(' . $this->objLanguage->languageText('mod_contextcontent_hidden', 'contextcontent', ' Hidden') . ')';
            }
        }
        if ($enddate < $today) {
            $showChapter = FALSE;
            if ($this->isValid('addchapter')) {
                $showChapter = TRUE;
                $chapter['chaptertitle'] = $chapter['chaptertitle'] . '&nbsp;(' . $this->objLanguage->languageText('mod_contextcontent_hidden', 'contextcontent', ' Hidden') . ')';
            }
        }
    }

    if ($showChapter) {
        $addedCounter++;
        $chapterLabel = $this->objLanguage->languageText(
            'mod_contextcontent_chaptermarker',
            'contextcontent',
            'CHAPTER'
        );
        $chapterMarker = '<span class="chisimba-chapter-marker">'
            . '<span class="chisimba-chapter-marker-icon">' . $chapterIcon . '</span>'
            . '<span class="chisimba-chapter-marker-text">'
            . htmlspecialchars($chapterLabel, ENT_QUOTES, 'UTF-8') . ' ' . $addedCounter
            . '</span></span>';
        if (false) {

            // Get List of Pages in the Chapter
            $chapterPages = $this->objContentOrder->getTree($this->contextCode, $chapter['chapterid'], 'htmllist');

            if (trim($chapterPages) == '<ul class="htmlliststyle"></ul>') {
                $hasPages = FALSE;
                //$dropdown->addOption($chapter['chapterid'], $chapter['chaptertitle'], ' disabled="disabled" title="' . $this->objLanguage->languageText('mod_contextcontent_chapterhasnopages', 'contextcontent') . '"');
                $notVisibleCounter++;
            } else {
                $hasPages = TRUE;
                $dropdown->addOption($chapter['chapterid'], $chapter['chaptertitle']);
            }

            $editLink = new link($this->uri(array('action' => 'editscorm', 'id' => $chapter['chapterid'])));
            $editLink->link = $editIcon;
            $editLink->cssClass = 'chisimba-chapter-action chisimba-chapter-action-edit';
            $editLink->extra = ' aria-label="' . htmlspecialchars($editLabel, ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($editLabel, ENT_QUOTES, 'UTF-8') . '"';

            $deleteLink = new link($this->uri(array('action' => 'deletechapter', 'id' => $chapter['chapterid'])));
            $deleteLink->link = $deleteIcon;
            $deleteLink->cssClass = 'chisimba-chapter-action chisimba-chapter-action-delete';
            $deleteLink->extra = ' aria-label="' . htmlspecialchars($deleteLabel, ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($deleteLabel, ENT_QUOTES, 'UTF-8') . '"';

            $addPageLink = new link($this->uri(array('action' => 'addcontent', 'chapter' => $chapter['chapterid'])));
            $addPageLink->link = $addPageIcon;
            $addPageLink->cssClass = 'chisimba-chapter-action chisimba-chapter-action-add';
            $addPageLink->extra = ' aria-label="' . htmlspecialchars($addPageLabel, ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($addPageLabel, ENT_QUOTES, 'UTF-8') . '"';



            $chapterLink = new link($this->uri(array('action' => 'viewscorm', 'folderId' => $chapter['introduction'], 'chapterid' => $chapter['chapterid']), $module = 'scorm'));
            $chapterLink->link = $chapter['chaptertitle'];
            if ($this->eventsEnabled) {
                $ischapterlogged = $this->objContextActivityStreamer->getRecord($this->userId, $chapter['chapterid']);
            } else {
                $ischapterlogged = FALSE;
                $streamerimg = "";
            }
            $chapterHeading = $chapterLink->show();
            if ($ischapterlogged == FALSE) {
                $chapterHeading = $streamerimg . ' ' . $chapterHeading;
            }
            $content = '<h2 class="chisimba-chapter-title"><span class="chisimba-chapter-heading-text">'
                . $chapterMarker
                . '<span class="chisimba-chapter-name">' . $chapterHeading . '</span>'
                . '</span></span><span class="chisimba-chapter-actions">';

            if ($this->isValid('editchapter')) {
                $content .= ' ' . $editLink->show();
            }

            if ($this->isValid('deletechapter')) {
                $content .= ' ' . $deleteLink->show();
            }

            /*
              if ($this->isValid('addpage')) {
              $content .= ' '.$addPageLink->show();
              }
             */
            $content .= '</span></h2><hr />';
        } else {


            // Get List of Pages in the Chapter
            $chapterPages = $this->objContentOrder->getTree($this->contextCode, $chapter['chapterid'], 'htmllist');

            if ($stageGateLocked) {
                $chapterPages = preg_replace_callback(
                    '#<a\\b[^>]*>(.*?)</a>#is',
                    function ($matches) {
                        return '<span class="chisimba-stage-gate-locked-page" aria-disabled="true">'
                            . $matches[1] . '</span>';
                    },
                    $chapterPages
                );
                $chapterPages = '<div class="chisimba-stage-gate-lock-note" role="note">'
                    . htmlspecialchars($stageGateLockedLabel, ENT_QUOTES, 'UTF-8') . '</div>'
                    . '<div class="chisimba-stage-gate-locked-pages" aria-disabled="true">'
                    . $chapterPages . '</div>';
            }

            if (trim($chapterPages) == '<ul class="htmlliststyle"></ul>') {
                $hasPages = FALSE;
                //$dropdown->addOption($chapter['chapterid'], $chapter['chaptertitle'], ' disabled="disabled" title="' . $this->objLanguage->languageText('mod_contextcontent_chapterhasnopages', 'contextcontent') . '"');
                $notVisibleCounter++;
            } else {
                $hasPages = TRUE;
                //$dropdown->addOption($chapter['chapterid'], $chapter['chaptertitle']);
            }

            $editLink = new link($this->uri(array('action' => 'editchapter', 'id' => $chapter['chapterid'])));
            $editLink->link = $editIcon;
            $editLink->cssClass = 'chisimba-chapter-action chisimba-chapter-action-edit';
            $editLink->extra = ' aria-label="' . htmlspecialchars($editLabel, ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($editLabel, ENT_QUOTES, 'UTF-8') . '"';

            $deleteLink = new link($this->uri(array('action' => 'deletechapter', 'id' => $chapter['chapterid'])));
            $deleteLink->link = $deleteIcon;
            $deleteLink->cssClass = 'chisimba-chapter-action chisimba-chapter-action-delete';
            $deleteLink->extra = ' aria-label="' . htmlspecialchars($deleteLabel, ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($deleteLabel, ENT_QUOTES, 'UTF-8') . '"';

            $addPageLink = new link($this->uri(array('action' => 'addcontent', 'chapter' => $chapter['chapterid'])));
            $addPageLink->link = $addPageIcon;
            $addPageLink->cssClass = 'chisimba-chapter-action chisimba-chapter-action-add';
            $addPageLink->extra = ' aria-label="' . htmlspecialchars($addPageLabel, ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($addPageLabel, ENT_QUOTES, 'UTF-8') . '"';

            $addPageFromFileLink = new link($this->uri(array('action' => 'addpagefromfile', 'chapterid' => $chapter['chapterid'])));
            $addPageFromFileLink->link = $addPageFromFileIcon;

            $chapterLink = new link($this->uri(array('action' => 'viewchapter', 'id' => $chapter['chapterid'])));
            $chapterLink->link = $chapter['chaptertitle'];

            $reorderPagesLink = new link($this->uri(array('action' => 'viewchapter', 'id' => $chapter['chapterid'])));
            $reorderPagesLink->link = $reorderPagesIcon;
            $reorderPagesLink->cssClass = 'chisimba-chapter-action chisimba-chapter-action-reorder';
            $reorderPagesLink->extra = ' aria-label="' . htmlspecialchars($reorderPagesLabel, ENT_QUOTES, 'UTF-8')
                . '" title="' . htmlspecialchars($reorderPagesLabel, ENT_QUOTES, 'UTF-8') . '"';

            if ($this->eventsEnabled) {
                $ischapterlogged = $this->objContextActivityStreamer->getRecord($this->userId, $chapter['chapterid']);
            } else {
                $ischapterlogged = FALSE;
                $streamerimg = "";
            }
            if (trim($chapterPages) == '<ul class="htmlliststyle"></ul>') {
                $chapterHeading = $chapter['chaptertitle'];
            } else {
                $chapterHeading = $chapterLink->show();
            if ($stageGateLocked) {
                $chapterHeading = '<span class="chisimba-stage-gate-locked-heading" aria-disabled="true" title="'
                    . htmlspecialchars($stageGateLockedLabel, ENT_QUOTES, 'UTF-8') . '">'
                    . htmlspecialchars($chapter['chaptertitle'], ENT_QUOTES, 'UTF-8') . '</span>';
            }
            }
            if ($ischapterlogged == FALSE) {
                $chapterHeading = $streamerimg . ' ' . $chapterHeading;
            }
            $content = '<h2 class="chisimba-chapter-title"><span class="chisimba-chapter-heading-text">'
                . $chapterMarker
                . '<span class="chisimba-chapter-name">' . $chapterHeading . '</span>'
                . '</span></span><span class="chisimba-chapter-actions">';

            if ($this->isValid('editchapter')) {
                $content .= ' ' . $editLink->show();
            }

            if ($this->isValid('deletechapter')) {
                $content .= ' ' . $deleteLink->show();
            }

            if ($this->isValid('addpage')) {
                $content .= ' ' . $addPageLink->show();
                $content .= ' ' . $reorderPagesLink->show();
                //$content .= ' '.$addPageFromFileLink->show();
            }


            if ($pdfHtmlDoc && trim($chapterPages) != '<ul class="htmlliststyle"></ul>') {

                $pdfLink = new link($this->uri(array('action' => 'viewprintchapter', 'id' => $chapter['chapterid'])));
                $pdfLink->link = $pdfIcon;

                $content .= ' ' . $pdfLink->show();
            }

            $content .= '</span></h2>';

            //print_r($chapter);

            if ($this->isValid('viewhiddencontent') && $chapter['visibility'] != 'Y') {

                switch ($chapter['visibility']) {
                    case 'I': $notice = $this->objLanguage->code2Txt('mod_contextcontent_studentcanonlyviewintro', 'contextcontent');
                        break;
                    case 'N': $notice = $this->objLanguage->code2Txt('mod_contextcontent_chapternotvisibletostudents', 'contextcontent');
                        break;
                    default: $notice = '';
                        break;
                }
                $content .= '<p class="warning"><strong>' . $this->objLanguage->languageText('mod_contextcontent_note', 'contextcontent') . ': </strong>' . $notice . '</p>';
            }

            $content .= $objWashout->parseText($chapter['introduction']);

            $chapterOptions = array();
            $chapterContents = '';

            if ($chapter['visibility'] == 'I' && !$this->isValid('viewhiddencontent')) {
                $content .= '<p class="warning">' . ucfirst($this->objLanguage->code2Txt('mod_contextcontent_studentscannotaccesscontent', 'contextcontent')) . '.</p>';

                // Empty variable for use later on
                $chapterPages = '';
            } else {

                if (trim($chapterPages) == '<ul class="htmlliststyle"></ul>' && $this->isValid('viewhiddencontent')) {
                    $content .= '<div class="noRecordsMessage">' . $this->objLanguage->languageText('mod_contextcontent_chapternewcontentpages', 'contextcontent') . '</div>';

                    // Empty variable for use later on
                    $chapterPages = '';
                } else if (trim($chapterPages) == '<ul class="htmlliststyle"></ul>') {
                    $content .= '<div class="noRecordsMessage">' . $this->objLanguage->languageText('mod_contextcontent_chapternewcontentpages', 'contextcontent') . '</div>';

                    // Empty variable for use later on
                    $chapterPages = '';
                } else {
                    $tocId = 'toc_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $chapter['chapterid']);
                    $toggleLabel = $this->objLanguage->languageText(
                        'mod_contextcontent_showhidecontents',
                        'contextcontent',
                        'Show/Hide Contents'
                    );
                    $chapterContents = '<button type="button" class="chisimba-chapter-contents-toggle"'
                        . ' aria-expanded="false" aria-controls="' . $tocId . '">'
                        . htmlspecialchars($toggleLabel, ENT_QUOTES, 'UTF-8')
                        . '</button>'
                        . '<div id="' . $tocId . '" class="chisimba-chapter-contents" hidden>'
                        . $chapterPages
                        . '</div>';
                }
            }


            $addPageLink = new link($this->uri(array('action' => 'addcontent', 'chapter' => $chapter['chapterid'])));
            $addPageLink->link = $this->objLanguage->languageText('mod_contextcontent_addapagetothischapter', 'contextcontent');

            if ((is_countable($chapters) ? count($chapters) : 0) > 1
                && $counter > 1 && $this->isValid('movechapterup')) {
                $chapterOptions[] = $chapterOrderForm(
                    'movechapterup',
                    $chapter['contextchapterid'],
                    $moveUpLabel,
                    $moveUpIcon
                );
            }

            if ($counter < (is_countable($chapters) ? count($chapters) : 0)
                && $this->isValid('movechapterdown')) {
                $chapterOptions[] = $chapterOrderForm(
                    'movechapterdown',
                    $chapter['contextchapterid'],
                    $moveDownLabel,
                    $moveDownIcon
                );
            }

            if ($chapterContents !== '') {
                $content .= $chapterContents;
            }

            if ((is_countable($chapterOptions) ? count($chapterOptions) : 0) > 0) {
                $content .= '<div class="chisimba-chapter-order-actions">';
                foreach ($chapterOptions as $option) {
                    $content .= $option;
                }
                $content .= '</div>';
            }
        }
        $chapterList .= '<section class="chapterlisting">' . $content . '</section>';
    }

    $counter++;
}

$chapterList .= '</div>';

/**
if ((is_countable($chapters) ? count($chapters) : 0) > 1) {
    $form->addToForm($dropdown->show());

    $button = new button('', 'Go');
    $button->setToSubmit();

    if ($notVisibleCounter == $addedCounter) {
        $button->extra = ' disabled="disabled" ';
    }

    $form->addToForm(' ' . $button->show());

    echo $form->show();
}
**/

echo $chapterList;
?>
<script>
(function () {
    'use strict';

    var chapterList = document.getElementById('allchapters');
    if (!chapterList || chapterList.dataset.contentsToggleReady === 'true') {
        return;
    }

    chapterList.dataset.contentsToggleReady = 'true';
    var storageKey = chapterList.dataset.toggleStorageKey;

    function setExpanded(button, expanded) {
        var contents = document.getElementById(button.getAttribute('aria-controls'));
        if (!contents) {
            return;
        }
        button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        contents.hidden = !expanded;
    }

    function saveExpandedChapters() {
        if (!storageKey) {
            return;
        }
        var expanded = Array.prototype.map.call(
            chapterList.querySelectorAll('.chisimba-chapter-contents-toggle[aria-expanded="true"]'),
            function (button) { return button.getAttribute('aria-controls'); }
        );
        try {
            window.localStorage.setItem(storageKey, JSON.stringify(expanded));
        } catch (ignore) {}
    }

    if (storageKey) {
        try {
            var stored = JSON.parse(window.localStorage.getItem(storageKey) || '[]');
            if (Array.isArray(stored)) {
                stored.forEach(function (contentsId) {
                    var button = chapterList.querySelector(
                        '.chisimba-chapter-contents-toggle[aria-controls="' + CSS.escape(contentsId) + '"]'
                    );
                    if (button) {
                        setExpanded(button, true);
                    }
                });
            }
        } catch (ignore) {}
    }

    chapterList.addEventListener('click', function (event) {
        var button = event.target.closest('.chisimba-chapter-contents-toggle');
        if (!button || !chapterList.contains(button)) {
            return;
        }
        setExpanded(button, button.getAttribute('aria-expanded') !== 'true');
        saveExpandedChapters();
    });
}());
</script>
<?php

if ($this->isValid('addchapter')) {
    $link = new link($this->uri(array('action' => 'addchapter')));
    $link->link = $this->objLanguage->languageText('mod_contextcontent_addanewchapter', 'contextcontent');

    echo $link->show();
}


if ($this->objModuleCatalogue->checkIfRegistered('feed')) {
    //creating the rss feeds link
    $link = new link($this->uri(array(
                        'action' => 'rss', 'title' => $this->objContext->getTitle(), 'rss_contextcode' => $this->contextCode)));
    $objIcon->setIcon('rss');
    $objIcon->alt = null;
    $objIcon->title = null;
    $link->link = $this->objLanguage->languageText('mod_contextcontent_feedstext', 'contextcontent');
    echo '<br/><br clear="left" />' . $objIcon->show() . ' ' . $link->show();
}

if ($this->objModuleCatalogue->checkIfRegistered('kbookmark') && $this->objUser->isLoggedIn()) {
    //creating the bookmark button
    $this->bookmarkbutton = $this->getObject('bookmarkbutton', 'kbookmark');
    $this->bookmarkbutton->bookmark_button($this->objContext->getTitle(), str_replace('&amp;', '&', $this->uri(array('action' => 'rsscall', 'rss_contextcode' => $this->contextCode))), '', '');
    echo $this->bookmarkbutton->show();
}
?>
