<?php

//  Copyright (C) 2006-2008 Meertens Instituut / KNAW
//  Copyright (C) 2019 Jan Pieter Kunst
//
//  The following code is a derivative work of the code from the Meertens Kaart module.
//
//  This program is free software; you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation; either version 2 of the License, or
//  (at your option) any later version.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License along
//  with this program; if not, write to the Free Software Foundation, Inc.,
//  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

/**
 * Include directory for database connection details
 *
 * This should be a path outside the web server's document root
 */
define('KAART_SAFE_INCLUDE_PATH', dirname(__DIR__));
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . KAART_SAFE_INCLUDE_PATH);

/**
 * Include directory for .ini files with map information
 *
 * Default is the PEAR data directory
 */
define('KAART_DATADIR', KAART_SAFE_INCLUDE_PATH . '/ini');
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . KAART_DATADIR);


/**
 * Include directory for files with coordinates
 */
define('KAART_COORDSDIR', KAART_SAFE_INCLUDE_PATH . '/coords');

/**
 * JavaScript for SVG maps to show 'onmouseover' placename and Kloeke code of _symbols on the map
 */
define('KAART_ONMOUSEOVER_ECMASCRIPT', <<<'TAG'

var svgdoc;

function init(event) {
	svgdoc = event.target.ownerDocument;
}

function ShowTooltip(txt) {
	var tttelem;
	tttelem=svgdoc.getElementById("ttt");
	tttelem.childNodes.item(0).data = txt;
	tttelem.setAttribute("display", "inherit");
}

function HideTooltip() {
	var tttelem;
	tttelem=svgdoc.getElementById("ttt");
	tttelem.childNodes.item(0).data = "";
}

TAG
);

/**
 * Should be Truetype font that can be used by the GD Library
 */
define('KAART_BITMAP_DEFAULTFONT', KAART_SAFE_INCLUDE_PATH . '/font/luxisr.ttf');


/**
 * Should be Truetype font that can be used by the GD Library
 */
define('KAART_BITMAP_TITLEFONT', KAART_SAFE_INCLUDE_PATH . '/font/luxisb.ttf');
