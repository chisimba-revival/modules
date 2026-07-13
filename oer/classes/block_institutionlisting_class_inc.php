<?php

/**
 * renders list of institutions
 *
 * @author davidwaf
 */
class block_institutionlisting extends ChisimbaObject {

    function init() {
        $this->title = "";
    }

    function show() {
        $institutionManager = $this->getObject("institutionmanager", "oer");
        $message=$this->configData;
        return $institutionManager->getAllInstitutions($message);
    }

}

?>
