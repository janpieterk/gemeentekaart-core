# GemeenteKaart
Deze documentatie in het [Nederlands](README.nl.md).
___

PHP library to create choropleth maps of the municipalities of the Netherlands (default: borders as of 2007, 443 municipalities). Areas can be assigned colors, typically to denote the relative frequency of some phenomenon.

Additional available data: the municipalities of Flanders (308 municipalities), the forty [COROP](https://en.wikipedia.org/wiki/COROP) regions of the Netherlands, the twelve [provinces](https://en.wikipedia.org/wiki/Provinces_of_the_Netherlands) of the Netherlands, or the twenty-eight [dialect areas](https://nl.wikipedia.org/wiki/Jo_Daan#/media/File:Dutch-dialects.svg) of Daan/Blok (1969) mapped on municipality borders.

New in version 1.1: the historical municipality and province borders fron the data set [NLGis shapefiles](https://doi.org/10.17026/dans-xb9-t677)
    by Dr. O.W.A. Boonstra are incorporated in the project. Borders from 1812-1997 area available and can be requested via an optional `year` parameter. Note that no year parameter results in the 2007 maps from version 1.0. Maps from the NLGis data set use _Amsterdam codes_ for municipalities (see Van der Meer &amp; Boonstra 2011) while the version 1.0 maps use CBS codes.


Output formats are: SVG (default), PNG, GIF, JPEG, KML, GeoJSON.

## Getting Started

To install as a library from github, use this in your composer.json:
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/janpieterk/gemeentekaart-core"
        }
    ],
    "require": {
    "janpieterk/gemeentekaart-core": "1.0.1"
    }
}
```

To install as a library from [packagist.org](https://packagist.org), leave out the `repositories`:

```json
{
    "require": {
    "janpieterk/gemeentekaart-core": "1.0.1"
    }
}
```


To install as a project from github using composer:

```bash
$  composer create-project janpieterk/gemeentekaart-core --repository='{"type":"vcs","url":"https://github.com/janpieterk/gemeentekaart-core"}'
```

To install as a project from [packagist.org](https://packagist.org), leave out the `--repository`:

```bash
$  composer create-project janpieterk/gemeentekaart-core
```

Running the PHPUnit tests for gemeentekaart-core:

* Run the shell script `test/run_tests.sh`.

## Basic usage

To color the municipality areas of Amsterdam and Rotterdam from 2007 with red and green,
respectively, and create an SVG image:

```php
$municipalities = array('g_0363' => '#FF0000', 'g_0599' => '#00FF00');

