<?php
/**
 *
 * Streams class to build the Cortado applet
 *
 * Streams is a module to allow for the streaming of audio and video to a
 * web browser using the Cortado streaming applet for Ogg formats from
 * Flumotion at http://www.flumotion.net/cortado/
 *
 * Cortado currently include Java decoders for Ogg Theora, Ogg Vorbis,
 * Mulaw audio, MJPEG and Fluendo's own Smoke codec.
 *
 * You can get the applet source from
 *    http://www.flumotion.net/src/cortado/
 * under GPL.
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
 * @package   twitter
 * @author    Derek Keats _EMAIL
 * @copyright 2007 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id: tweetbox_class_inc.php 8227 2008-03-27 20:05:32Z dkeats $
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
*
 * Streams class to build the Cortado applet
*
* @author Derek Keats
* @package streams
*
*/
class streamapplet extends ChisimbaObject
{
    /** Historical class name retained for the paused module's controller. */
    public function show()
    {
        return $this->getObject('nativeplayer', 'files')->video(
            (string) $this->getParam('url', ''),
            $this->getParam('width', 320), $this->getParam('height', 240)
        );
    }
}
