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
 * Class to generate the bitmap version of a map
 */
class Bitmap extends Image
{
    /**
     * @var resource GD image resource voor bitmapversie van de kaart
     */
    public $gd_image;
    /**
     * @var float de dikte van de dunste lijnen in de bitmap (afhankelijk van de grootte)
     */
    private $bitmap_linewidth;
    /**
     * @var bool boolean die aangeeft of er een <map> met de plaatsnamen in "title" attributen gemaakt moet worden
     */
    private $interactive = false;
    /**
     * @var string HTML met de <area> elementen voor de afgebeelde plaatsen, om in <map> op te nemen
     */
    private $imagemap_areas = '';
    /**
     * @var int factor waarmee Rijksdriehoeksstelsel-coordinaten, of andere op dezelfde fijnmazigheid
     *         gebaseerde SVG-coordinaten, naar pixel-coordinaten worden omgezet. Afhankelijk van de grootte
     *         van de kaart.
     */
    private $bitmap_factor;
    /**
     * @var int de legenda begint op 1/20ste van de bovenkant van het plaatje (als er geen titel is)
     */
    private $legend_symbol_y_factor = 20;
    /**
     * @var int de legenda begint op 1/15de van de zijkant van het plaatje
     */
    private $legend_symbol_x_factor = 15;
    /**
     * @var float hulpgetal om de x-coordinaat van de legendatekst uit de fontgrootte af te leiden
     */
    private $legend_text_x_factor = 1.5;
    /**
     * @var float hulpgetal om de initi�le y-coordinaat van de legendatekst uit de fontgrootte af te leiden
     */
    private $legend_text_y_factor = 0.5;
    /**
     * @var int aantal pixels dat bij de y-coordinaten opgeteld moet worden als de
     *         titel boven de kaart staat
     */
    private $extra_pixels = 0;
    /**
     * @var int hulpgetal om $this->extra_pixels uit te rekenen
     */
    private $extra_pixels_factor = 9;
    /**
     * @var int hulpgetal om de fontgrootte in GD-termen af te leiden uit de fontgrootte
     *         zoals uitgedrukt in SVG-fontgrootte in Rijksdriehoeksfijnmazigheid
     */
    private $fontsize_factor;
    /**
     * @var int
     */
    private $title_y_factor;
    /**
     * @var array array met kleuren die standaard gealloceerd moeten worden
     */
    private $default_colors = array('blue', 'brown', 'yellow', 'green', 'red', 'black', 'gray', 'dodgerblue');
    /**
     * @var array array voor de integers van de gealloceerde kleuren
     */
    private $colors = array();
    /**
     * @var int fontgrootte in Rijksdriehoeksfijnmazigheid voor de titel
     */
    private $svg_title_fontsize;
    /**
     * @var float hulpgetal om de fontgrootte voor de titel mee uit te rekenen
     */
    private $svg_title_fontsize_factor = 1.5;
    /**
     * @var int fontgrootte in GD-termen voor de legenda
     */
    private $fontsize;
    /**
     * @var int x-coordinaat voor het legendasymbool
     */
    private $legend_symbol_x;
    /**
     * @var int y-coordinaat voor het legendasymbool
     */
    private $legend_symbol_y;
    /**
     * @var int x-coordinaat voor de legendatekst
     */
    private $legend_text_x;
    /**
     * @var int y-coordinaat voor de legendatekst
     */
    private $legend_text_y;
    /**
     * @var int needed for conversion of rd-coordinates to pixels
     */
    private $rd2pixel_x;
    /**
     * @var int needed for conversion of rd-coordinates to pixels
     */
    private $rd2pixel_y;