$kaart = new Kaart('municipalities');
$kaart->addData($municipalities);
$kaart->show('svg');
```

The code numbers for the municipalities are the official Dutch municipality codes,
available at [www.cbs.nl](https://www.cbs.nl), prefixed by `g_` so that they can be used
as values of HTML or XMD id attributes. Possible codes can be requested by calling the `getPossibleMunicipalities()` method on a `Kaart` object created without a `year` parameter.

To color the areas from 1821 of what are today the four "big cities" of the Randstad area:

```php
$municipalities = array("a_11150" => "#990000", a_10345" => "#990000", "a_11434" => "#990000", "a_10722": "#990000");

$kaart = new Kaart('municipalities', 1821);
$kaart->addData($municipalities);
$kaart->show('svg');
```

The code numbers are  _Amsterdam codes_ from Van der Meer &amp; Boonstra 2011, prefixed by `a_` so that they can be used
as values of HTML or XMD id attributes. Possible codes can be requested  by calling the `getPossibleMunicipalities()` method on a `Kaart` object created with a `year` parameter.


## API

#### `__construct(string $type = 'municipalities', integer $year = NULL)`
Default map type is `municipalities`. Possible map types are: `'municipalities', 'gemeentes', 'corop','provincies', 'provinces', 'municipalities_nl_flanders', 'municipalities_flanders', 'dialectareas'`. `municipalities` is a synonym for `gemeentes`. `provincies` is a synonym for `provinces`. When `$year` is omitted, the default map for version 1.0.X is used, which means the municipality borders of 2007. Use `Kaart::getAllowedYears(string $maptype)` to find the available year for the given map type.
#### Examples
```php
$kaart = new Kaart(); // equals new Kaart('municipalities');
$kaart = new Kaart('provinces');
$kaart = new Kaart('gemeentes', 1921);
```

---
#### `setPathsFile(string $paths_file)`
Path files are geoJSON files (specification 2008) containing coordinates and names for the features of the map. See the contents of the _coords_ directory for examples. Note that the coordinate system should be EPSG:28992. Only needed if the default paths file for the given map type does not suffice. Warning: file should not be user-submitted as it is used as-is. 
#### Examples
```php
$kaart = new Kaart();
$kaart->setPathsFile($alternative_paths_file);
```

---
#### `setAdditionalPathsFiles(array $paths_files )`
Adds more layers on top of the base map. Typical use case: combining different choropleth types in one map, e.g. municipalities as the base with borders of larger areas drawn on top of them. Restricted to files in the _coords_ directory.
#### Examples
```php
// adding dialect areas to map of municipalities
$kaart = new Kaart('gemeentes');
$kaart->setAdditionalPathsFiles(array('dialectareas.json'));
```

---
#### `setIniFile(string $ini_file)`
If alternative styling for features of the map is needed. Can be from the _ini_ directory (no full path needed) or from elsewhere. See the _ini_ directory for examples. Warning: file should not be user-submitted as it is used as-is. 
#### Examples
```php
$kaart = new Kaart('gemeentes');
$kaart->setAdditionalPathsFiles(array('municipalities_flanders.json', 'border_nl_be.json'));
$kaart->setIniFile('municipalities_nl_flanders.ini');
```

---
#### `setData(array $data)`
`$data` should be an array containing areas to be highlighted. Area codes are keys, colors are values. See the geoJSON files in the _coords_ directory for area codes or call `getPossibleAreas()` on a `Kaart` object. Colors can be either HTML color names, #RRGGBB hex strings, or AABBGGRR hex strings. The AA (opacity) part only has an effect on KML maps.
#### Examples
```php
$kaart = new Kaart();
$data = array('g_0534' => '#FFC513');
$kaart->setData($data);
```

```php
$kaart = new Kaart('gemeentes', 1950);
$kaart->setData(array("a_11150" => "#990000"); // note the Amsterdam code instead of the CBS code
```
---
#### `setTitle(string $title)`
Sets the title of the map. Is shown in-picture, above the map.
#### Examples
```php
$kaart = new Kaart('corop');
$kaart->setTitle('The 40 COROP areas');
```

---
#### `setInteractive(bool $value = true)`
Adds JavaScript or title attributes to show area names when hovering. In SVG maps embedded in the map itself and shown using Javascript. A list of `<area>` tags with title attributes is used to achieve the same effect in bitmap maps. Request this with the `getImagemap()` method after calling `setInteractive()`.
#### Examples
```php
$kaart = new Kaart('provincies');
$kaart->setInteractive();
```

---
#### `setPixelWidth(int $width)`
Sets the width of the map in pixels. If not used, width is the default value, defined in the .ini file for the current map type. Default height is defined in the .ini file as a factor. For the default map showing the Netherlands height is e.g. 1.1 times the width.
#### Examples
```php
// set a picture width of 500 pixels
$kaart = new Kaart();
$kaart->setPixelWidth(500);
```

---
#### `setPixelHeight(int $height)`
Sets the height of the map in pixels. If you don't want the default height from the .ini file (for the default map of the Netherlands height is e.g. 1.1 times the width), you can overrule it with an absolute value using this method. You probably shouldn't, though. Note that this method should always be called after `setPixelWidth()`.
#### Examples
```php
// create a square 500 * 500 map
$kaart = new Kaart();
$kaart->setPixelWidth(500);
$kaart->setPixelHeight(500);
```

---
#### `setLink(string $link, mixed $target = NULL)`
Adds the same link to all areas. `%s` placeholder will be replaced with the area code. For bitmap maps the links are in `<area>` elements, which can be obtained by calling the `getImagemap()` method, for other (textual) formats the links are embedded in the map. The optional parameter `$target` can be used to create e.g. `target="_blank"` attributes for the links. 
#### Examples
```php
$kaart = new Kaart();
$kaart->setLink('https://www.example.com/?code=%s');
// now all areas have a link
```

---
#### `setLinkHighlighted(string $link, mixed $target = NULL)`
Adds the same link to all highlighted areas. `%s` placeholder will be replaced with the area code. For bitmap maps the links are in `<area>` elements, which can be obtained by calling the `getImagemap()` method, for other (textual) formats the links are embedded in the map. The optional parameter `$target` can be used to create e.g. `target="_blank"` attributes for the links. 
#### Examples
```php
$kaart = new Kaart();
$kaart->setData(array('g_0363' => '#FFC513'));
$kaart->setLinkHighlighted('http://www.example.com/?code=%s');
// now only area g_0363 (Amsterdam) has a link
// http://www.example.com/?code=g_0363
```

---
#### `setLinks(array $data, mixed $target = NULL)`
Adds links to areas (potentially a different link for each area). `$data` should be an array with area codes as keys and links as values. 
#### Examples
```php
$kaart = new Kaart();
$links = array(
    'g_0003' => 'http://www.example.com/some-path/',
    'g_0363' => 'http://www.example.net/another-path/'
    );
$kaart->setLinks($links, '_blank');
```

---
#### `setToolTips(array $data)`
Adds tooltips to areas. These become 'title' attributes in HTML `<area>` elements or SVG `<path>` elements, `<description>` elements in KML, and 'name' properties in GeoJSON. `$data` should be an array with area codes as keys and strings as values. Note that if `$kaart->setInteractive()` was set to `TRUE` previously, the tooltips overwrite the default area names for the given areas. 
#### Examples
```php
$kaart = new Kaart();
$tooltips = array(
    'g_0003' => 'Some text',
    'g_0363' => 'Another informative text'
    );
$kaart->setLinks($tooltips);
```

---
#### `setJavaScript(array $data, string $event = 'onclick')`
Adds JavaScript code to areas. Possible values of the `$event` parameter: onclick, onmouseover, onmouseout. Note (SVG maps only): if `$kaart->setInteractive()` was set to `TRUE` previously, any onmouseover or onmouseout events added with this method overwrite the default onmouseover and onmouseout events. `$data` should be an array with area codes as keys and Javascript code as values. 
#### Examples
```php
$kaart = new Kaart();
$kaart->setJavaScript(array('g_0003' => "alert('g_0003');"));
```

---
#### `array getData()`
Returns the current associative array with map data (highlighted areas).
#### Examples
```php
$kaart = new Kaart();
$kaart->setData(array('g_0534' => '#FFC513'));
$data = $kaart->getData();
print_r($data);
// Array
// (
//     [g_0534] => #FFC513
// )
```

---
#### `int getPixelWidth()`
Returns the width of the map in pixels.

---
#### `int getPixelHeight()`
Returns the height of the map in pixels.

---
#### `string getTitle()`
Returns the title of the map. Empty string if no title has been set.

---
#### `string | boolean getImagemap()`
For bitmap maps only: returns a string of `<area>` elements for features of the map, for use in a `<map>` HTML element. Can only be called **after** generating a map with the `show()`, `fetch()` or `saveAsFile()` method! Returns FALSE if no bitmap has been generated. 

---
#### `array getPossibleAreas()`
Returns an associative array (area codes as keys, area names as values) for the current map object.

---
#### `array getPossibleMunicipalities()`
Synonym for `getPossibleAreas()` on a map object of type 'municipalities'/'gemeentes'. 

---
#### `void show(string $format = 'svg')`
 Hands the map over to a web browser for further handling. Depending on the capabilities and settings of the browser, the map will be shown on a page, handed to another application, or downloaded. Possible formats are: 'svg', 'png', 'gif','jpeg', 'jpg', 'kml', 'json'.

---
#### `string|resource fetch(string $format = 'svg')`
Returns the map as a string (SVG, JSON, KML) or binary blob (bitmap image). See `show()` for possible formats. 

---
#### `bool saveAsFile(string $filename, string $format = 'svg')`
Saves the map as a file. See `show()` for possible formats. Returns `TRUE` if succeeded, `FALSE` if failed.

---
#### `array Kaart::getAllowedMaptypes()`
Returns an array of possible map types to be used in the constructor.

#### Examples
```php
$types = Kaart::getAllowedMaptypes();
print_r($types);
// Array
// (
//    [0] => municipalities
//    [1] => gemeentes
//    [2] => corop
//    [3] => provincies
//    [4] => provinces
//    [5] => municipalities_nl_flanders
//    [6] => municipalities_flanders
//    [7] => dialectareas
// )
```

---
#### `array Kaart::getAllowedFormats()`
Returns an array of possible map formats to be used in `show()`, `fetch()` or `saveAsFile()` calls.

#### Examples
```php
$formats = Kaart::getAllowedFormats();
print_r($formats);
// Array
// (
//     [0] => gif
//     [1] => png
//     [2] => jpg
//     [3] => jpeg
//     [4] => svg
//     [5] => kml
//     [6] => json
// )
```

---
#### `array Kaart::getAllowedYears(string $maptype)`
Returns an array of possible years to be used in the constructor for the given maptype.

#### Examples
```php
$formats = Kaart::getAllowedYears('provinces');
print_r($formats);
// Array
// (
//    [0] => 1830
//    [1] => 1860
//    [2] => 1890
//    [3] => 1920
//    [4] => 1940
//    [5] => 1950
//    [6] => 1980
// )
```

---
## References
J. Daan and D.P. Blok (1969). _Van randstad tot landrand. Toelichting bij de kaart: dialecten en naamkunde. Bijdragen en mededelingen der Dialectencommissie van de Koninklijke Nederlandse Akademie van Wetenschappen te Amsterdam 37_, Amsterdam, N.V. Noord-Hollandsche uitgevers maatschappij.

Meer, Ad van der  and Onno Boonstra (2011). _Repertorium van Nederlandse gemeenten vanaf 1812, waaraan toegevoegd: de Amsterdamse code_. 2de editie. [Den Haag: DANS.] (DANS Data Guides, 2). [https://dans.knaw.nl/nl/over/organisatie-beleid/publicaties/DANSrepertoriumnederlandsegemeenten2011.pdf](https://dans.knaw.nl/nl/over/organisatie-beleid/publicaties/DANSrepertoriumnederlandsegemeenten2011.pdf)

## Acknowledgments

* This library is a derivative work of the code from the [Meertens Kaart module](http://www.meertens.knaw.nl/kaart/downloads.html).
* This library incorporates slightly modified versions of the PEAR packages Image_Color and XML_SVG.
* This library incorporates the GIS data from: Dr. O.W.A. Boonstra; (2007): NLGis shapefiles. DANS. [https://doi.org/10.17026/dans-xb9-t677](https://doi.org/10.17026/dans-xb9-t677)
* This library is dedicated to Ilse van Gemert (1979-2018).
