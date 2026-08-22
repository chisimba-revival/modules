<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

class odtingestparser extends ChisimbaObject
{
    private const OFFICE_NS = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const TEXT_NS = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const DRAW_NS = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
    private const XLINK_NS = 'http://www.w3.org/1999/xlink';
    private const SVG_NS = 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0';

    public function parse($path, array $options = array())
    {
        if (!class_exists('ZipArchive') || !class_exists('DOMDocument')) {
            throw new RuntimeException('ODT ingestion requires the ZIP and DOM PHP extensions.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('The ODT package could not be opened.');
        }
        try {
            $content = $this->loadXml($this->requiredEntry($zip, 'content.xml'), 'content.xml');
            $styles = $this->readStyles($zip, $content);
            return $this->readDocument($zip, $content, $styles, $options);
        } finally {
            $zip->close();
        }
    }

    private function readDocument($zip, $dom, array $styles, array $options)
    {
        $xpath = $this->xpath($dom);
        $blocks = array();
        $assets = array();
        $issues = array();
        $policy = strtolower((string) ($options['unknownStylePolicy'] ?? 'preserve'));
        if (!in_array($policy, array('preserve', 'ignore', 'warn', 'error'), true)) {
            throw new InvalidArgumentException('Unknown named-style policy must be preserve, ignore, warn, or error.');
        }
        $nodes = $xpath->query('//office:body/office:text//*[self::text:h or self::text:p or self::draw:frame]');
        foreach ($nodes as $position => $node) {
            if ($node->localName === 'frame' && $node->parentNode && in_array($node->parentNode->localName, array('p', 'h'), true)) {
                continue;
            }
            $location = 'content[' . $position . ']';
            if ($node->localName === 'frame') {
                $image = $this->imageBlock($xpath, $node, $zip, $assets, $options, $issues, $location);
                if ($image) { $blocks[] = $image; }
                continue;
            }
            $styleId = $node->getAttributeNS(self::TEXT_NS, 'style-name');
            $styleName = $styles[$styleId]['displayName'] ?? $styleId;
            $known = isset($styles[$styleId]) || $styleId === '';
            if (!$known && $policy === 'warn') {
                $issues[] = $this->issue('warning', 'style.unknown', 'An unrecognised named style was preserved.', $location);
            } elseif (!$known && $policy === 'error') {
                $issues[] = $this->issue('error', 'style.unknown', 'An unrecognised named style is not allowed.', $location);
            } elseif (!$known && $policy === 'ignore') {
                $styleName = '';
            }
            $text = trim($this->plainText($node));
            $source = array('path' => $location, 'styleId' => $styleId, 'styleName' => $styleName);
            if ($text !== '') {
                if ($node->localName === 'h') {
                    $level = (int) $node->getAttributeNS(self::TEXT_NS, 'outline-level');
                    if ($level < 1) { $level = (int) ($styles[$styleId]['level'] ?? 1); }
                    $blocks[] = array('type' => 'heading', 'level' => min(6, max(1, $level)), 'text' => $text,
                        'html' => $this->inlineHtml($node), 'style' => $styleName, 'source' => $source);
                } else {
                    $blocks[] = array('type' => 'paragraph', 'text' => $text, 'html' => $this->inlineHtml($node),
                        'style' => $styleName, 'source' => $source);
                }
            }
            foreach ($xpath->query('.//draw:frame', $node) as $frame) {
                $image = $this->imageBlock($xpath, $frame, $zip, $assets, $options, $issues, $location);
                if ($image) { $image['source'] = $source; $blocks[] = $image; }
            }
        }
        return array('schema' => 'chisimba.ingest-document/v1', 'metadata' => array(), 'blocks' => $blocks,
            'assets' => array_values($assets), 'issues' => $issues);
    }

    private function imageBlock($xpath, $frame, $zip, array &$assets, array $options, array &$issues, $location)
    {
        $image = $xpath->query('./draw:image', $frame)->item(0);
        $target = $image ? rawurldecode($image->getAttributeNS(self::XLINK_NS, 'href')) : '';
        if ($target === '' || str_starts_with($target, '/') || str_contains($target, '..')) {
            $issues[] = $this->issue('error', 'image.invalid_relationship', 'An embedded image has an invalid package path.', $location);
            return null;
        }
        $content = $zip->getFromName($target);
        if ($content === false) {
            $issues[] = $this->issue('error', 'image.missing', 'An embedded image is missing from the ODT package.', $location);
            return null;
        }
        if (strlen($content) > max(1, (int) ($options['maxImageBytes'] ?? 10485760))) {
            $issues[] = $this->issue('error', 'image.too_large', 'An embedded image exceeds the configured size limit.', $location);
            return null;
        }
        $extension = strtolower(pathinfo($target, PATHINFO_EXTENSION));
        $mime = array('png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp')[$extension] ?? '';
        if ($mime === '') {
            $issues[] = $this->issue('error', 'image.unsupported_type', 'An embedded image uses an unsupported format.', $location);
            return null;
        }
        $id = 'asset-' . substr(hash('sha256', $content), 0, 20);
        $assets[$id] = array('id' => $id, 'name' => basename($target), 'mediaType' => $mime,
            'bytes' => strlen($content), 'content' => base64_encode($content));
        $title = $xpath->query('./svg:title', $frame)->item(0);
        $description = $xpath->query('./svg:desc', $frame)->item(0);
        return array('type' => 'image', 'assetId' => $id, 'assets' => array($id),
            'alt' => trim($description ? $description->textContent : ''),
            'caption' => trim($title ? $title->textContent : ''));
    }

    private function readStyles($zip, $contentDom)
    {
        $documents = array($contentDom);
        $stylesXml = $zip->getFromName('styles.xml');
        if ($stylesXml !== false) { $documents[] = $this->loadXml($stylesXml, 'styles.xml'); }
        $styles = array();
        foreach ($documents as $dom) {
            $xpath = $this->xpath($dom);
            foreach ($xpath->query('//style:style[@style:family="paragraph"]') as $style) {
                $id = $style->getAttributeNS(self::STYLE_NS, 'name');
                $display = $style->getAttributeNS(self::STYLE_NS, 'display-name') ?: str_replace('_20_', ' ', $id);
                $level = 0;
                if (preg_match('/(?:Heading|heading)[ _](\d)$/', $display, $match)) { $level = (int) $match[1]; }
                $styles[$id] = array('displayName' => $display, 'level' => $level);
            }
        }
        return $styles;
    }

    private function inlineHtml($node)
    {
        return htmlspecialchars($this->plainText($node), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function plainText($node)
    {
        $text = '';
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMText) { $text .= $child->nodeValue; continue; }
            if ($child->namespaceURI === self::DRAW_NS) { continue; }
            if ($child->namespaceURI === self::TEXT_NS && $child->localName === 'tab') { $text .= "\t"; continue; }
            if ($child->namespaceURI === self::TEXT_NS && $child->localName === 'line-break') { $text .= "\n"; continue; }
            if ($child->namespaceURI === self::TEXT_NS && $child->localName === 's') {
                $count = max(1, (int) $child->getAttributeNS(self::TEXT_NS, 'c')); $text .= str_repeat(' ', $count); continue;
            }
            $text .= $this->plainText($child);
        }
        return $text;
    }

    private function xpath($dom)
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('office', self::OFFICE_NS);
        $xpath->registerNamespace('text', self::TEXT_NS);
        $xpath->registerNamespace('style', self::STYLE_NS);
        $xpath->registerNamespace('draw', self::DRAW_NS);
        $xpath->registerNamespace('svg', self::SVG_NS);
        return $xpath;
    }

    private function requiredEntry($zip, $name) { $value = $zip->getFromName($name); if ($value === false) { throw new RuntimeException('The ODT package is missing ' . $name . '.'); } return $value; }
    private function loadXml($xml, $name) { $dom = new DOMDocument(); $previous = libxml_use_internal_errors(true); $ok = $dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT); libxml_clear_errors(); libxml_use_internal_errors($previous); if (!$ok) { throw new RuntimeException('Invalid XML in ' . $name . '.'); } return $dom; }
    private function issue($severity, $code, $message, $path) { return array('severity' => $severity, 'code' => $code, 'message' => $message, 'path' => $path); }
}
?>
