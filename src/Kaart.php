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

namespace JanPieterK\GemeenteKaart;

use JanPieterK\GemeenteKaart\Output\Bitmap;
use JanPieterK\GemeenteKaart\Output\JSON;
use JanPieterK\GemeenteKaart\Output\KML;
use JanPieterK\GemeenteKaart\Output\SVG;

/**
 * Central configuration file
 */
require('Kaart.config.inc.php');

class Kaart
{

    /**
     * @var bool whether placename + Kloeke-code should be displayed 'onmouseover' on placemarks
     */
    private $interactive = false;
    /**
     * @var string with %s placeholder which will be replaced with municipality code
     */
    private $link = '';
    /**
     * @var KML KML version of the map
     */
    private $kml;
    /**
     * @var array additional files with paths
     */
    private $additional_paths_files = array();
    /**
     * @var string title of the map
     */
    private $title = '';
    /**
     * @var int extra space to fit the title of the map into
     */
    private $svg_title_extra_space = 38000;
    /**
     * @var array array with paths and styles for the base map, taken from .ini file
     */
    private $map_definitions = array();
    /**
     * @var int default fontsize for the legend
     */
    private $default_fontsize;
    /**
     * @var Bitmap bitmap version of the map
     */
    private $bitmap;
    /**
     * @var JSON JSON version of the map
     */
    private $json;
    /**
     * @var array with links for (a subset of) the municipalities (municipality codes are keys, href values are values).
     * Optional key: 'target'
     */
    private $links = array();
    /**
     * @var int height of the map in pixels
     */
    private $height;
    /**
     * @var SVG SVG version of the map
     */
    private $svg;
    /**
     * @var bool $width_manually_changed if TRUE, setIniFile() should restore the manually changed width
     */
    private $width_manually_changed = false;
    /**
     * @var string one of selt::$choroplethtypes
     */
    private $type = 'municipalities';
    /**
     * @var array to hold the data to be displayed on the map
     */
    private $map_array = array();
    /**
     * @var string optional target to be used for $link above
     */
    private $target = '';
    /**
     * @var boolean whether the general link above, if set, applies to all municipalities or only highlighted ones
     */
    private $linkhighlightedonly = false;
    /**
     * @var array list with extra background layers
     */
    private $backgrounds = array();
    /**
     * @var int width of the map in pixels
     */
    private $width;
    /**
     * @var array list of parts of the basemap which should be drawn. If empty, draw complete basemap
     */
    private $parts = array();
    /**
     * @var array with tooltips for municipalities (municipality codes are keys, tooltip texts are values)
     */
    private $tooltips = array();
    /**
     * Default file resides in the Kaart subdirectory, can be overruled by parameter with alternate file
     *
     * @var string File with arrays with coordinates which form the basemap
     */
    private $kaart_paths_file;

    private static $choroplethtypes
        = array(
            'municipalities',
            'gemeentes',
            'corop',
            'provincies',
            'provinces',
            'municipalities_nl_flanders',
            'municipalities_flanders',
            'dialectareas'
        );

    private static $allowedformats = array('gif', 'png', 'jpg', 'jpeg', 'svg', 'kml', 'json');

    /**
     * The constructor.
     *
     * @param string $type
     * @param null|string $paths_file
     */
    public function __construct($type = 'municipalities')
    {
        if (in_array($type, self::$choroplethtypes)) {
            $ini_files = array('default_map_settings.ini');

            if ($type == 'municipalities_nl_flanders') {
                $paths_file = 'municipalities.json';
                $additionalpathsfiles = array('municipalities_flanders.json', 'border_nl_be.json');
            }

            if ($type == 'gemeentes') {
                $this->type = 'municipalities';
            } elseif ($type == 'provincies') {
                $this->type = 'provinces';
            } elseif ($type == 'netherlands') {
                $this->type = 'nederland';
            } else {
                $this->type = $type;
            }

            $ini_files[] = $this->type . '.ini';

            if (!isset($paths_file)) {
                $this->kaart_paths_file = KAART_COORDSDIR . '/' . $this->type . '.json';
            } else {
                $this->kaart_paths_file = $this->getRealPathToPathsFile($paths_file);
            }

            foreach ($ini_files as $ini_file) {
                $this->parseIniFile($ini_file);
            }

            if (isset($additionalpathsfiles)) {
                $this->setAdditionalPathsFiles($additionalpathsfiles);
            }
        }
    }

