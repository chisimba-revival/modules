<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

class contextcontentingestconsumer extends ChisimbaObject
{
    public function init()
    {
        $this->chapterRows = $this->getObject('db_contextcontent_chapters', 'contextcontent');
        $this->contextChapters = $this->getObject('db_contextcontent_contextchapter', 'contextcontent');
        $this->authoring = $this->getObject('contentauthoringservice', 'contextcontent');
    }

    public function consume(array $document, array $options)
    {
        $contextCode = trim((string) ($options['contextcode'] ?? $options['target'] ?? ''));
        $language = trim((string) ($options['language'] ?? 'en'));
        if (!preg_match('/^[A-Za-z0-9._-]{1,255}$/', $contextCode)) {
            throw new InvalidArgumentException('A valid course code is required.');
        }
        if (!preg_match('/^[a-z]{2,3}$/', $language)) {
            throw new InvalidArgumentException('A valid content language is required.');
        }
        $assets = array_column($document['assets'] ?? array(), null, 'id');
        $created = array('contextcode' => $contextCode, 'chapters' => array());
        $this->chapterRows->beginTransaction();
        try {
            foreach ($document['chapters'] as $chapter) {
                $overview = $this->materialiseAssets((string) $chapter['overview'], $assets);
                $chapterId = $this->chapterRows->addChapter('', $chapter['title'], $overview, $language);
                if (!$chapterId || !$this->contextChapters->addChapterToContext($chapterId, $contextCode, 'Y')) {
                    throw new RuntimeException('Could not create or place an imported chapter.');
                }
                $chapterResult = array('id' => $chapterId, 'title' => $chapter['title'], 'pages' => array());
                $previousPlacement = '';
                foreach ($chapter['pages'] as $page) {
                    $placementId = $this->authoring->createNativePage(array(
                        'contextcode' => $contextCode,
                        'chapterid' => $chapterId,
                        'parentid' => 'root',
                        'insert_after' => $previousPlacement,
                        'contenttype' => 'rich_text',
                        'title' => $page['title'],
                        'body' => $this->materialiseAssets((string) $page['html'], $assets),
                        'language' => $language,
                        'manage_transaction' => false
                    ));
                    $previousPlacement = $placementId;
                    $chapterResult['pages'][] = array('id' => $placementId, 'title' => $page['title']);
                }
                $created['chapters'][] = $chapterResult;
            }
            $this->chapterRows->commitTransaction();
        } catch (Throwable $error) {
            $this->chapterRows->rollbackTransaction();
            throw $error;
        }
        return $created;
    }

    private function materialiseAssets($html, array $assets)
    {
        return preg_replace_callback('/ingest-asset:\/\/([A-Za-z0-9_-]+)/', function ($match) use ($assets) {
            $asset = $assets[$match[1]] ?? null;
            if (!$asset || empty($asset['content']) || empty($asset['mediaType'])) {
                throw new RuntimeException('Imported content references a missing image asset.');
            }
            return 'data:' . $asset['mediaType'] . ';base64,' . $asset['content'];
        }, $html);
    }
}
?>
