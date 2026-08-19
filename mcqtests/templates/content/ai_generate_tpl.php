<?php
/**
 * Source-text form for AI-assisted MCQ generation.
 *
 * @category  Chisimba
 * @package   mcqtests
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die(); }
$this->setLayoutTemplate('mcqtests_layout_tpl.php');

$esc = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$text = isset($aiText) && is_array($aiText) ? $aiText : array();
$test = isset($aiTest) && is_array($aiTest) ? $aiTest : array();
$source = isset($aiSourceText) ? (string) $aiSourceText : '';
$error = isset($aiError) ? (string) $aiError : '';
?>
<section class="mcq-ai-generate">
    <h1><?php echo $esc($text['title'] ?? ''); ?></h1>
    <p><?php echo $esc($text['test'] ?? ''); ?>: <strong><?php echo $esc($test['name'] ?? ''); ?></strong></p>
    <p><?php echo $esc($text['intro'] ?? ''); ?></p>
    <p><strong><?php echo $esc($text['grounding'] ?? ''); ?></strong></p>

    <?php if ($error !== ''): ?>
        <p class="error"><?php echo $esc($text['error_' . $error] ?? ($text['error_provider'] ?? '')); ?></p>
    <?php endif; ?>

    <form method="post" action="index.php?module=mcqtests&amp;action=aigeneratequestions">
        <input type="hidden" name="id" value="<?php echo $esc($test['id'] ?? ''); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $esc($aiToken ?? ''); ?>">
        <label for="mcq-ai-source"><strong><?php echo $esc($text['source'] ?? ''); ?></strong></label><br>
        <textarea id="mcq-ai-source" name="source_text" rows="18" style="width:100%;max-width:70rem" required><?php echo $esc($source); ?></textarea>
        <p>
            <button type="submit"><?php echo $esc($text['generate'] ?? ''); ?></button>
            <a href="index.php?module=mcqtests&amp;action=view&amp;id=<?php echo $esc($test['id'] ?? ''); ?>"><?php echo $esc($text['cancel'] ?? ''); ?></a>
        </p>
    </form>
</section>
