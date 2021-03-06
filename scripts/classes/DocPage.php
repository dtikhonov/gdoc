<?php

class DocPage extends Item
{
    protected $_nav;

	public $_tables;
	public $_items;

    public function __construct($buf='', $src='', $start=-1)
    {
        $this->_type = HelpDocDef::TYPE_PAGE;
        $this->_src = $src;
        $this->_startLine = $start;

		if ( $buf != '' ) {
			$this->parseDoc($buf);
        }
        $this->_tables = array();
        $this->_items = array();
    }

    public function getName()
    {
        return $this->getValueInLang('NAME');
    }

    public function getItems()
    {
        return $this->_items;
    }

    public function getCont()
    {
        return $this->hasValue('CONT');
    }

    public function addContTbl($tbl)
    {
        $this->_tables[$tbl->getId()] = $tbl;
        $items = $tbl->getItems();
        foreach ($items as $item) {
            $id = $item->getId();
            if (!isset($this->_items[$id])) {
                $this->_items[$id] = $item;
            }
        }
    }

	public function setNav($prevPage, $topPage, $nextPage)
	{
        if ($this->_nav != NULL) {
            $this->showErr('Already setNav for page');
        }
        else {
            $this->_nav = array('prev' => $prevPage, 'top' => $topPage, 'next' => $nextPage);
        }
	}

	public function genDoc($db)
	{
        $id = $this->getId();
		echo ("generating $id  \n");

        $config = Config::getInstance();

        $gentool = new GenTool;

		//$buf = GenTool::getHeader($this->getDefaultValue('NAME'));//header name still show english
		$buf = GenTool::getHeader($this->getValueInLang('NAME'));
		$buf .= GenTool::getSideTree("{$id}.html");
		$buf .= '<article class="contentwrapper ls-col-3-5 clearfix">' . GenTool::getNavBar($this->_nav);

		$webbuf = '';

		if ($staticfile = $this->hasValue('STATIC')) {
			$webbuf .= $db->getStaticContent($staticfile);
		}
		else {
			$webbuf .= '<h1>' . $this->getValueInLang('NAME') . '</h1>';
			if ( $desc = $this->getValueInLang('DESCR')) {
				$webbuf .= '<section class="ls-spacer-small-bottom">' . $desc . "</section>\n";
			}

			$helpList = array();
			if ( $this->_tables )
			{
				$webbuf .= '<h2 id="top">' . GenTool::getTerm('label_toc') . '</h2>';
				$webbuf .= '<section class="toc">';

				foreach ( $this->_tables as $table )
				{
					if ( isset($table) )
						$webbuf .= $table->toTableOfContents();
				}
				$webbuf .= "</section>\n" ;

			}
			$webbuf .= '<section>';
			if ( $this->_items )
			{
				foreach ( $this->_items as $item )
				{
					if ( $item != NULL && $item->hasValue('DESCR') )
					{
						$itembuf = $item->toDoc();
						$itembuf = GenTool::translateTag($itembuf, $db);
						$webbuf .= '<div class="helpitem">' . $itembuf . "</div>\n";
					}
				}
			}
			$webbuf .= "</section>\n";
			//$webbuf = $gentool->translateTag($webbuf, $base);
		}


		$buf .= $webbuf;

		$buf .= '</article>'; // contentwrapper
		$buf .= GenTool::getFooter();

        $docname = $config->getOutputDir('docs') . $id . '.html';
		GenTool::writePage($docname, $buf);

        if ($config->getForWeb()) {
			$docname = $config->getOutputDir('web') . $id . '.html';
			$webbuf = '<div id="lsdoc_content">' . $webbuf . '</div>';
			GenTool::writePage($docname, $webbuf);
		}
	}

}

