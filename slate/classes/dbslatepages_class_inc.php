<?php
/**
 *
 * Database access for slate pages
 *
 * Database access for slate pages, used to get data to edit and show the
 * slate pages.
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
 * @package   switchboard
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
 * Database access for slate pages
 *
 * Database access for slate pages, used to get data to edit and show the
 * slate pages.
 *
*
* @package   slate
* @author    Derek Keats derek@dkeats.com
*
*/
class dbslatepages extends dbtable
{

    /**
    *
    * Intialiser for the slate database connector
    * @access public
    * @return VOID
    *
    */
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        //Set the parent table to our demo table
        parent::init('tbl_slate_pages');
    }

    /**
     *
     * Get the slate pages
     *
     * @return string The text of the init_overview
     * @access public
     *
     */
    public function getPages()
    {
        return $this->getAll();
    }

    /**
     *
     * Save a link when coming from edit
     *
     * @param string $id The record id
     * @param string $page The URL for the page
     * @param string $title The title for the page
     * @param string $description The description of the page
     * @access public
     * @return boolean TRUE|FALSE
     *
     */
    public function savePage()
    {
        return $this->persistPage(true);
    }

    /**
     *
     * Save a page when coming from add
     *
     * @access public
     * @return string The id of the saved record
     *
     */
    public function addPage()
    {
        return $this->persistPage(false);
    }

    private function persistPage(bool $editing)
    {
        $id = $this->getParam('id', '');
        $page = $this->getParam('page', '');
        $values = ['page'=>$page, 'title'=>trim($this->getParam('title', '')), 'description'=>trim($this->getParam('description', ''))];
        return $this->withDatabaseLock('slate-edit', function () use ($id, $page, $values, $editing) {
            return $this->transaction(function () use ($id, $page, $values, $editing) {
                $duplicates = $this->selectPrepared('SELECT id FROM tbl_slate_pages WHERE page = ? AND id <> ?', [$page, $id === '' ? '-' : $id]);
                if ($duplicates) throw new DomainException('Page already exists');
                if ($editing) {
                    $row = $this->getPageById($id)[0] ?? null;
                    if (!$row || (string)$row['page'] !== $page) throw new DomainException('Page changed');
                    if (!$this->update('id', $id, $values)) throw new RuntimeException('Save failed');
                    return $id;
                }
                $saved = $this->insert($values + ['datecreated'=>$this->now()]);
                if (!$saved) throw new RuntimeException('Save failed');
                return $saved;
            });
        });
    }

    /**
     *
     * Retrieve a page data by its primary key, id
     *
     * @param string $id The primary key
     * @return string array An array of page data
     * @access public
     *
     */
    public function getPageById($id)
    {
        return $this->selectPrepared('SELECT * FROM tbl_slate_pages WHERE id = ?', [$id]);
    }

    /**
     *
     * Delete a slate page data
     *
     * @param string $id The primary key (id) of the page to delete
     * @return boolean
     * @access public
     * 
     */
    public function deletePage($id)
    {
        return $this->withDatabaseLock('slate-edit', function () use ($id) {
            return $this->transaction(function () use ($id) {
                $page = $this->getPageById($id)[0] ?? null;
                if (!$page) return false;
                $uri = str_replace('&amp;', '&', $this->uri(['page'=>$page['page']], 'slate'));
                $this->executePrepared('DELETE FROM tbl_slate_pageblocks WHERE pageid = ?', [md5($uri)]);
                return $this->executePrepared('DELETE FROM tbl_slate_pages WHERE id = ?', [$id]) === 1;
            });
        });
    }

    /**
     * Get a list of used pages for use by AJAX to determine
     * if a given page can be used.
     *
     * @return string array An array of used pages
     * @access public
     *
     */
    public function getTakenPages()
    {
        $stmt = "SELECT page FROM tbl_slate_pages";
        $arTaken = $this->getArray($stmt);
        $retAr = array();
        foreach ($arTaken as $key=>$value) {
            $retAr[] = $value['page'];
        }
        unset($arTaken);
        return $retAr;
    }
}
?>
