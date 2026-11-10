<?php
/**
 * Context blocks
 * 
 * Chisimba Context blocks class
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
 * @package   workgroupops
 * @author    Wesley Nitsckie <wnitsckie@uwc.ac.za>
 * @copyright 2007 Wesley Nitsckie
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id: block_browsecontext_class_inc.php 11989 2008-12-29 22:27:44Z charlvn $
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
/** Legacy service callers receive only currently authorised file links. */
class workgroupops extends ChisimbaObject
{
    public function showFiles($workgroupId) {
        $service=$this->getObject('sharingservice','workgroup');
        $e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
        $html='<ul>';
        foreach($service->visibleFiles($workgroupId) as $row) {
            if(!$row['file'])continue;
            $url=html_entity_decode($this->uri(['action'=>'file','id'=>$row['fileid'],'filename'=>$row['file']['filename']],'filemanager'),ENT_QUOTES,'UTF-8');
            $html.='<li><a href="'.$e($url).'">'.$e($row['title']).'</a></li>';
        }
        return $html.'</ul>';
    }
}
