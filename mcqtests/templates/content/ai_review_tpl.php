<?php
/**
 * Review page for five AI-generated grounded MCQ candidates.
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
$questions = isset($aiQuestions) && is_array($aiQuestions) ? $aiQuestions : array();
?>
<section class="mcq-ai-review">
    <h1><?php echo $esc($text['review_title'] ?? ''); ?></h1>
    <p><?php echo $esc($text['test'] ?? ''); ?>: <strong><?php echo $esc($test['name'] ?? ''); ?></strong></p>
    <p><?php echo $esc($text['review_intro'] ?? ''); ?></p>

    <?php foreach ($questions as $index => $question): ?>
        <article style="border:1px solid rgba(15,23,42,.14);border-radius:.6rem;padding:1rem;margin:0 0 1rem">
            <h2><?php echo $esc(($text['question'] ?? '') . ' ' . ($index + 1)); ?></h2>
            <p><strong><?php echo $esc($question['stem'] ?? ''); ?></strong></p>
            <ol type="A">
                <?php foreach (($question['options'] ?? array()) as $optionIndex => $option): ?>
                    <li>
                        <?php echo $esc($option); ?>
                        <?php if ((int) ($question['correctIndex'] ?? -1) === $optionIndex): ?>
                            <strong> — <?php echo $esc($text['correct'] ?? ''); ?></strong>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
            <p><strong><?php echo $esc($text['source_basis'] ?? ''); ?>:</strong>
                <?php echo $esc($question['sourceBasis'] ?? ''); ?></p>
        </article>
    <?php endforeach; ?>

    <form method="post" action="index.php?module=mcqtests&amp;action=aiinsertquestions">
        <input type="hidden" name="id" value="<?php echo $esc($test['id'] ?? ''); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $esc($aiToken ?? ''); ?>">
        <button type="submit"><?php echo $esc($text['insert'] ?? ''); ?></button>
        <a href="index.php?module=mcqtests&amp;action=aigenerate&amp;id=<?php echo $esc($test['id'] ?? ''); ?>"><?php echo $esc($text['back'] ?? ''); ?></a>
    </form>
</section>
