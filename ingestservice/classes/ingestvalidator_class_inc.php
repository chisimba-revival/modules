<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

class ingestvalidator extends ChisimbaObject
{
    public function validate(array $document)
    {
        $issues = $document['issues'] ?? array();
        if (($document['schema'] ?? '') !== 'chisimba.ingest-document/v1') {
            $issues[] = $this->issue('error', 'document.invalid_schema', 'The document does not use the supported neutral ingest schema.', 'document.schema');
        }
        if (empty($document['blocks'])) {
            $issues[] = $this->issue('error', 'document.no_content', 'The source contains no importable content.', 'document.blocks');
        }
        $assetIds = array();
        foreach (($document['assets'] ?? array()) as $assetIndex => $asset) {
            $id = (string) ($asset['id'] ?? '');
            if ($id === '' || isset($assetIds[$id])) {
                $issues[] = $this->issue('error', 'asset.invalid_id', 'An asset has a missing or duplicate identifier.', 'assets[' . $assetIndex . ']');
            }
            $assetIds[$id] = true;
        }
        foreach (($document['blocks'] ?? array()) as $blockIndex => $block) {
            $path = 'blocks[' . $blockIndex . ']';
            if (!in_array(($block['type'] ?? ''), array('heading', 'paragraph', 'image', 'list', 'table'), true)) {
                $issues[] = $this->issue('error', 'block.unsupported_type', 'A block has an unsupported type.', $path);
            }
            foreach (($block['assets'] ?? array()) as $assetId) {
                if (!isset($assetIds[$assetId])) {
                    $issues[] = $this->issue('error', 'block.missing_asset', 'A block references an asset that is not present.', $path . '.assets');
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
