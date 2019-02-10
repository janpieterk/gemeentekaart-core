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
 * Class to generate the SVG version of a map
 */
class SVG extends Image
{
    /**
     * @var object XML_SVG_Document <svg> element, contains the generated map
     */
    public $svg;
    /**
     * @var object XML_SVG_Group the <g> element containing the National Triangulation transform, first child of <svg>
     */
    private $transformer;
    /**
     * @var bool should placenames be shown onmouseover()?
     */
    private $interactive = false;
    /**
     * @var int  x-coordinate for the title, also used for dynamic tooltip
     */
    private $title_x;
    /**
     * @var int y-coordinate for the title
     */
    private $title_y;
    /**
     * @var int font size for title and tooltip
     */
    private $title_fontsize;
    /**
     * @var array default style for the title
     */
    private $title_style = array('fill' => 'black', 'font-weight' => 'bold', 'text-anchor' => 'middle');

    /**
     * @param $parameters array
     */
    public function __construct($parameters)
    {
        parent::__construct($parameters);

        $map_definitions = $parameters['map_definitions'];
        $width = $parameters['width'];
        $height = $parameters['height'];
        $this->interactive = $parameters['interactive'];
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
        $translate_x = $map_definitions['map_settings']['svg_translate_x'];
        $translate_y = $map_definitions['map_settings']['svg_translate_y'];
        $scale_x = $map_definitions['map_settings']['svg_scale_x'];
        $scale_y = $map_definitions['map_settings']['svg_scale_y'];
        $viewbox_width = $map_definitions['map_settings']['svg_viewbox_width'];
        $viewbox_height = $map_definitions['map_settings']['svg_viewbox_height'];
        $title_extra_space = $map_definitions['map_settings']['svg_title_extra_space'];
        $this->title_x = $map_definitions['map_settings']['svg_title_x'];
        $this->title_y = $map_definitions['map_settings']['svg_title_y'];
        $this->title_fontsize = $map_definitions['map_settings']['svg_title_fontsize'];
        $tooltip_x = $map_definitions['map_settings']['svg_tooltip_x'];
        $tooltip_y = $map_definitions['map_settings']['svg_tooltip_y'];
        $tooltip_text_anchor = $map_definitions['map_settings']['svg_tooltip_text-anchor'];

        if (!empty($this->title)) {
            // make space above the map for the title
            $viewbox_height += $title_extra_space;
            $translate_y += $title_extra_space;
            $tooltip_y += $title_extra_space;
        }


        $this->svg = new XML_SVG_Document(array('width' => $width, 'height' => $height));
        $this->svg->setParam('viewBox', '0 0 ' . $viewbox_width . ' ' . $viewbox_height);
        $this->svg->setParam('preserveAspectRatio', 'xMidYMid');
        $this->svg->setParam('onload', 'init(evt)');

        if (array_key_exists('picturebackground', $parameters)) {
            $this->svg->addChild($parameters['picturebackground']);
        }

        if ($this->interactive || !empty($tooltips)) {
            // add ECMAscript for placenames onmouseover
            $defs = new \XML_SVG_Defs;
            $script = new XML_SVG_Script(array('type' => 'text/ecmascript'));
            $javascript = KAART_ONMOUSEOVER_ECMASCRIPT;
            $cdata = new XML_SVG_CData($javascript);
            $script->addChild($cdata);
            $defs->addChild($script);
            $this->svg->addChild($defs);
            $g = new \XML_SVG_Group(array('id' => 'tooltip'));
            $text = new XML_SVG_Text(array(
                'id' => 'ttt',
                'text' => 'tooltip',
                'x' => $tooltip_x,
                'y' => $tooltip_y,
                'display' => 'none',
                'fill' => 'blue',
                'font-size' => $this->title_fontsize,
                'font-weight' => 'bold',
                'text-anchor' => $tooltip_text_anchor
            ));
            $g->addChild($text);
            $this->svg->addChild($g);
        }

        $this->transformer = new \XML_SVG_Group(array(
            'transform' =>
                'translate(' . $translate_x . ',' . $translate_y . ') scale(' . $scale_x . ',' . $scale_y . ')'
        ));
        $this->svg->addChild($this->transformer);

        $this->drawBasemap($map_definitions, $highlighted, $links, $tooltips);
        $this->drawTitle();
    }

    /**
     * Insert a copyright/license statement as an XML comment in the map, if applicable
     */
    public function insertCopyrightStatement()
    {
        foreach (array_unique($this->map_copyright_strings) as $string) {
            $comment = new XML_SVG_Comment($string);
            $this->svg->addChild($comment);
        }
    }

