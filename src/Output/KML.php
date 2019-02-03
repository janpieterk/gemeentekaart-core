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
 * Class to generate the KML version of a map
 */
class KML extends Image
{

    /**
     * @var object DOM object dat de KML bevat
     * @access public
     */
    public $dom;
    /**
     * @var object DOMNode object voor het Document element
     * @access private
     */
    private $document;
    /**
     * @var array <LookAt> elementen
     * @access private
     */
    private $lookat = array();
    /**
     * @var boolean is there a basemap drawn on top of Google Maps?
     * @access private
     */
    private $basemap = false;

    private $default_polygon_style_name;
    private $default_linestyle_linewidth;
    private $default_linestyle_color;
    private $default_polystyle_color;

    /**
     * De constructor
     *
     * Maakt de grondkaart in KML
     *
     * @access public
     *
     * @param array array with parameters for map construction
     */
    public function __construct($parameters)
    {
        parent::__construct($parameters);

        $this->lookat = $parameters['kml_lookat'];
        $this->default_polygon_style_name = $parameters['kml_defaults']['default_polygon_style_name'];
        $this->default_linestyle_linewidth = $parameters['kml_defaults']['default_linestyle_linewidth'];
        $this->default_linestyle_color = $parameters['kml_defaults']['default_linestyle_color'];
        $this->default_polystyle_color = $parameters['kml_defaults']['default_polystyle_color'];
        if (isset($parameters['basemap']) && is_bool($parameters['basemap'])) {
            $this->basemap = $parameters['basemap'];
        }
        if (array_key_exists('highlighted', $parameters)) {
            $highlighted = $parameters['highlighted'];
        } else {
            $highlighted = array();
        }
        if (array_key_exists('links', $parameters)) {
            $links = $parameters['links'];
        } else {
            $links = array();
        }
        if (array_key_exists('tooltips', $parameters)) {
            $tooltips = $parameters['tooltips'];
        } else {
            $tooltips = array();
        }
        $this->dom = new \DomDocument('1.0', 'UTF-8');
        $this->dom->formatOutput = true;
        $kml = $this->dom->createElementNS('http://earth.google.com/kml/2.1', 'kml');
        $this->document = $this->dom->createElement('Document');
        $this->dom->appendChild($kml);
        $kml->appendChild($this->document);

        $this->setTitle();
        $this->setLookAt();
        if ($this->basemap) {
            $this->drawBasemapKML($highlighted, $links, $tooltips, $parameters['map_definitions']);
        }
    }


    /**
     * @param               $coordinates
     * @param               $name
     * @param               $id
     * @param               $link
     * @param               $tooltip
     * @param \DOMNode $folder
     * @param               $highlight
     * @param string $outline
     * @param string $strokefactor
     */
    private function createPolygon(
        $coordinates,
        $name,
        $id,
        $link,
        $tooltip,
        $folder,
        $highlight = '',
        $outline = '',
        $strokefactor = ''
    ) {
        $coordinatestring = '';

        while ($coordinates) {
            $x = array_shift($coordinates);
            $y = array_shift($coordinates);
            list($noorderbreedte, $oosterlengte) = Kaart::rd2latlong($x, $y);
            $coordinatestring .= "{$oosterlengte},{$noorderbreedte},0 ";
        }

        if ($coordinatestring != '') {
            $placemark = $this->dom->createElement('Placemark');

            if ($highlight == '' && $outline == '' && $strokefactor == '') {
                $highlightedpath = false;
                $styleUrl = $this->dom->createElement('styleUrl');
                $styleUrl->appendChild($this->dom->createTextNode('#' . $this->default_polygon_style_name));
                $placemark->appendChild($styleUrl);
            } else {
                if ($highlight == '') {
                    $highlightedpath = false;
                    $highlight = $this->default_polystyle_color;
                } else {
                    $highlightedpath = true;
                }
                if ($outline == '') {
                    $outline = $this->default_linestyle_color;
                }
                if ($strokefactor == '') {
                    $linewidth = $this->default_linestyle_linewidth;
                } else {
                    $linewidth = $this->default_linestyle_linewidth * $strokefactor;
                }

                $style = $this->dom->createElement('Style');
                $linestyle = $this->dom->createElement('LineStyle');
                $width = $this->dom->createElement('width');
                $color = $this->dom->createElement('color');

                $width->appendChild($this->dom->createTextNode($linewidth));
                $color->appendChild($this->dom->createTextNode($outline));
                $linestyle->appendChild($width);
                $linestyle->appendChild($color);
                $style->appendChild($linestyle);

                $polystyle = $this->dom->createElement('PolyStyle');
                $color = $this->dom->createElement('color');
                $color->appendChild($this->dom->createTextNode($highlight));
                $polystyle->appendChild($color);
                $style->appendChild($polystyle);
                $placemark->appendChild($style);
            }

            $name_elem = $this->dom->createElement('name');
            $placemark->appendChild($name_elem);
            $name_elem->appendChild($this->dom->createTextNode($name));

            $polygon = $this->dom->createElement('Polygon');
            $outerboundaryis = $this->dom->createElement('outerBoundaryIs');
            $linearring = $this->dom->createElement('LinearRing');
            $tessellate = $this->dom->createElement('tessellate');
            $tessellate->appendChild($this->dom->createTextNode('0'));
            $coordinates_elem = $this->dom->createElement('coordinates');
            $coordinates_elem->appendChild($this->dom->createTextNode($coordinatestring));
            $linearring->appendChild($tessellate);
            $linearring->appendChild($coordinates_elem);
            $outerboundaryis->appendChild($linearring);
            $polygon->appendChild($outerboundaryis);
            $placemark->appendChild($polygon);

            $infotext = array();
            if (!empty($this->link)) {
                if (!$this->linkhighlightedonly || ($this->linkhighlightedonly && $highlightedpath)) {
                    $href = sprintf($this->link, $id);
                    $tmp = '<a href="' . Kaart::escapeXMLString($href) . '"';
                    if (!empty($this->target)) {
                        $tmp .= ' target="' . Kaart::escapeXMLString($this->target) . '"';
                    }
                    $tmp .= '>' . Kaart::escapeXMLString($href) . '</a>';
                    $infotext[] = $tmp;
                }
            }
            if ($link != '') {
                $tmp = '<a href="' . Kaart::escapeXMLString($link['href']) . '"';
                if (array_key_exists('target', $link)) {
                    $tmp .= ' target="' . Kaart::escapeXMLString($link['target']) . '"';
                }
                $tmp .= '>' . Kaart::escapeXMLString($link['href']) . '</a>';
                $infotext[] = $tmp;
            }
            if ($tooltip != '') {
                $infotext[] = $tooltip;
            }

            if (!empty($infotext)) {
                $descriptiontext = '<p> ' . join('<br />', $infotext) . '</p>';
            }

            if (!empty($descriptiontext)) {
                $description = $this->dom->createElement('description');
                $cdata = $this->dom->createCDATASection($descriptiontext);
                $description->appendChild($cdata);
                $placemark->appendChild($description);
            }

            $folder->appendChild($placemark);
        }
    }

