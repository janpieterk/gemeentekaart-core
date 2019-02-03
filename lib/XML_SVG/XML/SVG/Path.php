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
 * XML_SVG_Path
 *
 * @package XML_SVG
 */
class XML_SVG_Path extends XML_SVG_Element 
{

    var $_d;

    function printElement()
    {
        echo '<path';
        $this->printParams('id', 'd', 'style', 'transform');
        if (is_array($this->_elements)) {
            // Print children, start and end tag.
            print(">\n");
            parent::printElement();
            print("</path>\n");
        } else {
            // Print short tag.
            print("/>\n");
        }
    }

    function setShape($d)
    {
        $this->_d = $d;
    }

}
