<?php
/**
 * Package for building SVG graphics.
 *
 * Copyright 2002-2007 The Horde Project (http://www.horde.org/)
 * Small modifications for PHP 7 & composer compabibility by Jan Pieter Kunst (2019)
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package XML_SVG
 * @license http://www.fsf.org/copyleft/lgpl.html
 */

/**
 * XML_SVG_Desc
 *
 * @package XML_SVG
 */
class XML_SVG_Desc extends XML_SVG_Element
{

    var $_desc;

    function printElement()
    {
        echo '<desc';
        $this->printParams('id', 'style');
        echo '>' . $this->_desc;
        parent::printElement();
        echo "</desc>\n";
    }

}
