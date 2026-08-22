<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

class contextcontentingestprofile extends ChisimbaObject
{
    public function transform(array $document, array $options = array())
    {
        if (($document['schema'] ?? '') !== 'chisimba.ingest-document/v1') {
            throw new InvalidArgumentException('Context Content requires a neutral ingest document.');
        }
        $overviewStyles = array_map('mb_strtolower', $options['overviewStyles'] ?? array('Chapter Overview'));
        $chapters = array();
        $issues = $document['issues'] ?? array();
        $chapter = null;
        $page = null;
        foreach (($document['blocks'] ?? array()) as $index => $block) {
            $path = 'blocks[' . $index . ']';
            $type = $block['type'] ?? '';
            $level = (int) ($block['level'] ?? 0);
            if ($type === 'heading' && $level === 1) {
                $this->flushPage($chapter, $page);
                $this->flushChapter($chapters, $chapter);
                $chapter = array('title' => trim((string) ($block['text'] ?? '')), 'overview' => '', 'pages' => array(), 'source' => $block['source'] ?? array());
                continue;
            }
            if ($type === 'heading' && $level === 2) {
                $this->flushPage($chapter, $page);
                if ($chapter === null) {
                    $issues[] = $this->issue('error', 'structure.page_before_chapter', 'A Heading 2 page occurs before the first Heading 1 chapter.', $path);
                    continue;
                }
                $page = array('title' => trim((string) ($block['text'] ?? '')), 'html' => '', 'assets' => array(), 'source' => $block['source'] ?? array());
                continue;
            }
            $style = mb_strtolower(trim((string) ($block['style'] ?? '')));
            $isOverview = $type === 'paragraph' && in_array($style, $overviewStyles, true);
            if ($chapter === null) {
                $issues[] = $this->issue('warning', 'structure.content_before_chapter', 'Content before the first Heading 1 was ignored by the Context Content profile.', $path);
                continue;
            }
            $html = $this->renderBlock($block);
            if ($isOverview) {
                if ($page !== null) {
                    $issues[] = $this->issue('warning', 'structure.late_overview', 'A Chapter Overview occurs after the first page and was still assigned to its chapter.', $path);
                }
                $chapter['overview'] .= $html;
            } elseif ($page === null) {
                $chapter['overview'] .= $html;
            } else {
                $page['html'] .= $html;
                foreach (($block['assets'] ?? array()) as $assetId) {
                    if (!in_array($assetId, $page['assets'], true)) { $page['assets'][] = $assetId; }
                }
            }
        }
        $this->flushPage($chapter, $page);
        $this->flushChapter($chapters, $chapter);
        if (empty($chapters)) {
            $issues[] = $this->issue('error', 'document.no_chapters', 'No Heading 1 chapter was found.', 'document');
        }
        foreach ($chapters as $index => $projectedChapter) {
            if ($projectedChapter['title'] === '') {
                $issues[] = $this->issue('error', 'chapter.missing_title', 'A chapter has no title.', 'chapters[' . $index . ']');
            }
            if (empty($projectedChapter['pages'])) {
                $issues[] = $this->issue('warning', 'chapter.no_pages', 'The chapter contains no Heading 2 pages.', 'chapters[' . $index . ']');
            }
        }
        return array(
            'schema' => 'chisimba.contextcontent-import/v1',
            'source' => $document['source'] ?? array(),
            'chapters' => $chapters,
            'assets' => $document['assets'] ?? array(),
            'issues' => $issues,
            'valid' => !array_filter($issues, fn($issue) => $issue['severity'] === 'error')
        );
    }

    private function renderBlock(array $block)
    {
        if (($block['type'] ?? '') === 'heading') {
            $level = min(6, max(3, (int) ($block['level'] ?? 3)));
            return '<h' . $level . '>' . ($block['html'] ?? '') . '</h' . $level . '>';
        }
        if (($block['type'] ?? '') === 'image') {
            $caption = trim((string) ($block['caption'] ?? ''));
            return '<figure><img src="ingest-asset://' . htmlspecialchars($block['assetId'], ENT_QUOTES, 'UTF-8')
                . '" alt="' . htmlspecialchars((string) ($block['alt'] ?? ''), ENT_QUOTES, 'UTF-8') . '">'
                . ($caption === '' ? '' : '<figcaption>' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</figcaption>') . '</figure>';
        }
        if (($block['type'] ?? '') === 'list') {
            $tag = !empty($block['ordered']) ? 'ol' : 'ul'; $items = '';
            foreach (($block['items'] ?? array()) as $item) { $items .= '<li>' . ($item['html'] ?? '') . '</li>'; }
            return '<' . $tag . '>' . $items . '</' . $tag . '>';
        }
        if (($block['type'] ?? '') === 'table') {
            $rows = '';
            foreach (($block['rows'] ?? array()) as $row) {
                $cells = '';
                foreach ($row as $cell) {
                    $tag = !empty($cell['header']) ? 'th' : 'td';
                    $attributes = '';
                    if (($cell['colspan'] ?? 1) > 1) { $attributes .= ' colspan="' . (int) $cell['colspan'] . '"'; }
                    if (($cell['rowspan'] ?? 1) > 1) { $attributes .= ' rowspan="' . (int) $cell['rowspan'] . '"'; }
                    $content = '';
                    foreach (($cell['content'] ?? array()) as $cellBlock) { $content .= $this->renderBlock($cellBlock); }
                    if ($content === '') { $content = $cell['html'] ?? ''; }
                    $cells .= '<' . $tag . $attributes . '>' . $content . '</' . $tag . '>';
                }
                $rows .= '<tr>' . $cells . '</tr>';
            }
            return '<div class="ingest-table"><table><tbody>' . $rows . '</tbody></table></div>';
        }
        return '<p>' . ($block['html'] ?? '') . '</p>';
    }

    private function flushPage(&$chapter, &$page) { if ($page !== null && $chapter !== null) { $chapter['pages'][] = $page; } $page = null; }
    private function flushChapter(array &$chapters, &$chapter) { if ($chapter !== null) { $chapters[] = $chapter; } $chapter = null; }
    private function issue($severity, $code, $message, $path) { return array('severity' => $severity, 'code' => $code, 'message' => $message, 'path' => $path); }
}
?>