    /**
     * Vertaalt Rijksdriehoekscoördinaten naar noorderbreedte/oosterlengte
     *
     * Gebaseerd op Javascript van Ed Stevenhagen en Frank Kissels ({@link http://www.xs4all.nl/~estevenh/})
     *
     * @param $rd_x float x-coördinaat (RD)
     * @param $rd_y float y-coördinaat (RD)
     *
     * @return array array met noorderbreedte en oosterlengte
     */
    public static function rd2latlong($rd_x, $rd_y)
    {
        // constanten
        $X0 = 155000.000;
        $Y0 = 463000.000;
        $F0 = 52.156160556;
        $L0 = 5.387638889;

        $A01 = 3236.0331637;
        $B10 = 5261.3028966;
        $A20 = -32.5915821;
        $B11 = 105.9780241;
        $A02 = -0.2472814;
        $B12 = 2.4576469;
        $A21 = -0.8501341;
        $B30 = -0.8192156;
        $A03 = -0.0655238;
        $B31 = -0.0560092;
        $A22 = -0.0171137;
        $B13 = 0.0560089;
        $A40 = 0.0052771;
        $B32 = -0.0025614;
        $A23 = -0.0003859;
        $B14 = 0.0012770;
        $A41 = 0.0003314;
        $B50 = 0.0002574;
        $A04 = 0.0000371;
        $B33 = -0.0000973;
        $A42 = 0.0000143;
        $B51 = 0.0000293;
        $A24 = -0.0000090;
        $B15 = 0.0000291;

        $dx = ($rd_x - $X0) * pow(10, -5);
        $dy = ($rd_y - $Y0) * pow(10, -5);

        $df = ($A01 * $dy) + ($A20 * pow($dx, 2)) + ($A02 * pow($dy, 2)) + ($A21 * pow($dx, 2) * $dy) + (
                $A03 * pow($dy, 3));
        $df += ($A40 * pow($dx, 4)) + ($A22 * pow($dx, 2) * pow($dy, 2)) + ($A04 * pow($dy, 4)) + (
                $A41 * pow($dx, 4) * $dy);
        $df += ($A23 * pow($dx, 2) * pow($dy, 3)) + ($A42 * pow($dx, 4) * pow($dy, 2)) + (
                $A24 * pow($dx, 2) * pow($dy, 4));

        $noorderbreedte = $F0 + ($df / 3600);

        $dl = ($B10 * $dx) + ($B11 * $dx * $dy) + ($B30 * pow($dx, 3)) + ($B12 * $dx * pow($dy, 2)) + (
                $B31 * pow($dx, 3) * $dy);
        $dl += ($B13 * $dx * pow($dy, 3)) + ($B50 * pow($dx, 5)) + ($B32 * pow($dx, 3) * pow($dy, 2)) + (
                $B14 * $dx * pow($dy, 4));
        $dl += ($B51 * pow($dx, 5) * $dy) + ($B33 * pow($dx, 3) * pow($dy, 3)) + ($B15 * $dx * pow($dy, 5));

        $oosterlengte = $L0 + ($dl / 3600);

        return array($noorderbreedte, $oosterlengte);
    }

    /**
     * Escape string for use as value of XML attribute
     *
     * @param string string to be escaped
     *
     * @return string escaped string
     */
    public static function escapeXMLString($string)
    {
        return htmlspecialchars($string, ENT_COMPAT, 'UTF-8');
    }