    /**
     * Converts numeric array with x- and y-coordinates to a "d" attribute for a <path> element
     *
     * @param array $array array with x- and y-coordinates
     * @param bool $gesloten should the path be closed or not?
     *
     * @return string x- and y coordinates as value of a "d" attribute
     */
    private function svgPathFromArray($array, $gesloten = false)
    {
        $x = array_shift($array);
        $y = array_shift($array);
        $path = "M $x $y";
        while (count($array) > 0) {
            $x = array_shift($array);
            $y = array_shift($array);
            $path .= " L $x $y";
        }
        if ($gesloten !== false) {
            $path .= ' z';
        }
        return $path;
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

        $style = $map_definitions[$path_type]['svg_path_style'];
        $closed = $map_definitions[$path_type]['svg_path_closed'] == 1 ? true : false;
        $strokewidth = $map_definitions[$path_type]['svg_stroke_width'];

        if (array_key_exists($path_id, $highlighted)) {
            if (is_string($highlighted[$path_id])) {
                $style = preg_replace('/fill:.+?;/', "fill:{$highlighted[$path_id]};", $style);
            } elseif (is_array($highlighted[$path_id])) {
                $fill = $highlighted[$path_id]['fill'];
                $outline = $highlighted[$path_id]['outline'];
                $strokewidth = ($highlighted[$path_id]['strokewidth'] * $strokewidth);
                $style = "stroke:{$outline}; fill:{$fill}; stroke-width:{$strokewidth};";
            }
            $highlightedpath = true;
        } else {
            $highlightedpath = false;
        }

        if (array_key_exists($path_id, $tooltips)) {
            $tooltip = $tooltips[$path_id];
        } else {
            $tooltip = '';
        }

        if (is_array($coords[0])) {
            $g = new XML_SVG_Group(array('id' => $path_id));
            foreach ($coords as $c) {
                $this->addSVGPathToMap(
                    $c,
                    $path_id,
                    $name,
                    $closed,
                    $style,
                    $links,
                    $map_definitions,
                    $highlightedpath,
                    $tooltip,
                    $g
                );
            }
            $this->transformer->addChild($g);
            unset($g);
        } else {
            $this->addSVGPathToMap(
                $coords,
                $path_id,
                $name,
                $closed,
                $style,
                $links,
                $map_definitions,
                $highlightedpath,
                $tooltip
            );
        }
    }

    /**
     * @param      $coords
     * @param      $path_id
     * @param $name
     * @param      $closed
     * @param      $style
     * @param      $links
     * @param      $map_definitions
     * @param      $highlightedpath
     * @param      $tooltip
     * @param mixed $enclosing_group
     */
    private function addSVGPathToMap(
        $coords,
        $path_id,
        $name,
        $closed,
        $style,
        $links,
        $map_definitions,
        $highlightedpath,
        $tooltip,
        $enclosing_group = false
    ) {

        $parameters = array('d' => $this->svgPathFromArray($coords, $closed), 'style' => $style);
        if (!$enclosing_group) {
            $parameters['id'] = $path_id;
        }
        $svgpath = new XML_SVG_Path($parameters);

        if ($map_definitions['map_settings']['basemap_interactive']) {
            // if the path is a municipality, the id is of the form 'g_[numerical code]'
            if (!empty($tooltip) && strpos($path_id, 'g_') === 0) {
                $svgpath->setParam('onmouseover', "ShowTooltip('" . Kaart::escapeJSString($tooltip) . "')");
                $svgpath->setParam('onmouseout', 'HideTooltip()');
            } elseif ($this->interactive && /*strpos($path_id, 'g_') === 0*/
                !is_null($name)) {
                $svgpath->setParam('onmouseover', "ShowTooltip('" . Kaart::escapeJSString($name) . "')");
                $svgpath->setParam('onmouseout', 'HideTooltip()');
            }
        }

        if (array_key_exists($path_id, $links)) {
            if (array_key_exists('href', $links[$path_id])) {
                $a_params['xlink:href'] = Kaart::escapeXMLString($links[$path_id]['href']);
                if (array_key_exists('target', $links[$path_id])) {
                    $a_params['target'] = Kaart::escapeXMLString($links[$path_id]['target']);
                }
                $g = new XML_SVG_Group();
                $a = new XML_SVG_A($a_params);
                $g->addChild($a);
            }
            if (array_key_exists('onclick', $links[$path_id])) {
                $svgpath->setParam('onclick', Kaart::escapeXMLString($links[$path_id]['onclick']));
            }
            if (array_key_exists('onmouseover', $links[$path_id])) {
                $svgpath->setParam('onmouseover', Kaart::escapeXMLString($links[$path_id]['onmouseover']));
            }
        } elseif (!empty($this->link)) {
            if (!$this->linkhighlightedonly || ($this->linkhighlightedonly && $highlightedpath)) {
                $a_params['xlink:href'] = Kaart::escapeXMLString(sprintf($this->link, $path_id));
                if (!empty($this->target)) {
                    $a_params['target'] = Kaart::escapeXMLString($this->target);
                }
                $g = new XML_SVG_Group();
                $a = new XML_SVG_A($a_params);
                $g->addChild($a);
            }
        }

        /** @var $enclosing_group \XML_SVG_Group */
        if ($enclosing_group !== false) {
            if (isset($g) && isset($a)) {
                $a->addChild($svgpath);
                $enclosing_group->addChild($g);
            } else {
                $enclosing_group->addChild($svgpath);
            }
        } else {
            if (isset($g) && isset($a)) {
                $a->addChild($svgpath);
                $this->transformer->addChild($g);
            } else {
                $this->transformer->addChild($svgpath);
            }
        }
    }

    /**
     * Draws the title above the map
     */
    private function drawTitle()
    {
        if (empty($this->title)) {
            return;
        }

        $text = new XML_SVG_Text(array(
            'text' => $this->title,
            'x' => $this->title_x,
            'y' => $this->title_y,
            'font-size' => $this->title_fontsize,
            'fill' => $this->title_style['fill'],
            'font-weight' => $this->title_style['font-weight'],
            'text-anchor' => $this->title_style['text-anchor']
        ));
        // outside the National Triangulation transform
        $this->svg->addChild($text);
    }
}
