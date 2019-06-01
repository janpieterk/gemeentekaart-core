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
            $result = $image1->compareImages($image2,
                imagick::METRIC_MEANABSOLUTEERROR);
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
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename,
            $reference_image);
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
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename,
            $reference_image);
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
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename,
            $reference_image, 15);
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
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename,
            $reference_image);
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
        $this->assertEquals($actual1, $actual2,
            "check files $filename1 en $filename2");
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
        $this->assertEquals($actual1, $actual2,
            "check files $filename1 en $filename2");
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
        $this->assertEquals($actual1, $actual2,
            "check files $filename1 en $filename2");
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
        list($new_file_exists, $message) = $this->myFileExists(substr(__FUNCTION__,
                4) . '.svg', 'svg');
        $this->assertTrue($new_file_exists, $message);
    }

    public function testsaveAsFilePNG()
    {
        list($new_file_exists, $message) = $this->myFileExists(substr(__FUNCTION__,
                4) . '.png', 'png');
        $this->assertTrue($new_file_exists, $message);
    }

    public function testsaveAsFileGIF()
    {
        list($new_file_exists, $message) = $this->myFileExists(substr(__FUNCTION__,
                4) . '.gif', 'gif');
        $this->assertTrue($new_file_exists, $message);
    }

    public function testsaveAsFileJPEG()
    {
        list($new_file_exists, $message) = $this->myFileExists(substr(__FUNCTION__,
                4) . '.jpeg', 'jpeg');
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
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename,
            $reference_image);
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
        $svg = simplexml_load_string(str_replace('xlink:href', 'href',
                $this->kaart->fetch()));
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
        $expected = '<area shape="poly" coords="572,57,572,57,571,57,570,58,569,58,569,58,567,57,566,57,565,57,565,58,565,58,565,59,565,61,565,61,565,62,565,62,565,62,564,62,563,62,562,62,561,62,561,62,561,63,561,65,561,65,560,65,560,65,561,67,561,67,562,69,562,69,562,70,564,69,564,71,565,71,565,71,567,71,567,69,569,69,569,70,572,69,573,69,573,66,573,66,572,66,572,65,572,63,572,63,572,61,572,61,572,59,572,58,572,57" href="http://www.janpieterkunst.nl/" target="_blank" id="g_0003" />';
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
        $this->kaart->setJavaScript(array('g_0003' => 'alert(\'g_0003\');'),
            'onmouseover');
        $expected = "bdc67b3a013df088bed498a3ce3953e8";
        $actual = md5($this->saveFile($filename, $this->kaart->fetch()));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    public function testsetJavaScriptSVGOnmouseover()
    {
        $filename = substr(__FUNCTION__, 4) . '.svg';
        $this->kaart->setData(array('g_0003' => '#FFC513'));
        $this->kaart->setJavaScript(array('g_0003' => 'alert(\'g_0003\');'),
            'onmouseover');
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
        $expected = '<area shape="poly" coords="572,57,572,57,571,57,570,58,569,58,569,58,567,57,566,57,565,57,565,58,565,58,565,59,565,61,565,61,565,62,565,62,565,62,564,62,563,62,562,62,561,62,561,62,561,63,561,65,561,65,560,65,560,65,561,67,561,67,562,69,562,69,562,70,564,69,564,71,565,71,565,71,567,71,567,69,569,69,569,70,572,69,573,69,573,66,573,66,572,66,572,65,572,63,572,63,572,61,572,61,572,59,572,58,572,57" onclick="alert(\'g_0003\');" id="g_0003" />';
        $this->kaart->setJavaScript(array('g_0003' => 'alert(\'g_0003\');'));
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $imagemap_array = explode("\n", trim($this->kaart->getImagemap()));
        $actual = $imagemap_array[0];
        $this->assertEquals($expected, $actual);
    }

    public function testsetJavaScripBitmapOnmouseover()
    {
        $expected = '<area shape="poly" coords="572,57,572,57,571,57,570,58,569,58,569,58,567,57,566,57,565,57,565,58,565,58,565,59,565,61,565,61,565,62,565,62,565,62,564,62,563,62,562,62,561,62,561,62,561,63,561,65,561,65,560,65,560,65,561,67,561,67,562,69,562,69,562,70,564,69,564,71,565,71,565,71,567,71,567,69,569,69,569,70,572,69,573,69,573,66,573,66,572,66,572,65,572,63,572,63,572,61,572,61,572,59,572,58,572,57" onmouseover="alert(\'g_0003\');" id="g_0003" />';
        $this->kaart->setJavaScript(array('g_0003' => 'alert(\'g_0003\');'),
            'onmouseover');
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
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename,
            $reference_image);
        $this->assertEquals(0, $result,
            "method setPathsFile; check file $filename");
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
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename,
            $reference_image);
        $this->assertEquals(0, $result,
            "method setPathsFile; check file $filename");
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
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename,
            $reference_image);
        $this->assertEquals(0, $result,
            "method setPathsFile; check file $filename");
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
        $this->kaart->setAdditionalPathsFiles(array('municipalities_flanders.json',
            'border_nl_be.json'));
        $this->kaart->setIniFile('municipalities_nl_flanders.ini');
        $this->saveFile($filename, $this->kaart->fetch('png'));
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename,
            $reference_image);
        $this->assertEquals(0, $result,
            "method setPathsFile; check file $filename");

        unset($this->kaart);
        $this->kaart = new Kaart('gemeentes');
        $this->kaart->setAdditionalPathsFiles(array('municipalities_flanders.json',
            'border_nl_be.json'));
        $this->kaart->setIniFile('municipalities_nl_flanders.ini');
        $expected = 800;
        $actual = $this->kaart->getPixelWidth();
        $this->assertEquals($expected, $actual);

        unset($this->kaart);
        $this->kaart = new Kaart('gemeentes');
        $this->kaart->setAdditionalPathsFiles(array('municipalities_flanders.json',
            'border_nl_be.json'));
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
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename,
            $reference_image);
        $this->assertEquals(0, $result,
            "method setPathsFile; check file $filename");
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
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename,
            $reference_image);
        $this->assertEquals(0, $result,
            "method setPathsFile; check file $filename");
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
        $gemeentes = array('g_0171' => array('fill' => '#FFC513', 'outline' => 'black',
                'strokewidth' => '2'));
        $this->kaart->setData($gemeentes);
        $this->saveFile($filename, $this->kaart->fetch('png'));
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename,
            $reference_image);
        $this->assertEquals(0, $result,
            "method setPathsFile; check file $filename");
    }

    public function testCustomHighlightOutlineSVG()
    {
        $filename = substr(__FUNCTION__, 4) . '.svg';
        $gemeentes = array('g_0171' => array('fill' => '#FFC513', 'outline' => 'black',
                'strokewidth' => '2'));
        $this->kaart->setData($gemeentes);
        $expected = '21705f7e0aa2909968eefe9819da151e';
        $actual = md5($this->saveFile($filename, $this->kaart->fetch('svg')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    public function testCustomHighlightOutlineKML()
    {
        $filename = substr(__FUNCTION__, 4) . '.kml';
        $gemeentes = array('g_0171' => array('fill' => '#FFC513', 'outline' => 'black',
                'strokewidth' => '2'));
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
        $extra = array('p_20' => array('fill' => 'none', 'outline' => 'red',
                'strokewidth' => '2'));
        $this->kaart->setData($extra);
        $this->kaart->setAdditionalPathsFiles(array('provinces.json'));
        $this->saveFile($filename, $this->kaart->fetch('png'));
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename,
            $reference_image);
        $this->assertEquals(0, $result,
            "method setPathsFile; check file $filename");
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

    public function testWrongMapType()
    {
        unset($this->kaart);
        $this->expectException(\InvalidArgumentException::class);
        $this->kaart = new Kaart('nonexistingtype');
    }

    public function testWrongYear()
    {
        unset($this->kaart);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Year 1800 not available for map type municipalities');
        $this->kaart = new Kaart('gemeentes', 1800);
    }

    public function testMapWithYear()
    {
        unset($this->kaart);
        $this->kaart = new Kaart('gemeentes', 1860);
        $filename = substr(__FUNCTION__, 4) . '.png';
        $reference_image = KAART_REFERENCE_IMAGES_DIR . '/' . $filename;
        $this->saveFile($filename, $this->kaart->fetch('png'));
        $result = $this->compareTwoImages(KAART_TESTDIRECTORY . '/' . $filename,
            $reference_image);
        $this->assertEquals(0, $result, "check file $filename");
    }

    public function testGetAllowedYears()
    {
        $expected = array(1830, 1860, 1890, 1920, 1940, 1950, 1980);
        $actual = Kaart::getAllowedYears('provincies');
        $this->assertEquals($expected, $actual);
    }

    public function testgetPossibleMunicipalities()
    {
        $actual = $this->kaart->getPossibleMunicipalities();
        $expected = array(
            'g_0003' => 'Appingedam',
            'g_0005' => 'Bedum',
            'g_0007' => 'Bellingwedde',
            'g_0009' => 'Ten Boer',
            'g_0010' => 'Delfzijl',
            'g_0014' => 'Groningen',
            'g_0015' => 'Grootegast',
            'g_0017' => 'Haren',
            'g_0018' => 'Hoogezand-Sappemeer',
            'g_0022' => 'Leek',
            'g_0024' => 'Loppersum',
            'g_0025' => 'Marum',
            'g_0034' => 'Almere',
            'g_0037' => 'Stadskanaal',
            'g_0039' => 'Scheemda',
            'g_0040' => 'Slochteren',
            'g_0047' => 'Veendam',
            'g_0048' => 'Vlagtwedde',
            'g_0050' => 'Zeewolde',
            'g_0051' => 'Skarsterlân',
            'g_0052' => 'Winschoten',
            'g_0053' => 'Winsum',
            'g_0055' => 'Boarnsterhim',
            'g_0056' => 'Zuidhorn',
            'g_0058' => 'Dongeradeel',
            'g_0059' => 'Achtkarspelen',
            'g_0060' => 'Ameland',
            'g_0063' => 'het Bildt',
            'g_0064' => 'Bolsward',
            'g_0065' => 'Dantumadeel',
            'g_0070' => 'Franekeradeel',
            'g_0072' => 'Harlingen',
            'g_0074' => 'Heerenveen',
            'g_0079' => 'Kollumerland en Nieuwkruisland',
            'g_0080' => 'Leeuwarden',
            'g_0081' => 'Leeuwarderadeel',
            'g_0082' => 'Lemsterland',
            'g_0083' => 'Menaldumadeel',
            'g_0085' => 'Ooststellingwerf',
            'g_0086' => 'Opsterland',
            'g_0088' => 'Schiermonnikoog',
            'g_0090' => 'Smallingerland',
            'g_0091' => 'Sneek',
            'g_0093' => 'Terschelling',
            'g_0096' => 'Vlieland',
            'g_0098' => 'Weststellingwerf',
            'g_0104' => 'Nijefurd',
            'g_0106' => 'Assen',
            'g_0109' => 'Coevorden',
            'g_0114' => 'Emmen',
            'g_0118' => 'Hoogeveen',
            'g_0119' => 'Meppel',
            'g_0140' => 'Littenseradiel',
            'g_0141' => 'Almelo',
            'g_0147' => 'Borne',
            'g_0148' => 'Dalfsen',
            'g_0150' => 'Deventer',
            'g_0153' => 'Enschede',
            'g_0158' => 'Haaksbergen',
            'g_0160' => 'Hardenberg',
            'g_0163' => 'Hellendoorn',
            'g_0164' => 'Hengelo',
            'g_0166' => 'Kampen',
            'g_0168' => 'Losser',
            'g_0171' => 'Noordoostpolder',
            'g_0173' => 'Oldenzaal',
            'g_0175' => 'Ommen',
            'g_0177' => 'Raalte',
            'g_0180' => 'Staphorst',
            'g_0183' => 'Tubbergen',
            'g_0184' => 'Urk',
            'g_0189' => 'Wierden',
            'g_0193' => 'Zwolle',
            'g_0196' => 'Rijnwaarden',
            'g_0197' => 'Aalten',
            'g_0200' => 'Apeldoorn',
            'g_0202' => 'Arnhem',
            'g_0203' => 'Barneveld',
            'g_0209' => 'Beuningen',
            'g_0213' => 'Brummen',
            'g_0214' => 'Buren',
            'g_0216' => 'Culemborg',
            'g_0221' => 'Doesburg',
            'g_0222' => 'Doetinchem',
            'g_0225' => 'Druten',
            'g_0226' => 'Duiven',
            'g_0228' => 'Ede',
            'g_0230' => 'Elburg',
            'g_0232' => 'Epe',
            'g_0233' => 'Ermelo',
            'g_0236' => 'Geldermalsen',
            'g_0241' => 'Groesbeek',
            'g_0243' => 'Harderwijk',
            'g_0244' => 'Hattem',
            'g_0246' => 'Heerde',
            'g_0252' => 'Heumen',
            'g_0262' => 'Lochem',
            'g_0263' => 'Maasdriel',
            'g_0265' => 'Millingen aan de Rijn',
            'g_0267' => 'Nijkerk',
            'g_0268' => 'Nijmegen',
            'g_0269' => 'Oldebroek',
            'g_0273' => 'Putten',
            'g_0274' => 'Renkum',
            'g_0275' => 'Rheden',
            'g_0277' => 'Rozendaal',
            'g_0279' => 'Scherpenzeel',
            'g_0281' => 'Tiel',
            'g_0282' => 'Ubbergen',
            'g_0285' => 'Voorst',
            'g_0289' => 'Wageningen',
            'g_0293' => 'Westervoort',
            'g_0294' => 'Winterswijk',
            'g_0296' => 'Wijchen',
            'g_0297' => 'Zaltbommel',
            'g_0299' => 'Zevenaar',
            'g_0301' => 'Zutphen',
            'g_0302' => 'Nunspeet',
            'g_0303' => 'Dronten',
            'g_0304' => 'Neerijnen',
            'g_0305' => 'Abcoude',
            'g_0307' => 'Amersfoort',
            'g_0308' => 'Baarn',
            'g_0310' => 'De Bilt',
            'g_0311' => 'Breukelen',
            'g_0312' => 'Bunnik',
            'g_0313' => 'Bunschoten',
            'g_0317' => 'Eemnes',
            'g_0321' => 'Houten',
            'g_0327' => 'Leusden',
            'g_0329' => 'Loenen',
            'g_0331' => 'Lopik',
            'g_0333' => 'Maarssen',
            'g_0335' => 'Montfoort',
            'g_0339' => 'Renswoude',
            'g_0340' => 'Rhenen',
            'g_0342' => 'Soest',
            'g_0344' => 'Utrecht',
            'g_0345' => 'Veenendaal',
            'g_0351' => 'Woudenberg',
            'g_0352' => 'Wijk bij Duurstede',
            'g_0353' => 'IJsselstein',
            'g_0355' => 'Zeist',
            'g_0356' => 'Nieuwegein',
            'g_0358' => 'Aalsmeer',
            'g_0361' => 'Alkmaar',
            'g_0362' => 'Amstelveen',
            'g_0363' => 'Amsterdam',
            'g_0364' => 'Andijk',
            'g_0365' => 'Graft-De Rijp',
            'g_0366' => 'Anna Paulowna',
            'g_0370' => 'Beemster',
            'g_0372' => 'Bennebroek',
            'g_0373' => 'Bergen (NH.)',
            'g_0375' => 'Beverwijk',
            'g_0376' => 'Blaricum',
            'g_0377' => 'Bloemendaal',
            'g_0381' => 'Bussum',
            'g_0383' => 'Castricum',
            'g_0384' => 'Diemen',
            'g_0385' => 'Edam-Volendam',
            'g_0388' => 'Enkhuizen',
            'g_0392' => 'Haarlem',
            'g_0393' => 'Haarlemmerliede en Spaarnwoude',
            'g_0394' => 'Haarlemmermeer',
            'g_0395' => 'Harenkarspel',
            'g_0396' => 'Heemskerk',
            'g_0397' => 'Heemstede',
            'g_0398' => 'Heerhugowaard',
            'g_0399' => 'Heiloo',
            'g_0400' => 'Den Helder',
            'g_0402' => 'Hilversum',
            'g_0405' => 'Hoorn',
            'g_0406' => 'Huizen',
            'g_0412' => 'Niedorp',
            'g_0415' => 'Landsmeer',
            'g_0416' => 'Langedijk',
            'g_0417' => 'Laren',
            'g_0420' => 'Medemblik',
            'g_0424' => 'Muiden',
            'g_0425' => 'Naarden',
            'g_0431' => 'Oostzaan',
            'g_0432' => 'Opmeer',
            'g_0437' => 'Ouder-Amstel',
            'g_0439' => 'Purmerend',
            'g_0441' => 'Schagen',
            'g_0448' => 'Texel',
            'g_0450' => 'Uitgeest',
            'g_0451' => 'Uithoorn',
            'g_0453' => 'Velsen',
            'g_0457' => 'Weesp',
            'g_0458' => 'Schermer',
            'g_0459' => 'Wervershoof',
            'g_0462' => 'Wieringen',
            'g_0463' => 'Wieringermeer',
            'g_0473' => 'Zandvoort',
            'g_0476' => 'Zijpe',
            'g_0478' => 'Zeevang',
            'g_0479' => 'Zaanstad',
            'g_0482' => 'Alblasserdam',
            'g_0483' => 'Alkemade',
            'g_0484' => 'Alphen aan den Rijn',
            'g_0489' => 'Barendrecht',
            'g_0491' => 'Bergambacht',
            'g_0497' => 'Bodegraven',
            'g_0498' => 'Drechterland',
            'g_0499' => 'Boskoop',
            'g_0501' => 'Brielle',
            'g_0502' => 'Capelle aan den IJssel',
            'g_0503' => 'Delft',
            'g_0504' => 'Dirksland',
            'g_0505' => 'Dordrecht',
            'g_0511' => 'Goedereede',
            'g_0512' => 'Gorinchem',
            'g_0513' => 'Gouda',
            'g_0518' => '\'s-Gravenhage',
            'g_0523' => 'Hardinxveld-Giessendam',
            'g_0530' => 'Hellevoetsluis',
            'g_0531' => 'Hendrik-Ido-Ambacht',
            'g_0532' => 'Stede Broec',
            'g_0534' => 'Hillegom',
            'g_0537' => 'Katwijk',
            'g_0542' => 'Krimpen aan den IJssel',
            'g_0545' => 'Leerdam',
            'g_0546' => 'Leiden',
            'g_0547' => 'Leiderdorp',
            'g_0553' => 'Lisse',
            'g_0556' => 'Maassluis',
            'g_0559' => 'Middelharnis',
            'g_0563' => 'Moordrecht',
            'g_0567' => 'Nieuwerkerk aan den IJssel',
            'g_0568' => 'Bernisse',
            'g_0569' => 'Nieuwkoop',
            'g_0571' => 'Nieuw-Lekkerland',
            'g_0575' => 'Noordwijk',
            'g_0576' => 'Noordwijkerhout',
            'g_0579' => 'Oegstgeest',
            'g_0580' => 'Oostflakkee',
            'g_0584' => 'Oud-Beijerland',
            'g_0585' => 'Binnenmaas',
            'g_0588' => 'Korendijk',
            'g_0589' => 'Oudewater',
            'g_0590' => 'Papendrecht',
            'g_0595' => 'Reeuwijk',
            'g_0597' => 'Ridderkerk',
            'g_0599' => 'Rotterdam',
            'g_0600' => 'Rozenburg',
            'g_0603' => 'Rijswijk',
            'g_0606' => 'Schiedam',
            'g_0608' => 'Schoonhoven',
            'g_0610' => 'Sliedrecht',
            'g_0611' => 'Cromstrijen',
            'g_0612' => 'Spijkenisse',
            'g_0613' => 'Albrandswaard',
            'g_0614' => 'Westvoorne',
            'g_0617' => 'Strijen',
            'g_0620' => 'Vianen',
            'g_0622' => 'Vlaardingen',
            'g_0623' => 'Vlist',
            'g_0626' => 'Voorschoten',
            'g_0627' => 'Waddinxveen',
            'g_0629' => 'Wassenaar',
            'g_0632' => 'Woerden',
            'g_0637' => 'Zoetermeer',
            'g_0638' => 'Zoeterwoude',
            'g_0642' => 'Zwijndrecht',
            'g_0643' => 'Nederlek',
            'g_0644' => 'Ouderkerk',
            'g_0645' => 'Jacobswoude',
            'g_0653' => 'Gaasterlân-Sleat',
            'g_0654' => 'Borsele',
            'g_0664' => 'Goes',
            'g_0668' => 'West Maas en Waal',
            'g_0677' => 'Hulst',
            'g_0678' => 'Kapelle',
            'g_0683' => 'Wymbritseradiel',
            'g_0687' => 'Middelburg',
            'g_0689' => 'Giessenlanden',
            'g_0693' => 'Graafstroom',
            'g_0694' => 'Liesveld',
            'g_0703' => 'Reimerswaal',
            'g_0707' => 'Zederik',
            'g_0710' => 'Wnseradiel',
            'g_0715' => 'Terneuzen',
            'g_0716' => 'Tholen',
            'g_0717' => 'Veere',
            'g_0718' => 'Vlissingen',
            'g_0733' => 'Lingewaal',
            'g_0736' => 'De Ronde Venen',
            'g_0737' => 'Tytsjerksteradiel',
            'g_0738' => 'Aalburg',
            'g_0743' => 'Asten',
            'g_0744' => 'Baarle-Nassau',
            'g_0748' => 'Bergen op Zoom',
            'g_0753' => 'Best',
            'g_0755' => 'Boekel',
            'g_0756' => 'Boxmeer',
            'g_0757' => 'Boxtel',
            'g_0758' => 'Breda',
            'g_0762' => 'Deurne',
            'g_0765' => 'Pekela',
            'g_0766' => 'Dongen',
            'g_0770' => 'Eersel',
            'g_0772' => 'Eindhoven',
            'g_0777' => 'Etten-Leur',
            'g_0779' => 'Geertruidenberg',
            'g_0784' => 'Gilze en Rijen',
            'g_0785' => 'Goirle',
            'g_0786' => 'Grave',
            'g_0788' => 'Haaren',
            'g_0794' => 'Helmond',
            'g_0796' => '\'s-Hertogenbosch',
            'g_0797' => 'Heusden',
            'g_0798' => 'Hilvarenbeek',
            'g_0808' => 'Lith',
            'g_0809' => 'Loon op Zand',
            'g_0815' => 'Mill en Sint Hubert',
            'g_0820' => 'Nuenen, Gerwen en Nederwetten',
            'g_0823' => 'Oirschot',
            'g_0824' => 'Oisterwijk',
            'g_0826' => 'Oosterhout',
            'g_0828' => 'Oss',
            'g_0840' => 'Rucphen',
            'g_0844' => 'Schijndel',
            'g_0845' => 'Sint-Michielsgestel',
            'g_0846' => 'Sint-Oedenrode',
            'g_0847' => 'Someren',
            'g_0848' => 'Son en Breugel',
            'g_0851' => 'Steenbergen',
            'g_0852' => 'Waterland',
            'g_0855' => 'Tilburg',
            'g_0856' => 'Uden',
            'g_0858' => 'Valkenswaard',
            'g_0860' => 'Veghel',
            'g_0861' => 'Veldhoven',
            'g_0865' => 'Vught',
            'g_0866' => 'Waalre',
            'g_0867' => 'Waalwijk',
            'g_0870' => 'Werkendam',
            'g_0873' => 'Woensdrecht',
            'g_0874' => 'Woudrichem',
            'g_0879' => 'Zundert',
            'g_0880' => 'Wormerland',
            'g_0881' => 'Onderbanken',
            'g_0882' => 'Landgraaf',
            'g_0885' => 'Arcen en Velden',
            'g_0888' => 'Beek',
            'g_0889' => 'Beesel',
            'g_0893' => 'Bergen (L.)',
            'g_0899' => 'Brunssum',
            'g_0905' => 'Eijsden',
            'g_0907' => 'Gennep',
            'g_0917' => 'Heerlen',
            'g_0918' => 'Helden',
            'g_0928' => 'Kerkrade',
            'g_0929' => 'Kessel',
            'g_0934' => 'Maasbree',
            'g_0935' => 'Maastricht',
            'g_0936' => 'Margraten',
            'g_0938' => 'Meerssen',
            'g_0941' => 'Meijel',
            'g_0944' => 'Mook en Middelaar',
            'g_0946' => 'Nederweert',
            'g_0951' => 'Nuth',
            'g_0957' => 'Roermond',
            'g_0962' => 'Schinnen',
            'g_0964' => 'Sevenum',
            'g_0965' => 'Simpelveld',
            'g_0971' => 'Stein',
            'g_0981' => 'Vaals',
            'g_0983' => 'Venlo',
            'g_0984' => 'Venray',
            'g_0986' => 'Voerendaal',
            'g_0988' => 'Weert',
            'g_0993' => 'Meerlo-Wanssum',
            'g_0994' => 'Valkenburg aan de Geul',
            'g_0995' => 'Lelystad',
            'g_1507' => 'Horst aan de Maas',
            'g_1509' => 'Oude IJsselstreek',
            'g_1525' => 'Teylingen',
            'g_1581' => 'Utrechtse Heuvelrug',
            'g_1586' => 'Oost Gelre',
            'g_1598' => 'Koggenland',
            'g_1621' => 'Lansingerland',
            'g_1640' => 'Leudal',
            'g_1641' => 'Maasgouw',
            'g_1651' => 'Eemsmond',
            'g_1652' => 'Gemert-Bakel',
            'g_1655' => 'Halderberge',
            'g_1658' => 'Heeze-Leende',
            'g_1659' => 'Laarbeek',
            'g_1661' => 'Reiderland',
            'g_1663' => 'De Marne',
            'g_1666' => 'Zevenhuizen-Moerkapelle',
            'g_1667' => 'Reusel-De Mierden',
            'g_1669' => 'Roerdalen',
            'g_1671' => 'Maasdonk',
            'g_1672' => 'Rijnwoude',
            'g_1674' => 'Roosendaal',
            'g_1676' => 'Schouwen-Duiveland',
            'g_1680' => 'Aa en Hunze',
            'g_1681' => 'Borger-Odoorn',
            'g_1684' => 'Cuijk',
            'g_1685' => 'Landerd',
            'g_1690' => 'De Wolden',
            'g_1695' => 'Noord-Beveland',
            'g_1696' => 'Wijdemeren',
            'g_1699' => 'Noordenveld',
            'g_1700' => 'Twenterand',
            'g_1701' => 'Westerveld',
            'g_1702' => 'Sint Anthonis',
            'g_1705' => 'Lingewaard',
            'g_1706' => 'Cranendonck',
            'g_1708' => 'Steenwijkerland',
            'g_1709' => 'Moerdijk',
            'g_1711' => 'Echt-Susteren',
            'g_1714' => 'Sluis',
            'g_1719' => 'Drimmelen',
            'g_1721' => 'Bernheze',
            'g_1722' => 'Ferwerderadiel',
            'g_1723' => 'Alphen-Chaam',
            'g_1724' => 'Bergeijk',
            'g_1728' => 'Bladel',
            'g_1729' => 'Gulpen-Wittem',
            'g_1730' => 'Tynaarlo',
            'g_1731' => 'Midden-Drenthe',
            'g_1734' => 'Overbetuwe',
            'g_1735' => 'Hof van Twente',
            'g_1740' => 'Neder-Betuwe',
            'g_1742' => 'Rijssen-Holten',
            'g_1771' => 'Geldrop-Mierlo',
            'g_1773' => 'Olst-Wijhe',
            'g_1774' => 'Dinkelland',
            'g_1783' => 'Westland',
            'g_1842' => 'Midden-Delfland',
            'g_1859' => 'Berkelland',
            'g_1876' => 'Bronckhorst',
            'g_1883' => 'Sittard-Geleen',
            'g_1896' => 'Zwartewaterland',
            'g_1916' => 'Leidschendam-Voorburg',
            'g_1926' => 'Pijnacker-Nootdorp',
            'g_1955' => 'Montferland',
            'g_1987' => 'Menterwolde',
        );
        $this->assertEquals($expected, $actual);
        unset($this->kaart);
        $expected = array(
            'a_10996' => 'Adorp',
            'a_10999' => 'Aduard',
            'a_10886' => 'Appingedam',
            'a_10539' => 'Baflo',
            'a_10425' => 'Bedum',
            'a_11043' => 'Beerta',
            'a_11294' => 'Bierum',
            'a_10891' => 'Ten Boer',
            'a_10976' => 'Delfzijl',
            'a_10904' => 'Eenrum',
            'a_10136' => 'Ezinge',
            'a_10212' => 'Finsterwolde',
            'a_10426' => 'Groningen',
            'a_11080' => 'Grootegast',
            'a_10244' => 'Grijpskerk',
            'a_11058' => 'Haren',
            'a_10372' => 'Kantens',
            'a_10628' => 'Kloosterburen',
            'a_10604' => 'Leek',
            'a_11050' => 'Leens',
            'a_10934' => 'Loppersum',
            'a_11431' => 'Marum',
            'a_10304' => 'Meeden',
            'a_11199' => 'Middelstum',
            'a_10356' => 'Midwolda',
            'a_10550' => 'Muntendam',
            'a_10950' => 'Nieuwe Pekela',
            'a_10008' => 'Nieuweschans',
            'a_10126' => 'Nieuwolda',
            'a_10298' => 'Oldehove',
            'a_11332' => 'Oldekerk',
            'a_10848' => 'Oude Pekela',
            'a_10711' => 'Scheemda',
            'a_10747' => 'Slochteren',
            'a_10771' => 'Stedum',
            'a_11023' => 'Termunten',
            'a_11137' => 'Uithuizen',
            'a_10246' => 'Uithuizermeeden',
            'a_10180' => 'Ulrum',
            'a_11035' => 'Usquert',
            'a_11292' => 'Veendam',
            'a_11249' => 'Vlagtwedde',
            'a_10498' => 'Warffum',
            'a_10453' => 'Winschoten',
            'a_11135' => 'Winsum',
            'a_10956' => '\'t Zandt',
            'a_10021' => 'Zuidhorn',
            'a_10199' => 'Achtkarspelen',
            'a_11153' => 'Ameland',
            'a_10422' => 'Baarderadeel',
            'a_10156' => 'Barradeel',
            'a_10128' => 'het Bildt',
            'a_10865' => 'Bolsward',
            'a_10650' => 'Dantumadeel',
            'a_10198' => 'Dokkum',
            'a_10086' => 'Doniawerstal',
            'a_11284' => 'Ferwerderadeel',
            'a_11226' => 'Franeker',
            'a_10404' => 'Franekeradeel',
            'a_10036' => 'Gaasterland',
            'a_10909' => 'Harlingen',
            'a_10022' => 'Haskerland',
            'a_10877' => 'Hennaarderadeel',
            'a_10672' => 'Hindeloopen',
            'a_10815' => 'Idaarderadeel',
            'a_10984' => 'Kollumerland en Nieuwkruisland',
            'a_11228' => 'Leeuwarden',
            'a_10851' => 'Leeuwarderadeel',
            'a_10142' => 'Lemsterland',
            'a_11144' => 'Menaldumadeel',
            'a_11102' => 'Oostdongeradeel',
            'a_10836' => 'Ooststellingwerf',
            'a_10005' => 'Opsterland',
            'a_10330' => 'Rauwerderhem',
            'a_10355' => 'Schiermonnikoog',
            'a_10867' => 'Sloten (F.)',
            'a_10405' => 'Smallingerland',
            'a_11421' => 'Sneek',
            'a_11210' => 'Terschelling',
            'a_11241' => 'Tietjerksteradeel',
            'a_10140' => 'Utingeradeel',
            'a_10211' => 'Vlieland',
            'a_10129' => 'Westdongeradeel',
            'a_11322' => 'Weststellingwerf',
            'a_10454' => 'Wonseradeel',
            'a_10768' => 'Workum',
            'a_11427' => 'Wymbritseradeel',
            'a_11062' => 'IJlst',
            'a_10787' => 'Anloo',
            'a_10522' => 'Assen',
            'a_10520' => 'Beilen',
            'a_10448' => 'Borger',
            'a_10383' => 'Coevorden',
            'a_11004' => 'Dalen',
            'a_10418' => 'Diever',
            'a_10331' => 'Dwingeloo',
            'a_10622' => 'Eelde',
            'a_11180' => 'Emmen',
            'a_11099' => 'Gasselte',
            'a_10506' => 'Gieten',
            'a_10186' => 'Havelte',
            'a_10839' => 'Hoogeveen',
            'a_11204' => 'Meppel',
            'a_10608' => 'Norg',
            'a_10774' => 'Nijeveen',
            'a_11320' => 'Odoorn',
            'a_10490' => 'Oosterhesselen',
            'a_10501' => 'Peize',
            'a_10699' => 'Roden',
            'a_10470' => 'Rolde',
            'a_10629' => 'Ruinen',
            'a_10944' => 'Ruinerwold',
            'a_10489' => 'Sleen',
            'a_11033' => 'Smilde',
            'a_11120' => 'Vledder',
            'a_10630' => 'Vries',
            'a_11095' => 'Westerbork',
            'a_10753' => 'De Wijk',
            'a_10002' => 'Zuidlaren',
            'a_10262' => 'Zuidwolde',
            'a_10872' => 'Zweeloo',
            'a_11400' => 'Ambt Delden',
            'a_11190' => 'Avereest',
            'a_11030' => 'Bathmen',
            'a_10320' => 'Blankenham',
            'a_10689' => 'Blokzijl',
            'a_10326' => 'Borne',
            'a_11007' => 'Dalfsen',
            'a_10245' => 'Denekamp',
            'a_10899' => 'Deventer',
            'a_10311' => 'Diepenheim',
            'a_10933' => 'Diepenveen',
            'a_10364' => 'Enschede',
            'a_10746' => 'Genemuiden',
            'a_10708' => 'Giethoorn',
            'a_10076' => 'Goor',
            'a_11339' => 'Gramsbergen',
            'a_11435' => 'Haaksbergen',
            'a_10395' => 'Den Ham',
            'a_10115' => 'Hasselt',
            'a_10854' => 'Heino',
            'a_10806' => 'Hellendoorn',
            'a_10907' => 'Hengelo (O.)',
            'a_11246' => 'Holten',
            'a_10253' => 'Kampen',
            'a_11148' => 'Kuinre',
            'a_11166' => 'Losser',
            'a_11382' => 'Markelo',
            'a_10384' => 'Nieuwleusen',
            'a_10858' => 'Oldemarkt',
            'a_11100' => 'Oldenzaal',
            'a_10409' => 'Olst',
            'a_11079' => 'Ootmarsum',
            'a_10279' => 'Raalte',
            'a_10358' => 'Rijssen',
            'a_10913' => 'Stad Delden',
            'a_11362' => 'Staphorst',
            'a_10546' => 'Steenwijk',
            'a_11347' => 'Steenwijkerwold',
            'a_10694' => 'Tubbergen',
            'a_10783' => 'Urk',
            'a_10643' => 'Vriezenveen',
            'a_10105' => 'Wanneperveen',
            'a_10893' => 'Weerselo',
            'a_10676' => 'Wierden',
            'a_10888' => 'Wijhe',
            'a_10641' => 'IJsselmuiden',
            'a_10303' => 'Zwartsluis',
            'a_10093' => 'Zwolle',
            'a_11046' => 'Aalten',
            'a_10151' => 'Ammerzoden',
            'a_11131' => 'Angerlo',
            'a_11075' => 'Apeldoorn',
            'a_11296' => 'Appeltern',
            'a_10795' => 'Arnhem',
            'a_10906' => 'Barneveld',
            'a_10921' => 'Batenburg',
            'a_10259' => 'Beesd',
            'a_10744' => 'Bemmel',
            'a_10350' => 'Bergh',
            'a_10883' => 'Bergharen',
            'a_10417' => 'Beuningen',
            'a_11182' => 'Beusichem',
            'a_11307' => 'Borculo',
            'a_11125' => 'Brakel',
            'a_10798' => 'Brummen',
            'a_11286' => 'Buren',
            'a_10460' => 'Buurmalsen',
            'a_10342' => 'Culemborg',
            'a_10057' => 'Deil',
            'a_10153' => 'Didam',
            'a_11154' => 'Dinxperlo',
            'a_10647' => 'Dodewaard',
            'a_10327' => 'Doesburg',
            'a_10069' => 'Doornspijk',
            'a_11232' => 'Dreumel',
            'a_10068' => 'Druten',
            'a_11028' => 'Duiven',
            'a_10832' => 'Echteld',
            'a_10743' => 'Ede',
            'a_10436' => 'Eibergen',
            'a_11113' => 'Elburg',
            'a_11188' => 'Elst',
            'a_10940' => 'Epe',
            'a_10732' => 'Ermelo',
            'a_11092' => 'Est en Opijnen',
            'a_10471' => 'Ewijk',
            'a_10881' => 'Geldermalsen',
            'a_10260' => 'Gendringen',
            'a_10440' => 'Gendt',
            'a_10085' => 'Gorssel',
            'a_11094' => 'Groenlo',
            'a_10616' => 'Groesbeek',
            'a_10125' => 'Haaften',
            'a_10786' => 'Harderwijk',
            'a_10673' => 'Hattem',
            'a_10982' => 'Hedel',
            'a_10291' => 'Heerde',
            'a_10067' => 'Heerewaarden',
            'a_10359' => 'Hengelo (Gld.)',
            'a_10819' => 'Herwen en Aerdt',
            'a_11266' => 'Herwijnen',
            'a_10290' => 'Heteren',
            'a_10600' => 'Heumen',
            'a_11358' => 'Hoevelaken',
            'a_10607' => 'Horssen',
            'a_11047' => 'Huissen',
            'a_11235' => 'Hummelo en Keppel',
            'a_10791' => 'Kerkwijk',
            'a_10916' => 'Kesteren',
            'a_10098' => 'Laren (Gld.)',
            'a_10242' => 'Lichtenvoorde',
            'a_11093' => 'Lienden',
            'a_11263' => 'Lochem',
            'a_10300' => 'Maurik',
            'a_10367' => 'Neede',
            'a_10446' => 'Nijkerk',
            'a_11209' => 'Nijmegen',
            'a_11106' => 'Oldebroek',
            'a_10829' => 'Ophemert',
            'a_10952' => 'Overasselt',
            'a_10518' => 'Pannerden',
            'a_11109' => 'Putten',
            'a_11325' => 'Renkum',
            'a_11355' => 'Rheden',
            'a_10860' => 'Rossum',
            'a_10684' => 'Rozendaal',
            'a_10879' => 'Ruurlo',
            'a_11146' => 'Scherpenzeel',
            'a_10497' => 'Steenderen',
            'a_10027' => 'Tiel',
            'a_10408' => 'Ubbergen',
            'a_11071' => 'Valburg',
            'a_11064' => 'Varik',
            'a_10912' => 'Voorst',
            'a_10957' => 'Vorden',
            'a_11376' => 'Vuren',
            'a_10238' => 'Waardenburg',
            'a_11010' => 'Wageningen',
            'a_10042' => 'Wamel',
            'a_10838' => 'Warnsveld',
            'a_11116' => 'Wehl',
            'a_11085' => 'Westervoort',
            'a_11119' => 'Winterswijk',
            'a_10318' => 'Wisch',
            'a_10723' => 'Wijchen',
            'a_10557' => 'Zaltbommel',
            'a_11159' => 'Zelhem',
            'a_10938' => 'Zevenaar',
            'a_10897' => 'Zoelen',
            'a_10254' => 'Zutphen',
            'a_11372' => 'Amerongen',
            'a_10948' => 'Amersfoort',
            'a_11411' => 'Baarn',
            'a_11330' => 'Benschop',
            'a_10168' => 'De Bilt',
            'a_10820' => 'Bunnik',
            'a_11343' => 'Bunschoten',
            'a_11316' => 'Cothen',
            'a_10213' => 'Doorn',
            'a_10248' => 'Eemnes',
            'a_10183' => 'Harmelen',
            'a_10611' => 'Hoenkoop',
            'a_10545' => 'Hoogland',
            'a_10230' => 'Houten',
            'a_10374' => 'Jutphaas',
            'a_10765' => 'Kockengen',
            'a_11413' => 'Langbroek',
            'a_11142' => 'Leersum',
            'a_10978' => 'Leusden',
            'a_11324' => 'Linschoten',
            'a_11202' => 'Loenen',
            'a_10639' => 'Loosdrecht',
            'a_10333' => 'Lopik',
            'a_10392' => 'Maarn',
            'a_10191' => 'Maarssen',
            'a_10691' => 'Maartensdijk',
            'a_10033' => 'Montfoort',
            'a_11379' => 'Mijdrecht',
            'a_10160' => 'Nigtevecht',
            'a_10269' => 'Renswoude',
            'a_10309' => 'Rhenen',
            'a_10802' => 'Snelrewaard',
            'a_11021' => 'Soest',
            'a_10438' => 'Stoutenburg',
            'a_10722' => 'Utrecht',
            'a_11052' => 'Veenendaal',
            'a_11048' => 'Vinkeveen en Waverveen',
            'a_10441' => 'Vreeswijk',
            'a_10758' => 'Willeskop',
            'a_10365' => 'Wilnis',
            'a_11398' => 'Woudenberg',
            'a_10760' => 'Wijk bij Duurstede',
            'a_10152' => 'IJsselstein',
            'a_10949' => 'Zegveld',
            'a_10324' => 'Zeist',
            'a_11264' => 'Aalsmeer',
            'a_10439' => 'Abbekerk',
            'a_10346' => 'Akersloot',
            'a_10527' => 'Alkmaar',
            'a_11150' => 'Amsterdam',
            'a_10822' => 'Andijk',
            'a_11373' => 'Assendelft',
            'a_11147' => 'Avenhorn',
            'a_10973' => 'Barsingerhorn',
            'a_10816' => 'Beemster',
            'a_10536' => 'Beets',
            'a_10734' => 'Bennebroek',
            'a_11424' => 'Bergen (NH.)',
            'a_10354' => 'Berkhout',
            'a_10272' => 'Beverwijk',
            'a_10493' => 'Blaricum',
            'a_10850' => 'Bloemendaal',
            'a_10804' => 'Blokker',
            'a_10945' => 'Bovenkarspel',
            'a_10289' => 'Broek in Waterland',
            'a_10281' => 'Bussum',
            'a_10559' => 'Callantsoog',
            'a_11287' => 'Castricum',
            'a_11039' => 'Diemen',
            'a_10421' => 'Egmond aan Zee',
            'a_10989' => 'Egmond-Binnen',
            'a_10729' => 'Enkhuizen',
            'a_10484' => 'Graft',
            'a_10534' => '\'s-Graveland',
            'a_11337' => 'Grootebroek',
            'a_10357' => 'Haarlem',
            'a_10963' => 'Harenkarspel',
            'a_10679' => 'Heemskerk',
            'a_11288' => 'Heemstede',
            'a_10752' => 'Heerhugowaard',
            'a_10793' => 'Heiloo',
            'a_10285' => 'Den Helder',
            'a_10181' => 'Hensbroek',
            'a_11285' => 'Hilversum',
            'a_10642' => 'Hoogkarspel',
            'a_11029' => 'Hoogwoud',
            'a_11392' => 'Hoorn',
            'a_11170' => 'Huizen',
            'a_10809' => 'Ilpendam',
            'a_10481' => 'Jisp',
            'a_11136' => 'Katwoude',
            'a_10510' => 'Koedijk',
            'a_10776' => 'Koog aan de Zaan',
            'a_10373' => 'Krommenie',
            'a_10775' => 'Kwadijk',
            'a_11225' => 'Landsmeer',
            'a_10649' => 'Laren (NH.)',
            'a_11370' => 'Limmen',
            'a_10901' => 'Marken',
            'a_11215' => 'Medemblik',
            'a_10452' => 'Middelie',
            'a_10924' => 'Midwoud',
            'a_10983' => 'Monnickendam',
            'a_10958' => 'Muiden',
            'a_10187' => 'Naarden',
            'a_10715' => 'Nederhorst den Berg',
            'a_10091' => 'Nibbixwoud',
            'a_10073' => 'Nieuwe-Niedorp',
            'a_11338' => 'Obdam',
            'a_11121' => 'Oosthuizen',
            'a_11303' => 'Oostzaan',
            'a_10609' => 'Opmeer',
            'a_10523' => 'Opperdoes',
            'a_10133' => 'Oterleek',
            'a_11090' => 'Oudendijk',
            'a_10896' => 'Oude-Niedorp',
            'a_10998' => 'Ouder-Amstel',
            'a_10363' => 'Oudorp',
            'a_11066' => 'Purmerend',
            'a_11417' => 'De Rijp',
            'a_10511' => 'Schagen',
            'a_10772' => 'Schellinkhout',
            'a_10308' => 'Schermerhorn',
            'a_10627' => 'Schoorl',
            'a_10740' => 'Sint Maarten',
            'a_10513' => 'Sint Pancras',
            'a_11167' => 'Sijbekarspel',
            'a_10237' => 'Texel',
            'a_10371' => 'Twisk',
            'a_11238' => 'Uitgeest',
            'a_11206' => 'Uithoorn',
            'a_10696' => 'Ursem',
            'a_10620' => 'Velsen',
            'a_10433' => 'Venhuizen',
            'a_11073' => 'Warder',
            'a_10221' => 'Warmenhuizen',
            'a_10773' => 'Weesp',
            'a_11270' => 'Wervershoof',
            'a_11101' => 'Westwoud',
            'a_11381' => 'Westzaan',
            'a_11277' => 'Wieringen',
            'a_11164' => 'Wieringerwaard',
            'a_10923' => 'Winkel',
            'a_10818' => 'Wognum',
            'a_10648' => 'Wormer',
            'a_10361' => 'Wormerveer',
            'a_10631' => 'Wijdenes',
            'a_10434' => 'Wijdewormer',
            'a_11044' => 'Zaandam',
            'a_11404' => 'Zaandijk',
            'a_10910' => 'Zandvoort',
            'a_11311' => 'Zuid- en Noord-Schermer',
            'a_10218' => 'Zwaag',
            'a_10004' => 'Zijpe',
            'a_11104' => 'Ter Aar',
            'a_11026' => 'Abbenbroek',
            'a_11327' => 'Alblasserdam',
            'a_10349' => 'Alkemade',
            'a_11349' => 'Ameide',
            'a_10863' => 'Ammerstol',
            'a_10079' => 'Arkel',
            'a_11369' => 'Asperen',
            'a_10398' => 'Benthuizen',
            'a_10962' => 'Bergambacht',
            'a_10445' => 'Bergschenhoek',
            'a_10139' => 'Berkel en Rodenrijs',
            'a_10797' => 'Berkenwoude',
            'a_10994' => 'Bleiswijk',
            'a_11091' => 'Bodegraven',
            'a_10419' => 'Boskoop',
            'a_10169' => 'Brandwijk',
            'a_10232' => 'Brielle',
            'a_11248' => 'Capelle aan den IJssel',
            'a_10928' => 'Delft',
            'a_11344' => 'Dirksland',
            'a_11157' => 'Dordrecht',
            'a_10781' => 'Dubbeldam',
            'a_10638' => 'Everdingen',
            'a_10842' => 'Geervliet',
            'a_10981' => 'Goedereede',
            'a_10942' => 'Gorinchem',
            'a_10302' => 'Gouda',
            'a_11258' => 'Gouderak',
            'a_10450' => 'Goudriaan',
            'a_10914' => 'Goudswaard',
            'a_10052' => '\'s-Gravendeel',
            'a_11434' => '\'s-Gravenhage',
            'a_10640' => '\'s-Gravenzande',
            'a_11205' => 'Groot-Ammers',
            'a_11049' => 'Haastrecht',
            'a_10297' => 'Hagestein',
            'a_11257' => 'Hazerswoude',
            'a_10823' => 'Heenvliet',
            'a_10540' => 'Heerjansdam',
            'a_11024' => 'Hei- en Boeicop',
            'a_10821' => 'Heinenoord',
            'a_11078' => 'Hellevoetsluis',
            'a_10416' => 'Hendrik-Ido-Ambacht',
            'a_11308' => 'Heukelum',
            'a_11236' => 'Hillegom',
            'a_11040' => 'Hoogblokland',
            'a_10516' => 'Hoornaar',
            'a_10707' => 'Katwijk',
            'a_10280' => 'Kedichem',
            'a_10284' => 'Klaaswaal',
            'a_10617' => 'Krimpen aan de Lek',
            'a_10859' => 'Krimpen aan den IJssel',
            'a_11127' => 'Langerak',
            'a_10465' => 'Leerbroek',
            'a_10685' => 'Leerdam',
            'a_10702' => 'Leiden',
            'a_10058' => 'Leiderdorp',
            'a_11351' => 'Leimuiden',
            'a_10050' => 'Lekkerkerk',
            'a_10930' => 'Lexmond',
            'a_10241' => 'De Lier',
            'a_10197' => 'Lisse',
            'a_11019' => 'Maasdam',
            'a_10393' => 'Maasland',
            'a_10880' => 'Maassluis',
            'a_10618' => 'Meerkerk',
            'a_11067' => 'Middelharnis',
            'a_11383' => 'Moerkapelle',
            'a_10166' => 'Molenaarsgraaf',
            'a_10019' => 'Monster',
            'a_10120' => 'Moordrecht',
            'a_11112' => 'Mijnsheerenland',
            'a_11418' => 'Naaldwijk',
            'a_10092' => 'Nieuw-Beijerland',
            'a_10855' => 'Nieuwerkerk aan den IJssel',
            'a_10728' => 'Nieuwkoop',
            'a_11213' => 'Nieuwland',
            'a_10547' => 'Nieuw-Lekkerland',
            'a_10748' => 'Nieuwpoort',
            'a_10487' => 'Nieuwveen',
            'a_10296' => 'Noordeloos',
            'a_10769' => 'Noordwijk',
            'a_11134' => 'Noordwijkerhout',
            'a_10601' => 'Nootdorp',
            'a_11346' => 'Numansdorp',
            'a_10287' => 'Oegstgeest',
            'a_10009' => 'Oostvoorne',
            'a_10097' => 'Ottoland',
            'a_10826' => 'Oud-Alblas',
            'a_11301' => 'Oud-Beijerland',
            'a_10380' => 'Oudenhoorn',
            'a_10095' => 'Ouderkerk aan den IJssel',
            'a_10188' => 'Oudewater',
            'a_10427' => 'Papendrecht',
            'a_10179' => 'Piershil',
            'a_11395' => 'Poortugaal',
            'a_10222' => 'Puttershoek',
            'a_10031' => 'Pijnacker',
            'a_10485' => 'Reeuwijk',
            'a_10903' => 'Rhoon',
            'a_10646' => 'Ridderkerk',
            'a_10464' => 'Rockanje',
            'a_10345' => 'Rotterdam',
            'a_11314' => 'Rozenburg',
            'a_11129' => 'Rijnsaterwoude',
            'a_11118' => 'Rijnsburg',
            'a_11133' => 'Rijswijk (ZH.)',
            'a_10612' => 'Sassenheim',
            'a_11399' => 'Schelluinen',
            'a_11260' => 'Schiedam',
            'a_10130' => 'Schipluiden',
            'a_10980' => 'Schoonhoven',
            'a_10214' => 'Schoonrewoerd',
            'a_11331' => 'Sliedrecht',
            'a_10735' => 'Spijkenisse',
            'a_11250' => 'Stolwijk',
            'a_11105' => 'Streefkerk',
            'a_10558' => 'Strijen',
            'a_10706' => 'Tienhoven (ZH.)',
            'a_10028' => 'Valkenburg (ZH.)',
            'a_10887' => 'Vianen',
            'a_11056' => 'Vierpolders',
            'a_10811' => 'Vlaardingen',
            'a_11174' => 'Vlist',
            'a_11103' => 'Voorburg',
            'a_10017' => 'Voorhout',
            'a_10537' => 'Voorschoten',
            'a_10204' => 'Warmond',
            'a_10164' => 'Wassenaar',
            'a_11329' => 'Wateringen',
            'a_10695' => 'Westmaas',
            'a_10974' => 'Woerden',
            'a_10461' => 'Woubrugge',
            'a_10263' => 'Wijngaarden',
            'a_11317' => 'Zevenhoven',
            'a_11377' => 'Zevenhuizen',
            'a_10766' => 'Zoetermeer',
            'a_11074' => 'Zoeterwoude',
            'a_10917' => 'Zuid-Beijerland',
            'a_10316' => 'Zuidland',
            'a_10703' => 'Zwartewaal',
            'a_10468' => 'Zwijndrecht',
            'a_11020' => 'Aardenburg',
            'a_11419' => 'Arnemuiden',
            'a_11289' => 'Axel',
            'a_10634' => 'Baarland',
            'a_10257' => 'Biervliet',
            'a_10233' => 'Breskens',
            'a_10030' => 'Brouwershaven',
            'a_11291' => 'Bruinisse',
            'a_11002' => 'Cadzand',
            'a_10965' => 'Clinge',
            'a_10236' => 'Domburg',
            'a_11115' => 'Driewegen',
            'a_11300' => 'Ellewoutsdijk',
            'a_10674' => 'Goes',
            'a_10312' => 'Graauw en Langendam',
            'a_11059' => '\'s-Gravenpolder',
            'a_11312' => 'Groede',
            'a_10337' => '\'s-Heer-Abtskerke',
            'a_10875' => '\'s-Heer-Arendskerke',
            'a_10721' => '\'s-Heerenhoek',
            'a_10413' => 'Heinkenszand',
            'a_11072' => 'Hoedekenskerke',
            'a_10038' => 'Hoek',
            'a_11123' => 'Hontenisse',
            'a_10147' => 'Hoofdplaat',
            'a_11408' => 'Hulst',
            'a_11223' => 'Kapelle',
            'a_11218' => 'Kattendijke',
            'a_10155' => 'Kloetinge',
            'a_10892' => 'Koewacht',
            'a_10250' => 'Kortgene',
            'a_11038' => 'Krabbendijke',
            'a_10077' => 'Kruiningen',
            'a_10122' => 'Middelburg (Z.)',
            'a_11165' => 'Nieuwvliet',
            'a_11086' => 'Nisse',
            'a_10782' => 'Oostburg',
            'a_11139' => 'Oudelande',
            'a_10134' => 'Oud-Vossemeer',
            'a_10730' => 'Overslag',
            'a_11422' => 'Ovezande',
            'a_10486' => 'Philippine',
            'a_11181' => 'Poortvliet',
            'a_10400' => 'Retranchement',
            'a_11405' => 'Sas van Gent',
            'a_10375' => 'Scherpenisse',
            'a_10189' => 'Schoondijke',
            'a_11200' => 'Sint-Annaland',
            'a_10864' => 'Sint Jansteen',
            'a_10716' => 'Sint-Maartensdijk',
            'a_10394' => 'Sint Philipsland',
            'a_10895' => 'Sluis',
            'a_10619' => 'Stavenisse',
            'a_10704' => 'Terneuzen',
            'a_10376' => 'Tholen',
            'a_10369' => 'Veere',
            'a_10270' => 'Vlissingen',
            'a_10082' => 'Waarde',
            'a_10352' => 'Waterlandkerkje',
            'a_11145' => 'Wemeldinge',
            'a_10915' => 'Westdorpe',
            'a_10931' => 'Westkapelle',
            'a_10543' => 'Wolphaartsdijk',
            'a_11247' => 'Yerseke',
            'a_10688' => 'IJzendijke',
            'a_11173' => 'Zaamslag',
            'a_10843' => 'Zierikzee',
            'a_11087' => 'Zuiddorpe',
            'a_10437' => 'Zuidzande',
            'a_10757' => 'Aarle-Rixtel',
            'a_10788' => 'Almkerk',
            'a_10710' => 'Alphen en Riel',
            'a_10871' => 'Andel',
            'a_10478' => 'Asten',
            'a_10060' => 'Baarle-Nassau',
            'a_10749' => 'Bakel en Milheeze',
            'a_10173' => 'Beek en Donk',
            'a_11410' => 'Beers',
            'a_11037' => 'Bergen op Zoom',
            'a_11160' => 'Bergeyk',
            'a_10733' => 'Berghem',
            'a_11132' => 'Berkel-Enschot',
            'a_10362' => 'Berlicum',
            'a_10442' => 'Best',
            'a_11006' => 'Bladel en Netersel',
            'a_10432' => 'Boekel',
            'a_11227' => 'Boxmeer',
            'a_10083' => 'Boxtel',
            'a_10154' => 'Breda',
            'a_10219' => 'Budel',
            'a_11433' => 'Chaam',
            'a_10023' => 'Cuijk en Sint Agatha',
            'a_11185' => 'Diessen',
            'a_11193' => 'Dinteloord en Prinsenland',
            'a_11412' => 'Dongen',
            'a_10428' => 'Drunen',
            'a_10491' => 'Den Dungen',
            'a_10741' => 'Eersel',
            'a_11298' => 'Eindhoven',
            'a_10295' => 'Empel en Meerwijk',
            'a_10322' => 'Engelen',
            'a_10385' => 'Erp',
            'a_10709' => 'Esch',
            'a_10206' => 'Fijnaart en Heijningen',
            'a_10101' => 'Geertruidenberg',
            'a_10370' => 'Geffen',
            'a_10529' => 'Geldrop',
            'a_10277' => 'Gemert',
            'a_10193' => 'Giessen',
            'a_11375' => 'Gilze en Rijen',
            'a_10971' => 'Goirle',
            'a_10165' => 'Grave',
            'a_10469' => '\'s-Gravenmoer',
            'a_11155' => 'Haaren',
            'a_10387' => 'Halsteren',
            'a_11283' => 'Haps',
            'a_10149' => 'Heesch',
            'a_10462' => 'Heeze',
            'a_10932' => 'Helmond',
            'a_10389' => 'Helvoirt',
            'a_10054' => '\'s-Hertogenbosch',
            'a_10307' => 'Heusden',
            'a_11297' => 'Hilvarenbeek',
            'a_10065' => 'Hoeven',
            'a_10657' => 'Hoogeloon, Hapert en Casteren',
            'a_10476' => 'Hooge en Lage Mierde',
            'a_11318' => 'Hooge en Lage Zwaluwe',
            'a_10466' => 'Huijbergen',
            'a_10656' => 'Klundert',
            'a_11017' => 'Leende',
            'a_10390' => 'Liempde',
            'a_10637' => 'Lieshout',
            'a_11219' => 'Lith',
            'a_11259' => 'Loon op Zand',
            'a_10011' => 'Luyksgestel',
            'a_11143' => 'Maarheeze',
            'a_10258' => 'Made en Drimmelen',
            'a_10988' => 'Megen, Haren en Macharen',
            'a_10215' => 'Mierlo',
            'a_10535' => 'Mill en Sint Hubert',
            'a_11191' => 'Moergestel',
            'a_10401' => 'Nieuw-Vossemeer',
            'a_10094' => 'Nistelrode',
            'a_10761' => 'Nuenen, Gerwen en Nederwetten',
            'a_10118' => 'Nuland',
            'a_10360' => 'Oeffelt',
            'a_10225' => 'Oirschot',
            'a_10103' => 'Oisterwijk',
            'a_10220' => 'Oost-, West- en Middelbeers',
            'a_11280' => 'Oosterhout',
            'a_11415' => 'Oploo, Sint Anthonis en Ledeacker',
            'a_10834' => 'Oss',
            'a_10929' => 'Ossendrecht',
            'a_10397' => 'Oudenbosch',
            'a_11363' => 'Oud en Nieuw Gastel',
            'a_10099' => 'Putte',
            'a_11391' => 'Raamsdonk',
            'a_10805' => 'Ravenstein',
            'a_10162' => 'Reusel',
            'a_10455' => 'Riethoven',
            'a_10407' => 'Roosendaal en Nispen',
            'a_10970' => 'Rosmalen',
            'a_11152' => 'Rucphen',
            'a_10866' => 'Rijsbergen',
            'a_11016' => 'Rijswijk (NB.)',
            'a_10276' => 'Schaijk',
            'a_11178' => 'Schijndel',
            'a_10496' => 'Sint-Michielsgestel',
            'a_10399' => 'Sint-Oedenrode',
            'a_10203' => 'Someren',
            'a_10549' => 'Son en Breugel',
            'a_11212' => 'Standdaarbuiten',
            'a_10905' => 'Terheijden',
            'a_10677' => 'Teteringen',
            'a_10792' => 'Tilburg',
            'a_11141' => 'Uden',
            'a_11268' => 'Udenhout',
            'a_11031' => 'Valkenswaard',
            'a_10015' => 'Veen',
            'a_10985' => 'Veghel',
            'a_11414' => 'Vessem, Wintelre en Knegsel',
            'a_10066' => 'Vierlingsbeek',
            'a_11293' => 'Vlijmen',
            'a_11224' => 'Vught',
            'a_10959' => 'Waalre',
            'a_11359' => 'Waalwijk',
            'a_10265' => 'Wanroij',
            'a_10402' => 'Waspik',
            'a_10078' => 'Werkendam',
            'a_11041' => 'Westerhoven',
            'a_10827' => 'Willemstad',
            'a_10602' => 'Woensdrecht',
            'a_10282' => 'Woudrichem',
            'a_10808' => 'Wouw',
            'a_11084' => 'Wijk en Aalburg',
            'a_10759' => 'Zeeland',
            'a_10046' => 'Zevenbergen',
            'a_11192' => 'Zundert',
            'a_10922' => 'Amby',
            'a_10062' => 'Amstenrade',
            'a_10202' => 'Arcen en Velden',
            'a_11255' => 'Baexem',
            'a_11089' => 'Beegden',
            'a_11374' => 'Beek (L.)',
            'a_10195' => 'Beesel',
            'a_10292' => 'Belfeld',
            'a_10878' => 'Bemelen',
            'a_10796' => 'Berg en Terblijt',
            'a_11281' => 'Bergen (L.)',
            'a_10967' => 'Bingelrade',
            'a_10986' => 'Bocholtz',
            'a_11334' => 'Borgharen',
            'a_10927' => 'Born',
            'a_10856' => 'Broekhuizen',
            'a_10533' => 'Brunssum',
            'a_10831' => 'Bunde',
            'a_11447' => 'Cadier en Keer',
            'a_10941' => 'Echt',
            'a_11187' => 'Elsloo',
            'a_10960' => 'Eijgelshoven',
            'a_10314' => 'Eijsden',
            'a_10048' => 'Geleen',
            'a_10542' => 'Gennep',
            'a_10530' => 'Geulle',
            'a_11008' => 'Grathem',
            'a_10624' => 'Grevenbicht',
            'a_10323' => 'Gronsveld',
            'a_10176' => 'Grubbenvorst',
            'a_11032' => 'Gulpen',
            'a_10321' => 'Haelen',
            'a_11208' => 'Heel en Panheel',
            'a_11407' => 'Heer',
            'a_10902' => 'Heerlen',
            'a_10205' => 'Helden',
            'a_10138' => 'Herten',
            'a_10840' => 'Heythuysen',
            'a_11177' => 'Hoensbroek',
            'a_11176' => 'Horn',
            'a_11108' => 'Horst',
            'a_11216' => 'Hulsberg',
            'a_10784' => 'Hunsel',
            'a_10458' => 'Itteren',
            'a_10256' => 'Jabeek',
            'a_10313' => 'Kerkrade',
            'a_10890' => 'Kessel',
            'a_10977' => 'Klimmen',
            'a_11124' => 'Limbricht',
            'a_10990' => 'Linne',
            'a_10964' => 'Maasbracht',
            'a_11352' => 'Maasbree',
            'a_10182' => 'Maastricht',
            'a_11243' => 'Margraten',
            'a_11416' => 'Meerlo',
            'a_10494' => 'Meerssen',
            'a_10196' => 'Melick en Herkenbosch',
            'a_10763' => 'Merkelbeek',
            'a_10925' => 'Meijel',
            'a_10538' => 'Mheer',
            'a_10266' => 'Montfort',
            'a_10271' => 'Mook en Middelaar',
            'a_10273' => 'Munstergeleen',
            'a_10681' => 'Nederweert',
            'a_10682' => 'Neer',
            'a_10088' => 'Nieuwenhagen',
            'a_11140' => 'Nieuwstadt',
            'a_10283' => 'Noorbeek',
            'a_10104' => 'Nuth',
            'a_10388' => 'Obbicht en Papenhoven',
            'a_10420' => 'Ohé en Laak',
            'a_10504' => 'Oirsbeek',
            'a_10862' => 'Ottersum',
            'a_10456' => 'Posterholt',
            'a_11313' => 'Roermond',
            'a_10505' => 'Roggel',
            'a_10954' => 'Roosteren',
            'a_11335' => 'Schaesberg',
            'a_11076' => 'Schimmert',
            'a_10132' => 'Schinnen',
            'a_10492' => 'Schinveld',
            'a_10045' => 'Sevenum',
            'a_10515' => 'Simpelveld',
            'a_10288' => 'Sint Geertruid',
            'a_10278' => 'Sint Odiliënberg',
            'a_11230' => 'Sittard',
            'a_11274' => 'Slenaken',
            'a_10552' => 'Spaubeek',
            'a_11319' => 'Stein (L.)',
            'a_11203' => 'Stevensweert',
            'a_11186' => 'Stramproy',
            'a_10457' => 'Susteren',
            'a_10474' => 'Swalmen',
            'a_11333' => 'Tegelen',
            'a_11195' => 'Thorn',
            'a_10146' => 'Ubach over Worms',
            'a_11220' => 'Ulestraten',
            'a_10700' => 'Urmond',
            'a_10007' => 'Vaals',
            'a_10477' => 'Venlo',
            'a_11222' => 'Venray',
            'a_10644' => 'Vlodrop',
            'a_11341' => 'Voerendaal',
            'a_10991' => 'Wanssum',
            'a_11081' => 'Weert',
            'a_10127' => 'Wessem',
            'a_11371' => 'Wittem',
            'a_10131' => 'Wijlre',
            'a_10992' => 'Wijnandsrade',
            'a_10531' => 'Aagtekerke',
            'a_11423' => 'Aalst',
            'a_11242' => 'Aarlanderveen',
            'a_11184' => 'Abcoude-Baambrugge',
            'a_10712' => 'Abcoude-Proosdij',
            'a_10524' => 'Achttienhoven (U.)',
            'a_10157' => 'Aengwirden',
            'a_10495' => 'Alem, Maren en Kessel',
            'a_10517' => 'Alphen',
            'a_11065' => 'Ambt-Almelo',
            'a_10396' => 'Ambt-Doetinchem',
            'a_10174' => 'Ambt-Hardenberg',
            'a_11069' => 'Ambt-Ommen',
            'a_11088' => 'Ambt-Vollenhove',
            'a_10936' => 'Ankeveen',
            'a_10006' => 'Baardwijk',
            'a_10849' => 'Balgoij',
            'a_10447' => 'Barwoutswaarder',
            'a_10623' => 'Bath',
            'a_10340' => 'Bellingwolde',
            'a_10056' => 'Besoijen',
            'a_10252' => 'Beugen en Rijkevoort',
            'a_11364' => 'Biggekerke',
            'a_11083' => 'Bokhoven',
            'a_10293' => 'Den Bommel',
            'a_10785' => 'Bommenede',
            'a_11013' => 'Borkel en Schaft',
            'a_10605' => 'Boschkapelle',
            'a_10807' => 'Breukelen-Nijenrode',
            'a_11149' => 'Breukelen-Sint Pieters',
            'a_11214' => 'Broek',
            'a_10955' => 'Broek op Langedijk',
            'a_10655' => 'Broeksittard',
            'a_10857' => 'Buggenum',
            'a_10961' => 'Buiksloot',
            'a_11000' => 'Burgh',
            'a_11233' => 'Capelle',
            'a_10430' => 'Charlois',
            'a_10121' => 'Colijnsplaat',
            'a_10053' => 'Cromvoirt',
            'a_10908' => 'Delfshaven',
            'a_10379' => 'Deurne en Liessel',
            'a_10209' => 'Deursen en Dennenburg',
            'a_11138' => 'Dieden, Demen en Langel',
            'a_10690' => 'Dinther',
            'a_10424' => 'Dommelen',
            'a_11304' => 'Doorwerth',
            'a_10885' => 'Dreischor',
            'a_10777' => 'Driebergen',
            'a_10335' => 'Duivendijke',
            'a_11005' => 'Duizel en Steensel',
            'a_11128' => 'Eede',
            'a_10184' => 'Elkerzee',
            'a_10102' => 'Ellemeet',
            'a_10621' => 'Emmikhoven',
            'a_10299' => 'Escharen',
            'a_10037' => 'Gameren',
            'a_10705' => 'Gassel',
            'a_11269' => 'Gestel en Blaarthem',
            'a_10255' => 'Giessen-Nieuwkerk',
            'a_10606' => 'Giessendam',
            'a_10406' => 'Ginneken en Bavel',
            'a_10224' => 'Grafhorst',
            'a_11042' => 'Groote Lindt',
            'a_10801' => 'Grijpskerke',
            'a_10701' => 'Haamstede',
            'a_10737' => 'Haarzuilens',
            'a_10167' => 'Hardinxveld',
            'a_11130' => 'Hedikhuizen',
            'a_11229' => 'Heeswijk',
            'a_11309' => 'Heille',
            'a_10873' => 'Hekelingen',
            'a_10217' => 'Hekendorp',
            'a_11162' => 'Hemmen',
            'a_10636' => 'Hengstdijk',
            'a_10267' => 'Herkingen',
            'a_11262' => 'Herpen',
            'a_10845' => 'Herpt',
            'a_10227' => 'Hillegersberg',
            'a_10089' => 'Hof van Delft',
            'a_10410' => 'Hoogezand',
            'a_10953' => 'Hoogkerk',
            'a_10348' => 'Hoogvliet',
            'a_11111' => 'Houthem',
            'a_11252' => 'Houtrijk en Polanen',
            'a_10049' => 'Huisseling en Neerloon',
            'a_11432' => 'Hurwenen',
            'a_10190' => 'Ittervoort',
            'a_10847' => 'Jaarsveld',
            'a_10443' => 'Kamperveen',
            'a_11401' => 'Katendrecht',
            'a_10919' => 'Kats',
            'a_10837' => 'Kerkwerve',
            'a_10551' => 'Kethel en Spaland',
            'a_10467' => 'Kortenhoef',
            'a_11428' => 'Koudekerke',
            'a_11367' => 'Kralingen',
            'a_10305' => 'Laag-Nieuwkoop',
            'a_10900' => 'Lange Ruige Weide',
            'a_11279' => 'Lierop',
            'a_11305' => 'Linden',
            'a_10024' => 'Lithoijen',
            'a_10208' => 'Loenersloot',
            'a_11045' => 'Lonneker',
            'a_10997' => 'Loosduinen',
            'a_10874' => 'Maarsseveen',
            'a_10532' => 'Maashees en Overloon',
            'a_11261' => 'Maasniel',
            'a_11221' => 'Meliskerke',
            'a_10918' => 'Melissant',
            'a_11070' => 'Mesch',
            'a_10310' => 'Nederhemert',
            'a_11001' => 'Neeritter',
            'a_11340' => 'Nieuw- en Sint Joosland',
            'a_10264' => 'Nieuw-Helvoet',
            'a_10502' => 'Nieuwe Tonge',
            'a_10177' => 'Nieuwendam',
            'a_10338' => 'Nieuwenhoorn',
            'a_10911' => 'Nieuwerkerk',
            'a_11278' => 'Nieuwkuijk',
            'a_10075' => 'Noord-Scharwoude',
            'a_11342' => 'Noord-Waddinxveen',
            'a_10814' => 'Noordbroek',
            'a_10979' => 'Noorddijk',
            'a_11107' => 'Noordgouwe',
            'a_10055' => 'Noordwelle',
            'a_10736' => 'Nunhem',
            'a_11003' => 'Odijk',
            'a_10119' => 'Oerle',
            'a_11163' => 'Ooltgensplaat',
            'a_10943' => 'Oost- en West-Barendrecht',
            'a_11396' => 'Oost- en West-Souburg',
            'a_11237' => 'Oosterland',
            'a_10720' => 'Oostkapelle',
            'a_10841' => 'Ossenisse',
            'a_11302' => 'Oud en Nieuw Mathenesse',
            'a_10868' => 'Oud-Valkenburg',
            'a_11348' => 'Oud-Vroenhoven',
            'a_11126' => 'Ouddorp',
            'a_10714' => 'Oude-Tonge',
            'a_10114' => 'Oudenrijn',
            'a_10812' => 'Oudheusden',
            'a_11051' => 'Oudkarspel',
            'a_11011' => 'Oudshoorn',
            'a_10968' => 'Ouwerkerk',
            'a_10683' => 'Overschie',
            'a_10869' => 'Oijen en Teeffelen',
            'a_11244' => 'Papekop',
            'a_11245' => 'Pernis',
            'a_11057' => 'Petten',
            'a_10391' => 'Peursum',
            'a_10135' => 'Poederoijen',
            'a_10762' => 'Princenhage',
            'a_10789' => 'Ransdorp',
            'a_10671' => 'Reek',
            'a_10412' => 'Renesse',
            'a_10828' => 'Rietveld',
            'a_10479' => 'Rilland',
            'a_10020' => 'Rimburg',
            'a_10652' => 'Ritthem',
            'a_10526' => 'Ruwiel',
            'a_10824' => 'Rijckholt',
            'a_10613' => 'Rijsenburg',
            'a_10339' => 'Sambeek',
            'a_10080' => 'Sappemeer',
            'a_10378' => 'Schalkwijk',
            'a_11306' => 'Schiebroek',
            'a_10780' => 'Schin op Geul',
            'a_10544' => 'Schore',
            'a_10382' => 'Schoten',
            'a_10680' => 'Schoterland',
            'a_11014' => 'Serooskerke (Schouwen-Duivenland)',
            'a_11256' => 'Serooskerke (Walcheren)',
            'a_11012' => 'Sint Anna Termuiden',
            'a_10235' => 'Sint Kruis',
            'a_10106' => 'Sint Laurens',
            'a_10137' => 'Sint Pieter',
            'a_10148' => 'Sloten (NH.)',
            'a_11402' => 'Sluipwijk',
            'a_10047' => 'Soerendonk',
            'a_11097' => 'Sommelsdijk',
            'a_10192' => 'Spaarndam',
            'a_10063' => 'Spanbroek',
            'a_10659' => 'Sprang',
            'a_11239' => 'Stad aan \'t Haringvliet',
            'a_11053' => 'Stad-Almelo',
            'a_10603' => 'Stad-Doetinchem',
            'a_11169' => 'Stad-Hardenberg',
            'a_10381' => 'Stad-Ommen',
            'a_11183' => 'Stad-Vollenhove',
            'a_11437' => 'Stein (ZH.)',
            'a_10087' => 'Stellendam',
            'a_11386' => 'Stiphout',
            'a_10742' => 'Stompwijk',
            'a_10100' => 'Stoppeldijk',
            'a_10172' => 'Stratum',
            'a_11034' => 'Strucht',
            'a_10521' => 'Strijp',
            'a_10035' => 'Tienhoven (U.)',
            'a_11240' => 'Tongelre',
            'a_11022' => 'Tull en \'t Waal',
            'a_10071' => 'Veldhoven en Meerveldhoven',
            'a_10713' => 'Veldhuizen',
            'a_10170' => 'Velp',
            'a_10366' => 'Veur',
            'a_10790' => 'Vlaardinger-Ambacht',
            'a_10658' => 'Vleuten',
            'a_10268' => 'Vlierden',
            'a_10898' => 'Vreeland',
            'a_10635' => 'Vrouwenpolder',
            'a_10853' => 'Vrijenban',
            'a_10870' => 'Vrijhoeve-Capelle',
            'a_10830' => 'Waarder',
            'a_10201' => 'Wadenoijen',
            'a_11122' => 'Watergraafsmeer',
            'a_10247' => 'Wedde',
            'a_10377' => 'Weesperkarspel',
            'a_10116' => 'de Werken en Sleeuwijk',
            'a_11299' => 'Werkhoven',
            'a_10145' => 'Westbroek',
            'a_10825' => 'Wildervank',
            'a_10368' => 'Willige-Langerak',
            'a_10194' => 'Wilsum',
            'a_10894' => 'Woensel',
            'a_11366' => 'Wijk aan Zee en Duin',
            'a_11357' => 'IJsselmonde',
            'a_11430' => 'IJzendoorn',
            'a_10633' => 'Zalk en Veecaten',
            'a_10096' => 'Zeelst',
            'a_10185' => 'Zegwaart',
            'a_10329' => 'Zesgehuchten',
            'a_10040' => 'Zonnemaire',
            'a_10779' => 'Zoutelande',
            'a_10159' => 'Zuid-Scharwoude',
            'a_10081' => 'Zuid-Waddinxveen',
            'a_11282' => 'Zuidbroek (Gr.)',
            'a_11394' => 'Zuidschalkwijk',
            'a_10059' => 'Zuilen',
            'a_11036' => 'Zuilichem',
            'a_10675' => 'Zwammerdam',
            'a_10654' => 'Zwollerkerspel',
            'a_10341' => 'Driel',
            'a_10799' => 'Nieuwer-Amstel',
            'a_10610' => 'Onstwedde',
            'a_10750' => 'Etten en Leur',
            'a_11077' => 'Valkenburg (L.)',
            'a_10243' => 'Wissekerke',
            'a_10043' => 'Borssele',
            'a_10010' => 'Eethen, Genderen en Heesbeen',
            'a_10029' => 'Koudekerk',
            'a_10240' => 'Staveren',
            'a_10503' => 'Rijsoort en Strevelshoek',
            'a_10072' => 'Millingen',
            'a_10334' => 'Hemelumer Oldephaerd en Noordwolde',
            'a_10451' => 'Bleskensgraaf',
            'a_10111' => 'Drongelen,Haagoord,Gansoyen,Doevere',
            'a_10216' => 'Meeuwen, Hill en Babyloniënbroek',
            'a_10261' => 'Dussen, Munster en Muilkerk',
            'a_10946' => 'Abtsregt',
            'a_10141' => 'Achthoven',
            'a_10972' => 'Achttienhoven (ZH.)',
            'a_10013' => 'Ackersdijk en Vrouwenregt',
            'a_10223' => 'Berkenrode',
            'a_10686' => 'Biert',
            'a_11275' => 'Cabouw',
            'a_11168' => 'Darthuizen',
            'a_10070' => 'Duist',
            'a_10884' => 'Edam',
            'a_10306' => 'Gapinge',
            'a_11336' => 'Gerverscop',
            'a_10123' => 'Goidschalxoord',
            'a_10882' => 'Gravesloot',
            'a_10764' => 'Groeneveld',
            'a_11271' => 'Grosthuizen',
            'a_10041' => 'Haarlemmerliede',
            'a_10117' => '\'s-Heer Hendrikskinderen',
            'a_11207' => 'Heer Oudelands Ambacht',
            'a_10876' => 'Hodenpijl',
            'a_10325' => 'Hofwegen',
            'a_10755' => 'Hoogeveen in Rijnland',
            'a_10026' => 'Hoogmade',
            'a_10724' => 'Kalslagen',
            'a_10554' => 'Kamerik Houtdijken',
            'a_10555' => 'Kamerik Mijzijde',
            'a_10319' => 'Kijfhoek',
            'a_10150' => 'Kleine Lindt',
            'a_10519' => 'Kleverskerke',
            'a_10351' => 'Laagblokland',
            'a_10423' => 'Loenen en Wolveren',
            'a_10171' => 'Maarssenbroek',
            'a_10435' => 'Meerdervoort',
            'a_10483' => 'Middelburg (ZH.)',
            'a_11360' => 'De Mijl',
            'a_11384' => 'Naters',
            'a_10693' => 'Nederslingelandt',
            'a_11406' => 'Nieuwland, Kortland en s\'-Graveland',
            'a_10993' => 'Noord-Polsbroek',
            'a_11217' => 'Onwaard',
            'a_11356' => 'Oud-Wulven',
            'a_10347' => 'Oude en Nieuwe Struiten',
            'a_11171' => 'Oudhuizen',
            'a_11310' => 'Oukoop',
            'a_10016' => 'Portengen',
            'a_10051' => 'Rhijnauwen',
            'a_10473' => 'Rietwijkeroord',
            'a_11054' => 'Roxenisse',
            'a_11328' => 'Sandelinge-Ambacht',
            'a_10414' => 'Schardam',
            'a_11254' => 'Scharwoude',
            'a_10651' => 'Schellingwoude',
            'a_11189' => 'Schokland',
            'a_11098' => 'Schonauwen',
            'a_10626' => 'Schuddebeurs en Simonshaven',
            'a_10112' => 'Sint Maartensregt',
            'a_10508' => 'Spaarnwoude',
            'a_11397' => 'Spijk',
            'a_10731' => 'Steenbergen en Kruisland',
            'a_10697' => 'Sterkenburg',
            'a_10108' => 'Stormpolder',
            'a_11429' => 'Strijensas',
            'a_10499' => 'Teckop',
            'a_10175' => 'Tempel',
            'a_10718' => 'Veenhuizen',
            'a_10541' => 'de Vennip',
            'a_10275' => 'Verwolde',
            'a_10767' => 'Vrije en Lage Boekhorst',
            'a_11441' => 'de Vuursche',
            'a_11353' => 'Wieldrecht',
            'a_10725' => 'Wimmenum',
            'a_10817' => 'Wulverhorst',
            'a_11151' => 'Zevender',
            'a_11231' => 'Zouteveen',
            'a_10229' => 'Zuid-Polsbroek',
            'a_10751' => 'Zuidbroek (ZH.)',
        );
        $this->kaart = new Kaart('gemeentes', 1850);
        $actual = $this->kaart->getPossibleMunicipalities();
        $this->assertEquals($expected, $actual);
    }
}