    /**
     * @param $highlighted array contaiing areas to be highlighted (area codes are keys, colors are values)
     * @param $format      string with color (name or code)
     *
     * @return array with the colors translated to hexadecimal color codes, either HTML or Google Earth
     */
    private static function translateColors($highlighted, $format)
    {
        $retval = array();
        foreach ($highlighted as $k => $v) {
            if (is_string($v)) {
                list($hex_color, $ge_color) = Kaart::translateColor($v);
                if ($v == 'none') {
                    $hex_color = 'none';
                    $ge_color = '00ffffff';
                }
                if ($format == 'kml') {
                    $retval[$k] = $ge_color;
                } elseif ($format == 'html') {
                    $retval[$k] = $hex_color;
                }
            } elseif (is_array($v)) {
                list($hex_color, $ge_color) = Kaart::translateColor($v['fill']);
                if ($v['fill'] == 'none') {
                    $hex_color = 'none';
                    $ge_color = '00ffffff';
                }
                if ($format == 'kml') {
                    $retval[$k]['fill'] = $ge_color;
                } elseif ($format == 'html') {
                    $retval[$k]['fill'] = $hex_color;
                }
                list($hex_color, $ge_color) = Kaart::translateColor($v['outline']);
                if ($format == 'kml') {
                    $retval[$k]['outline'] = $ge_color;
                } elseif ($format == 'html') {
                    $retval[$k]['outline'] = $hex_color;
                }
                $retval[$k]['strokewidth'] = $v['strokewidth'];
            }
        }

        return $retval;
    }

    /**
     * @param $format
     *
     * @return string
     */
    private function getFormat($format)
    {

        if ($format == 'svg' || $format == 'kml' || $format == 'json') {
            $type = $format;
        } else {
            $type = 'bitmap';
        }

        return $type;
    }

    /**
     * Returns the map as string or binary stream
     *
     * @param string string indicating format of the map (svg, png, gif, jpeg, kml, json)
     *
     * @return string document containing the map
     */
    public function fetch($format = 'svg')
    {
        $retval = null;
        $type = $this->getFormat($format);

        $this->createMap($type);

        switch ($format) {
            case 'svg':
                $retval = $this->svg->svg->bufferObject();
                break;
            case 'png':
                ob_start();
                imagepng($this->bitmap->gd_image);
                $retval = ob_get_contents();
                ob_end_clean();
                imagedestroy($this->bitmap->gd_image);
                break;
            case 'gif':
                ob_start();
                imagegif($this->bitmap->gd_image);
                $retval = ob_get_contents();
                ob_end_clean();
                imagedestroy($this->bitmap->gd_image);
                break;
            case 'jpeg':
                ob_start();
                imagejpeg($this->bitmap->gd_image);
                $retval = ob_get_contents();
                ob_end_clean();
                imagedestroy($this->bitmap->gd_image);
                break;
            case 'kml':
                $retval = $this->kml->dom->saveXML();
                break;
            case 'json':
                $retval = $this->json->toJSON();
                break;
        }
        return $retval;
    }

    /**
     * Hands the map over to a web browser for further handling. Depending on the capabilities and
     * setting of the browser, the map will be embedded on the page, handed to another application, or
     * downloaded.
     *
     * @param string string indicating format of the map
     */
    public function show($format = 'svg')
    {
        $type = $this->getFormat($format);
        $this->createMap($type);

        switch ($format) {
            case 'svg':
                header('Content-type: image/svg+xml');
                $this->svg->svg->printElement();
                break;
            case 'png':
                header('Content-type: image/png');
                imagepng($this->bitmap->gd_image);
                imagedestroy($this->bitmap->gd_image);
                break;
            case 'gif':
                header('Content-type: image/gif');
                imagegif($this->bitmap->gd_image);
                imagedestroy($this->bitmap->gd_image);
                break;
            case 'jpeg':
                header('Content-type: image/jpeg');
                imagejpeg($this->bitmap->gd_image);
                imagedestroy($this->bitmap->gd_image);
                break;
            case 'kml':
                header('Content-type: application/vnd.google-earth.kml+xml');
                header('Content-Disposition: attachment; filename="map.kml"');
                echo $this->kml->dom->saveXML();
                break;
            case 'json':
                header('Content-type: application/json');
                echo $this->json->toJSON();
                break;
        }
    }

