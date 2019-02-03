<?php

//  Copyright (C) 2006-2008 Meertens Instituut / KNAW
//  Copyright (C) 2019 Jan Pieter Kunst
//
//  The following code is a derivative work of the code from the Meertens Kaart module.
//
//  Licensed under the LGPL for compatibility with PEAR's XML_SVG package
//
//  This library is free software; you can redistribute it and/or
//  modify it under the terms of the GNU Lesser General Public
//  License as published by the Free Software Foundation; either
//  version 2.1 of the License, or (at your option) any later version.
//
//  This library is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//  Lesser General Public License for more details.

namespace JanPieterK\GemeenteKaart\Output;

class XML_SVG_Fragment extends \XML_SVG_Element
{

    public function printElement()
    {
        echo '<svg';
        $this->printParams('id', 'width', 'height', 'x', 'y', 'viewBox', 'style', 'preserveAspectRatio', 'onload');
        echo ' xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">' . "\n";
        parent::printElement();
        echo "</svg>\n";
    }

    public function bufferObject()
    {
        ob_start();
        $this->printElement();
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}
