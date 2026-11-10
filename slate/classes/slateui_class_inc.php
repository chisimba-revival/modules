<?php
/**
 *
 * User Interface code for Slate
 *
 * User Interface code for slate. This class builds the user interface
 * elements of the slate module add/edit block.
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
 * User Interface code for Slate
 *
 * User Interface code for slate. This class builds the user interface
 * elements of the slate module add/edit block.
*
* @package   slate
* @author    Derek Keats derek@dkeats.com
*
*/
class slateui extends ChisimbaObject
{
    private string $revision = "";
    public $mode, $page, $link, $title, $description, $id, $datecreated;


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
        $this->mode = $this->getParam('mode', 'add');
        if ($this->mode == 'edit') {
            $id = $this->getParam('id', NULL);
            $this->loadData($id);
        } else {
            $this->page = NULL;
            $this->link = NULL;
            $this->title = NULL;
            $this->description = NULL;
        }
    }


    /**
     *
     * Insert an add icon for use by javacript. It will be visitble
     * when you are in edit mode, but invisible when you are in add
     * mode. After a save, it will be toggled to visible.
     *
     * @param string $mode The edit|add mode
     * @return string The rendered icon
     * @access private
     *
     */
    private function insertAddIcon($mode, $labelText=NULL)
    {
        $objIcon = $this->newObject('geticon', 'htmlelements');
        $link =  $this->uri(
           array("action" => "edit", "mode" => "add"),
           'slate');
        $addlink = new link($link);
        $objIcon->setIcon('add');
        $addlink->link = $objIcon->show();
        if ($mode == 'add') {
            $showCss = "style='visibility:hidden'";
        } else {
            $showCss = " style='visibility:show' ";
        }
        return "&nbsp; <span class='conditional_add' "
          . $showCss . ">" . $labelText . $addlink->show()
          . "</span>";
    }

    /**
     *
     * Make a heading for the form
     *
     * @return string The text of the heading
     * @access private
     *
     */
    private function makeHeading()
    {
        $this->loadClass('htmlheading','htmlelements');
        $this->loadClass('link','htmlelements');
        // Get heading based on whether it is edit or add.
        if ($this->mode == 'edit') {
            $h = $this->objLanguage->languageText(
              'mod_slate_heading_edit', 'slate', "Edit page");
        } else {
            $h = $this->objLanguage->languageText(
              'mod_slate_heading_new','slate', "New page");
        }
        // Setup and show heading.
        $header = new htmlHeading();
        $header->str = $h . $this->insertAddIcon($this->mode);
        $header->type = 2;
        return $header->show();
    }

    /**
     *
     * Show the pages that are available within existing slates.
     *
     * @return string The rendered links
     * @access public
     *
     */
    public function showPages()
    {
        $canEdit = $this->getObject('slatesecurity','slate')->canEdit();
        $objData = $this->getObject("dbslatepages", "slate");
        $arData = $objData->getPages();
        $this->loadClass('htmltable','htmlelements');
        $this->loadClass('link','htmlelements');
        //----------$this->loadClass('button','htmlelements');
        // Setup table for display layout.
        $table = $this->newObject('htmltable', 'htmlelements');
        $table->css_class = 'chisimba-table';
        $d = $this->objLanguage->languageText(
              'mod_slate_defaultpage','slate', "The default slate page, it cannot be deleted.");
        $l = $this->objLanguage->languageText(
              'mod_slate_default','slate', "DEFAULT");
        $st = $this->objLanguage->languageText(
              'mod_slate_defaulttitle','slate', "Default slate");
        $linkUrl = $this->uri(array(),'slate');
        $al = new link($linkUrl);
        $al->link = $st;
        $al->cssClass = "slate_page_link";
        $table->startRow();
        $table->addCell($al->show());
        $table->addCell($l);
        $table->addCell($d);
        if ($canEdit) {
            $table->addCell("");
            $table->addCell("");
        }
        unset($d, $al, $l, $st);
        foreach ($arData as $linkItem) {
            $id = $linkItem['id'];
            $linkPage = htmlspecialchars((string)$linkItem['page'], ENT_QUOTES, 'UTF-8');
            $linkUrl = $this->uri(array('page' => $linkPage), 'slate');
            $pageTitle = htmlspecialchars((string)$linkItem['title'], ENT_QUOTES, 'UTF-8');
            $pageDesc = htmlspecialchars((string)$linkItem['description'], ENT_QUOTES, 'UTF-8');
            $activeLink = new link($linkUrl);
            $activeLink->cssClass = "slate_page_link";
            $activeLink->link = $pageTitle;

            $description =  "<div class='slate_desc'>" . $pageDesc . "</div>";
            $table->startRow(NULL, "ROW_" . $id);
            $table->addCell($activeLink->show());
            $table->addCell($linkPage);
            $table->addCell($description);
            unset($activeLink);
            if ($canEdit) {
                $edUrl = $this->uri(array(
                    'action' => 'edit',
                    'mode' => 'edit',
                    'id' => $id
                    )
                );
                $table->addCell('<a class="chisimba-icon-button" href="'.htmlspecialchars($edUrl, ENT_QUOTES, 'UTF-8').'" aria-label="'
                    .htmlspecialchars($this->objLanguage->languageText('word_edit'), ENT_QUOTES, 'UTF-8').'">'
                    .$this->getObject('iconservice','ui')->render('pencil').'</a>');
                $table->addCell($this->getObject('rowdelete','ui')->render([
                    'url'=>$this->uri(['action'=>'delete'],'slate'), 'id'=>$id,
                    'revision'=>$objData->deleteRevision($linkItem),
                    'csrf'=>$this->getObject('slatesecurity','slate')->csrf()->issue('slate_edit'),
                    'label'=>$this->objLanguage->languageText('word_delete'),
                    'confirm'=>$this->objLanguage->languageText('mod_slate_delete_confirm','slate'),
                    'failure'=>$this->objLanguage->languageText('mod_slate_delete_failure','slate'),
                    'success'=>$this->objLanguage->languageText('mod_slate_delete_success','slate')
                ]));
            }
            $table->endRow();
        }
        if ($canEdit) {
            $ret = '<div class="slate_addlink">' . $this->insertAddIcon('showadd', "Add a page ") . "</div>";
        } else {
            $ret = NULL;
        }
        return $ret . $this->getObject('contextualhelp','help')->show('slate','editing')
            . $this->getObject('rowdelete','ui')->wrap($table->show());
    }

    /**
     *
     * Show the page navigation for display in a narrow block
     *
     * @return string  The rendered page navigation
     * @access public
     *
     */
    public function showPageNav()
    {
        $objData = $this->getObject("dbslatepages", "slate");
        $arData = $objData->getPages();
        $this->loadClass('link','htmlelements');
        $ret = "";
        foreach ($arData as $linkItem) {
            $id = $linkItem['id'];
            $linkPage = htmlspecialchars((string)$linkItem['page'], ENT_QUOTES, 'UTF-8');
            $linkUrl = $this->uri(array('page' => $linkPage), 'slate');
            $pageTitle = htmlspecialchars((string)$linkItem['title'], ENT_QUOTES, 'UTF-8');
            $pageDesc = htmlspecialchars((string)$linkItem['description'], ENT_QUOTES, 'UTF-8');
            $activeLink = new link($linkUrl);
            $activeLink->cssClass = "slate_page_link";
            $activeLink->link = $pageTitle;
            $ret .= $activeLink->show() . "<br />";
        }
        if ($this->objUser->isAdmin()) {
            $ret .= $this->insertAddIcon('showadd', "Add a page ");
        }
        return $ret;
    }


    /**
     *
     * Show the form for adding / editing a slate page
     *
     * @return string The formatted form
     * @access public
     *
     */
    public function showPageEditForm()
    {
        if (!$this->objUser->isLoggedIn() || !$this->objUser->lookupAdmin($this->objUser->userId())) return '';
        $this->appendArrayVar('headerParams', $this->getJavaScriptFile('editor.js', 'slate'));
        $this->getObject('serializevars','utilities')->languagetojs([
            'status_success'=>'mod_slate_status_success', 'status_fail'=>'mod_slate_status_fail'
        ], 'slate');
        $escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        $label = fn($key) => $escape(str_starts_with($key, 'mod_slate_') ? $this->objLanguage->languageText($key, 'slate') : $this->objLanguage->languageText($key));
        $html = '<form id="form_slatepageEditor" method="post" action="'.$escape($this->uri(['action'=>'pagesave'],'slate')).'" class="chisimba-form-card chisimba-form-card--wide">';
        $html .= $this->makeHeading();
        $html .= $this->getObject('contextualhelp','help')->show('slate','editing');
        foreach (['id'=>$this->getParam('id',''), 'mode'=>$this->mode, 'revision'=>$this->revision,
            'csrf_token'=>$this->getObject('slatesecurity','slate')->csrf()->issue('slate_edit')] as $name=>$value) {
            $html .= '<input type="hidden" name="'.$name.'" value="'.$escape($value).'">';
        }
        $html .= '<div class="chisimba-form-field"><label for="input_title">'.$label('mod_slate_pagetitle').'</label><input type="text" id="input_title" name="title" required maxlength="250" value="'.$escape($this->title).'"></div>';
        $html .= '<div class="chisimba-form-field"><label for="input_page">'.$label('mod_slate_page').'</label><input type="text" id="input_page" name="page" required inputmode="numeric" pattern="[1-9][0-9]{0,8}"'.($this->mode==='edit'?' readonly':'').' value="'.$escape($this->page).'"></div>';
        $html .= '<div class="chisimba-form-field"><label for="input_description">'.$label('mod_slate_description').'</label><textarea id="input_description" name="description" rows="4" required maxlength="10000">'.$escape($this->description).'</textarea></div>';
        $html .= '<div class="chisimba-form-actions"><button type="submit">'.$label('word_save').'</button><a href="'.$escape($this->uri(['action'=>'manage'],'slate')).'">'.$label('mod_slate_manage').'</a></div>';
        return $html.'<p id="save_results" role="status" aria-live="polite"></p></form>';
    }

    /**
     *
     * For editing, load the data according to the ID provided. It
     * loads the data into object properties.
     *
     * @param string $id The id of the record to load
     * @return boolean TRUE|FALSE
     * @access private
     *
     */
    private function loadData($id)
    {
        $objDb = $this->getObject('dbslatepages', 'slate');
        $arData = $objDb->getPageById($id);
        if (!empty($arData)) {
            $this->revision = $objDb->rowRevision($arData[0]);
            foreach ($arData[0] as $key=>$value) {
                $this->$key =  $value;
            }
            return TRUE;
        } else {
            return FALSE;
        }
    }

}
?>