    /**
     * Saves the map as a file
     *
     * @param string string containing path to file where the map should be written to
     * @param string string indicating format of the map
     *
     * @return bool TRUE if saving was successful, FALSE otherwise
     */
    public function saveAsFile($filename, $format = 'svg')
    {
        $type = $this->getFormat($format);

        if (file_exists($filename)) {
            @unlink($filename);
        }

        if (@!$fp = fopen($filename, 'w')) {
            return false;
        } else {
            $this->createMap($type);

            switch ($format) {
                case 'svg':
                    fwrite($fp, $this->svg->svg->bufferObject());
                    break;
                case 'png':
                    imagepng($this->bitmap->gd_image, $filename);
                    imagedestroy($this->bitmap->gd_image);
                    break;
                case 'gif':
                    imagegif($this->bitmap->gd_image, $filename);
                    imagedestroy($this->bitmap->gd_image);
                    break;
                case 'jpeg':
                    imagejpeg($this->bitmap->gd_image, $filename);
                    imagedestroy($this->bitmap->gd_image);
                    break;
                case 'kml':
                    $this->kml->dom->save($filename);
                    break;
                case 'json':
                    fwrite($fp, $this->json->toJSON());
                    break;
            }
            return true;
        }
    }

    /**
     * For bitmap maps only: returns a string of <area> elements for features of the map, to use in an imagemap
     * HTML element. Must be called after creating a map!
     *
     * @return mixed string of <area> elements or FALSE if not a bitmap map
     */
    public function getImagemap()
    {
        if (isset($this->bitmap)) {
            return $this->bitmap->getImagemapAreas();
        } else {
            return false;
        }
    }

    /**
     * Set an alternate file with paths for the map. File should be a geoJSON file (specification 2008)
     * using EPSG:28992 as the coordinate system. See the coords directory for examples.
     *
     * Warning: file should not be user-submitted as it is used as-is.
     *
     * @param string string containing file name or path to file with alternate paths
     */
    public function setPathsFile($paths_file)
    {
        $this->kaart_paths_file = $this->getRealPathToPathsFile($paths_file);
    }

    /**
     * Add more layers on top of the base map. Typical use case: combining different choropleth type in one map,
     * e.g. municipalities as the base with borders of larger areas drawn on top of them. Restricted to files
     * in the coords directory.
     *
     * @param array $paths_files
     */
    public function setAdditionalPathsFiles($paths_files)
    {
        foreach ($paths_files as $file) {
            if (stream_resolve_include_path(KAART_COORDSDIR . '/' . $file)) {
                $this->additional_paths_files[] = KAART_COORDSDIR . '/' . $file;
            }
        }
    }

    /**
     * Get the title of the map
     *
     * @return string the title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the title of the map
     *
     * @param string $title string containing the title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Translates color names to hex codes and vice versa
     *
     * @internal
     *
     * @param $color               string indicating color (AABBGGRR hex, #RRGGBB hex, or HTML color name)
     * @param $current_symbol_type mixed
     *
     * @return array met respectievelijk de #RRGGBB en de AABBGGRR hex representatie
     */
    public static function translateColor($color, $current_symbol_type = null)
    {
        if (preg_match('/^[0-9a-fA-F]{8}$/', $color)) {
            // Google Earth AABBGGRR hex
            $bbggrr = substr($color, -6);
            $rr = substr($bbggrr, 4, 2);
            $gg = substr($bbggrr, 2, 2);
            $bb = substr($bbggrr, 0, 2);
            $ge_color = strtolower($color);
            $hex_color = '#' . $rr . $gg . $bb;
        } elseif (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            // #RRGGBB hex
            $rrggbb = substr($color, -6);
            $bb = substr($rrggbb, 4, 2);
            $gg = substr($rrggbb, 2, 2);
            $rr = substr($rrggbb, 0, 2);
            // opacity set to fully opaque
            $ge_color = 'ff' . strtolower($bb . $gg . $rr);
            $hex_color = $color;
        } else {
            // presumably color name
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            list($r, $g, $b) = \Image_Color::namedColor2RGB($color);
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            $rrggbb = \Image_Color::rgb2hex(array($r, $g, $b));
            $bb = substr($rrggbb, 4, 2);
            $gg = substr($rrggbb, 2, 2);
            $rr = substr($rrggbb, 0, 2);

            // GE _symbols can't have "fill:none"!
            // if the current color == 'none' && the current symbol is of type 'filled',
            // emulated empty fill with a 25% transparent white fill in KML
            if ($color == 'none' && $current_symbol_type == 'filled') {
                $ge_color = '7fffffff';
            } else {
                // opacity set to fully opaque
                $ge_color = 'ff' . strtolower($bb . $gg . $rr);
            }

            $hex_color = '#' . $rrggbb;
        }


        return array($hex_color, $ge_color);
    }