    /**
     * De constructor
     *
     * Maakt de grondkaart als GD image resource
     *   *
     *
     * @param $parameters
     */
    public function __construct($parameters)
    {
        parent::__construct($parameters);

        $map_definitions = $parameters['map_definitions'];
        $width = $parameters['width'];
        $height = $parameters['height'];
        $this->interactive = $parameters['interactive'];
        $fontsize = $parameters['fontsize'];
        if (array_key_exists('highlighted', $parameters)) {
            $highlighted = $parameters['highlighted'];
        } else {
            $highlighted = array();
        }
        if (array_key_exists('tooltips', $parameters)) {
            $tooltips = $parameters['tooltips'];
        } else {
            $tooltips = array();
        }
        if (array_key_exists('links', $parameters)) {
            $links = $parameters['links'];
        } else {
            $links = array();
        }
        $this->rd2pixel_x = $map_definitions['map_settings']['bitmap_rd2pixel_x'];
        $this->rd2pixel_y = $map_definitions['map_settings']['bitmap_rd2pixel_y'];

        $bitmap_size_factor = $map_definitions['map_settings']['bitmap_size_factor'];
        $smaller_bitmap_factor = $map_definitions['map_settings']['bitmap_smaller_bitmap_factor'];
        $this->fontsize_factor = $map_definitions['map_settings']['bitmap_fontsize_factor'];
        $this->title_y_factor = $map_definitions['map_settings']['bitmap_title_y_factor'];

        $this->legend_symbol_x = round($width / $this->legend_symbol_x_factor);
        $this->legend_symbol_y = round($height / $this->legend_symbol_y_factor);

        if (!empty($this->title)) {
            // kaart iets kleiner
            $bitmap_size_factor += ($bitmap_size_factor / $smaller_bitmap_factor);
            // en naar beneden geschoven
            $this->extra_pixels = round($height / $this->extra_pixels_factor) - $this->legend_symbol_y;
            $this->legend_symbol_y += $this->extra_pixels;
        }

        $this->bitmap_linewidth = $width / $bitmap_size_factor;
        $this->bitmap_factor = round($bitmap_size_factor / $this->bitmap_linewidth);
        $this->svg_title_fontsize = $fontsize * $this->svg_title_fontsize_factor;
        $this->fontsize = ($fontsize * $this->fontsize_factor) / $this->bitmap_factor;

        $this->legend_text_x = $this->legend_symbol_x + ($this->fontsize * $this->legend_text_x_factor);
        $this->legend_text_y = $this->legend_symbol_y + ($this->fontsize * $this->legend_text_y_factor);

        $this->gd_image = imagecreate($width, $height);
        // achtergroundkleur = eerste gealloceerde kleur
        $this->colors[$map_definitions['map_settings']['background_color']] = \Image_Color::allocateColor(
            $this->gd_image,
            $map_definitions['map_settings']['background_color']
        );


        // om symbolen zonder fill te maken
        $this->colors['none'] = false;

        foreach ($this->default_colors as $color) {
            $this->colors[$color] = \Image_Color::allocateColor($this->gd_image, $color);
        }
        $this->colors['grey'] = $this->colors['gray']; // Image_Color gebruikt spelling 'gray'

        if ($map_definitions['map_settings']['bitmap_outline']) {
            imagerectangle($this->gd_image, 0, 0, $width - 1, $height - 1, $this->colors['black']);
        }

        $this->drawBasemap($map_definitions, $highlighted, $links, $tooltips);
        $this->drawTitle($width, $height);
    }

