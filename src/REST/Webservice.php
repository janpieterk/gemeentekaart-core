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

class WebService
{

    public static function createMap($kaart, $type, $parameters)
    {

        if (in_array($type, Kaart::getAllowedMaptypes())) {
            self::createChoroplethMap($kaart, $parameters);
        }
    }

    /**
     * @static
     *
     * @param $kaart Kaart
     * @param $parameters
     */
    private static function createChoroplethMap($kaart, $parameters)
    {

        if (isset($parameters['data'])) {
            $kaart->setData($parameters['data']);
        }
        if (isset($parameters['links'])) {
            if (isset($parameters['target'])) {
                $kaart->setLinks($parameters['links'], $parameters['target']);
            } else {
                $kaart->setLinks($parameters['links']);
            }
        }
        if (isset($parameters['tooltips'])) {
            $kaart->setToolTips($parameters['tooltips']);
        }
        self::handleParameters($kaart, $parameters);
    }

    /**
     * @static
     *
     * @param $kaart Kaart
     * @param $parameters
     */
    private static function handleParameters($kaart, $parameters)
    {

        if (array_key_exists('linkhighlightedonly', $parameters) && $parameters['linkhighlightedonly'] != true) {
            unset($parameters['linkhighlightedonly']);
        }


        if (array_key_exists('interactive', $parameters) && $parameters['interactive'] == true) {
            $kaart->setInteractive(true);
        }

        // backwards compatible with v. 2
        if (array_key_exists('pixelwidth', $parameters)) {
            $kaart->setPixelWidth(intval($parameters['pixelwidth']));
        }

        if (array_key_exists('width', $parameters)) {
            $kaart->setPixelWidth(intval($parameters['width']));
        }

        if (array_key_exists('title', $parameters)) {
            $kaart->setTitle(trim($parameters['title']));
        }

        if (array_key_exists('link', $parameters)) {
            if (!array_key_exists('linkhighlightedonly', $parameters)) {
                $kaart->setLink($parameters['link']);
            } elseif (array_key_exists('linkhighlightedonly', $parameters)) {
                $kaart->setLinkHighlighted($parameters['link']);
            }
        }

        if (array_key_exists('additionaldata', $parameters)) {
            if ($parameters['type'] == 'gemeentes' || $parameters['type'] == 'municipalities') {
                $additionalpathsfiles = array();
                if (in_array('corop', $parameters['additionaldata'])) {
                    $additionalpathsfiles[] = 'corop.json';
                }
                if (in_array('provincies', $parameters['additionaldata']) || in_array(
                    'provinces',
                    $parameters['additionaldata']
                )
                ) {
                    $additionalpathsfiles[] = 'provinces.json';
                }
                if (in_array('dialectareas', $parameters['additionaldata'])) {
                    $additionalpathsfiles[] = 'dialectareas.json';
                }
                $kaart->setAdditionalPathsFiles($additionalpathsfiles);
            }
        }
    }
}
