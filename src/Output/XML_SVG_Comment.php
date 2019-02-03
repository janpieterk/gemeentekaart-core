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

/**
 * @package Kaart
 */
class XML_SVG_Comment
{
    private $_comment = '';

    public function __construct($comment)
    {
        $this->_comment = $comment;
    }

    public function printElement()
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        echo \XML_Util::createComment($this->_comment) . "\n";
    }
}
