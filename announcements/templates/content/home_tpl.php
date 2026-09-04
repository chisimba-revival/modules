<?php
$escape = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$icons = $this->getObject('iconservice', 'ui');
$dateTime = $this->getObject('dateandtime', 'utilities');
$typeLabels = array(
    'whats_new' => $this->objLanguage->languageText('mod_announcements_whatsnew', 'announcements', 'What’s new'),
    'general' => $this->objLanguage->languageText('mod_announcements_general', 'announcements', 'General announcement'),
    'service' => $this->objLanguage->languageText('mod_announcements_service', 'announcements', 'Service notice'),
);
$title = $this->objLanguage->languageText('mod_announcements_archiveheading', 'announcements', 'Announcements');
$intro = $this->objLanguage->languageText('mod_announcements_archivehelp', 'announcements', 'News, notices and information shared with you.');
$publish = $this->objLanguage->languageText('mod_announcements_postannouncement', 'announcements', 'Publish announcement');
?>
<main class="announcements-archive">
    <header class="announcements-archive__header">
        <div>
            <p class="announcements-archive__eyebrow"><?php echo $escape($this->objLanguage->languageText('mod_announcements_updateslabel', 'announcements', 'Updates')); ?></p>
            <h1><?php echo $escape($title); ?></h1>
            <p><?php echo $escape($intro); ?></p>
        </div>
        <?php if ($canPublish): ?>
            <a class="announcements-archive__publish" href="<?php echo $escape($this->uri(array('action' => 'add'))); ?>">
                <?php echo $icons->render('plus', array('decorative' => true)); ?><span><?php echo $escape($publish); ?></span>
            </a>
        <?php endif; ?>
    </header>

    <?php if (!$announcements): ?>
        <section class="announcements-archive__empty">
            <?php echo $icons->render('megaphone', array('decorative' => true)); ?>
            <h2><?php echo $escape($this->objLanguage->languageText('mod_announcements_noannouncements', 'announcements', 'There are no announcements')); ?></h2>
            <p><?php echo $escape($this->objLanguage->languageText('mod_announcements_emptyhelp', 'announcements', 'New announcements will appear here when they are published.')); ?></p>
        </section>
    <?php else: ?>
        <div class="announcements-archive__list">
            <?php foreach ($announcements as $announcement):
                $type = (string) ($announcement['announcement_type'] ?? 'general');
                $date = $announcement['publish_at'] ?: $announcement['createdon'];
                $viewUrl = $this->uri(array('action' => 'view', 'id' => $announcement['id']));
                $excerpt = trim(preg_replace('/\s+/', ' ', strip_tags((string) $announcement['message'])));
                if (mb_strlen($excerpt) > 240) $excerpt = mb_substr($excerpt, 0, 237) . '…';
            ?>
                <article class="announcements-archive__item announcements-archive__item--<?php echo $escape(str_replace('_', '-', $type)); ?>">
                    <div class="announcements-archive__icon"><?php echo $icons->render($type === 'service' ? 'triangle-alert' : ($type === 'whats_new' ? 'sparkles' : 'megaphone'), array('decorative' => true)); ?></div>
                    <div class="announcements-archive__body">
                        <div class="announcements-archive__meta">
                            <span><?php echo $escape($typeLabels[$type] ?? $typeLabels['general']); ?></span>
                            <time datetime="<?php echo $escape(substr((string) $date, 0, 10)); ?>"><?php echo $escape($dateTime->formatDate($date)); ?></time>
                        </div>
                        <h2><a href="<?php echo $escape($viewUrl); ?>"><?php echo $escape($announcement['title']); ?></a></h2>
                        <?php if ($excerpt !== ''): ?><p><?php echo $escape($excerpt); ?></p><?php endif; ?>
                        <a class="announcements-archive__read" href="<?php echo $escape($viewUrl); ?>">
                            <span><?php echo $escape($this->objLanguage->languageText('mod_announcements_readannouncement', 'announcements', 'Read announcement')); ?></span>
                            <?php echo $icons->render('arrow-right', array('decorative' => true)); ?>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($page > 0 || $hasNext): ?>
        <nav class="announcements-archive__pagination" aria-label="<?php echo $escape($this->objLanguage->languageText('mod_announcements_paginationlabel', 'announcements', 'Announcement pages')); ?>">
            <?php if ($page > 0): ?><a href="<?php echo $escape($this->uri(array('page' => $page - 1))); ?>"><?php echo $icons->render('arrow-left', array('decorative' => true)); ?><span><?php echo $escape($this->objLanguage->languageText('word_previous', 'system', 'Previous')); ?></span></a><?php endif; ?>
            <?php if ($hasNext): ?><a href="<?php echo $escape($this->uri(array('page' => $page + 1))); ?>"><span><?php echo $escape($this->objLanguage->languageText('word_next', 'system', 'Next')); ?></span><?php echo $icons->render('arrow-right', array('decorative' => true)); ?></a><?php endif; ?>
        </nav>
    <?php endif; ?>
</main>
