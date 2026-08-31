<?php
/**
 * Read-only embed renderer for the [knowmap id=...] content token.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');

/** Produces a progressively enhanced, permission-checked graph figure. */
class knowmapembedservice extends controller
{
    private $maps;

    /** Initialise the authorized map service. */
    public function init(){$this->maps=$this->getObject('knowledgemapservice');}

    /** Render a read-only map or an inert unavailable notice. */
    public function render($id){$document=$this->maps->document((string)$id,'view');if(!$document)return '<p class="chisimba-notice">This knowledge map is unavailable.</p>';$this->appendArrayVar('headerParams','<link rel="stylesheet" type="text/css" href="'.$this->getResourceUri('knowledgemap.css','knowledgemap').'?v=09" />');$this->appendArrayVar('headerParams','<script defer type="text/javascript" src="'.$this->getResourceUri('knowledgemap.js','knowledgemap').'?v=10"></script>');$title=htmlspecialchars($document['map']['title'],ENT_QUOTES,'UTF-8');$json=htmlspecialchars(json_encode($document,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE),ENT_QUOTES,'UTF-8');return '<figure class="knowmap-embed chisimba-card" data-knowmap-readonly data-knowmap-document="'.$json.'"><figcaption><strong>'.$title.'</strong><span>Read-only knowledge map</span></figcaption><div class="knowmap-embed__viewport chisimba-spatial-workspace" tabindex="0" aria-label="'.$title.' knowledge map"></div></figure>';}
}
?>
