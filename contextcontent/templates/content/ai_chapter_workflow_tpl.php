<?php
/**
 * Chapter AI workflow hosted by the existing content-type picker.
 *
 * @category  Chisimba
 * @package   contextcontent
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
$esc = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$ai = $this->getObject('contextcontentaigenerator', 'contextcontent');
$stack = $this->getObject('nativeauthwebcomposition', 'security')->build();
$csrf = $stack['csrf'];
$aiMode = (string) $this->getParam('ai', 'start');
$sourceText = (string) $this->getParam('source_text', '');
$authorDirection = (string) $this->getParam('author_direction', '');
$aiError = '';
$aiPages = array();
$aiInserted = false;

$txt = function ($key) {
    return $this->objLanguage->languageText('mod_contextcontent_ai_' . $key, 'contextcontent');
};

if ($aiMode === 'generate' || $aiMode === 'insert') {
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? ''));
    $token = (string) $this->getParam('csrf_token', '');
    if ($method !== 'POST' || !$csrf->consume('contextcontent_authoring', $token)) {
        $aiError = 'request';
    } elseif ($aiMode === 'generate') {
        $result = $ai->generate($sourceText, $authorDirection);
        if (empty($result['ok'])) {
            $aiError = (($result['error'] ?? '') === 'source_too_short')
                ? 'source_too_short'
                : ((($result['error'] ?? '') === 'grounding_validation_failed') ? 'grounding' : 'provider');
        } else {
            $aiPages = $result['pages'];
            $this->setSession('contextcontent_ai_pages_' . $chapter, $aiPages);
        }
    } else {
        $aiPages = $this->getSession('contextcontent_ai_pages_' . $chapter);
        if (!is_array($aiPages) || count($aiPages) !== 4) {
            $aiError = 'expired';
        } else {
            $result = $ai->insertPages($this->contextCode, $chapter, $aiPages);
            if (empty($result['ok'])) {
                $aiError = 'insert';
            } else {
                $this->unsetSession('contextcontent_ai_pages_' . $chapter);
                $aiInserted = true;
            }
        }
    }
}
$nextToken = $csrf->issue('contextcontent_authoring');
?>
<div class="contextcontent-ai-workflow">
<h2><?php echo $esc($txt('title')); ?></h2>
<p><?php echo $esc($txt('intro')); ?></p>
<p><strong><?php echo $esc($txt('grounding')); ?></strong></p>

<?php if ($aiInserted): ?>
<p class="success"><?php echo $esc($txt('inserted')); ?></p>
<p><a class="contextcontent-canvas-action" href="<?php echo $esc(str_replace('&amp;', '&', $this->uri(array('action'=>'viewchapter', 'id'=>$chapter)))); ?>"><?php echo $esc($txt('backchapter')); ?></a></p>

<?php elseif (!empty($aiPages) && $aiError === ''): ?>
<h3><?php echo $esc($txt('review_title')); ?></h3>
<p><?php echo $esc($txt('review_intro')); ?></p>
<?php foreach ($aiPages as $index => $page): ?>
<article style="border:1px solid #d7e0e4;border-radius:12px;padding:1rem;margin:0 0 1rem">
<p><strong><?php echo $esc($txt('page') . ' ' . ($index + 1)); ?></strong> — <?php echo $esc($page['contentType'] === 'short_text' ? $txt('type_short') : $txt('type_rich')); ?></p>
<h4><?php echo $esc($page['title']); ?></h4>
<div style="white-space:pre-line"><?php echo $esc($page['bodyText']); ?></div>
<p><strong><?php echo $esc($txt('source_basis')); ?>:</strong> <?php echo $esc($page['sourceBasis']); ?></p>
</article>
<?php endforeach; ?>
<form method="post" action="index.php?module=contextcontent&amp;action=addpage&amp;chapter=<?php echo $esc($chapter); ?>&amp;ai=insert">
<input type="hidden" name="csrf_token" value="<?php echo $esc($nextToken); ?>">
<button type="submit"><?php echo $esc($txt('insert')); ?></button>
</form>

<?php else: ?>
<?php if ($aiError !== ''): ?><p class="error"><?php echo $esc($txt('error_' . $aiError)); ?></p><?php endif; ?>
<form method="post" action="index.php?module=contextcontent&amp;action=addpage&amp;chapter=<?php echo $esc($chapter); ?>&amp;ai=generate">
<input type="hidden" name="csrf_token" value="<?php echo $esc($nextToken); ?>">
<label for="contextcontent-ai-source"><strong><?php echo $esc($txt('source')); ?></strong></label><br>
<textarea id="contextcontent-ai-source" name="source_text" rows="16" style="width:100%;box-sizing:border-box" required><?php echo $esc($sourceText); ?></textarea>
<p><label for="contextcontent-ai-direction"><strong><?php echo $esc($txt('direction')); ?></strong></label><br>
<textarea id="contextcontent-ai-direction" name="author_direction" rows="4" style="width:100%;box-sizing:border-box"><?php echo $esc($authorDirection); ?></textarea></p>
<p><?php echo $esc($txt('types')); ?></p>
<p><button type="submit"><?php echo $esc($txt('generate')); ?></button></p>
</form>
<?php endif; ?>
</div>
