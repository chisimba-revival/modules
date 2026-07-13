<?php
	// security check - must be included in all scripts
	if (!$GLOBALS['kewl_entry_point_run'])
	{
		die("You cannot view this page directly");
	}
	// end security check
	
	/**
	* The class that demonstrates how to use blocks
	*
	* @author Derek Keats
	
	* 
	* $Id$
	*
	*/
	class block_wikipedia extends ChisimbaObject
	{
	    /**
	    * @var string $title The title of the block
	    */
	    public $title;
	    
	    /**
	    * @var object $objLanguage String to hold the language object
	    */
	    public $objLanguage;
	    
	    /**
	    * @var string $imgLocation The image location for the 
	    * wikipedia image
	    */
	    public $imgLocation;
	    
	    /**
	    * Standard init function to instantiate language object
	    * and create title
	    */
	    function init()
	    {
	      $objConfig = $this->getObject('altconfig', 'config');
	       $this->imgLocation = $objConfig->getsiteRoot()
	          . "modules/websearch" . "/resources/images/";
	        $this->objLanguage=&$this->getObject('language', 'language');
	        $this->title=$this->objLanguage->languageText("mod_websearch_wpsearch", "websearch");
	    }
	    
	    /**
	    * Standard block show method. It uses the search
	    * class to render the search interface
	    */
	    public function show()
	    {
	        $objSearch = & $this->getObject('search', 'websearch');
	        return $objSearch->renderWikiPediaForm();
	    }
	} #class
?>