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
 * XML_SVG_Use
 *
 * @package XML_SVG
 */
class XML_SVG_Use extends XML_SVG_Element 
{

    var $_symbol;

    function __construct($symbol, $params = array())
    {
        parent::__construct($params);
        $this->_symbol = $symbol;
    }

    function printElement()
    {
        echo '<use xlink:href="#' . $this->_symbol . '"/>';
    }

}
