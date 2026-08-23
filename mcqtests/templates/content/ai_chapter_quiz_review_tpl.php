<?php
$this->setLayoutTemplate('mcqtests_layout_tpl.php');
$e = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$u = fn($params) => html_entity_decode($this->uri($params, 'mcqtests'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
$t = fn($key, $fallback) => $this->objLanguage->languageText($key, 'mcqtests', $fallback);
?>
<section class="mcq-ai-chapter-review">
    <h1><?php echo $e($t('mod_mcqtests_ai_chapter_review', 'Review generated chapter quizzes')); ?></h1>
    <p><?php echo $e($t('mod_mcqtests_ai_chapter_review_help', 'Review the questions below. Creating them will add one inactive formative test per chapter and set a 70% chapter pass mark.')); ?></p>
    <form method="post" action="<?php echo $e($u(array('action' => 'aiinsertchapterquizzes'))); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $e($chapterQuizToken); ?>" />
        <div class="mcq-ai-chapter-review__chapters">
            <?php foreach ($chapterQuizGenerated as $chapterIndex => $chapter): ?>
                <article class="chisimba-card mcq-ai-chapter-review__chapter">
                    <h2><?php echo $e(sprintf(
                        $t('mod_mcqtests_ai_chapter_numbered_title', 'Chapter %d: %s'),
                        $chapterIndex + 1,
                        $chapter['title']
                    )); ?></h2>
                    <ol class="mcq-ai-review-questions">
                        <?php foreach ($chapter['questions'] as $questionIndex => $question): ?>
                            <li class="mcq-ai-review-question">
                                <strong class="mcq-ai-review-question__stem"><?php echo $e($question['stem']); ?></strong>
                                <ol class="mcq-ai-review-options" type="A">
                                    <?php foreach ($question['options'] as $optionIndex => $option): ?>
                                        <li<?php echo $optionIndex === $question['correctIndex'] ? ' class="mcq-ai-review-option--correct"' : ''; ?>>
                                            <span><?php echo $e($option); ?></span>
                                            <?php if ($optionIndex === $question['correctIndex']): ?>
                                                <span class="chisimba-pill chisimba-pill--success"><?php echo $e($t('mod_mcqtests_ai_correct_answer', 'Correct answer')); ?></span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                                <label class="mcq-ai-correction-flag">
                                    <input type="checkbox" name="correction_flags[]" value="<?php echo $e($chapter['chapterId'] . ':' . $questionIndex); ?>" />
                                    <?php echo $e($t('mod_mcqtests_ai_flag_correction', 'Flag for correction')); ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="chisimba-form-actions">
            <button class="button" type="submit"><?php echo $e($t('mod_mcqtests_ai_chapter_create', 'Create quizzes and add them to chapters')); ?></button>
            <a class="button chisimba-button-secondary" href="<?php echo $e($u(array('action' => 'aichapterquizzes'))); ?>"><?php echo $e($this->objLanguage->languageText('word_cancel', 'system', 'Cancel')); ?></a>
        </div>
    </form>
</section>