    /**
     * Voegt een <area> element toe aan $this->imagemap_areas om later in een <map> element te stoppen
     *
     * @param string $shape circle, rectangle or polygon
     * @param array $coords in pixels
     * @param string $default_title
     * @param string $custom_title
     * @param string $area_id
     * @param array $link_array
     * @param bool $highlightedpath
     * @param bool $sprintf
     * @param bool $id_attribute
     */
    private function createAreaElement(
        $shape,
        $coords,
        $default_title,
        $custom_title,
        $area_id,
        $link_array,
        $highlightedpath = false,
        $sprintf = true,
        $id_attribute = false
    ) {
        $href = $target = $onclick = $onmouseover = $onmouseout = '';

        // specific link for this area
        if (!empty($link_array)) {
            if (array_key_exists('href', $link_array)) {
                if ($sprintf) {
                    $href = sprintf($link_array['href'], $area_id);
                } else {
                    $href = $link_array['href'];
                }
            }
            if (array_key_exists('target', $link_array) && !is_null($link_array['target'])) {
                $target = $link_array['target'];
            }
            if (array_key_exists('onclick', $link_array)) {
                if ($sprintf) {
                    $onclick = sprintf($link_array['onclick'], $area_id);
                } else {
                    $onclick = $link_array['onclick'];
                }
            }
            if (array_key_exists('onmouseover', $link_array)) {
                if ($sprintf) {
                    $onmouseover = sprintf($link_array['onmouseover'], $area_id);
                } else {
                    $onmouseover = $link_array['onmouseover'];
                }
            }
            if (array_key_exists('onmouseout', $link_array)) {
                if ($sprintf) {
                    $onmouseout = sprintf($link_array['onmouseout'], $area_id);
                } else {
                    $onmouseout = $link_array['onmouseout'];
                }
            }
            // general link for all areas
        } elseif (!empty($this->link)) {
            if (!$this->linkhighlightedonly || ($this->linkhighlightedonly && $highlightedpath)) {
                $href = sprintf($this->link, $area_id);
                if (!empty($this->target)) {
                    $target = $this->target;
                }
            }
        }

        if (is_array($coords[0])) {
            $area = '';
            foreach ($coords as $i => $subset) {
                $area .= $this->addAreaHTML(
                    $shape,
                    $subset,
                    $default_title,
                    $custom_title,
                    $area_id . '_' . $i,
                    $id_attribute,
                    $href,
                    $target,
                    $onclick,
                    $onmouseover,
                    $onmouseout
                );
            }
        } else {
            $area = $this->addAreaHTML(
                $shape,
                $coords,
                $default_title,
                $custom_title,
                $area_id,
                $id_attribute,
                $href,
                $target,
                $onclick,
                $onmouseover,
                $onmouseout
            );
        }

        $this->imagemap_areas .= $area;
    }

    /**
     * Korte omschrijving
     *
     * lange omschrijving
     *
     * @tags
     * @param $shape
     * @param $coords
     * @param $default_title
     * @param $custom_title
     * @param $area_id
     * @param $id_attribute
     * @param $href
     * @param $target
     * @param $onclick
     * @param $onmouseover
     * @param $onmouseout
     * @return string
     */
    private function addAreaHTML(
        $shape,
        $coords,
        $default_title,
        $custom_title,
        $area_id,
        $id_attribute,
        $href,
        $target,
        $onclick,
        $onmouseover,
        $onmouseout
    ) {
        $area = '<area shape="' . $shape . '" coords="' . join(',', $coords) . '"';
        if (!empty($custom_title)) {
            $area
                .= ' title="' . Kaart::escapeXMLString($custom_title)
                . '" alt="' . Kaart::escapeXMLString($custom_title)
                . '"';
        } elseif ($this->interactive) {
            $area .= ' title="' . Kaart::escapeXMLString($default_title) . '" alt="' . Kaart::escapeXMLString(
                $default_title
            ) . '"';
        }
        if (!empty($href)) {
            $area .= ' href="' . Kaart::escapeXMLString($href) . '"';
        }
        if (!empty($target)) {
            $area .= ' target="' . Kaart::escapeXMLString($target) . '"';
        }
        if (!empty($onclick)) {
            $area .= ' onclick="' . Kaart::escapeXMLString($onclick) . '"';
        }
        if (!empty($onmouseover)) {
            $area .= ' onmouseover="' . Kaart::escapeXMLString($onmouseover) . '"';
        }
        if (!empty($onmouseout)) {
            $area .= ' onmouseout="' . Kaart::escapeXMLString($onmouseout) . '"';
        }
        if ($id_attribute) {
            $area .= ' id="' . Kaart::escapeXMLString($area_id) . '"';
        }
        $area .= ' />' . "\n";

        return $area;
    }

    /**
     * Returns the <area> elements for use in a <map> element for interactive bitmaps
     *
     * @access public
     * @return string <area> elements
     */
    public function getImagemapAreas()
    {
        return $this->imagemap_areas;
    }

