<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
if ($this->getObject('modules','modulecatalogue')->checkIfRegistered('help')) {
    echo $this->getObject('contextualhelp','help')->show('oembed','media-previews');
}
echo $str;
