<?php
/** Accessible long-form essay authoring form using shared skin primitives. @package essayadmin @author Derek Keats */
$esc = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$record = !empty($data[0]) ? $data[0] : array();
$essayId = isset($record['id']) ? (string) $record['id'] : '';
$icons = $this->getObject('iconservice', 'ui');
$notesEditor = $this->newObject('htmlarea', 'htmlelements');
$notesEditor->name = 'notes';
$notesEditor->value = isset($record['notes']) ? $record['notes'] : '';
$modelEditor = $this->newObject('htmlarea', 'htmlelements');
$modelEditor->name = 'model_essay';
$modelEditor->value = isset($record['model_essay']) ? $record['model_essay'] : '';
$rubricTitle = !empty($defaultRubric['title']) ? $defaultRubric['title'] : 'Default essay rubric';
?>
<main class="chisimba-workspace">
  <p>Create the learner task and the reference material used for consistent marking.</p>
  <form class="chisimba-form chisimba-form--wide" method="post" action="<?php echo $this->uriForHtmlAttribute(array('action'=>'saveessay'), 'essayadmin'); ?>">
    <input type="hidden" name="id" value="<?php echo $esc($topicid); ?>">
    <input type="hidden" name="essay" value="<?php echo $esc($essayId); ?>">
    <div class="chisimba-form-field">
      <label for="essay-title">Essay title or question</label>
      <input id="essay-title" class="chisimba-input" type="text" name="essaytopic" value="<?php echo $esc(isset($record['topic']) ? $record['topic'] : ''); ?>" required>
    </div>
    <section aria-labelledby="essay-guidance-heading">
      <h2 id="essay-guidance-heading" class="chisimba-section-heading">Notes and guidance for learners</h2>
      <p class="chisimba-field-help">Give the instructions, scope and expectations learners need to write the essay.</p>
      <?php echo $notesEditor->show(); ?>
    </section>
    <section aria-labelledby="essay-model-heading">
      <h2 id="essay-model-heading" class="chisimba-section-heading">Model essay</h2>
      <p class="chisimba-field-help">Optional lecturer-only reference for marking and future AI suggestions. Learners will not see it.</p>
      <?php echo $modelEditor->show(); ?>
    </section>
    <section class="chisimba-details" aria-labelledby="essay-rubric-heading">
      <h2 id="essay-rubric-heading" class="chisimba-section-heading">Marking rubric</h2>
      <p><strong><?php echo $esc($rubricTitle); ?></strong></p>
      <p class="chisimba-field-help">This protected default rubric will be used unless a customised essay rubric is attached later.</p>
    </section>
    <div class="chisimba-form-actions">
      <button class="button" type="submit"><?php echo $icons->render('save', array('decorative'=>true, 'class'=>'chisimba-action-icon')); ?><span>Save essay</span></button>
      <a class="button chisimba-button-secondary" href="<?php echo $this->uriForHtmlAttribute(array('action'=>'view', 'id'=>$topicid), 'essayadmin'); ?>"><?php echo $icons->render('x', array('decorative'=>true, 'class'=>'chisimba-action-icon')); ?><span>Cancel</span></a>
    </div>
  </form>
</main>
