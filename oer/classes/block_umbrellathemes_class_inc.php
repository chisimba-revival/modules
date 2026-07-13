<?php

/**
 * Lists current product themes
 *
 * @author davidwaf
 */
class block_umbrellathemes extends ChisimbaObject {

    function init() {
        $this->title="";
    }

    function show() {
        $objThemeManager = $this->getObject("thememanager", "oer");
        return $objThemeManager->createThemeListing();
    }

}

?>