    /**
     * Adds JavaScript or title attributes to show area names when hovering.
     *
     * In SVG maps embedded in the map itself and shown using Javascript.
     * a list of <area> tags with title attributes is used to achieve the same effect in bitmap maps.
     *
     * @param bool boolean TRUE or FALSE, interactive on or off
     */
    public function setInteractive($value = true)
    {
        $this->interactive = $value;
    }

    /**
     * Set the height of the map in pixels
     *
     * If you don't want the default height from the .ini file you can overrule it with this method.
     * Probably you shouldn't, though. Note that this method should always be called after setPixelWidth().
     *
     * @param int integer for the desired height
     */
    public function setPixelHeight($height)
    {
        $this->height = $height;
    }

    /**
     * Set the width of the map in pixels
     *
     * If not used, width is the default value, defined in the .ini file for the map type
     * Default height is a factor in the .ini file times the width
     *
     * @param int integer for the desired width
     */
    public function setPixelWidth($width)
    {
        $this->width = $width;
        $this->height = round(
            $this->width * $this->map_definitions['map_settings']['height_factor']
        );
        $this->width_manually_changed = true;
    }

    /**
     * Returns the width of the map in pixels
     *
     * @return int width of the map
     */
    public function getPixelWidth()
    {
        return $this->width;
    }

    /**
     * Returns the height of the map in pixels
     *
     * @return int height of the map
     */
    public function getPixelHeight()
    {
        return $this->height;
    }

    /**
     * Returns an array with possible municipalities for the basemap. Works only if map is of type 'municipalities'.
     *
     * @return array associative array with municipality code => municipality name
     */
    public function getPossibleMunicipalities()
    {
        if ($this->type == 'municipalities') {
            return $this->getPossibleAreas();
        } else {
            return array();
        }
    }

    /**
     * Returns an array with possible areas for the basemap
     *
     * @return array associative array with area code => area name
     */
    public function getPossibleAreas()
    {
        return $this->getPossibleAreasFromPathsFile();
    }

    /**
     * @param $file
     * @return array
     */
    private static function getNamesFromGeoJSON($file)
    {
        $retval = array();
        $data = json_decode(file_get_contents($file), true);
        foreach ($data['features'] as $f) {
            if (isset($f['properties']['name']) && isset($f['properties']['id'])) {
                $retval[$f['properties']['id']] = $f['properties']['name'];
            }
        }
        return $retval;
    }

    public static function getDataFromGeoJSON($file)
    {
        $map_lines = array();
        $map_copyright_string = '';
        $map_name = '';
        $data = json_decode(file_get_contents($file), true);
        foreach ($data['features'] as $f) {
            $id = $f['properties']['id'];
            if (isset($f['properties']['name'])) {
                $map_lines[$id]['name'] = $f['properties']['name'];
            }
            if ($f['geometry']['type'] == 'MultiPolygon') {
                $map_lines[$id]['coords'] = self::parseMultiPolygon($f['geometry']['coordinates']);
            } elseif ($f['geometry']['type'] == 'Polygon') {
                $map_lines[$id]['coords'] = self::parsePolygon($f['geometry']['coordinates']);
            } elseif ($f['geometry']['type'] == 'LineString') {
                $map_lines[$id]['coords'] = self::parseLineString($f['geometry']['coordinates']);
            }
            if (isset($f['properties']['path_type'])) {
                $map_lines[$id]['path_type'] = $f['properties']['path_type'];
            }
        }
        if (isset($data['properties']['copyright'])) {
            $map_copyright_string = $data['properties']['copyright'][0];
        }
        if (isset($data['properties']['name'])) {
            $map_name = $data['properties']['name'];
        }
        return array(
            'map_lines' => array('paths' => $map_lines /*, 'styling' => $map_styling*/),
            'map_copyright_string' => $map_copyright_string,
            'map_name' => $map_name
        );
    }

