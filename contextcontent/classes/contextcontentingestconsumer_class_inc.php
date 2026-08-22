<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

class contextcontentingestconsumer extends ChisimbaObject
{
    public function init()
    {
        $this->chapterRows = $this->getObject('db_contextcontent_chapters', 'contextcontent');
        $this->contextChapters = $this->getObject('db_contextcontent_contextchapter', 'contextcontent');
        $this->authoring = $this->getObject('contentauthoringservice', 'contextcontent');
        $this->profile = $this->getObject('contextcontentingestprofile', 'contextcontent');
        $this->fileApi = $this->getObject('fileapi', 'filemanager');
        $this->objLanguage = $this->getObject('language', 'language');
    }

    public function consume(array $document, array $options)
    {
        $document = $this->profile->transform($document, $options['profileOptions'] ?? array());
        if (!$document['valid']) {
            throw new InvalidArgumentException('The document does not satisfy the Context Content ingest profile.');
        }
        $contextCode = trim((string) ($options['contextcode'] ?? $options['target'] ?? ''));
        $language = trim((string) ($options['language'] ?? 'en'));
        if (!preg_match('/^[A-Za-z0-9._-]{1,255}$/', $contextCode)) {
            throw new InvalidArgumentException('A valid course code is required.');
        }
        if (!preg_match('/^[a-z]{2,3}$/', $language)) {
            throw new InvalidArgumentException('A valid content language is required.');
        }
        $assets = $this->storeAssets($document['assets'] ?? array(), $contextCode, $document['source']['fingerprint'] ?? 'source');
        $created = array('contextcode' => $contextCode, 'chapters' => array(), 'assets' => array_values($assets));
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
            if (!$asset || empty($asset['url'])) {
                throw new RuntimeException('Imported content references a missing image asset.');
            }
            return $asset['url'];
        }, $html);
    }

    private function storeAssets(array $assets, $contextCode, $fingerprint)
    {
        $stored = array();
        $collection = substr(preg_replace('/[^a-f0-9]+/i', '', (string) $fingerprint), 0, 24) ?: 'source';
        foreach ($assets as $asset) {
            if (empty($asset['id'])) { throw new RuntimeException($this->objLanguage->languageText('mod_contextcontent_ingest_invalid_asset', 'contextcontent')); }
            $result = $this->fileApi->storeContextGeneratedImage($contextCode, $asset, $collection);
            if (empty($result['ok']) || empty($result['file']['url'])) {
                $message = $result['error']['message'] ?? $this->objLanguage->languageText('mod_contextcontent_ingest_asset_store_failed', 'contextcontent');
                throw new RuntimeException($message);
            }
            $stored[$asset['id']] = array(
                'assetId' => $asset['id'],
                'fileId' => $result['file']['id'],
                'url' => $result['file']['url'],
                'reused' => !empty($result['reused'])
            );
        }
        return $stored;
    }
}
?>
