<?php
/**
 *
 * User Interface code for CMS blocks
 *
 * User Interface code for CMS blocks. It builds the reusable blocks
 * that are new additions to the CMS module.
 *
 * PHP version 5
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the
 * Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @category  Chisimba
 * @package   slate
 * @author    Derek Keats derek@dkeats.com
 * @copyright 2007 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   0.001
 * @link      http://www.chisimba.com
 *
 */

// security check - must be included in all scripts
if (!
/**
 * The $GLOBALS is an array used to control access to certain constants.
 * Here it is used to check if the file is opening in engine, if not it
 * stops the file from running.
 *
 * @global entry point $GLOBALS['kewl_entry_point_run']
 * @name   $kewl_entry_point_run
 *
 */
$GLOBALS['kewl_entry_point_run'])
{
        die("You cannot view this page directly");
}
// end security check

/**
*
 * User Interface code for CMS blocks
 *
 * User Interface code for CMS blocks. It builds the reusable blocks
 * that are new additions to the CMS module.
*
* @package   slate
* @author    Derek Keats derek@dkeats.com
*
*/
class cmsblockui extends ChisimbaObject
{

    /**
    *
    * @var string $objLanguage String object property for holding the
    * language object
    * @access public
    *
    */
    public $objLanguage;
    public $objUser;

    /**
    *
    * Constructor for the slate UI object
    *
    * @access public
    * @return VOID
    *
    */
    public function init()
    {
        $this->objLanguage = $this->getObject('language', 'language');
        $this->objUser = $this->getObject('user', 'security');
    }
    
    
    /**
     *
     * Render a CMS item based on it's id value
     * 
     * @return string/boolean The rendered item or FALSE
     * @access public
     * 
     */
    public function showItem()
    {
        $id = $this->getParam('cmsid', FALSE);
        if ($id) {
            // Check security
            $objSecurity = $this->getObject('dbsecurity', 'cmsadmin');
            if (!$objSecurity->isContentPublic($id)) {
                return FALSE;
            } else {
                $objContent = $this->getObject('dbcontent', 'cmsadmin');
                $page = $objContent->getContentPageFiltered($id);
                $bbcode = $this->getObject('washout', 'utilities');
                $title = '<h3 class="cmstitle">' . $page['title'] . '</h3>';
                $summary = '<div class="cmssummary">' . $page['introtext'] . '</div>';
                $body = $page['body'];
                $body = $bbcode->parseText($body);
                $cclic = $page['post_lic'];
                $cclic = $this->showLicense($cclic);
                $ret = $title . $summary . $body . $cclic;
                return $ret;
            }
        } else {
            return FALSE;
        }
    }
    
    /**
     *
     * Turn CC license into its representative icon
     * 
     * @param string $cclic
     * @return type 
     */
    private function showLicense($cclic) {
            //get the lic that matches from the db
            $objCC = $this->getObject('displaylicense', 'creativecommons');
            if ($cclic == '') {
                $cclic = 'copyright';
            }
            return $objCC->show($cclic);
    }
}
?>