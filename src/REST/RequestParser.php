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


namespace JanPieterK\GemeenteKaart\REST;

use JanPieterK\GemeenteKaart\Kaart;

class RequestParser
{

    private $errors = array();
    private $allowed_formats = array();
    private $allowed_types = array();
    private $allowed_additional_data = array('provincies', 'provinces', 'corop', 'dialectareas');
    private $parameters = array();
    private $raw_request_array = array();
    /**
     * @var string $datakey_regexp : regular expression to match municipalities, corop, provinces, dialectareas
     */
    private $datakey_regexp = '/a_\d|g_\d|corop_\d|p_\d|dial_\d/';
    public $error = false;

    public function __construct()
    {
        $this->allowed_types = Kaart::getAllowedMaptypes();
        $this->allowed_formats = Kaart::getAllowedFormats();
        $rawpostdata = file_get_contents("php://input");
        if (!empty($rawpostdata)) {
            $this->parseRawPostData($rawpostdata);
        }

        // returns the first uploaded file, irrespective of the name chosen in the form
        // FALSE if no file uploaded
        $uploaded_files = array_values($_FILES);
        $uploaded_file = reset($uploaded_files);
        if (is_array($uploaded_file)) {
            $this->parseCSVFile($uploaded_file['tmp_name']);
        }

        $request_array = array_merge($_GET, $_POST);

        if (!empty($request_array)) {
            $this->raw_request_array = $this->getRequestArray();
        }

        if (!empty($this->raw_request_array)) {
            // no map creation requested, map type implicit, so no need to check map parameters
            if (!isset($this->raw_request_array['type'])
                && (isset($this->raw_request_array['possiblemunicipalities'])
                    || isset($this->raw_request_array['possibletypes'])
                    || isset($this->raw_request_array['possibleformats']))
            ) {
                if (isset($this->raw_request_array['possiblemunicipalities'])) {
                    $this->checkBooleanTrue('possiblemunicipalities');
                    $this->checkParameter('year');
                } elseif (isset($this->raw_request_array['possibletypes'])) {
                    $this->checkBooleanTrue('possibletypes');
                } elseif (isset($this->raw_request_array['possibleformats'])) {
                    $this->checkBooleanTrue('possibleformats');
                }
            } else {
                $this->checkParameter('type');
                $this->checkParameter('format');
                $this->checkParameter('width');
                $this->checkParameter('height');
                $this->checkParameter('imagemap');
                $this->checkParameter('interactive');
                $this->checkParameter('title');
                $this->checkParameter('maptype');
                $this->checkParameter('target');
                $this->checkParameter('link');
                $this->checkParameter('linkhighlightedonly');
                $this->checkParameter('base64');
                $this->checkParameter('data');
                $this->checkParameter('possiblemunicipalities');
                $this->checkParameter('possibleareas'); // can apply to either municipalities, COROP or provinces
                $this->checkParameter('additionaldata');
                $this->checkParameter('pathsfile');
                $this->checkParameter('year');
            }
        }

        if (!empty($this->errors)) {
            $this->error = true;
        }
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getError()
    {
        return join("\n", $this->errors);
    }

    private function checkParameter($param)
    {

        switch ($param) {
            case 'type':
                if (!isset($this->raw_request_array['type'])) {
                    $this->errors[] = 'Parameter "type" (one of ' . join(', ', $this->allowed_types) . ') missing';
                } elseif (!in_array($this->raw_request_array['type'], $this->allowed_types)) {
                    $this->errors[]
                        = 'Parameter type ' . $this->raw_request_array['type'] . ' not one of ' . join(
                            ', ',
                            $this->allowed_types
                        );
                } else {
                    $this->parameters['type'] = $this->raw_request_array['type'];
                }
                break;
            case 'format':
                if (!isset($this->raw_request_array['format'])) {
                    $this->parameters['format'] = 'png';
                } elseif (!in_array($this->raw_request_array['format'], $this->allowed_formats)) {
                    $this->errors[] = 'Parameter format ' . $this->raw_request_array['format'] . ' not one of '
                            . join(', ', $this->allowed_formats);
                } else {
                    $this->parameters['format'] = $this->raw_request_array['format'];
                }
                break;
            case 'title':
                if (isset($this->raw_request_array['title'])) {
                    $this->parameters['title'] = $this->raw_request_array['title'];
                }
                break;
            case 'target':
                if (isset($this->raw_request_array['target'])) {
                    $this->parameters['target'] = $this->raw_request_array['target'];
                }
                break;
            case 'link':
                if (isset($this->raw_request_array['link'])) {
                    $this->parameters['link'] = $this->raw_request_array['link'];
                }
                break;
            case 'width':
            case 'height':
            case 'year':
                $this->checkInteger($param);
                break;
            case 'imagemap':
            case 'interactive':
            case 'linkhighlightedonly':
            case 'possiblemunicipalities':
            case 'possibleareas':
            case 'base64':
                $this->checkBooleanTrue($param);
                break;
            case 'data':
                if (isset($this->raw_request_array['data'])) {
                    if (is_array($this->raw_request_array['data'])) {
                        $this->parameters['data'] = $this->raw_request_array['data'];
                        // assume comma-separated string
                    } elseif (is_string($this->raw_request_array['data'])) {
                        $this->parameters['data'] = explode(',', $this->raw_request_array['data']);
                    }
                }
                break;
            case 'additionaldata':
                if (isset($this->raw_request_array['additionaldata'])) {
                    $additional_data = explode(',', $this->raw_request_array['additionaldata']);
                    foreach ($additional_data as $d) {
                        if (!in_array($d, $this->allowed_additional_data)) {
                            $this->errors[]
                                = 'Parameter additionaldata ' . $d . ' not one of ' . join(
                                    ', ',
                                    $this->allowed_additional_data
                                );
                        } else {
                            $this->parameters['additionaldata'][] = $d;
                        }
                    }
                }
                break;
            case 'pathsfile':
                // leave it to the REST index file to see if this file can be included
                if (isset($this->raw_request_array['pathsfile'])) {
                    $this->parameters['pathsfile'] = $this->raw_request_array['pathsfile'];
                }
                break;
        }
    }

    private function parseRawPostData($rawpostdata)
    {

        $data = json_decode($rawpostdata);

        if (is_null($data)) {
            $this->errors[] = 'Error parsing JSON input';
        } elseif (is_array($data)) {
            $this->parameters['data'] = $data;
        } elseif (is_object($data)) {
            $tmp = $this->objectToArray($data);
            $keys = array_keys($tmp);
            $key = array_shift($keys);
            if (preg_match($this->datakey_regexp, $key)) {
                $this->parameters['data'] = $tmp;
            } else {
                if (isset($tmp['data'])) {
                    $this->parameters['data'] = $tmp['data'];
                    unset($tmp['data']);
                    // assume that it's a comma-seperated list
                    if (is_string($this->parameters['data'])) {
                        $this->parameters['data'] = explode(',', $this->parameters['data']);
                    }
                }
                if (isset($tmp['links'])) {
                    $this->parameters['links'] = $tmp['links'];
                    unset($tmp['links']);
                }
                if (isset($tmp['tooltips'])) {
                    $this->parameters['tooltips'] = $tmp['tooltips'];
                    unset($tmp['tooltips']);
                }
                $this->raw_request_array = $tmp;
            }
        }
    }

    private function parseCSVFile($filename)
    {
        $tmp['data'] = array();
        if (($handle = fopen($filename, "r")) !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                $tmp['data'][$data[0]] = $data[1];
            }
            fclose($handle);
        }

        $this->parameters['data'] = $tmp['data'];
    }

