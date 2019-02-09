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

namespace JanPieterK\GemeenteKaart\Output;

use JanPieterK\GemeenteKaart\Kaart;

/**
 * Abstract class with properties and methods shared by more than one map format
 * @todo nadenken over class hierarchieën!! probleem: child class heeft niet alle abstract methods uit de parent nodig.
 * http://stackoverflow.com/questions/10277317/abstract-class-children-type
 * Multiple Inheritance vs. Composition
 * http://propelorm.org/blog/2011/03/03/don-t-copy-code-oh-and-inheritance-and-composition-are-bad-too.html
 */
abstract class Image
{
    protected $tablename;
    /**
     * @var string filename containing the paths for the current map
     */
    protected $kaart_paths_file;
    /**
     * @var array additional files with paths
     */
    protected $additional_paths_files = array();
    /**
     * @var string title of the map
     */
    protected $title = '';
    /**
     * @var string link to be used for all paths (or, optionally, only highlighted paths), if set
     */
    protected $link = '';
    /**
     * @var string target to be used for all links, if set
     */
    protected $target = '';
    /**
     * @var boolean whether the general link above, if set, applies to all municipalities or only highlighted ones
     */
    protected $linkhighlightedonly = false;
    /**
     * @var array copyright information for paths file(s)
     */
    protected $map_copyright_strings = array();

    abstract protected function drawPath(
        $path_id,
        $coords,
        $path_type,
        $map_definitions,
        $highlighted,
        $links,
        $tooltips
    );

    public function __construct($parameters)
    {
        $this->kaart_paths_file = $parameters['paths_file'];
        $this->additional_paths_files = $parameters['additional_paths_files'];
        $this->title = $parameters['title'];
        if (array_key_exists('link', $parameters)) {
            $this->link = $parameters['link'];
        }
        if (array_key_exists('target', $parameters)) {
            $this->target = $parameters['target'];
        }
        if (array_key_exists('linkhighlightedonly', $parameters)) {
            $this->linkhighlightedonly = $parameters['linkhighlightedonly'];
        }
    }

    /**
     * Draws the basemap, depending on which parts are requested
     *
     * @param $map_definitions
     * @param $highlighted
     * @param $links
     * @param $tooltips
     */
    protected function drawBasemap($map_definitions, $highlighted, $links, $tooltips)
    {
        $path_files = array_merge(array($this->kaart_paths_file), $this->additional_paths_files);
        foreach ($path_files as $file) {
            $map_lines_higlighted = array();
            $map_data = Kaart::getDataFromGeoJSON($file);
            $map_lines = $map_data['map_lines']['paths'];
            $this->map_copyright_strings[] = $map_data['map_copyright_string'];

            foreach (array_keys($highlighted) as $path_id) {
                if (array_key_exists($path_id, $map_lines)) {
                    $map_lines_higlighted[$path_id] = $map_lines[$path_id];
                    // remove element and re-add at end of array
                    unset($map_lines[$path_id]);
                    $map_lines[$path_id] = $map_lines_higlighted[$path_id];
                }
            }
            $this->drawPaths($map_lines, $map_definitions, $highlighted, $links, $tooltips);
        }
    }

    private function drawPaths($map_lines, $map_definitions, $highlighted, $links, $tooltips)
    {
        foreach ($map_lines as $path_id => $data) {
            $this->drawPath(
                $path_id,
                $data,
                $data['path_type'],
                $map_definitions,
                $highlighted,
                $links,
                $tooltips
            );
        }
    }
}
