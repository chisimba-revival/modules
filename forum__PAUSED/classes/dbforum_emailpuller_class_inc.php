<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of db_forum_emailpuller
 *
 * @author monwabisi
 */
class dbforum_emailpuller extends dbtable {
        //put your code here
        /* CHISIMBA_PHP8_FORUM_INIT_SIGNATURE: match dbTable::init() for PHP 8 compatibility. */
        function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler'){
                parent::init('tbl_forum_mailjobs');
        }
}

?>
