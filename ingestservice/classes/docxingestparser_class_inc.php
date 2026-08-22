<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

class docxingestparser extends ChisimbaObject
{
    private const WORD_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    private const REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const PACKAGE_REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';
    private const DRAWING_NS = 'http://schemas.openxmlformats.org/drawingml/2006/main';

    public function parse($path, array $options = array())
    {
        if (!class_exists('ZipArchive') || !class_exists('DOMDocument')) {
            throw new RuntimeException('DOCX ingestion requires the ZIP and DOM PHP extensions.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('The DOCX package could not be opened.');
        }
        try {
            $documentXml = $this->requiredEntry($zip, 'word/document.xml');
            $styles = $this->readStyles($zip);
            $relationships = $this->readRelationships($zip);
            $policy = $this->normalisePolicy($options);
            return $this->readDocument($zip, $documentXml, $styles, $relationships, $policy, $options);
        } finally {
            $zip->close();
        }
    }

    private function readDocument($zip, $xml, array $styles, array $relationships, array $policy, array $options)
    {
        $dom = $this->loadXml($xml, 'word/document.xml');
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::WORD_NS);
        $chapters = array();
        $assets = array();
        $issues = array();
        $chapter = null;
        $page = null;
        if ($xpath->query('//w:body/w:tbl')->length > 0) {
            $issues[] = $this->issue('warning', 'content.tables_unsupported', 'Tables are not imported by this release.', 'document');
        }
        foreach ($xpath->query('//w:body/w:p') as $position => $paragraph) {
            $styleId = $this->attribute($xpath, './w:pPr/w:pStyle', 'val', $paragraph);
            $styleName = $styles[$styleId] ?? $styleId;
            $role = $this->roleForStyle($styleId, $styleName, $policy);
            $text = trim($this->paragraphText($xpath, $paragraph));
            $location = 'paragraphs[' . $position . ']';
            if ($role === 'named' && $policy['unknown'] === 'warn') {
                $issues[] = $this->issue('warning', 'style.unknown', 'An unrecognised named style was preserved.', $location);
            } elseif ($role === 'named' && $policy['unknown'] === 'error') {
                $issues[] = $this->issue('error', 'style.unknown', 'An unrecognised named style is not allowed.', $location);
            } elseif ($role === 'named' && $policy['unknown'] === 'ignore') {
                $role = 'paragraph';
            }
            if ($role === 'chapter') {
                $this->flushPage($chapter, $page);
                $this->flushChapter($chapters, $chapter);
                $chapter = array('title' => $text, 'overview' => '', 'pages' => array(), 'source' => array('path' => $location, 'style' => $styleName));
                continue;
            }
            if ($role === 'page') {
                $this->flushPage($chapter, $page);
                if ($chapter === null) {
                    $issues[] = $this->issue('error', 'structure.page_before_chapter', 'A Heading 2 page occurs before the first Heading 1 chapter.', $location);
                    continue;
                }
                $page = array('title' => $text, 'html' => '', 'assets' => array(), 'source' => array('path' => $location, 'style' => $styleName));
                continue;
            }
            if ($role === 'overview') {
                if ($chapter === null) {
                    $issues[] = $this->issue('error', 'structure.overview_before_chapter', 'A Chapter Overview occurs before the first Heading 1 chapter.', $location);
                } elseif ($page !== null) {
                    $issues[] = $this->issue('warning', 'structure.late_overview', 'A Chapter Overview occurs after the first page and was still assigned to its chapter.', $location);
                    $chapter['overview'] .= $this->paragraphHtml($xpath, $paragraph, 'paragraph', $styleName, $zip, $relationships, $assets, $options, $issues, $location);
                } else {
                    $chapter['overview'] .= $this->paragraphHtml($xpath, $paragraph, 'paragraph', $styleName, $zip, $relationships, $assets, $options, $issues, $location);
                }
                continue;
            }
            if ($chapter === null && ($text !== '' || $this->hasDrawing($xpath, $paragraph))) {
                $issues[] = $this->issue('warning', 'structure.content_before_chapter', 'Content before the first Heading 1 was ignored.', $location);
                continue;
            }
            if ($page === null) {
                if ($text !== '') {
                    $chapter['overview'] .= $this->paragraphHtml($xpath, $paragraph, $role, $styleName, $zip, $relationships, $assets, $options, $issues, $location);
                }
                continue;
            }
            $page['html'] .= $this->paragraphHtml($xpath, $paragraph, $role, $styleName, $zip, $relationships, $assets, $options, $issues, $location, $page);
        }
        $this->flushPage($chapter, $page);
        $this->flushChapter($chapters, $chapter);
        return array('schema' => 'chisimba.content-ingest/v1', 'chapters' => $chapters, 'assets' => array_values($assets), 'issues' => $issues);
    }

