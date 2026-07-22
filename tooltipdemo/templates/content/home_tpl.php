<?php
/**
 * Native Chisimba tooltip demonstration.
 *
 * This template formerly demonstrated ExtJS tooltips. It now exercises the
 * Chisimba-native ui/tooltip component and contains no ExtJS dependency.
 *
 * @category Chisimba
 * @package  tooltipdemo
 * @author   Derek Keats
 */

$tooltip = $this->getObject('tooltip', 'ui');

$tooltip->setId('tooltipdemo-native-example')
    ->setTrigger(
        '<button type="button" class="chisimba-ui-button">'
        . 'Focus or point here'
        . '</button>'
    )
    ->setText(
        'This tooltip is rendered by the Chisimba UI component layer '
        . 'without ExtJS.'
    );

echo '<h1>Native tooltip demonstration</h1>';
echo '<p>This module has been migrated completely away from ExtJS.</p>';
echo $tooltip->show();
