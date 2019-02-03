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
 * XML_SVG_Marker
 *
 * @package XML_SVG
 */
class XML_SVG_Marker extends XML_SVG_Element 
{

    var $_refX;
    var $_refY;
    var $_markerUnits;
    var $_markerWidth;
    var $_markerHeight;
    var $_orient;

    function printElement()
    {
        echo '<marker';
        $this->printParams('id', 'refX', 'refY', 'markerUnits',
                           'markerWidth', 'markerHeight', 'orient');
        if (is_array($this->_elements)) { // Print children, start and end tag.
            print(">\n");
            parent::printElement();
            print("</marker>\n");
        } else {
            print("/>\n");
        }
    }

    function setShape($refX = '', $refY = '', $markerUnits = '',
                      $markerWidth = '', $markerHeight = '', $orient = '')
    {
        $this->_refX = $refX;
        $this->_refY  = $refY;
        $this->_markerUnits = $markerUnits;
        $this->_markerWidth = $markerWidth;
        $this->_markerHeight = $markerHeight;
        $this->_orient = $orient;
    }

}

