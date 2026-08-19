<?php
/**
 * Domain-specific AI consumer for chapter content generation.
 *
 * @category  Chisimba
 * @package   contextcontent
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class contextcontentaigenerator extends ChisimbaObject
{
    private $aiService = null;
    private $authoring;
    private $order;

    public function init()
    {
        $this->authoring = $this->getObject('contentauthoringservice', 'contextcontent');
        $this->order = $this->getObject('db_contextcontent_order', 'contextcontent');
    }

    public function generate($sourceText, $authorInstruction = '')
    {
        $sourceText = trim((string) $sourceText);
        $authorInstruction = trim((string) $authorInstruction);
        if (mb_strlen($sourceText, 'UTF-8') < 150) {
            return array('ok' => false, 'error' => 'source_too_short');
        }
        if ($this->aiService === null) {
            $this->aiService = $this->getObject('aiservice', 'ai');
        }

        $schema = array(
            'type' => 'object',
            'properties' => array(
                'pages' => array(
                    'type' => 'array',
                    'minItems' => 4,
                    'maxItems' => 4,
                    'items' => array(
                        'type' => 'object',
                        'properties' => array(
                            'contentType' => array('type' => 'string', 'enum' => array('rich_text', 'short_text')),
                            'title' => array('type' => 'string'),
                            'bodyText' => array('type' => 'string'),
                            'sourceBasis' => array('type' => 'string')
                        ),
                        'required' => array('contentType', 'title', 'bodyText', 'sourceBasis'),
                        'additionalProperties' => false
                    )
                )
            ),
            'required' => array('pages'),
            'additionalProperties' => false
        );

        $instructions =
            "Create exactly four learning pages for one chapter using ONLY the supplied source material. "
            . "Use only contentType rich_text or short_text and use both types at least once. "
            . "Arrange the pages as a coherent learning sequence. "
            . "Do not use, infer, introduce, test, or rely on facts not explicitly present in the source. "
            . "bodyText must be plain text only: no HTML, Markdown, URLs, citations or invented references. "
            . "A short_text page must contain at most 1000 visible characters. "
            . "For every page, sourceBasis must be a short VERBATIM excerpt copied from the supplied source that directly supports the page content. "
            . "Do not paraphrase sourceBasis. If the source cannot support four useful pages, do not invent material.";

        $input = "SOURCE MATERIAL:\n" . $sourceText;
        if ($authorInstruction !== '') {
            $input .= "\n\nAUTHOR DIRECTION:\n" . $authorInstruction
                . "\nThe author direction may guide emphasis, audience and sequence but may not add factual material beyond the source.";
        }

        $result = $this->aiService->execute(array(
            'consumer' => 'contextcontent',
            'task' => 'generate_grounded_chapter_pages',
            'instructions' => $instructions,
            'input' => $input,
            'schemaName' => 'contextcontent_grounded_chapter_pages_v1',
            'schema' => $schema
        ));

        if (empty($result['ok']) || empty($result['data']['pages'])) {
            return array('ok' => false, 'error' => isset($result['error']) ? (string) $result['error'] : 'provider_failed');
        }

        $pages = $this->validatePages($sourceText, $result['data']['pages']);
        if ($pages === false) {
            return array('ok' => false, 'error' => 'grounding_validation_failed');
        }
        return array('ok' => true, 'pages' => $pages);
    }

    public function insertPages($contextCode, $chapterId, array $pages)
    {
        if (count($pages) !== 4) {
            return array('ok' => false, 'error' => 'invalid_candidates');
        }

        $existing = $this->order->getContextPages($contextCode, $chapterId);
        $insertAfter = '';
        if (is_array($existing) && count($existing) > 0) {
            $last = end($existing);
            if (is_array($last) && !empty($last['id'])) {
                $insertAfter = (string) $last['id'];
            }
        }

        $created = array();
        foreach ($pages as $page) {
            if (!$this->validPage($page)) {
                return array('ok' => false, 'error' => 'invalid_candidates');
            }
            $placementId = $this->authoring->createNativePage(array(
                'contextcode' => $contextCode,
                'chapterid' => $chapterId,
                'parentid' => '',
                'insert_after' => $insertAfter,
                'contenttype' => $page['contentType'],
                'title' => trim((string) $page['title']),
                'body' => $this->safeParagraphBody($page['bodyText']),
                'language' => 'en'
            ));
            if (empty($placementId)) {
                return array('ok' => false, 'error' => 'page_insert_failed');
            }
            $created[] = $placementId;
            $insertAfter = $placementId;
        }

        return array('ok' => true, 'created' => $created);
    }

    private function validatePages($sourceText, array $pages)
    {
        if (count($pages) !== 4) { return false; }

        $normalSource = $this->normaliseWhitespace($sourceText);
        $seenTypes = array();
        $validated = array();

        foreach ($pages as $page) {
            if (!$this->validPage($page)) { return false; }

            $basis = $this->normaliseWhitespace($page['sourceBasis']);
            if ($basis === '' || mb_stripos($normalSource, $basis, 0, 'UTF-8') === false) {
                return false;
            }

            $type = (string) $page['contentType'];
            $body = trim((string) $page['bodyText']);
            if ($type === 'short_text' && mb_strlen($body, 'UTF-8') > 1000) {
                return false;
            }

            $seenTypes[$type] = true;
            $validated[] = array(
                'contentType' => $type,
                'title' => trim((string) $page['title']),
                'bodyText' => $body,
                'sourceBasis' => trim((string) $page['sourceBasis'])
            );
        }

        if (empty($seenTypes['rich_text']) || empty($seenTypes['short_text'])) {
            return false;
        }
        return $validated;
    }

    private function validPage(array $page)
    {
        return isset($page['contentType'], $page['title'], $page['bodyText'], $page['sourceBasis'])
            && in_array((string) $page['contentType'], array('rich_text', 'short_text'), true)
            && trim((string) $page['title']) !== ''
            && mb_strlen(trim((string) $page['title']), 'UTF-8') <= 255
            && trim((string) $page['bodyText']) !== ''
            && trim((string) $page['sourceBasis']) !== '';
    }

    private function safeParagraphBody($text)
    {
        $parts = preg_split('/\R{2,}/u', trim((string) $text));
        $html = array();
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') { continue; }
            $html[] = '<p>' . nl2br(htmlspecialchars($part, ENT_QUOTES, 'UTF-8')) . '</p>';
        }
        return implode("\n", $html);
    }

    private function normaliseWhitespace($text)
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $text));
    }
}
?>