    /**
     * Zet de titel bij de kaart
     *
     * @access private
     */
    private function setTitle()
    {
        if (empty($this->title)) {
            return;
        }

        $name = $this->dom->createElement('name');
        $name->appendChild($this->dom->createTextNode($this->title));
        $this->document->appendChild($name);
    }

    /**
     * LookAt
     *
     * @access private
     */
    private function setLookAt()
    {
        $lookat = $this->dom->createElement('LookAt');

        $longitude = $this->dom->createElement('longitude');
        $longitude->appendChild($this->dom->createTextNode($this->lookat['longitude']));
        $lookat->appendChild($longitude);

        $latitude = $this->dom->createElement('latitude');
        $latitude->appendChild($this->dom->createTextNode($this->lookat['latitude']));
        $lookat->appendChild($latitude);

        $altitude = $this->dom->createElement('altitude');
        $altitude->appendChild($this->dom->createTextNode($this->lookat['altitude']));
        $lookat->appendChild($altitude);

        $range = $this->dom->createElement('range');
        $range->appendChild($this->dom->createTextNode($this->lookat['range']));
        $lookat->appendChild($range);

        $tilt = $this->dom->createElement('tilt');
        $tilt->appendChild($this->dom->createTextNode($this->lookat['tilt']));
        $lookat->appendChild($tilt);

        $heading = $this->dom->createElement('heading');
        $heading->appendChild($this->dom->createTextNode($this->lookat['heading']));
        $lookat->appendChild($heading);

        $this->document->appendChild($lookat);
    }

    /**
     * Draws the basemap
     *
     * @access private
     * @param $highlighted
     * @param $links
     * @param $tooltips
     * @param $map_definitions
     */
    private function drawBasemapKML($highlighted, $links, $tooltips, $map_definitions)
    {
        $this->setDefaultPolygonStyle();

        $map_lines_highlighted = array();

        $map_data = Kaart::getDataFromGeoJSON($this->kaart_paths_file);
        $map_lines = $map_data['map_lines']['paths'];
//    $default_path_styling = $map_data['map_lines']['styling'];
        $map_name = $map_data['map_name'];
        $this->map_copyright_strings[] = $map_data['map_copyright_string'];

        foreach (array_keys($highlighted) as $path) {
            if (array_key_exists($path, $map_lines)) {
                $map_lines_highlighted[$path] = $map_lines[$path];
                unset($map_lines[$path]);
            }
        }
        $this->createPolygons($map_lines, $map_definitions, array(), $links, $tooltips, $map_name);
        if (!empty($map_lines_highlighted)) {
            $this->createPolygons(
                $map_lines_highlighted,
                $map_definitions,
                $highlighted,
                $links,
                $tooltips,
                $map_name . ' (highlighted)'
            );
        }

        foreach ($this->additional_paths_files as $file) {
            $map_lines_highlighted = array();

            $map_data = Kaart::getDataFromGeoJSON($file);
            $map_lines = $map_data['map_lines']['paths'];
            $map_name = $map_data['map_name'];
            $this->map_copyright_strings[] = $map_data['map_copyright_string'];

            foreach (array_keys($highlighted) as $path) {
                if (array_key_exists($path, $map_lines)) {
                    $map_lines_highlighted[$path] = $map_lines[$path];
                    unset($map_lines[$path]);
                }
            }
            $this->createPolygons($map_lines, $map_definitions, array(), $links, $tooltips, $map_name);
            if (!empty($map_lines_highlighted)) {
                $this->createPolygons(
                    $map_lines_highlighted,
                    $map_definitions,
                    $highlighted,
                    $links,
                    $tooltips,
                    $map_name . ' (highlighted)'
                );
            }
        }
    }

