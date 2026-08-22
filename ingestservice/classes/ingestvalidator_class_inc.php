<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

class ingestvalidator extends ChisimbaObject
{
    public function validate(array $document)
    {
        $issues = $document['issues'] ?? array();
        if (empty($document['chapters'])) {
            $issues[] = $this->issue('error', 'document.no_chapters', 'No Heading 1 chapter was found.', 'document');
        }
        foreach (($document['chapters'] ?? array()) as $chapterIndex => $chapter) {
            $chapterPath = 'chapters[' . $chapterIndex . ']';
            if (trim((string) ($chapter['title'] ?? '')) === '') {
                $issues[] = $this->issue('error', 'chapter.missing_title', 'A chapter has no title.', $chapterPath);
            }
            if (empty($chapter['pages'])) {
                $issues[] = $this->issue('warning', 'chapter.no_pages', 'The chapter contains no Heading 2 pages.', $chapterPath);
            }
            foreach (($chapter['pages'] ?? array()) as $pageIndex => $page) {
                if (trim((string) ($page['title'] ?? '')) === '') {
                    $issues[] = $this->issue('error', 'page.missing_title', 'A page has no title.', $chapterPath . '.pages[' . $pageIndex . ']');
                }
            }
        }
        $document['issues'] = $issues;
        $document['valid'] = !array_filter($issues, fn($issue) => $issue['severity'] === 'error');
        return $document;
    }

    private function issue($severity, $code, $message, $path)
    {
        return array('severity' => $severity, 'code' => $code, 'message' => $message, 'path' => $path);
    }
}
?>
