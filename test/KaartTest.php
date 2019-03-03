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

/** @noinspection PhpUndefinedFieldInspection */
/** @noinspection HtmlRequiredAltAttribute */

require('testconfig_local.inc.php');

use Imagick;
use PHPUnit\Framework\TestCase;

echo "Running tests using PHP version " . PHP_VERSION . "\n";

class KaartTest extends TestCase
{

    /** @var $kaart Kaart */
    private $kaart; // contains the object handle of the Kaart class

    public function setUp()
    {
        $this->kaart = new Kaart('gemeentes');
    }

    public function tearDown()
    {
        unset($this->kaart);
    }

    /**
     * @param $name
     * @return \ReflectionMethod
     * @throws \ReflectionException
     */
    public static function getMethod($name)
    {
        $class = new \ReflectionClass('JanPieterK\GemeenteKaart\Kaart');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    private function saveFile($filename, $data)
    {
        file_put_contents(KAART_TESTDIRECTORY . '/' . $filename, $data);
        return file_get_contents(KAART_TESTDIRECTORY . '/' . $filename);
    }

    private function myFileExists($filename, $format)
    {
        $retval = false;
        $message = '';
        @ unlink(KAART_TESTDIRECTORY . '/' . $filename);
        if (file_exists(KAART_TESTDIRECTORY . '/' . $filename)) {
            // weggooien oude file mislukt, dus data kan niet goed uitgevoerd worden
            $message = "could not delete file " . KAART_TESTDIRECTORY . '/' . $filename;
            return array($retval, $message);
        } else {
            $this->kaart->saveAsFile(KAART_TESTDIRECTORY . '/' . $filename, $format);
            $retval = file_exists(KAART_TESTDIRECTORY . '/' . $filename);
            if (!$retval) {
                $message = "could not save file " . KAART_TESTDIRECTORY . '/' . $filename;
            }
        }
        return array($retval, $message);
    }

    /**
     * @param $actual
     * @param $expected
     * @param int $fuzzfactor
     * @return mixed
     * @throws \ImagickException
     */
    private function compareTwoImages($actual, $expected, $fuzzfactor = 0)
    {
        if ($fuzzfactor === 0) {
            $image1 = new imagick($actual);
            $image2 = new imagick($expected);
            $result = $image1->compareImages($image2, imagick::METRIC_MEANABSOLUTEERROR);
        } else {
            // see http://nl1.php.net/manual/en/imagick.compareimages.php + comments
            $image1 = new imagick();
            $image2 = new imagick();
            $image1->SetOption('fuzz', $fuzzfactor . '%');
            $image1->readImage($actual);
            $image2->readImage($expected);
            $result = $image1->compareImages($image2, 1);
        }
        return $result[1];
    }

    public function testfetchSVG()
    {
        $filename = substr(__FUNCTION__, 4) . '.svg';
        $expected = '669d2f34c268f4e49fdd9fce9e0b5d5b';
        $gemeentes = array('g_0534' => '#FFC513');
        $this->kaart->setData($gemeentes);
        $actual = md5($this->saveFile($filename, $this->kaart->fetch('svg')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    /**
     * @throws \ImagickException
     */
    public function testfetchPNG()
    {
        $filename = substr(__FUNCTION__, 4) . '.png';
        $reference_image = KAART_REFERENCE_IMAGES_DIR . '/' . $filename;
        $this->saveFile($filename, $this->kaart->fetch('png'));
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename, $reference_image);
        $this->assertEquals(0, $result, "check file $filename");
    }

    /**
     * @throws \ImagickException
     */
    public function testfetchGIF()
    {
        $filename = substr(__FUNCTION__, 4) . '.gif';
        $reference_image = KAART_REFERENCE_IMAGES_DIR . '/' . $filename;
        $this->saveFile($filename, $this->kaart->fetch('gif'));
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename, $reference_image);
        $this->assertEquals(0, $result, "check file $filename");
    }

    /**
     * @throws \ImagickException
     */
    public function testfetchJPEG()
    {
        $filename = substr(__FUNCTION__, 4) . '.jpg';
        $reference_image = KAART_REFERENCE_IMAGES_DIR . '/' . $filename;
        $this->saveFile($filename, $this->kaart->fetch('jpeg'));
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename, $reference_image, 15);
        $this->assertEquals(0, $result, "check file $filename");
    }

    public function testfetchKML()
    {
        $filename = substr(__FUNCTION__, 4) . '.kml';
        $expected = 'c3b6a2a55579d4fce5be8826748cba70';
        $actual = md5($this->saveFile($filename, $this->kaart->fetch('kml')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }


    /**
     * @throws \ImagickException
     */
    public function testfetchPNGProvinces()
    {
        $filename = substr(__FUNCTION__, 4) . '.png';
        $reference_image = KAART_REFERENCE_IMAGES_DIR . '/' . $filename;
        $expected = 'a96974e8fe0d7b593fd05c530278ab7d';
        unset($this->kaart);
        $this->kaart = new Kaart('provinces');
        $this->kaart->setData(array('p_22' => '#FFC513'));
        $this->saveFile($filename, $this->kaart->fetch('png'));
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename, $reference_image);
        $this->assertEquals(0, $result, "check file $filename");
    }

    /**
     * @group JSON
     */
    public function testfetchJSON()
    {
        $expected = array(443, 36681);
        $jsonkaart = json_decode($this->kaart->fetch('json'), true);
        $num_features = count($jsonkaart['features']);
        $num_points = 0;
        foreach ($jsonkaart['features'] as $f) {
            if ($f['geometry']['type'] == 'Polygon') {
                $num_points += count($f['geometry']['coordinates'][0]);
            } elseif ($f['geometry']['type'] == 'MultiPolygon') {
                foreach ($f['geometry']['coordinates'][0] as $c) {
                    $num_points += count($c);
                }
            }
        }
        $this->assertEquals($expected, array($num_features, $num_points));
    }


    /**
     * @group JSON
     */
    public function testfetchJSONDialectAreas()
    {
        $expected = array(25, 7160);
        $this->kaart = new Kaart('dialectareas');
        $jsonkaart = json_decode($this->kaart->fetch('json'), true);
        $num_features = count($jsonkaart['features']);
        $num_points = 0;
        foreach ($jsonkaart['features'] as $f) {
            if ($f['geometry']['type'] == 'Polygon') {
                $num_points += count($f['geometry']['coordinates'][0]);
            } elseif ($f['geometry']['type'] == 'MultiPolygon') {
                foreach ($f['geometry']['coordinates'][0] as $c) {
                    $num_points += count($c);
                }
            }
        }
        $this->assertEquals($expected, array($num_features, $num_points));
    }


    public function testHighlightColorsKML()
    {
        $filename1 = substr(__FUNCTION__, 4) . '.html.kml';
        $filename2 = substr(__FUNCTION__, 4) . '.kml';
        $gemeentes = array('g_0534' => '#FFC513');
        $this->kaart->setData($gemeentes);
        $actual1 = md5($this->saveFile($filename1, $this->kaart->fetch('kml')));
        $gemeentes = array('g_0534' => 'FF13C5FF');
        $this->kaart->setData($gemeentes);
        $actual2 = md5($this->saveFile($filename2, $this->kaart->fetch('kml')));
        $this->assertEquals($actual1, $actual2, "check files $filename1 en $filename2");
    }

    public function testHighlightColorsSVG()
    {
        $filename1 = substr(__FUNCTION__, 4) . '.html.svg';
        $filename2 = substr(__FUNCTION__, 4) . '.svg';
        $gemeentes = array('g_0534' => '#FFC513');
        $this->kaart->setData($gemeentes);
        $actual1 = md5($this->saveFile($filename1, $this->kaart->fetch('svg')));
        $gemeentes = array('g_0534' => 'FF13C5FF');
        $this->kaart->setData($gemeentes);
        $actual2 = md5($this->saveFile($filename2, $this->kaart->fetch('svg')));
        $this->assertEquals($actual1, $actual2, "check files $filename1 en $filename2");
    }

    public function testHighlightColorsBitmap()
    {

        $filename1 = substr(__FUNCTION__, 4) . '.html.png';
        $filename2 = substr(__FUNCTION__, 4) . '.png';
        $gemeentes = array('g_0534' => '#FFC513');
        $this->kaart->setData($gemeentes);
        $actual1 = md5($this->saveFile($filename1, $this->kaart->fetch('png')));
        $gemeentes = array('g_0534' => 'FF13C5FF');
        $this->kaart->setData($gemeentes);
        $actual2 = md5($this->saveFile($filename2, $this->kaart->fetch('png')));
        $this->assertEquals($actual1, $actual2, "check files $filename1 en $filename2");
    }

    /**
     * @group JSON
     */
    public function testHighlightJSON()
    {
        $expected = array(
            'name' => 'Hillegom',
            'id' => 'g_0534',
            'style' => array(
                'fill' => '#FFC513',
                'stroke' => '#808080',
                'stroke-width' => '200'
            )
        );
        $gemeentes = array('g_0534' => '#FFC513');
        $this->kaart->setData($gemeentes);
        $jsonkaart = json_decode($this->kaart->fetch('json'), true);
        $actual = $jsonkaart['features'][442]['properties'];
        $this->assertEquals($expected, $actual);
    }


    public function testsaveAsFileSVG()
    {
        list($new_file_exists, $message) = $this->myFileExists(substr(__FUNCTION__, 4) . '.svg', 'svg');
        $this->assertTrue($new_file_exists, $message);
    }

    public function testsaveAsFilePNG()
    {
        list($new_file_exists, $message) = $this->myFileExists(substr(__FUNCTION__, 4) . '.png', 'png');
        $this->assertTrue($new_file_exists, $message);
    }

    public function testsaveAsFileGIF()
    {
        list($new_file_exists, $message) = $this->myFileExists(substr(__FUNCTION__, 4) . '.gif', 'gif');
        $this->assertTrue($new_file_exists, $message);
    }

    public function testsaveAsFileJPEG()
    {
        list($new_file_exists, $message) = $this->myFileExists(substr(__FUNCTION__, 4) . '.jpeg', 'jpeg');
        $this->assertTrue($new_file_exists, $message);
    }

    public function testaddData()
    {
        $expected_map_array = array('g_0534' => '#FFC513', 'g_1740' => '#E30000');
        $this->kaart->setData(array('g_0534' => '#FFC513', 'g_1740' => '#E30000'));
        $actual_map_array = $this->kaart->getData();
        $this->assertEquals($expected_map_array, $actual_map_array);
    }

    public function testsetPixelWidth()
    {
        $width = rand(1, 1000);
        $expected_width = $width;
        $expected_height = round($width * 1.1);
        $this->kaart->setPixelWidth($width);
        $actual_width = $this->kaart->getPixelWidth();
        $this->assertEquals($expected_width, $actual_width);
        $actual_height = $this->kaart->getPixelHeight();
        $this->assertEquals($expected_height, $actual_height);
    }

    public function testsetPixelHeight()
    {
        $expected = 10;
        $this->kaart->setPixelHeight(10);
        $actual = $this->kaart->getPixelHeight();
        $this->assertEquals($expected, $actual);
    }

    public function testgetPixelWidth()
    {
        $expected = strval(640);
        $actual = $this->kaart->getPixelWidth();
        $this->assertEquals($expected, $actual);
    }

    public function testgetPixelHeight()
    {
        $expected = 640 * 1.1;
        $expected = strval($expected);
        $actual = strval($this->kaart->getPixelHeight());
        $this->assertEquals($expected, $actual);
    }

    public function testaddTooltipsInteractiveBitmap()
    {
        $filename = substr(__FUNCTION__, 4) . '.html';
        $expected = '838554f79c6678e07f2ed1966704fc05';
        $gemeentes = array('g_0534' => '#FFC513');
        $this->kaart->setInteractive();
        $this->kaart->setData($gemeentes);
        $this->kaart->setToolTips(array('g_0534' => 'Juinen'));
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $actual = md5($this->saveFile($filename, $this->kaart->getImagemap()));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    public function testaddTooltipsNonInteractiveBitmap()
    {
        $filename = substr(__FUNCTION__, 4) . '.html';
        $expected = 'cee1dee0286d4d085506560a61baf3ef';
        $gemeentes = array('g_0534' => '#FFC513');
        $this->kaart->setData($gemeentes);
        $this->kaart->setToolTips(array('g_0534' => 'Juinen'));
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $actual = md5($this->saveFile($filename, $this->kaart->getImagemap()));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    public function testaddTooltipsNonInteractiveSVG()
    {
        $filename = substr(__FUNCTION__, 4) . '.svg';
        $expected = 'a5ffc828b54489c8e24daa706ac59002';
        $gemeentes = array('g_0363' => '#FFC513');
        $this->kaart->setData($gemeentes);
        $this->kaart->setToolTips(array('g_0363' => 'Juinen'));
        $actual = md5($this->saveFile($filename, $this->kaart->fetch('svg')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    public function testaddTooltipsInteractiveSVG()
    {
        $filename = substr(__FUNCTION__, 4) . '.svg';
        $expected = 'f8123f92e7dad6b7381820862951c0dc';
        $gemeentes = array('g_0363' => '#FFC513');
        $this->kaart->setInteractive();
        $this->kaart->setData($gemeentes);
        $this->kaart->setToolTips(array('g_0363' => 'Juinen'));
        $actual = md5($this->saveFile($filename, $this->kaart->fetch('svg')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    public function testaddTooltipsNonInteractiveKML()
    {
        $filename = substr(__FUNCTION__, 4) . '.kml';
        $expected = 'cb53d1e6b015d6cfd666a5230f622ff1';
        $gemeentes = array('g_0363' => '#FFC513');
        $this->kaart->setData($gemeentes);
        $this->kaart->setToolTips(array('g_0363' => 'Juinen'));
        $actual = md5($this->saveFile($filename, $this->kaart->fetch('kml')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    /**
     * @group JSON
     */
    public function testaddTooltipsJSON()
    {
        $expected = array(
            'name' => 'Juinen',
            'id' => 'g_0363',
            'style' => array(
                'fill' => '#FFC513',
                'stroke' => '#808080',
                'stroke-width' => '200'
            )
        );
        $gemeentes = array('g_0363' => '#FFC513');
        $this->kaart->setData($gemeentes);
        $this->kaart->setToolTips(array('g_0363' => 'Juinen'));
        $jsonkaart = json_decode($this->kaart->fetch('json'), true);
        $actual = $jsonkaart['features'][442]['properties'];
        $this->assertEquals($expected, $actual);
    }


    /**
     * @throws \ReflectionException
     */
    public function testsetTitleSVG()
    {

        $expected = 'dit is een testtitel';
        $this->kaart->setTitle('dit is een testtitel');

        $actual = $this->kaart->getTitle();
        $this->assertequals($expected, $actual);
        self::getMethod('createMap')->invokeArgs($this->kaart, array());
        $svg = simplexml_load_string($this->kaart->fetch('svg'));
        $expected = '0 0 288051 400430';
        $actual = strval($svg->attributes()->viewBox);
        $this->assertEquals($expected, $actual);

        $expected = 288051;
        $actual = intval($svg->rect[0]->attributes()->width);
        $this->assertEquals($expected, $actual);

        $expected = 360430;
        $actual = intval($svg->rect[0]->attributes()->height);
        $this->assertEquals($expected, $actual);
    }


    /**
     * @throws \ImagickException
     */
    public function testsetTitlePNG()
    {

        $filename = substr(__FUNCTION__, 4) . '.png';
        $reference_image = KAART_REFERENCE_IMAGES_DIR . '/' . $filename;
        $this->kaart->setTitle('dit is een testtitel');
        $this->saveFile($filename, $this->kaart->fetch('png'));
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename, $reference_image);
        $this->assertLessThan(0.005, $result, "check file $filename");
    }


    public function testsetInteractiveSVG()
    {
        $this->kaart->setInteractive();
        $svg = simplexml_load_string($this->kaart->fetch('svg'));
        $expected = "Appingedam";
        $actual = trim($svg->g[0]->path[0]->title);
        $this->assertequals($expected, $actual);
    }


    public function testImagemap()
    {
        $filename = substr(__FUNCTION__, 4) . '.html';
        $expected = 'bb01853072c7416a08d192e2da2dd54d';
        $this->kaart->setInteractive();
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $actual = md5($this->saveFile($filename, $this->kaart->getImagemap()));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    public function testsetLinkSVG()
    {
        $gemeentes = array('g_0003' => '#FFC513');
        $links = array('g_0003' => 'http://www.janpieterkunst.nl/');
        $this->kaart->setData($gemeentes);
        $this->kaart->setLinks($links, '_blank');
        $expected = 'http://www.janpieterkunst.nl/';
        // om de een of andere reden kan simplexml attributen met een namespace niet zien.
        // daarom dit maar gedaan (ugly hack, maar goed, fuck it).
        $svg = simplexml_load_string(str_replace('xlink:href', 'href', $this->kaart->fetch()));
        $actual = trim($svg->g[0]->g[21]->a[0]['href']);
        $this->assertEquals($expected, $actual);
        $expected = 'g_0003';
        $actual = trim($svg->g[0]->g[21]->a[0]->path[0]->attributes()->id);
        $this->assertEquals($expected, $actual);
        $expected = '_blank';
        $actual = trim($svg->g[0]->g[21]->a[0]['target']);
        $this->assertEquals($expected, $actual);
    }

    public function testsetLinksBitmap()
    {
        $links = array('g_0003' => 'http://www.janpieterkunst.nl/');
        $this->kaart->setLinks($links, '_blank');
        $expected
            = '<area shape="poly" coords="572,57,572,57,571,57,570,58,569,58,569,58,567,57,566,57,565,57,565,58,565,58,565,59,565,61,565,61,565,62,565,62,565,62,564,62,563,62,562,62,561,62,561,62,561,63,561,65,561,65,560,65,560,65,561,67,561,67,562,69,562,69,562,70,564,69,564,71,565,71,565,71,567,71,567,69,569,69,569,70,572,69,573,69,573,66,573,66,572,66,572,65,572,63,572,63,572,61,572,61,572,59,572,58,572,57" href="http://www.janpieterkunst.nl/" target="_blank" id="g_0003" />';
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $imagemap = trim($this->kaart->getImagemap());
        $imagemap_array = explode("\n", $imagemap);
        $actual = trim($imagemap_array[0]);
        $this->assertequals($expected, $actual);
    }


    public function testsetLinksKML()
    {
        $filename = substr(__FUNCTION__, 4) . '.kml';
        $expected = '0080401d2572ad2d33c65fc9b5d9f086';
        $links = array('g_0003' => 'http://www.janpieterkunst.nl/');
        $this->kaart->setLinks($links, '_blank');
        $actual = md5($this->saveFile($filename, $this->kaart->fetch('kml')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }


    /**
     * @group JSON
     */
    public function testsetLinksJSON()
    {
        $expected = array('http://www.janpieterkunst.nl/', '_blank');
        $links = array('g_0003' => 'http://www.janpieterkunst.nl/');
        $this->kaart->setLinks($links, '_blank');
        $jsonkaart = json_decode($this->kaart->fetch('json'), true);
        $properties = $jsonkaart['features'][0]['properties'];
        $actual = array($properties['href'], $properties['target']);
        $this->assertEquals($expected, $actual);
    }

    public function testsetJavaScriptSVGOnclick()
    {
        $filename = substr(__FUNCTION__, 4) . '.svg';
        $this->kaart->setData(array('g_0003' => '#FFC513'));
        $this->kaart->setJavaScript(array('g_0003' => 'alert(\'g_0003\');'));
        $expected = "65115dc25d0402d8d2f7cb3c9ab36e52";
        $actual = md5($this->saveFile($filename, $this->kaart->fetch()));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    public function testsetJavaScriptSVGOnmouseoverInteractive()
    {
        $filename = substr(__FUNCTION__, 4) . '.svg';
        $this->kaart->setInteractive();
        $this->kaart->setData(array('g_0003' => '#FFC513'));
        $this->kaart->setJavaScript(array('g_0003' => 'alert(\'g_0003\');'), 'onmouseover');
        $expected = "bdc67b3a013df088bed498a3ce3953e8";
        $actual = md5($this->saveFile($filename, $this->kaart->fetch()));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    public function testsetJavaScriptSVGOnmouseover()
    {
        $filename = substr(__FUNCTION__, 4) . '.svg';
        $this->kaart->setData(array('g_0003' => '#FFC513'));
        $this->kaart->setJavaScript(array('g_0003' => 'alert(\'g_0003\');'), 'onmouseover');
        $expected = "23c0855d040d63342f9f483d9e2c2f29";
        $actual = md5($this->saveFile($filename, $this->kaart->fetch()));
        $this->assertEquals($expected, $actual, "check file $filename");
    }


    /**
     * @group JSON
     */
    public function testsetJavaScriptJSONOnclick()
    {
        $expected = array('#FFC513', "alert(\'g_0003\');");
        $this->kaart->setData(array('g_0003' => '#FFC513'));
        $this->kaart->setJavaScript(array('g_0003' => "alert('g_0003');"));
        $jsonkaart = json_decode($this->kaart->fetch('json'), true);
        $actual = array($jsonkaart['features'][442]['properties']['style']['fill'],
            $jsonkaart['features'][442]['properties']['onclick']);
        $this->assertEquals($expected, $actual);
    }

    public function testsetJavaScripBitmapOnclick()
    {
        $expected
            = '<area shape="poly" coords="572,57,572,57,571,57,570,58,569,58,569,58,567,57,566,57,565,57,565,58,565,58,565,59,565,61,565,61,565,62,565,62,565,62,564,62,563,62,562,62,561,62,561,62,561,63,561,65,561,65,560,65,560,65,561,67,561,67,562,69,562,69,562,70,564,69,564,71,565,71,565,71,567,71,567,69,569,69,569,70,572,69,573,69,573,66,573,66,572,66,572,65,572,63,572,63,572,61,572,61,572,59,572,58,572,57" onclick="alert(\'g_0003\');" id="g_0003" />';
        $this->kaart->setJavaScript(array('g_0003' => 'alert(\'g_0003\');'));
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $imagemap_array = explode("\n", trim($this->kaart->getImagemap()));
        $actual = $imagemap_array[0];
        $this->assertEquals($expected, $actual);
    }

    public function testsetJavaScripBitmapOnmouseover()
    {
        $expected
            = '<area shape="poly" coords="572,57,572,57,571,57,570,58,569,58,569,58,567,57,566,57,565,57,565,58,565,58,565,59,565,61,565,61,565,62,565,62,565,62,564,62,563,62,562,62,561,62,561,62,561,63,561,65,561,65,560,65,560,65,561,67,561,67,562,69,562,69,562,70,564,69,564,71,565,71,565,71,567,71,567,69,569,69,569,70,572,69,573,69,573,66,573,66,572,66,572,65,572,63,572,63,572,61,572,61,572,59,572,58,572,57" onmouseover="alert(\'g_0003\');" id="g_0003" />';
        $this->kaart->setJavaScript(array('g_0003' => 'alert(\'g_0003\');'), 'onmouseover');
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $imagemap_array = explode("\n", trim($this->kaart->getImagemap()));
        $actual = $imagemap_array[0];
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws \ImagickException
     */
    public function testAlternatePathsFile()
    {
        $filename = substr(__FUNCTION__, 4) . '.png';
        $reference_image = KAART_REFERENCE_IMAGES_DIR . '/' . $filename;
        $this->kaart->setPathsFile(__DIR__ . '/data/alternate_paths.json');
        $this->saveFile($filename, $this->kaart->fetch('png'));
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename, $reference_image);
        $this->assertEquals(0, $result, "method setPathsFile; check file $filename");
    }

    public function testSetGeneralLinkSVG()
    {
        $filename = substr(__FUNCTION__, 4) . '.svg';
        $expected = 'd86034c76117adba22dc26be089f1c6a';
        $gemeentes = array('g_0363' => '#FFC513');
        $this->kaart->setData($gemeentes);
        $this->kaart->setLink('http://www.example.com/?code=%s');
        $actual = md5($this->saveFile($filename, $this->kaart->fetch('svg')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    public function testSetGeneralLinkBitmap()
    {
        $filename = substr(__FUNCTION__, 4) . '.html';
        $expected = 'df64971e0ff6b2537f44d0eee9690def';
        $gemeentes = array('g_0363' => '#FFC513');
        $this->kaart->setData($gemeentes);
        $this->kaart->setLink('http://www.example.com/?code=%s');
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $actual = md5($this->saveFile($filename, $this->kaart->getImagemap()));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    public function testSetGeneralLinkKML()
    {
        $filename = substr(__FUNCTION__, 4) . '.kml';
        $expected = 'a7f869e8909c35f1710967ca623a824e';
        $this->kaart->setLink('http://www.example.com/?gemeente=%s');
        $actual = md5($this->saveFile($filename, $this->kaart->fetch('kml')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    /**
     * @group JSON
     */
    public function testSetGeneralLinkJSON()
    {
        $expected = 'http://www.example.com/?gemeente=g_0003';
        $this->kaart->setLink('http://www.example.com/?gemeente=%s');
        $jsonkaart = json_decode($this->kaart->fetch('json'), true);
        $properties = $jsonkaart['features'][0]['properties'];
        $actual = $properties['href'];
        $this->assertEquals($expected, $actual);
    }

    public function testSetGeneralLinkSVGHighlightedOnly()
    {
        $filename = substr(__FUNCTION__, 4) . '.svg';
        $expected = '1b98664a7673e576dac2a2c5077b5bec';
        $gemeentes = array('g_0363' => '#FFC513');
        $this->kaart->setData($gemeentes);
        $this->kaart->setLinkHighlighted('http://www.example.com/?code=%s');
        $actual = md5($this->saveFile($filename, $this->kaart->fetch('svg')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    public function testSetGeneralLinkBitmapHighlightedOnly()
    {
        $filename = substr(__FUNCTION__, 4) . '.html';
        $expected = '7cb5d8919bfa8adfcd18ac8924f3c5d0';
        $gemeentes = array('g_0363' => '#FFC513');
        $this->kaart->setData($gemeentes);
        $this->kaart->setLinkHighlighted('http://www.example.com/?code=%s');
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $actual = md5($this->saveFile($filename, $this->kaart->getImagemap()));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    public function testSetGeneralLinkKMLHighlightedOnly()
    {
        $filename = substr(__FUNCTION__, 4) . '.kml';
        $expected = 'd7700526eb6cd46522aca7b48e9e64e7';
        $gemeentes = array('g_0363' => '#FFC513');
        $this->kaart->setData($gemeentes);
        $this->kaart->setLinkHighlighted('http://www.example.com/?code=%s');
        $actual = md5($this->saveFile($filename, $this->kaart->fetch('kml')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    /**
     * @group JSON
     */
    public function testSetGeneralLinkJSONHighlightedOnly()
    {
        $expected = 'http://www.example.com/?code=g_0363';
        $gemeentes = array('g_0363' => '#FFC513');
        $this->kaart->setData($gemeentes);
        $this->kaart->setLinkHighlighted('http://www.example.com/?code=%s');
        $jsonkaart = json_decode($this->kaart->fetch('json'), true);
        $properties = $jsonkaart['features'][0]['properties'];
        $this->assertArrayNotHasKey('href', $properties);
        $actual = $jsonkaart['features'][442]['properties']['href'];
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws \ImagickException
     */
    public function testCorop()
    {
        $filename = substr(__FUNCTION__, 4) . '.png';
        $reference_image = KAART_REFERENCE_IMAGES_DIR . '/' . $filename;
        $data = array('corop_22' => '#FFC513');
        unset($this->kaart);
        $this->kaart = new Kaart('corop');
        $this->kaart->setData($data);
        $this->saveFile($filename, $this->kaart->fetch('png'));
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename, $reference_image);
        $this->assertEquals(0, $result, "method setPathsFile; check file $filename");
    }

    /**
     * @throws \ImagickException
     */
    public function testDialectAreas()
    {
        $filename = substr(__FUNCTION__, 4) . '.png';
        $reference_image = KAART_REFERENCE_IMAGES_DIR . '/' . $filename;
        $data = array('dial_07' => '#FFC513');
        unset($this->kaart);
        $this->kaart = new Kaart('dialectareas');
        $this->kaart->setData($data);
        $this->saveFile($filename, $this->kaart->fetch('png'));
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename, $reference_image);
        $this->assertEquals(0, $result, "method setPathsFile; check file $filename");
    }

    public function testfetchKMLCorop()
    {
        $filename = substr(__FUNCTION__, 4) . '.kml';
        $expected = '87bb57b90d8cfaf0e4aedd17d3352ee7';
        unset($this->kaart);
        $this->kaart = new Kaart('corop');
        $actual = md5($this->saveFile($filename, $this->kaart->fetch('kml')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    public function testfetchKMLProvincies()
    {
        $filename = substr(__FUNCTION__, 4) . '.kml';
        $expected = 'e49f1dd148f0bec2da24cfc5d8710e46';
        unset($this->kaart);
        $this->kaart = new Kaart('provincies');
        $actual = md5($this->saveFile($filename, $this->kaart->fetch('kml')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    /**
     * @throws \ImagickException
     */
    public function testsetAdditionalPathsFiles()
    {
        $filename = substr(__FUNCTION__, 4) . '.png';
        $reference_image = KAART_REFERENCE_IMAGES_DIR . '/' . $filename;
        $this->kaart->setAdditionalPathsFiles(array('municipalities_flanders.json', 'border_nl_be.json'));
        $this->kaart->setIniFile('municipalities_nl_flanders.ini');
        $this->saveFile($filename, $this->kaart->fetch('png'));
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename, $reference_image);
        $this->assertEquals(0, $result, "method setPathsFile; check file $filename");

        unset($this->kaart);
        $this->kaart = new Kaart('gemeentes');
        $this->kaart->setAdditionalPathsFiles(array('municipalities_flanders.json', 'border_nl_be.json'));
        $this->kaart->setIniFile('municipalities_nl_flanders.ini');
        $expected = 800;
        $actual = $this->kaart->getPixelWidth();
        $this->assertEquals($expected, $actual);

        unset($this->kaart);
        $this->kaart = new Kaart('gemeentes');
        $this->kaart->setAdditionalPathsFiles(array('municipalities_flanders.json', 'border_nl_be.json'));
        $this->kaart->setPixelWidth(400);
        $this->kaart->setIniFile('municipalities_nl_flanders.ini');
        $expected = 400;
        $actual = $this->kaart->getPixelWidth();
        $this->assertEquals($expected, $actual);

        unset($this->kaart);
        $this->kaart = new Kaart('gemeentes');
        $filename = substr(__FUNCTION__, 4) . '.dialect.png';
        $reference_image = KAART_REFERENCE_IMAGES_DIR . '/' . $filename;
        $this->kaart->setAdditionalPathsFiles(array('dialectareas.json'));
        $this->saveFile($filename, $this->kaart->fetch('png'));
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename, $reference_image);
        $this->assertEquals(0, $result, "method setPathsFile; check file $filename");
    }

    /**
     * @throws \ImagickException
     */
    public function testMunicipalitiesDutchlanguagearea()
    {
        $filename = substr(__FUNCTION__, 4) . '.png';
        $reference_image = KAART_REFERENCE_IMAGES_DIR . '/' . $filename;
        unset($this->kaart);
        $this->kaart = new Kaart('municipalities_nl_flanders');
        $this->saveFile($filename, $this->kaart->fetch('png'));
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename, $reference_image);
        $this->assertEquals(0, $result, "method setPathsFile; check file $filename");
    }


    public function testgetPossibleAreas()
    {
        unset($this->kaart);
        $this->kaart = new Kaart('provincies');
        $actual = $this->kaart->getPossibleAreas();
        $expected = array(
            'p_20' => 'Groningen',
            'p_21' => 'Friesland',
            'p_22' => 'Drenthe',
            'p_23' => 'Overijssel',
            'p_24' => 'Flevoland',
            'p_25' => 'Gelderland',
            'p_26' => 'Utrecht',
            'p_27' => 'Noord-Holland',
            'p_28' => 'Zuid-Holland',
            'p_29' => 'Zeeland',
            'p_30' => 'Noord-Brabant',
            'p_31' => 'Limburg'
        );
        $this->assertEquals($expected, $actual);

        unset($this->kaart);
        $this->kaart = new Kaart('municipalities_nl_flanders');
        $expected = 'c829e95bba946698060baaa2386eb18a';
        $actual = md5(join(',', $this->kaart->getPossibleAreas()));
        $this->assertEquals($expected, $actual);
    }

    public function testaddDataMunicipalitiesNLFlanders()
    {
        unset($this->kaart);
        $expected = array('d87506bd137552743adb9918b37ca247');
        $this->kaart = new Kaart('municipalities_nl_flanders');
        $filename = substr(__FUNCTION__, 4) . '.svg';
        $gemeentes = array(
            'g_0432' => '#FFE680',
            'g_0420' => '#FFDD55',
            'g_0448' => '#FFD42A',
            'g_0476' => '#FFCC00',
            'g_0373' => '#D4AA00',
            'g_0400' => '#AA8800',
            'g_0366' => '#806600',
            'g_0463' => '#FFCC00',
            'g_0462' => '#FFEEAA',
            'g_12029' => '#FFE680',
            'g_13036' => '#FFDD55',
            'g_23103' => '#FFD42A',
            'g_23094' => '#FFCC00',
            'g_24107' => '#D4AA00',
            'g_24109' => '#AA8800',
            'g_73042' => '#806600'
        );
        $this->kaart->setData($gemeentes);
        $actual = md5($this->saveFile($filename, $this->kaart->fetch('svg')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    /**
     * @throws \ImagickException
     */
    public function testCustomHighlightOutlinePNG()
    {
        $filename = substr(__FUNCTION__, 4) . '.png';
        $reference_image = KAART_REFERENCE_IMAGES_DIR . '/' . $filename;
        $gemeentes = array('g_0171' => array('fill' => '#FFC513', 'outline' => 'black', 'strokewidth' => '2'));
        $this->kaart->setData($gemeentes);
        $this->saveFile($filename, $this->kaart->fetch('png'));
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename, $reference_image);
        $this->assertEquals(0, $result, "method setPathsFile; check file $filename");
    }

    public function testCustomHighlightOutlineSVG()
    {
        $filename = substr(__FUNCTION__, 4) . '.svg';
        $gemeentes = array('g_0171' => array('fill' => '#FFC513', 'outline' => 'black', 'strokewidth' => '2'));
        $this->kaart->setData($gemeentes);
        $expected = '21705f7e0aa2909968eefe9819da151e';
        $actual = md5($this->saveFile($filename, $this->kaart->fetch('svg')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    public function testCustomHighlightOutlineKML()
    {
        $filename = substr(__FUNCTION__, 4) . '.kml';
        $gemeentes = array('g_0171' => array('fill' => '#FFC513', 'outline' => 'black', 'strokewidth' => '2'));
        $this->kaart->setData($gemeentes);
        $expected = '8af437d3254180bc499c1a746f61abab';
        $actual = md5($this->saveFile($filename, $this->kaart->fetch('kml')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    /**
     * @throws \ImagickException
     */
    public function testCustomOutlineProvinciePNG()
    {
        $filename = substr(__FUNCTION__, 4) . '.png';
        $reference_image = KAART_REFERENCE_IMAGES_DIR . '/' . $filename;
        $extra = array('p_20' => array('fill' => 'none', 'outline' => 'red', 'strokewidth' => '2'));
        $this->kaart->setData($extra);
        $this->kaart->setAdditionalPathsFiles(array('provinces.json'));
        $this->saveFile($filename, $this->kaart->fetch('png'));
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename, $reference_image);
        $this->assertEquals(0, $result, "method setPathsFile; check file $filename");
    }

    public function testCustomOutlineProvincieSVG()
    {
        $extra = array('p_20' => array('fill' => 'none', 'outline' => 'red', 'strokewidth' => '2'));
        $this->kaart->setData($extra);
        $this->kaart->setAdditionalPathsFiles(array('provinces.json'));

        $filename = substr(__FUNCTION__, 4) . '.svg';
        $expected = array('8a94c9557b7b0ee6b763998592f4e452');
        $actual = md5($this->saveFile($filename, $this->kaart->fetch('svg')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    public function testCustomOutlineProvincieKML()
    {
        $extra = array('p_20' => array('fill' => 'none', 'outline' => 'red', 'strokewidth' => '2'));
        $this->kaart->setData($extra);
        $this->kaart->setAdditionalPathsFiles(array('provinces.json'));

        $filename = substr(__FUNCTION__, 4) . '.kml';
        $expected = 'aeeb0ca9a279fbe0a90468cd5569a701';
        $actual = md5($this->saveFile($filename, $this->kaart->fetch('kml')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }
}
