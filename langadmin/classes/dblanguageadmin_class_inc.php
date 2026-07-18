<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of exporter_class_inc
 *
 * @author davidwaf
 */
class dblanguageadmin extends dbtable {
    
    function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler'){
        parent::init('tbl_languagetext');
    }
    
    function exportLangItems(){
        
    }
}

?>