    /**
     * Vertaalt rijksdriehoeksco�rdinaten naar GD-co�rdinaten in pixels
     *
     * @param $coordinates array x- en y-coordinaat (passed by reference)
     */
    private function rd2pixels(&$coordinates)
    {
        foreach ($coordinates as $key => $coordinate) {
            if ($key % 2 == 0) { // even: x-coordinaat
                $coordinates[$key] = round(($coordinate + $this->rd2pixel_x) / $this->bitmap_factor);
            } else { // oneven: y-coordinaat
                $coordinates[$key]
                    = round(-(($coordinate - $this->rd2pixel_y) / $this->bitmap_factor)) + $this->extra_pixels;
            } // $this->extra_pixels == 0 als er geen titel boven de kaart staat
        }
    }

    /**
     * Emulatie van het SVG-element <path> door achter elkaar getekende lijnstukken
     *
     * @param $gd_image    resource GD image resource (passed by reference)
     * @param $coordinates array met coordinaten
     * @param $color       int GD-allocated kleur
     */
    private function imagepath(&$gd_image, $coordinates, $color)
    {
        $x = array_shift($coordinates);
        $y = array_shift($coordinates);
        while (count($coordinates) > 0) {
            $x2 = array_shift($coordinates);
            $y2 = array_shift($coordinates);
            imageline($gd_image, $x, $y, $x2, $y2, $color);
            $x = $x2;
            $y = $y2;
        }
    }

