<?php
$e=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
if(empty($sitepagesMissing))foreach($this->getObject('pagemetadata','ui')->build($sitepagesPage['title'],$sitepagesPage['body_html'],($sitepagesPage['slug']==='home'?[]:['module'=>'sitepages','action'=>'view','slug'=>$sitepagesPage['slug']])) as $key=>$value)$this->setVar($key,$value);
?>
<main class="chisimba-form-page">
<?php if(!empty($sitepagesMissing)): ?>
    <section class="chisimba-card"><h1><?= $e($sitepagesMissing) ?></h1></section>
<?php else: ?>
    <article class="chisimba-form-card chisimba-form-card--wide">
        <header class="chisimba-page-heading">
            <h1><?= $e($sitepagesPage['title']) ?></h1>
            <?php if(!empty($sitepagesCanEdit)):
                $editLabel=$this->getObject('language','language')->languageText('mod_sitepages_edit','sitepages').' '.$sitepagesPage['title'];
                $editUrl=html_entity_decode($this->uri(['action'=>'manage','id'=>$sitepagesPage['id']],'sitepages'),ENT_QUOTES,'UTF-8');
            ?>
                <a class="chisimba-icon-button" href="<?= $e($editUrl) ?>" aria-label="<?= $e($editLabel) ?>" title="<?= $e($editLabel) ?>"><?= $this->getObject('iconservice','ui')->render('pencil',['decorative'=>true]) ?></a>
            <?php endif; ?>
        </header>
        <div class="chisimba-flow chisimba-prose"><?= $sitepagesPage['body_html'] ?></div>
    </article>
<?php endif; ?>
</main>