    private static function parseLineString($array)
    {
        $coords = array();
        foreach ($array as $coord) {
            $coords[] = $coord[0];
            $coords[] = $coord[1];
        }
        return $coords;
    }

    private static function parsePolygon($array)
    {
        return self::parseLineString($array[0]);
    }

    private static function parseMultiPolygon($array)
    {
        $coords = array();
        foreach ($array as $polygon) {
            $coords[] = self::parsePolygon($polygon);
        }
        return $coords;
    }

    /**
     * Add areas to be highlighted
     *
     * @param array $data array containing areas to be highlighted (area codes are keys, colors are values)
     */
    public function setData($data)
    {
        $this->map_array = $data;
    }

    /**
     * Add links to areas (potentially a different link for each area)
     *
     * @param array array containing links for areas (area codes are keys, href values are values)
     * @param mixed NULL or string with value of 'target' attribute for the links
     */
    public function setLinks($data, $target = null)
    {
        foreach ($data as $code => $link) {
            $this->links[$code]['href'] = $link;
            if (!is_null($target)) {
                $this->links[$code]['target'] = $target;
            }
        }
    }

    /**
     * Add the same link to all areas. %s placeholder will be replaced with area code
     *
     * @param string string containing href value of the link
     * @param mixed  NULL or string with value of 'target' attribute for the link
     */
    public function setLink($link, $target = null)
    {
        $this->link = $link;
        if (!is_null($target)) {
            $this->target = $target;
        }
    }

    /**
     * If alternative styling for features of the map is needed. See the ini directory for examples.
     *
     *  Warning: file should not be user-submitted as it is used as-is.
     *
     * @param string $ini_file
     */
    public function setIniFile($ini_file)
    {
        if (stream_resolve_include_path($ini_file)) {
            // keep changed height and width (if any)
            $original_width = $this->width;
            $original_height = $this->height;
            $this->parseIniFile($ini_file);
            if ($this->width_manually_changed) {
                $this->width = $original_width;
                $this->height = $original_height;
            }
        }
    }


    private function getPossibleAreasFromPathsFile()
    {

        $map_names = Kaart::getNamesFromGeoJSON($this->kaart_paths_file);
        if (empty($this->additional_paths_files)) {
            return $map_names;
        } else {
            $merged_names = $map_names;
            foreach ($this->additional_paths_files as $file) {
                $additional_names = Kaart::getNamesFromGeoJSON($file);
                $merged_names = array_merge($merged_names, $additional_names);
            }
            return $merged_names;
        }
    }

    /**
     * Add the same link to all highlighted areas. %s placeholder will be replaced with area code
     *
     * @param string string containing href value of the link
     * @param mixed  NULL or string with value of 'target' attribute for the link
     */
    public function setLinkHighlighted($link, $target = null)
    {
        $this->link = $link;
        if (!is_null($target)) {
            $this->target = $target;
        }
        $this->linkhighlightedonly = true;
    }

    /**
     * Add tooltips to areas. These become 'title' attributes in imagemap <area>s or SVG <path>s, <description>
     * elements in KML, and 'name' properties in GeoJSON.
     *
     * @param array array containing tooltips for areas (area codes are keys, tooltip texts are values)
     */
    public function setToolTips($data)
    {
        $this->tooltips = $data;
    }

    /**
     * Add JavaScript events to areas
     *
     * @param array  array containing javascript for areas (area codes are keys, javascript code snippets are values)
     * @param string string containing event on which the Javascript should execute.
     * Possible values: onclick, onmouseover, onmouseout
     */
    public function setJavaScript($data, $event = 'onclick')
    {
        foreach ($data as $code => $javascript) {
            $this->links[$code][$event] = $javascript;
        }
    }

