<?php
/**
 * Layout template for the MCQ tests module.
 *
 * @category  Chisimba
 * @package   mcqtests
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
$cssLayout = &$this->newObject('csslayout', 'htmlelements');
$leftMenu = &$this->getObject('contextsidebar', 'context');
$objHead = &$this->newObject('htmlheading', 'htmlelements');
if (!isset($heading)) {
    $heading = $objLanguage->languageText('mod_mcqtests_name', 'mcqtests');
}
$objHead->str = $heading;
$objHead->type = 1;
$head = $objHead->show();
$left = $leftMenu->show();

/*
 * MCQ supplies semantic structure; colours, radii and surfaces come from the
 * active skin's shared Chisimba variables and button primitives.
 */
$this->appendArrayVar('headerParams', '<style type="text/css">
.mcq_main .mcq_questions{width:100%!important;border-collapse:separate;border-spacing:0;overflow:hidden;border:1px solid var(--chisimba-border);border-radius:var(--chisimba-radius-md,.5rem);background:var(--chisimba-surface)}
.mcq_main .mcq_questions th,.mcq_main .mcq_questions td{padding:.78rem .85rem!important;border:0;border-bottom:1px solid var(--chisimba-border);vertical-align:top;text-align:left}
.mcq_main .mcq_questions th{font-weight:700;background:var(--chisimba-surface-muted)}
.mcq_main .mcq_questions tr:last-child td{border-bottom:0}
.mcq_main .mcq_questions tr:nth-child(even) td{background:var(--chisimba-surface-subtle)}
.mcq_main .mcq_questions td:first-child{width:2.5rem;font-weight:700}
.mcq_main .mcq_questions td:nth-child(3){width:5rem;text-align:center}
.mcq_main .mcq_questions td:nth-child(4),.mcq_main .mcq_questions td:nth-child(5){width:2.5rem;text-align:center}
.mcq_main button,.mcq_main input[type="submit"],.mcq_main input[type="button"]{appearance:none;display:inline-flex;align-items:center;justify-content:center;min-height:2.45rem;padding:.48rem .9rem;border:1px solid var(--chisimba-primary);border-radius:var(--chisimba-radius-sm,.35rem);background:var(--chisimba-primary);color:var(--chisimba-text-inverse);font:inherit;font-weight:700;line-height:1.2;cursor:pointer;box-shadow:none}
.mcq_main button:hover,.mcq_main button:focus-visible,.mcq_main input[type="submit"]:hover,.mcq_main input[type="submit"]:focus-visible,.mcq_main input[type="button"]:hover,.mcq_main input[type="button"]:focus-visible{background:var(--chisimba-primary-dark);border-color:var(--chisimba-primary-dark);color:var(--chisimba-text-inverse)}
.mcq_main .chisimba-button-secondary{background:var(--chisimba-surface-muted);border-color:var(--chisimba-border);color:var(--chisimba-ink)}
.mcq_main .chisimba-button-secondary:hover,.mcq_main .chisimba-button-secondary:focus-visible{background:var(--chisimba-primary-soft);border-color:var(--chisimba-border);color:var(--chisimba-ink)}
.mcq-test-editor .chisimba-form{max-width:68rem}
.mcq-form-section{overflow:hidden;margin:0 0 1.25rem!important;border:1px solid var(--chisimba-border);border-radius:var(--chisimba-radius-md,.5rem);background:var(--chisimba-surface)}
.mcq-form-section-title{margin:0;padding:.85rem 1rem;border-bottom:1px solid var(--chisimba-border);background:var(--chisimba-surface-muted);font-size:1.05rem}
.mcq-form-section-body{padding:1rem}
.mcq-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 1.5rem}
.mcq-test-editor .chisimba-choice-group table{width:100%;border:0}
.mcq-test-editor .chisimba-choice-group td{padding:.2rem .8rem .2rem 0;border:0}
.mcq-test-editor input[type="text"],.mcq-test-editor textarea,.mcq-test-editor select{box-sizing:border-box;max-width:100%}
.mcq-test-editor input[type="text"],.mcq-test-editor textarea{width:100%}
.mcq-assessment-sheet-field p{margin:.35rem 0 0;color:var(--chisimba-text-muted)}
.mcq-duration,.mcq-lab-control,.mcq-inline-choice{display:flex;align-items:center;flex-wrap:wrap;gap:.55rem}
.mcq-duration select{width:auto}
.mcq-test-editor .chisimba-form-actions{padding:.25rem 0 1rem}
@media(max-width:48rem){.mcq-form-grid{grid-template-columns:1fr}.mcq_main .mcq_questions th,.mcq_main .mcq_questions td{padding:.65rem .55rem!important}}
</style>');

$middle = $head.$this->getContent();
$middle = "<div class='mcq_main'>$middle</div>";
$cssLayout->setLeftColumnContent($left);
$cssLayout->setMiddleColumnContent($middle);
echo $cssLayout->show();
?>