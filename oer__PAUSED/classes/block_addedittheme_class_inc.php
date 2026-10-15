<?php

/**
 * creates a gui for a new umbrella theme
 *
 * @author davidwaf
 */
class block_addedittheme extends ChisimbaObject {

    function init() {
        
    }

    function show() {
        $objThemeManager=$this->getObject("thememanager", "oer");
        return $objThemeManager->createAddEditThemeForm();
    }

}

?>