    private function paragraphHtml($xpath, $paragraph, $role, $styleName, $zip, array $relationships, array &$assets, array $options, array &$issues, $location, &$page = null)
    {
        $html = '';
        $text = $this->inlineHtml($xpath, $paragraph);
        if (str_starts_with($role, 'heading')) {
            $level = (int) substr($role, 7);
            $html .= '<h' . $level . '>' . $text . '</h' . $level . '>';
        } elseif ($text !== '') {
            $class = $role === 'named' && $styleName !== '' ? ' class="docx-style-' . $this->slug($styleName) . '"' : '';
            $html .= '<p' . $class . '>' . $text . '</p>';
        }
        foreach ($xpath->query('.//*[local-name()="blip"]', $paragraph) as $blip) {
            $relationshipId = $blip->getAttributeNS(self::REL_NS, 'embed');
            $target = $relationships[$relationshipId] ?? '';
            if ($target === '' || str_contains($target, '..')) {
                $issues[] = $this->issue('error', 'image.invalid_relationship', 'An embedded image has an invalid package relationship.', $location);
                continue;
            }
            $entry = 'word/' . ltrim($target, '/');
            $content = $zip->getFromName($entry);
            if ($content === false) {
                $issues[] = $this->issue('error', 'image.missing', 'An embedded image is missing from the DOCX package.', $location);
                continue;
            }
            $limit = max(1, (int) ($options['maxImageBytes'] ?? 10485760));
            if (strlen($content) > $limit) {
                $issues[] = $this->issue('error', 'image.too_large', 'An embedded image exceeds the configured size limit.', $location);
                continue;
            }
            $id = 'asset-' . substr(hash('sha256', $content), 0, 20);
            $extension = strtolower(pathinfo($target, PATHINFO_EXTENSION));
            $mime = array('png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp')[$extension] ?? '';
            if ($mime === '') {
                $issues[] = $this->issue('error', 'image.unsupported_type', 'An embedded image uses an unsupported format.', $location);
                continue;
            }
            $assets[$id] = array('id' => $id, 'name' => basename($target), 'mediaType' => $mime, 'bytes' => strlen($content), 'content' => base64_encode($content));
            if ($page !== null && !in_array($id, $page['assets'], true)) { $page['assets'][] = $id; }
            $html .= '<figure><img src="ingest-asset://' . $id . '" alt=""></figure>';
        }
        return $html;
    }

    private function inlineHtml($xpath, $paragraph)
    {
        $parts = array();
        foreach ($xpath->query('.//w:t|.//w:tab|.//w:br', $paragraph) as $node) {
            if ($node->localName === 'tab') { $parts[] = ' '; }
            elseif ($node->localName === 'br') { $parts[] = '<br>'; }
            else { $parts[] = htmlspecialchars($node->textContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
        }
        return implode('', $parts);
    }

    private function readStyles($zip)
    {
        $xml = $zip->getFromName('word/styles.xml');
        if ($xml === false) { return array(); }
        $dom = $this->loadXml($xml, 'word/styles.xml');
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::WORD_NS);
        $styles = array();
        foreach ($xpath->query('//w:style[@w:type="paragraph"]') as $style) {
            $id = $style->getAttributeNS(self::WORD_NS, 'styleId');
            $styles[$id] = $this->attribute($xpath, './w:name', 'val', $style) ?: $id;
        }
        return $styles;
    }

    private function readRelationships($zip)
    {
        $xml = $zip->getFromName('word/_rels/document.xml.rels');
        if ($xml === false) { return array(); }
        $dom = $this->loadXml($xml, 'word/_rels/document.xml.rels');
        $relationships = array();
        foreach ($dom->getElementsByTagNameNS(self::PACKAGE_REL_NS, 'Relationship') as $relationship) {
            if ($relationship->getAttribute('TargetMode') !== 'External') {
                $relationships[$relationship->getAttribute('Id')] = $relationship->getAttribute('Target');
            }
        }
        return $relationships;
    }

    private function normalisePolicy(array $options)
    {
        $defaults = array('heading 1' => 'chapter', 'chapter overview' => 'overview', 'heading 2' => 'page');
        for ($level = 3; $level <= 6; $level++) { $defaults['heading ' . $level] = 'heading' . $level; }
        foreach (($options['styleMap'] ?? array()) as $style => $role) { $defaults[mb_strtolower(trim($style))] = $role; }
        $unknown = strtolower((string) ($options['unknownStylePolicy'] ?? 'preserve'));
        if (!in_array($unknown, array('preserve', 'ignore', 'warn', 'error'), true)) {
            throw new InvalidArgumentException('Unknown named-style policy must be preserve, ignore, warn, or error.');
        }
        return array('map' => $defaults, 'unknown' => $unknown);
    }

    private function roleForStyle($styleId, $styleName, array $policy)
    {
        $keys = array(mb_strtolower(trim($styleName)), mb_strtolower(trim(preg_replace('/(?<=\D)(\d)$/', ' $1', $styleId))));
        foreach ($keys as $key) { if (isset($policy['map'][$key])) { return $policy['map'][$key]; } }
        return $styleName === '' ? 'paragraph' : ($policy['unknown'] === 'ignore' ? 'paragraph' : 'named');
    }

    private function flushPage(&$chapter, &$page)
    {
        if ($page !== null && $chapter !== null) { $chapter['pages'][] = $page; }
        $page = null;
    }

    private function flushChapter(array &$chapters, &$chapter)
    {
        if ($chapter !== null) { $chapters[] = $chapter; }
        $chapter = null;
    }

    private function requiredEntry($zip, $name)
    {
        $content = $zip->getFromName($name);
        if ($content === false) { throw new RuntimeException('The DOCX package is missing ' . $name . '.'); }
        return $content;
    }

    private function loadXml($xml, $name)
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$loaded) { throw new RuntimeException('Invalid XML in ' . $name . '.'); }
        return $dom;
    }

    private function attribute($xpath, $query, $localName, $context)
    {
        $node = $xpath->query($query, $context)->item(0);
        return $node ? $node->getAttributeNS(self::WORD_NS, $localName) : '';
    }

    private function paragraphText($xpath, $paragraph) { return implode('', array_map(fn($node) => $node->textContent, iterator_to_array($xpath->query('.//w:t', $paragraph)))); }
    private function hasDrawing($xpath, $paragraph) { return $xpath->query('.//*[local-name()="blip"]', $paragraph)->length > 0; }
    private function slug($value) { return trim(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($value)), '-'); }
    private function issue($severity, $code, $message, $path) { return array('severity' => $severity, 'code' => $code, 'message' => $message, 'path' => $path); }
}
?>
