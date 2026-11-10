<?php
/**
 * Methods to track activities in the Chisimba framework
 * into the Chisimba framework
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
 * @package   activityops
 * @author    Wesley Nitsckie
 * @copyright 2008 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @link      http://avoir.uwc.ac.za
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
 * Class to supply an easy API for use from this module or even other modules.
 * @author Wesley Nitsckie
 * @package turnitin
 */
class activityops extends ChisimbaObject
{
    private $store;
    private $config;
    public function init()
    {
        $this->store = $this->getObject('activitydb','activitystreamer');
        $this->config = $this->getObject('dbsysconfig','sysconfig');
    }
    /** Recording is opt-in; installing the browser must not begin collecting events. */
    public function postmade($notification)
    {
        if (!in_array(strtolower((string)$this->config->getValue('ENABLE_RECORDING','activitystreamer')), ['true','1'], true)) return false;
        return $this->store->insertPost($notification);
    }
    /** Old demonstration feed publishing is retired; no external hub is contacted. */
    public function createFeeds($notification) { return false; }
}