    private function getRequestArray()
    {
        return array_merge($this->raw_request_array, $_GET, $_POST);
    }

    /**
     * http://codesnippets.joyent.com/posts/show/1641
     * @param $data
     * @return array
     */
    private function objectToArray($data)
    {
        if (is_array($data) || is_object($data)) {
            $result = array();
            foreach ($data as $key => $value) {
                $result[$key] = $this->objectToArray($value);
            }
            return $result;
        }
        return $data;
    }

    /**
     * Accept parameter as boolean only if 1, true, on or yes
     *
     * @param string parameter
     */
    private function checkBooleanTrue($key)
    {
        if (isset($this->raw_request_array[$key])) {
            $value = strtolower($this->raw_request_array[$key]);
            if (in_array($value, array('1', 'true', 'on', 'yes'))) {
                $this->parameters[$key] = true;
            }
        }
    }/** @noinspection PhpUnusedPrivateMethodInspection */

    /**
     * Accept parameter as boolean only if 0, off, false or no
     *
     * @param string parameter
     */
    private function checkBooleanFalse($key)
    {
        if (isset($this->raw_request_array[$key])) {
            $value = strtolower($this->raw_request_array[$key]);
            if (in_array($value, array('0', 'false', 'off', 'no'))) {
                $this->parameters[$key] = false;
            }
        }
    }


    /**
     * Accept parameter as integer only if of ctype_digit
     *
     * @param string parameter
     */
    private function checkInteger($key)
    {
        if (isset($this->raw_request_array[$key])) {
            if (!ctype_digit($this->raw_request_array[$key])) {
                $this->errors[] = 'Parameter ' . $key . ' ' . $this->raw_request_array[$key] . ' is not an integer';
            } else {
                $this->parameters[$key] = $this->raw_request_array[$key];
            }
        }
    }
}
