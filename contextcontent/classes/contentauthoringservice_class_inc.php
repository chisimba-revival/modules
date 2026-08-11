<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

class contentauthoringservice extends ChisimbaObject
{
    public function init()
    {
        $this->titles = $this->getObject('db_contextcontent_titles', 'contextcontent');
        $this->pages = $this->getObject('db_contextcontent_pages', 'contextcontent');
        $this->order = $this->getObject('db_contextcontent_order', 'contextcontent');
        $this->chapters = $this->getObject('db_contextcontent_contextchapter', 'contextcontent');
        $this->chapterRows = $this->getObject('db_contextcontent_chapters', 'contextcontent');
        $this->chapterContent = $this->getObject('db_contextcontent_chaptercontent', 'contextcontent');
        $this->registry = $this->getObject('contenttyperegistry', 'contextcontent');
    }

    public function createNativePage(array $input)
    {
        $data = $this->validatePage($input);
        $this->titles->beginTransaction();
        try {
            $titleId = $this->titles->createTypedTitle($data['contenttype']);
            $this->pages->createNativeBody($titleId, $data['title'], $data['body'], $data['language']);
            $placementId = $this->order->addPageToContext(
                $titleId, $data['parentid'], $data['contextcode'], $data['chapterid'], '', '',
                $data['insert_after']
            );
            if (!$placementId) { throw new RuntimeException('Could not create content placement'); }
            $this->titles->commitTransaction();
            return $placementId;
        } catch (Throwable $error) {
            $this->titles->rollbackTransaction();
            throw $error;
        }
    }

    public function updateNativePage(array $input)
    {
        $data = $this->validatePage($input, true);
        $page = $this->order->getPage($data['placementid'], $data['contextcode']);
        if (!$page) { throw new InvalidArgumentException('Content item does not exist in this course'); }
        $this->registry->get($page['contenttype']);
        $this->titles->beginTransaction();
        try {
            $this->pages->updateNativeBody($page['pageid'], $data['title'], $data['body']);
            if ($data['parentid'] !== $page['parentid']) {
                $this->order->changeParent($data['contextcode'], $page['chapterid'], $data['placementid'], $data['parentid']);
            }
            $this->titles->commitTransaction();
        } catch (Throwable $error) {
            $this->titles->rollbackTransaction();
            throw $error;
        }
    }

    private function validatePage(array $input, $editing = false)
    {
        $data = array(
            'contextcode' => trim((string) ($input['contextcode'] ?? '')),
            'chapterid' => trim((string) ($input['chapterid'] ?? '')),
            'parentid' => trim((string) ($input['parentid'] ?? '')),
            'insert_after' => trim((string) ($input['insert_after'] ?? '')),
            'placementid' => trim((string) ($input['placementid'] ?? '')),
            'contenttype' => trim((string) ($input['contenttype'] ?? 'rich_text')),
            'title' => trim((string) ($input['title'] ?? '')),
            'body' => (string) ($input['body'] ?? ''),
            'language' => trim((string) ($input['language'] ?? 'en'))
        );
        if ($data['contextcode'] === '' || $data['chapterid'] === '' || $data['title'] === '') {
            throw new InvalidArgumentException('Course, chapter and title are required');
        }
        foreach (array('chapterid', 'placementid', 'insert_after') as $identifierField) {
            if ($data[$identifierField] !== ''
                && !preg_match('/^[A-Za-z0-9_-]{1,64}$/', $data[$identifierField])) {
                throw new InvalidArgumentException('Invalid content identifier');
            }
        }
        if ($data['parentid'] !== '' && $data['parentid'] !== 'root'
            && !preg_match('/^[A-Za-z0-9_-]{1,64}$/', $data['parentid'])) {
            throw new InvalidArgumentException('Invalid parent identifier');
        }
        if (!preg_match('/^[A-Za-z0-9._-]{1,255}$/', $data['contextcode'])) {
            throw new InvalidArgumentException('Invalid course code');
        }
        if ($editing && $data['placementid'] === '') { throw new InvalidArgumentException('Content ID is required'); }
        $this->registry->get($data['contenttype']);
        if (!preg_match('/^[a-z]{2,3}$/', $data['language'])) { throw new InvalidArgumentException('Invalid language'); }
        if ($data['contenttype'] === 'short_text' && mb_strlen(trim(strip_tags($data['body']))) > 1200) {
            throw new InvalidArgumentException('Short text exceeds the 1200-character authoring limit');
        }
        return $data;
    }
}
?>
