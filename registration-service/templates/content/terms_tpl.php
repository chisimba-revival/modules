<?php
$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = fn($key) => $this->objLanguage->languageText(
    'mod_registration_service_' . $key,
    'registration-service'
);
$policy = is_array($registrationPolicyContent ?? null)
    ? $registrationPolicyContent
    : array();
?>
<main class="chisimba-workspace registration-service registration-policy" aria-labelledby="terms-title">
    <header class="registration-policy__header">
        <p class="chisimba-eyebrow"><?php echo $e($t('policy_eyebrow')); ?></p>
        <h1 id="terms-title"><?php echo $e($t('terms_title')); ?></h1>
        <p class="registration-policy__summary"><?php echo $e($t('policy_summary')); ?></p>
        <p class="registration-policy__version"><?php echo $e($t('terms_version')); ?>: <?php echo $e($registrationPolicyVersion ?? ''); ?></p>
    </header>

    <?php foreach (array('terms', 'privacy') as $documentKey): ?>
        <?php $document = $policy[$documentKey] ?? array(); ?>
        <article class="chisimba-card registration-policy__document" aria-labelledby="policy-<?php echo $e($documentKey); ?>">
            <h2 id="policy-<?php echo $e($documentKey); ?>"><?php echo $e($document['title'] ?? ''); ?></h2>
            <p class="registration-policy__introduction"><?php echo $e($document['introduction'] ?? ''); ?></p>
            <?php foreach (($document['sections'] ?? array()) as $section): ?>
                <section class="registration-policy__section">
                    <h3><?php echo $e($section['heading'] ?? ''); ?></h3>
                    <?php foreach (($section['paragraphs'] ?? array()) as $paragraph): ?>
                        <p><?php echo $e($paragraph); ?></p>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        </article>
    <?php endforeach; ?>

    <div class="chisimba-form-actions">
        <a class="button chisimba-button-secondary" href="<?php echo $e(html_entity_decode($this->uri(array(), 'registration-service'), ENT_QUOTES, 'UTF-8')); ?>"><?php echo $e($t('back_to_registration')); ?></a>
    </div>
</main>