    /**
     * Geeft op grond van de naam van een kleur de GD-allocated integer terug
     *
     * @access private
     *
     * @param $string string: naam van de kleur
     *
     * @return mixed integer (int GD-allocated kleur) of FALSE indien niet bestaand
     */
    private function allocatedColor($string)
    {
        if (array_key_exists($string, $this->colors)) {
            return $this->colors[$string];
        } else {
            $this->colors[$string] = \Image_Color::allocateColor($this->gd_image, $string);
            return $this->colors[$string];
        }
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
    protected function drawPath(
        $path_id,
        $path_data,
        $path_type,
        $map_definitions,
        $highlighted,
        $links,
        $tooltips
    ) {
        $coords = $path_data['coords'];
        if (isset($path_data['name'])) {
            $name = $path_data['name'];
        } else {
            $name = null;
        }
        $thicknessfactor = $map_definitions[$path_type]['bitmap_thickness_factor'];
        imagesetthickness($this->gd_image, $this->bitmap_linewidth * $thicknessfactor);
        if (array_key_exists(
            'bitmap_color',
            $map_definitions[$path_type]
        ) && $map_definitions[$path_type]['bitmap_color'] !== "none") {
            // outline and fill the same, or polygon without fill
            $color = $fill = $this->allocatedColor($map_definitions[$path_type]['bitmap_color']);
        } else {
            // outline and fill different
            $color = $this->allocatedColor($map_definitions[$path_type]['bitmap_outline']);
            $fill = $this->allocatedColor($map_definitions[$path_type]['bitmap_fill']);
        }
        $bitmap_function = $map_definitions[$path_type]['bitmap_function'];

        if (array_key_exists($path_id, $highlighted)) {
            // only fill color defined
            if (is_string($highlighted[$path_id])) {
                $fill = $this->allocatedColor($highlighted[$path_id]);
                // fill, outline, strokewidth defined
            } elseif (is_array($highlighted[$path_id])) {
                $fill = $this->allocatedColor($highlighted[$path_id]['fill']);
                $color = $this->allocatedColor($highlighted[$path_id]['outline']);
                $thicknessfactor = $highlighted[$path_id]['strokewidth'];
                imagesetthickness($this->gd_image, $this->bitmap_linewidth * $thicknessfactor);
            }
            $highlightedpath = true;
        } else {
            $highlightedpath = false;
        }

        if (is_array($coords[0])) {
            $count = count($coords);
            for ($i = 0; $i < $count; $i++) {
                $this->rd2pixels($coords[$i]);
            }
        } else {
            $this->rd2pixels($coords);
        }

        switch ($bitmap_function) {
            case 'imagepolygon':
                if (is_array($coords[0])) {
                    foreach ($coords as $lines) {
                        imagepolygon($this->gd_image, $lines, count($lines) / 2, $color);
                    }
                } else {
                    imagepolygon($this->gd_image, $coords, count($coords) / 2, $color);
                }
                break;
            case 'imagepath':
                if (is_array($coords[0])) {
                    foreach ($coords as $lines) {
                        $this->imagepath($this->gd_image, $lines, $color);
                    }
                } else {
                    $this->imagepath($this->gd_image, $coords, $color);
                }
                break;
            case 'imagefilledpolygon':
                if (is_array($coords[0])) {
                    foreach ($coords as $lines) {
                        imagefilledpolygon($this->gd_image, $lines, count($lines) / 2, $color);
                    }
                } else {
                    imagefilledpolygon($this->gd_image, $coords, count($coords) / 2, $color);
                }
                break;
            case 'outlinedpolygon':
                if (is_array($coords[0])) {
                    foreach ($coords as $lines) {
                        imagefilledpolygon($this->gd_image, $lines, count($lines) / 2, $fill);
                        imagepolygon($this->gd_image, $lines, count($lines) / 2, $color);
                    }
                } else {
                    imagefilledpolygon($this->gd_image, $coords, count($coords) / 2, $fill);
                    imagepolygon($this->gd_image, $coords, count($coords) / 2, $color);
                }
                break;
        }

        $link_array = array();

        if (array_key_exists($path_id, $links)) {
            if (array_key_exists('href', $links[$path_id])) {
                $link_array['href'] = $links[$path_id]['href'];
            }
            if (array_key_exists('onclick', $links[$path_id])) {
                $link_array['onclick'] = $links[$path_id]['onclick'];
            }
            if (array_key_exists('onmouseover', $links[$path_id])) {
                $link_array['onmouseover'] = $links[$path_id]['onmouseover'];
            }
            if (array_key_exists('target', $links[$path_id])) {
                $link_array['target'] = $links[$path_id]['target'];
            }
        }
        $add_link = false;
        if (!empty($link_array)) {
            $add_link = true;
        }
        if (!empty($this->link) && !$this->linkhighlightedonly) {
            $add_link = true;
        }
        if (!empty($this->link) && ($this->linkhighlightedonly && $highlightedpath)) {
            $add_link = true;
        }

        // municipalities
        if ($map_definitions['map_settings']['basemap_interactive'] && !is_null($name)) {
            if (array_key_exists($path_id, $tooltips)) {
                // FALSE: no 'sprintf' to put Kloekecodes into links
                // TRUE: use the fourth parameter as the "id" attribute of the element
                $this->createAreaElement(
                    'poly',
                    $coords,
                    $name,
                    $tooltips[$path_id],
                    $path_id,
                    $link_array,
                    $highlightedpath,
                    false,
                    true
                );
            } elseif ($this->interactive || $add_link) {
                $this->createAreaElement(
                    'poly',
                    $coords,
                    $name,
                    '',
                    $path_id,
                    $link_array,
                    $highlightedpath,
                    false,
                    true
                );
            }
        }
    }

    /**
     * Tekent de titel boven de kaart
     *
     * @access private
     * @param $width
     * @param $height
     */
    private function drawTitle($width, $height)
    {
        if (empty($this->title)) {
            return;
        }

        $title_fontsize = ($this->svg_title_fontsize * $this->fontsize_factor) / $this->bitmap_factor;

        // Truuk om text te centreren binnen plaatje
        // van http://nl3.php.net/manual/en/function.imageftbbox.php gehaald
        $details = imageftbbox($title_fontsize, 0, KAART_BITMAP_TITLEFONT, $this->title);
        $title_x = ($width - $details[4]) / 2;
        $title_y = round($height / $this->title_y_factor);

        imagefttext(
            $this->gd_image,
            $title_fontsize,
            0,
            $title_x,
            $title_y,
            $this->colors['black'],
            KAART_BITMAP_TITLEFONT,
            $this->title
        );
    }
}
