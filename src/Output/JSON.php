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
 * Class to create the GeoJSON version of a map
 */
class JSON extends Image
{

    /**
     * @var array array containing the points to be drawn on the map
     */
    private $map_array = array();
    /**
     * @var array array containing the links for the series of points
     */
    private $links = array();
    /**
     * @var array array containing alternate texts for the municipalities
     */
    private $tooltips = array();
    /**
     * @var array array containing the different features (points, polygons) of the map
     */
    private $features = array();
    /**
     * @var array
     */
    private $highlighted = array();


    /**
     * @param array array with parameters for map construction
     */
    public function __construct($parameters)
    {
        parent::__construct($parameters);

        $map_definitions = $parameters['map_definitions'];
        if (isset($parameters['highlighted'])) {
            $this->highlighted = $parameters['highlighted'];
        }
        if (array_key_exists('tooltips', $parameters)) {
            $this->tooltips = $parameters['tooltips'];
        }
        if (isset($parameters['map_array'])) {
            $this->map_array = $parameters['map_array'];
        }
        if (isset($parameters['links'])) {
            $this->links = $parameters['links'];
        }
        $this->drawBasemap($map_definitions, $this->highlighted, $this->links, $this->tooltips);
    }

    /**
     * @param $path_id
     * @param $path_data
     * @param $path_type
     * @param $map_definitions
     * @param $highlighted
     * @param $links
     * @param $tooltips
     */
    protected function drawPath($path_id, $path_data, $path_type, $map_definitions, $highlighted, $links, $tooltips)
    {
        $coords = $path_data['coords'];
        if (isset($path_data['name'])) {
            $name = $path_data['name'];
        } else {
            $name = null;
        }

        $svg_style = $map_definitions[$path_type]['svg_path_style'];
        $json_geometry_type = $map_definitions[$path_type]['json_geometry_type'];

        $highlightedpath = false;
        $feature = array();
        $feature['type'] = 'Feature';
        $feature['properties'] = array();
        // tooltips overrule map_names
        if (array_key_exists($path_id, $tooltips)) {
            $feature['properties']['name'] = $tooltips[$path_id];
        } elseif (!is_null($name)) {
            $feature['properties']['name'] = $name;
        }
        $feature['properties']['id'] = $path_id;
        $feature['properties']['style'] = $this->parseSVGstyle($svg_style);
        if (array_key_exists($path_id, $highlighted)) {
            $feature['properties']['style']['fill'] = $highlighted[$path_id];
            $highlightedpath = true;
        }

        if (!empty($this->link)) {
            if (!$this->linkhighlightedonly || ($this->linkhighlightedonly && $highlightedpath)) {
                $feature['properties']['href'] = $this->escapeJSString(sprintf($this->link, $path_id));
                if (!empty($this->target)) {
                    $feature['target'] = $this->escapeJSString($this->target);
                }
            }
        }

        if (array_key_exists($path_id, $links)) {
            foreach ($links[$path_id] as $key => $value) {
                $feature['properties'][$key] = $this->escapeJSString($value);
            }
        }

        $feature['geometry'] = array();
        if (is_array($coords[0])) {
            $coordinates = array();
            $feature['geometry']['type'] = 'MultiPolygon';
            foreach ($coords as $subcoords) {
                $coordinates[] = array($this->createCoordinates($subcoords));
            }
        } else {
            $feature['geometry']['type'] = $json_geometry_type;
            if ($feature['geometry']['type'] == 'Polygon') {
                $coordinates = array($this->createCoordinates($coords));
            } else {
                $coordinates = $this->createCoordinates($coords);
            }
        }
        $feature['geometry']['coordinates'] = $coordinates;
        $this->features[] = $feature;
    }

    /**
     * @param $coords
     *
     * @return array
     */
    private function createCoordinates($coords)
    {
        $coordinates = array();
        while ($coords) {
            $x = array_shift($coords);
            $y = array_shift($coords);
            list($noorderbreedte, $oosterlengte) = Kaart::rd2latlong($x, $y);
            $coordinates[] = array($oosterlengte, $noorderbreedte);
        }
        return $coordinates;
    }


    /**
     * @param string string with SVG "style" attribute
     *
     * @return array array with three members describing the style
     */
    private function parseSVGstyle($style)
    {
        $fill = $stroke = $stroke_width = '';

        if (preg_match('/fill:\s*?(.+?);/', $style, $matches)) {
            $fill = $matches[1];
        }
        if (preg_match('/stroke:\s*?(.+?);/', $style, $matches)) {
            $stroke = $matches[1];
        }
        if (preg_match('/stroke-width:\s*?(.+?);/', $style, $matches)) {
            $stroke_width = $matches[1];
        }
        return array('fill' => $fill, 'stroke' => $stroke, 'stroke-width' => $stroke_width);
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        $geojson = array('type' => 'FeatureCollection');
        $geojson['features'] = $this->features;
        if (!empty($this->map_copyright_strings)) {
            $geojson['metadata']['copyright'] = array();
            foreach ($this->map_copyright_strings as $string) {
                $geojson['metadata']['copyright'][] = $string;
            }
        }

        $data = json_encode($geojson);

        return $data;
    }

    public function drawSymbol($coordinaten, $kloeke_nr, $plaatsnaam, $symbol, $size, $style, $link_array, $rd = true)
    {
    }
}
