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
            $this->assertSafeArchive($zip, $options);
            $documentXml = $this->requiredEntry($zip, 'word/document.xml');
            $styles = $this->readStyles($zip);
            $relationships = $this->readRelationships($zip);
            $numbering = $this->readNumbering($zip);
            $policy = $this->normalisePolicy($options);
            return $this->readDocument($zip, $documentXml, $styles, $relationships, $numbering, $policy, $options);
        } finally {
            $zip->close();
        }
    }

    private function readDocument($zip, $xml, array $styles, array $relationships, array $numbering, array $policy, array $options)
    {
        $dom = $this->loadXml($xml, 'word/document.xml');
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::WORD_NS);
        $blocks = array();
        $assets = array();
        $issues = array();
        foreach ($xpath->query('//w:body/*[self::w:p or self::w:tbl]') as $position => $paragraph) {
            if ($paragraph->localName === 'tbl') {
                if ($xpath->query('.//*[local-name()="blip"]', $paragraph)->length) {
                    $issues[] = $this->issue('warning', 'table.images_unsupported', 'Images inside table cells were not imported.', 'content[' . $position . ']');
                }
                $blocks[] = $this->tableBlock($xpath, $paragraph, $relationships, 'content[' . $position . ']');
                continue;
            }
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
            $source = array('path' => $location, 'styleId' => $styleId, 'styleName' => $styleName);
            if ($text !== '') {
                $numId = $this->attribute($xpath, './w:pPr/w:numPr/w:numId', 'val', $paragraph);
                if ($numId !== '' && !str_starts_with($role, 'heading')) {
                    $level = (int) $this->attribute($xpath, './w:pPr/w:numPr/w:ilvl', 'val', $paragraph);
                    $ordered = ($numbering[$numId] ?? 'bullet') !== 'bullet';
                    $last = count($blocks) - 1;
                    if ($last < 0 || $blocks[$last]['type'] !== 'list' || $blocks[$last]['ordered'] !== $ordered) {
                        $blocks[] = array('type' => 'list', 'ordered' => $ordered, 'items' => array(), 'source' => $source);
                        $last = count($blocks) - 1;
                    }
                    $blocks[$last]['items'][] = array('level' => $level, 'text' => $text, 'html' => $this->inlineHtml($xpath, $paragraph, $relationships));
                } elseif (str_starts_with($role, 'heading')) {
                    $blocks[] = array(
                        'type' => 'heading',
                        'level' => (int) substr($role, 7),
                        'text' => $text,
                        'html' => $this->inlineHtml($xpath, $paragraph, $relationships),
                        'style' => $styleName,
                        'source' => $source
                    );
                } else {
                    $blocks[] = array(
                        'type' => 'paragraph',
                        'text' => $text,
                        'html' => $this->inlineHtml($xpath, $paragraph, $relationships),
                        'style' => $styleName,
                        'source' => $source
                    );
                }
            }
            foreach ($this->extractImages($xpath, $paragraph, $zip, $relationships, $assets, $options, $issues, $location) as $imageBlock) {
                $imageBlock['source'] = $source;
                $blocks[] = $imageBlock;
            }
        }
        return array('schema' => 'chisimba.ingest-document/v1', 'metadata' => array(), 'blocks' => $blocks, 'assets' => array_values($assets), 'issues' => $issues);
    }

    private function tableBlock($xpath, $table, array $relationships, $location)
    {
        $rows = array();
        foreach ($xpath->query('./w:tr', $table) as $row) {
            $cells = array();
            foreach ($xpath->query('./w:tc', $row) as $cell) {
                $html = array(); $text = array();
                foreach ($xpath->query('./w:p', $cell) as $paragraph) {
                    $html[] = $this->inlineHtml($xpath, $paragraph, $relationships);
                    $text[] = trim($this->paragraphText($xpath, $paragraph));
                }
                $cells[] = array('text' => trim(implode("\n", $text)), 'html' => implode('<br>', $html));
            }
            $rows[] = $cells;
        }
        return array('type' => 'table', 'rows' => $rows, 'source' => array('path' => $location));
    }

    private function extractImages($xpath, $paragraph, $zip, array $relationships, array &$assets, array $options, array &$issues, $location)
    {
        $blocks = array();
        foreach ($xpath->query('.//*[local-name()="blip"]', $paragraph) as $blip) {
            $relationshipId = $blip->getAttributeNS(self::REL_NS, 'embed');
            $target = $relationships[$relationshipId]['target'] ?? '';
            if ($target === '' || str_contains($target, '..')) {
                $issues[] = $this->issue('error', 'image.invalid_relationship', 'An embedded image has an invalid package relationship.', $location);
                continue;
            }
            $content = $zip->getFromName('word/' . ltrim($target, '/'));
            if ($content === false) {
                $issues[] = $this->issue('error', 'image.missing', 'An embedded image is missing from the DOCX package.', $location);
                continue;
            }
            if (strlen($content) > max(1, (int) ($options['maxImageBytes'] ?? 10485760))) {
                $issues[] = $this->issue('error', 'image.too_large', 'An embedded image exceeds the configured size limit.', $location);
                continue;
            }
            $extension = strtolower(pathinfo($target, PATHINFO_EXTENSION));
            $mime = array('png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp')[$extension] ?? '';
            if ($mime === '') {
                $issues[] = $this->issue('error', 'image.unsupported_type', 'An embedded image uses an unsupported format.', $location);
                continue;
            }
            $id = 'asset-' . substr(hash('sha256', $content), 0, 20);
            $assets[$id] = array('id' => $id, 'name' => basename($target), 'mediaType' => $mime, 'bytes' => strlen($content), 'content' => base64_encode($content));
            $metadata = $xpath->query('.//*[local-name()="docPr"]', $paragraph)->item(0);
            $blocks[] = array('type' => 'image', 'assetId' => $id, 'assets' => array($id),
                'alt' => $metadata ? trim($metadata->getAttribute('descr')) : '',
                'caption' => $metadata ? trim($metadata->getAttribute('title')) : '');
        }
        return $blocks;
    }

    private function inlineHtml($xpath, $paragraph, array $relationships)
    {
        $parts = array();
        foreach ($paragraph->childNodes as $node) {
            if ($node->namespaceURI !== self::WORD_NS) { continue; }
            if ($node->localName === 'r') {
                $parts[] = $this->renderRun($xpath, $node);
            } elseif ($node->localName === 'hyperlink') {
                $label = '';
                foreach ($xpath->query('./w:r', $node) as $run) { $label .= $this->renderRun($xpath, $run); }
                $relationshipId = $node->getAttributeNS(self::REL_NS, 'id');
                $target = $relationships[$relationshipId]['external'] ?? false
                    ? $this->safeUrl($relationships[$relationshipId]['target'] ?? '') : '';
                $anchor = trim($node->getAttributeNS(self::WORD_NS, 'anchor'));
                if ($target === '' && $anchor !== '') { $target = '#' . rawurlencode($anchor); }
                $parts[] = $target === '' ? $label : '<a href="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '">' . $label . '</a>';
            }
        }
        return implode('', $parts);
    }

    private function renderRun($xpath, $run)
    {
        $html = '';
        foreach ($xpath->query('./w:t|./w:tab|./w:br', $run) as $node) {
            if ($node->localName === 'tab') { $html .= ' '; }
            elseif ($node->localName === 'br') { $html .= '<br>'; }
            else { $html .= htmlspecialchars($node->textContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
        }
        if ($html === '') { return ''; }
        if ($xpath->query('./w:rPr/w:b[not(@w:val="0") and not(@w:val="false")]', $run)->length) { $html = '<strong>' . $html . '</strong>'; }
        if ($xpath->query('./w:rPr/w:i[not(@w:val="0") and not(@w:val="false")]', $run)->length) { $html = '<em>' . $html . '</em>'; }
        if ($xpath->query('./w:rPr/w:u[not(@w:val="none")]', $run)->length) { $html = '<u>' . $html . '</u>'; }
        if ($xpath->query('./w:rPr/w:strike[not(@w:val="0") and not(@w:val="false")]', $run)->length) { $html = '<s>' . $html . '</s>'; }
        return $html;
    }

    private function safeUrl($url)
    {
        $url = trim((string) $url);
        if (str_starts_with($url, '#')) { return $url; }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, array('http', 'https', 'mailto'), true) ? $url : '';
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
            $relationships[$relationship->getAttribute('Id')] = array(
                'target' => $relationship->getAttribute('Target'),
                'external' => $relationship->getAttribute('TargetMode') === 'External'
            );
        }
        return $relationships;
    }

    private function readNumbering($zip)
    {
        $xml = $zip->getFromName('word/numbering.xml');
        if ($xml === false) { return array(); }
        $dom = $this->loadXml($xml, 'word/numbering.xml');
        $xpath = new DOMXPath($dom); $xpath->registerNamespace('w', self::WORD_NS);
        $abstract = array();
        foreach ($xpath->query('//w:abstractNum') as $definition) {
            $id = $definition->getAttributeNS(self::WORD_NS, 'abstractNumId');
            $format = $this->attribute($xpath, './w:lvl[@w:ilvl="0"]/w:numFmt', 'val', $definition);
            $abstract[$id] = $format ?: 'bullet';
        }
        $numbering = array();
        foreach ($xpath->query('//w:num') as $number) {
            $id = $number->getAttributeNS(self::WORD_NS, 'numId');
            $abstractId = $this->attribute($xpath, './w:abstractNumId', 'val', $number);
            $numbering[$id] = $abstract[$abstractId] ?? 'bullet';
        }
        return $numbering;
    }

    private function normalisePolicy(array $options)
    {
        $defaults = array();
        for ($level = 1; $level <= 6; $level++) { $defaults['heading ' . $level] = 'heading' . $level; }
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

    private function requiredEntry($zip, $name)
    {
        $content = $zip->getFromName($name);
        if ($content === false) { throw new RuntimeException('The DOCX package is missing ' . $name . '.'); }
        return $content;
    }

    private function assertSafeArchive($zip, array $options)
    {
        $entryLimit = max(1, (int) ($options['maxArchiveEntries'] ?? 2000));
        $expandedLimit = max(1, (int) ($options['maxExpandedBytes'] ?? 209715200));
        $ratioLimit = max(1, (int) ($options['maxCompressionRatio'] ?? 100));
        if ($zip->numFiles > $entryLimit) { throw new RuntimeException('The DOCX package contains too many entries.'); }
        $expanded = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            if (!$stat) { throw new RuntimeException('The DOCX package directory is invalid.'); }
            $name = str_replace('\\', '/', (string) $stat['name']);
            if (str_starts_with($name, '/') || preg_match('#(^|/)\.\.(/|$)#', $name)) {
                throw new RuntimeException('The DOCX package contains an unsafe entry path.');
            }
            $size = (int) ($stat['size'] ?? 0); $compressed = (int) ($stat['comp_size'] ?? 0);
            $expanded += $size;
            if ($expanded > $expandedLimit) { throw new RuntimeException('The DOCX package expands beyond the configured size limit.'); }
            if ($size > 1048576 && $compressed > 0 && ($size / $compressed) > $ratioLimit) {
                throw new RuntimeException('The DOCX package contains a suspiciously compressed entry.');
            }
        }
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
    private function issue($severity, $code, $message, $path) { return array('severity' => $severity, 'code' => $code, 'message' => $message, 'path' => $path); }
}
?>
