<?php

/**
 * Section navigator
 *
 * @author davidwaf
 */
class block_sectionnavigator extends ChisimbaObject {

    function init() {
        $this->title = "";
    }

    function show() {
        $sectionManager = $this->getObject("sectionmanager", "oer");
        $data = explode("|", $this->configData);
        $productId=$data[0];
        $nodeType=$data[1];
        $showThumbNail=$data[2];
        return $sectionManager->buildSectionsTree($productId,"", $showThumbNail);
    }

}

?>