    /**
     * Draws a basemap with optional highlighted areas
     *
     * @param $format string containing format of the map (svg, bitmap, kml, json)
     */
    private function createMap($format = 'svg')
    {
        $parameters = array(
            'paths_file' => $this->kaart_paths_file,
            'additional_paths_files' => $this->additional_paths_files,
            'width' => $this->width,
            'height' => $this->height,
            'interactive' => $this->interactive,
            'title' => $this->title,
            'fontsize' => $this->default_fontsize,
            'map_definitions' => $this->map_definitions,
            'parts' => $this->parts,
            'backgrounds' => $this->backgrounds,
            'highlighted' => $this->map_array,
            'tooltips' => $this->tooltips,
            'links' => $this->links,
            'link' => $this->link,
            'target' => $this->target,
            'linkhighlightedonly' => $this->linkhighlightedonly
        );

        switch ($format) {
            case 'svg':
                if (!empty($parameters['title'])) {
                    $parameters['map_definitions']['map_settings']['svg_viewbox_height']
                        += $this->svg_title_extra_space;
                    $picturebackgroundwidth = $parameters['map_definitions']['map_settings']['svg_viewbox_width'];
                    $picturebackgroundheight = $parameters['map_definitions']['map_settings']['svg_viewbox_height'];
                } else {
                    $picturebackgroundwidth =
                        $parameters['map_definitions']['map_settings']['svg_viewbox_width'] - 13000;
                    $picturebackgroundheight =
                        $parameters['map_definitions']['map_settings']['svg_viewbox_height'] - 5000;
                }

                $parameters['picturebackground'] = new \XML_SVG_Rect(array(
                    'x' => 15000,
                    'y' => 3000,
                    'width' => $picturebackgroundwidth,
                    'height' => $picturebackgroundheight,
                    'style' => 'fill:#eeeeff;stroke:#000000;stroke-width:200;'
                ));
                $parameters['highlighted'] = Kaart::translateColors($parameters['highlighted'], 'html');
                $this->svg = new SVG($parameters);
                $this->svg->insertCopyrightStatement();
                break;

            case 'bitmap':
                $parameters['highlighted'] = Kaart::translateColors($parameters['highlighted'], 'html');
                $this->bitmap = new Bitmap($parameters);
                break;

            case 'kml':
                $parameters['kml_lookat'] = $this->map_definitions['kml_lookat'];
                $parameters['kml_defaults'] = $this->map_definitions['kml_defaults'];
                $parameters['basemap'] = true;
                $parameters['highlighted'] = Kaart::translateColors($parameters['highlighted'], 'kml');
                $this->kml = new KML($parameters);
                $this->kml->insertCopyrightStatement();
                break;

            case 'json':
                $this->json = new JSON($parameters);
                break;
        }
    }

    /**
     * Initializes settings for the map based on the provided ini file
     * @param $ini_file
     */
    private function parseIniFile($ini_file)
    {
        if (empty($this->map_definitions)) {
            $this->map_definitions = parse_ini_file($ini_file, true);
        } else {
            $extra_definitions = parse_ini_file($ini_file, true);
            $sections = array_keys($extra_definitions);
            $new_sections = array();
            foreach ($sections as $section) {
                if (isset($this->map_definitions[$section])) {
                    $this->map_definitions[$section] = array_merge(
                        $this->map_definitions[$section],
                        $extra_definitions[$section]
                    );
                } else {
                    $new_sections[] = $section;
                }
            }
            if (!empty($new_sections)) {
                foreach ($new_sections as $section) {
                    $this->map_definitions[$section] = $extra_definitions[$section];
                }
            }
        }
        $this->width = $this->map_definitions['map_settings']['width'];
        $this->height = $this->width * $this->map_definitions['map_settings']['height_factor'];
        $this->default_fontsize = $this->map_definitions['map_settings']['svg_default_fontsize'];
    }

    private function getRealPathToPathsFile($paths_file)
    {
        $retval = null;

        if (stream_resolve_include_path($paths_file) !== false) {
            $retval = $paths_file;
        } else {
            if (stream_resolve_include_path(KAART_COORDSDIR . '/' . $paths_file) !== false) {
                $retval = KAART_COORDSDIR . '/' . $paths_file;
            }
        }

        return $retval;
    }

    /**
     * returns the current associative array with map data (area codes are keys, colors are values)
     *
     * @return array
     */
    public function getData()
    {
        return $this->map_array;
    }

    /**
     * @return array
     */
    public static function getAllowedMaptypes()
    {
        return self::$choroplethtypes;
    }

    /**
     * @return array
     */
    public static function getAllowedFormats()
    {
        return self::$allowedformats;
    }


}