    /**
     * @param $map_lines
     * @param $map_definitions
     * @param $highlighted
     * @param $links
     * @param $tooltips
     * @param $map_name
     */
    private function createPolygons($map_lines, $map_definitions, $highlighted, $links, $tooltips, $map_name)
    {
        $folder = $this->dom->createElement('Folder');
        $foldername = $this->dom->createElement('name');
        $foldername->appendChild($this->dom->createTextNode($map_name));
        $folder->appendChild($foldername);
        foreach ($map_lines as $path_id => $data) {
            $coordinates = $data['coords'];
            if (isset($data['name'])) {
                $name = $data['name'];
            } else {
                $name = null;
            }
            $path_type = $data['path_type'];

            $highlight = $outline = $strokefactor = $link = $tooltip = '';
            if (array_key_exists($path_id, $highlighted)) {
                if (is_string($highlighted[$path_id])) {
                    $highlight = $highlighted[$path_id];
                } elseif (is_array($highlighted[$path_id])) {
                    $highlight = $highlighted[$path_id]['fill'];
                    $outline = $highlighted[$path_id]['outline'];
                    $strokefactor = $highlighted[$path_id]['strokewidth'];
                }
            } else {
                if (array_key_exists('kml_linestyle_linewidth', $map_definitions[$path_type])) {
                    $strokefactor = $map_definitions[$path_type]['kml_linestyle_linewidth'];
                }
                if (array_key_exists('kml_linestyle_color', $map_definitions[$path_type])) {
                    $outline = $map_definitions[$path_type]['kml_linestyle_color'];
                }
                if (array_key_exists('kml_polystyle_color', $map_definitions[$path_type])) {
                    $highlight = $map_definitions[$path_type]['kml_polystyle_color'];
                }
            }
            if (array_key_exists($path_id, $links)) {
                $link = $links[$path_id];
            }
            if (array_key_exists($path_id, $tooltips)) {
                $tooltip = $tooltips[$path_id];
            }
            if (is_array($coordinates[0])) {
                $subfolder = $this->dom->createElement('Folder');
                $foldername = $this->dom->createElement('name');
                $foldername->appendChild($this->dom->createTextNode($name));
                $subfolder->appendChild($foldername);
                foreach ($coordinates as $i => $subpolygon) {
                    $counter = $i + 1;
                    $this->createPolygon(
                        $subpolygon,
                        $name . " ($counter)",
                        $path_id,
                        $link,
                        $tooltip,
                        $subfolder,
                        $highlight,
                        $outline,
                        $strokefactor
                    );
                }
                $folder->appendChild($subfolder);
            } else {
                $this->createPolygon(
                    $coordinates,
                    $name,
                    $path_id,
                    $link,
                    $tooltip,
                    $folder,
                    $highlight,
                    $outline,
                    $strokefactor
                );
            }
        }
        $this->document->appendChild($folder);
    }

    private function setDefaultPolygonStyle()
    {
        $id = $this->dom->createAttribute('id');
        $id->value = $this->default_polygon_style_name;
        $style = $this->dom->createElement('Style');
        $style->appendChild($id);
        $linestyle = $this->dom->createElement('LineStyle');
        $width = $this->dom->createElement('width');
        $color = $this->dom->createElement('color');
        $color->appendChild($this->dom->createTextNode($this->default_linestyle_color));
        $width->appendChild($this->dom->createTextNode($this->default_linestyle_linewidth));
        $linestyle->appendChild($width);
        $linestyle->appendChild($color);
        $style->appendChild($linestyle);
        $polystyle = $this->dom->createElement('PolyStyle');
        $color = $this->dom->createElement('color');
        $color->appendChild($this->dom->createTextNode($this->default_polystyle_color));
        $polystyle->appendChild($color);
        $style->appendChild($polystyle);
        $this->document->appendChild($style);
    }

    public function insertCopyrightStatement()
    {
        foreach (array_unique($this->map_copyright_strings) as $string) {
            $this->dom->appendChild($this->dom->createComment($string));
        }
    }

    // @todo kijken of dit niet beter kan
    protected function drawPath($path_id, $coords, $path_type, $map_definitions, $highlighted, $links, $tooltips)
    {
    }
}
