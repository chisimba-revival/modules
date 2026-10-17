<?php
/** Native numerical-question authoring. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class numerical_question extends ChisimbaObject {
    public function numericalQForm($testid=null, $data=null, $edit=false, $questionId=null) {
        $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $lang = $this->getObject('language', 'language');
        $t = fn($key) => $e($lang->languageText($key, 'mcqtests'));
        if (isset($data[0])) $data = $data[0];
        $data = is_array($data) ? $data : array();
        $answers = $questionId ? $this->getObject('dbquestion_numerical')->getAnswers($questionId) : array();
        $options = $questionId ? $this->getObject('dbnumericalunitsoptions')->getNumericalOptions($questionId) : array();
        $units = $questionId ? $this->getObject('dbnumericalunits')->getNumericalUnits($questionId) : array();
        $options = $options[0] ?? array();
        $unit = $units[0]['unit'] ?? $units['unit'] ?? '';
        $draft = $this->getSession('mcq_numerical_draft_' . $testid . '_' . $questionId, null);
        $value = fn($name, $default='') => is_array($draft) ? ($draft[$name] ?? $default) : $default;
        $token = $this->getObject('nativeauthwebcomposition', 'security')->build()['csrf']->issue('mcq_numerical_' . hash('sha256', $testid . '|' . (string)$questionId));
        $url = $this->uri(array('action'=>'addnumericalquestion','id'=>$testid,'questionId'=>$questionId,'edit'=>$edit?'true':''));
        $html = '<section class="chisimba-form-card chisimba-form-card--wide">' . $this->getObject('contextualhelp','help')->show('mcqtests','numerical',true);
        if (is_array($draft)) $html .= '<p class="chisimba-notice" role="status">' . $t('mod_mcqtests_numerical_retry') . '</p>';
        $html .= '<form method="post" action="' . $e($url) . '"><input type="hidden" name="csrf_token" value="' . $e($token) . '">';
        $input = function($name, $label, $current, $type='text', $extra='') use ($e) {
            return '<div class="chisimba-form-field"><label for="numerical-' . $name . '">' . $label . '</label><input id="numerical-' . $name . '" name="' . $name . '" type="' . $type . '" value="' . $e($current) . '" ' . $extra . '></div>';
        };
        $html .= $input('qName', $t('mod_mcqtests_questionname'), $value('qName', $data['name'] ?? ''), 'text', 'maxlength="250" required');
        foreach (array('qText'=>array('mod_mcqtests_questiontext','question'), 'calcqgenfeedback'=>array('mod_mcqtests_generalfeedback','generalfeedback')) as $name=>$spec) {
            $editor = $this->newObject('htmlarea','htmlelements'); $editor->name=$name; $editor->height='180px'; $editor->setContent($value($name, $data[$spec[1]] ?? ''));
            $html .= '<div class="chisimba-form-field"><label>' . $t($spec[0]) . '</label>' . $editor->show() . '</div>';
        }
        $html .= $input('qMark', $t('mod_mcqtest_marklabel'), $value('qMark', $data['mark'] ?? 1), 'number', 'min="0" step="any" required');
        $html .= $input('qPenalty', $t('mod_mcqtest_penaltylabel'), $value('qPenalty', $data['penalty'] ?? 0), 'number', 'min="0" max="1" step="any" required');
        for ($i=1; $i<=3; $i++) {
            $html .= '<fieldset><legend>' . $t('mod_mcqtests_answer') . ' ' . $i . '</legend>';
            $html .= $input('aNumerical'.$i, $t('mod_mcqtests_answer'), $value('aNumerical'.$i, $answers[$i-1]['answer'] ?? ''), 'number', 'step="any"' . ($i===1?' required':''));
            $html .= $input('mark_'.$i, $t('mod_mcqtests_mark'), $value('mark_'.$i, $answers[$i-1]['mark'] ?? 0), 'number', 'min="0" step="any"');
            $html .= '</fieldset>';
        }
        $marked = $value('unitmode', ($options['unitgradingtype'] ?? 'yes')==='yes'?'marked':'unmarked') === 'marked';
        $html .= '<fieldset><legend>' . $t('mod_mcqtests_unit') . '</legend><label><input type="radio" name="unitmode" value="marked"' . ($marked?' checked':'') . '> ' . $t('mod_mcqtests_numericalanswer') . '</label><label><input type="radio" name="unitmode" value="unmarked"' . (!$marked?' checked':'') . '> ' . $t('mod_mcqtests_onlynumericalanswer') . '</label>';
        $html .= $input('aUnit', $t('mod_mcqtests_unit'), $value('aUnit',$unit));
        $html .= $input('penaltyUnit', $t('mod_mcqtest_penaltylabel'), $value('penaltyUnit',$options['unitpenalty'] ?? 0), 'number', 'min="0" step="any"');
        $html .= '<fieldset><legend>' . $t('mod_mcqtests_displayunit') . '</legend>';
        foreach (array('yes'=>'word_yes','no'=>'word_no') as $v=>$label) $html .= '<label><input type="radio" name="dispUnit" value="'.$v.'"'.($value('dispUnit',$options['showunits']??'no')===$v?' checked':'').'> '.$e($lang->languageText($label,'system')).'</label>';
        $html .= '</fieldset>';
        $editor=$this->newObject('htmlarea','htmlelements');$editor->name='instructions';$editor->height='140px';$editor->setContent($value('instructions',$options['instructions']??''));
        $html .= '<div class="chisimba-form-field"><label>' . $t('mod_mcqtests_instructions') . '</label>' . $editor->show() . '</div></fieldset><div class="chisimba-form-actions"><button type="submit" class="button">' . $e($lang->languageText('word_save','system')) . '</button><a class="button chisimba-button-secondary" href="' . $e($this->uri(array('action'=>'view2','id'=>$testid))) . '">' . $e($lang->languageText('word_cancel','system')) . '</a></div></form></section>';
        return $html;
    }
}
