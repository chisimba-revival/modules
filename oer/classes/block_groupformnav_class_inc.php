<?php

/**
 * This creates a navigation for quick moving in between forms when creating
 * group
 *
 * @author davidwaf
 */
class block_groupformnav extends ChisimbaObject {

    function init() {
        $this->title="";
    }

    public function show() {
       $data = explode("|", $this->configData);
        $contextCode = NULL;
        $step = '1';
        if ((is_countable($data) ? count($data) : 0) == 2) {
            $contextCode = $data[0];
            $step = $data[1];
        } else if ((is_countable($data) ? count($data) : 0) == 1){
            $contextCode = $data[0];
        }
        
        $objGroupEdit = $this->getObject('groupedit', 'oer');
        return $objGroupEdit->buildGroupStepsNav($contextCode,$step);
    }

}

?>