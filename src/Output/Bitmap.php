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
     * @var resource GD image resource for bitmap version of the map
     */
    public $gd_image;
    /**
     * @var float thickness of the thinnest line (depends on size) )
     */
    private $bitmap_linewidth;
    /**
     * @var bool should <area> elements with area names be created
     */
    private $interactive = false;
    /**
     * @var string HTML with <area> elements, for use in <map> element
     */
    private $imagemap_areas = '';
    /**
     * @var int factor to convert Rijkdriehoekscoördinaten or SVG-coordinates into pixel coordinates. Depends on the
     * size of the map.
     */
    private $bitmap_factor;
    /**
     * @var int map should be shifted downward if there is a title
     */
    private $map_downward_factor = 20;
    /**
     * @var int number of pixels to be added to the y-coordinates if there is a title
     */
    private $extra_pixels = 0;
    /**
     * @var int help factor to calculate $this->extra_pixels
     */
    private $extra_pixels_factor = 9;
    /**
     * @var int help factor to calculate GD-fontsize from SVG or Rijksdriehoeksfontsize
     */
    private $fontsize_factor;
    /**
     * @var int
     */
    private $title_y_factor;
    /**
     * @var array array with colors which should be allocated by default
     */
    private $default_colors = array('blue', 'brown', 'yellow', 'green', 'red', 'black', 'gray', 'dodgerblue');
    /**
     * @var array array for integers of the allocated colors
     */
    private $colors = array();
    /**
     * @var int fontsize in Rijksdriehoeks-resolution
     */
    private $svg_title_fontsize;
    /**
     * @var float help factor to calculate font size for the title
     */
    private $svg_title_fontsize_factor = 1.5;
    /**
     * @var int needed for conversion of Rijksdriehoeks-coordinates to pixels
     */
    private $rd2pixel_x;
    /**
     * @var int needed for conversion of Rijksdriehoeks-coordinates to pixels
     */
    private $rd2pixel_y;

    /**
     * Creates the baseamp as GD resource
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

        if (!empty($this->title)) {
            // map somewhat smaller
            $bitmap_size_factor += ($bitmap_size_factor / $smaller_bitmap_factor);
            // and shifted downward
            $this->extra_pixels = round($height / $this->extra_pixels_factor)
                - round($height / $this->map_downward_factor);
        }

        $this->bitmap_linewidth = $width / $bitmap_size_factor;
        $this->bitmap_factor = round($bitmap_size_factor / $this->bitmap_linewidth);
        $this->svg_title_fontsize = $fontsize * $this->svg_title_fontsize_factor;

        $this->gd_image = imagecreate($width, $height);
        // backgroundcolor = first allocated color
        $this->colors[$map_definitions['map_settings']['background_color']] = \Image_Color::allocateColor(
            $this->gd_image,
            $map_definitions['map_settings']['background_color']
        );

        // for empty fills
        $this->colors['none'] = false;

        foreach ($this->default_colors as $color) {
            $this->colors[$color] = \Image_Color::allocateColor($this->gd_image, $color);
        }
        $this->colors['grey'] = $this->colors['gray']; // Image_Color uses the spelling 'gray'

        if ($map_definitions['map_settings']['bitmap_outline']) {
            imagerectangle($this->gd_image, 0, 0, $width - 1, $height - 1, $this->colors['black']);
        }

        $this->drawBasemap($map_definitions, $highlighted, $links, $tooltips);
        $this->drawTitle($width, $height);
    }

    /**
     * Adds <area> element to $this->imagemap_areas
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
     * Creates actual HTML for <area> element
     *
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
     * Vertaalt Rijksdriehoeks-coordinates to GD-coordinates in pixels
     *
     * @param $coordinates array
     */
    private function rd2pixels(&$coordinates)
    {
        foreach ($coordinates as $key => $coordinate) {
            if ($key % 2 == 0) { // even: x-coordinate
                $coordinates[$key] = round(($coordinate + $this->rd2pixel_x) / $this->bitmap_factor);
            } else { // odd: y-coordinate
                $coordinates[$key]
                    = round(-(($coordinate - $this->rd2pixel_y) / $this->bitmap_factor)) + $this->extra_pixels;
            } // $this->extra_pixels == 0 when no title above the map
        }
    }

    /**
     * Emulation of SVG <path> element
     *
     * @param $gd_image resource
     * @param $coordinates array
     * @param $color int GD-allocated color
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
     * Returns GD-allocated color from color name
     *
     * @param $string string
     *
     * @return mixed integer (int GD-allocated color) or FALSE if not existing
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
        imagesetthickness($this->gd_image, intval($this->bitmap_linewidth * $thicknessfactor));
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
                imagesetthickness($this->gd_image, intval($this->bitmap_linewidth * $thicknessfactor));
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
                        imagepolygon($this->gd_image, $lines, $color);
                    }
                } else {
                    imagepolygon($this->gd_image, $coords, $color);
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
                        imagefilledpolygon($this->gd_image, $lines, $color);
                    }
                } else {
                    imagefilledpolygon($this->gd_image, $coords, $color);
                }
                break;
            case 'outlinedpolygon':
                if (is_array($coords[0])) {
                    foreach ($coords as $lines) {
                        imagefilledpolygon($this->gd_image, $lines, $fill);
                        imagepolygon($this->gd_image, $lines, $color);
                    }
                } else {
                    imagefilledpolygon($this->gd_image, $coords, $fill);
                    imagepolygon($this->gd_image, $coords, $color);
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
     * Draws the title above the map
     *
     * @param $width
     * @param $height
     */
    private function drawTitle($width, $height)
    {
        if (empty($this->title)) {
            return;
        }

        $title_fontsize = ($this->svg_title_fontsize * $this->fontsize_factor) / $this->bitmap_factor;

        // Trick to center text within picture
        // see http://nl3.php.net/manual/en/function.imageftbbox.php
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
