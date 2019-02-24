# GemeenteKaart
This documentation in [English](README.md).
___

PHP library om vlakkenkaarten van de Nederlandse gemeentes te maken (gemeentegrenzen van 2007, 443 gemeentes). Gemeentes kunnen ingekleurd worden. Een gebruikelijke use case is om hiermee de relatieve dichtheid van een of ander verschijnsel aan te geven.

Overige beschikbare data: de gemeentes van Vlaanderen (308 gemeentes), de veertig Nederlandse [COROPgebieden](https://nl.wikipedia.org/wiki/COROP), de twaalf Nederlandse [provincies](https://nl.wikipedia.org/wiki/Provincies_van_Nederland), of de 28 [dialectgebieden](https://nl.wikipedia.org/wiki/Jo_Daan#/media/File:Dutch-dialects.svg) uit Daan/Blok (1969) afgebeeld op gemeentegrenzen. 

Kaarten kunnen gegenereerd worden als SVG (default), PNG, GIF, JPEG, KML, GeoJSON.

## Hoe te beginnen

Gebruik het volgende in composer.json om met [composer](https://getcomposer.org/) als een bibliotheek te installeren vanuit github:
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

Laat `repositories` weg om als een bibliotheek te installeren vanuit [packagist.org](https://packagist.org):

```json
{
    "require": {
    "janpieterk/gemeentekaart-core": "1.0.1"
    }
}
```

Als een project installeren vanuit github met composer:

```bash
$ composer create-project janpieterk/gemeentekaart-core --repository='{"type":"vcs","url":"https://github.com/janpieterk/gemeentekaart-core"}'
```

Laat `--repository` weg om vanuit [packagist.org](https://packagist.org) te installeren:

```bash
$ composer create-project janpieterk/gemeentekaart-core
```


De PHPUnit tests van gemeentekaart-core laten lopen:

* Start het shellscript `test/run_tests.sh`.


## Een simpel voorbeeld

Om de gemeentes Amsterdam en Rotterdam respectievelijk rood en groen te kleuren en een SVG-kaart te produceren:

```php
$municipalities = array('g_0363' => '#FF0000', 'g_0599' => '#00FF00');

$kaart = new Kaart('municipalities');
$kaart->addData($municipalities);
$kaart->show('svg');
```

Gebruikte codes zijn de officiële Nederlandse gemeentecoces, 
te verkrijgen bij [www.cbs.nl](https://www.cbs.nl), voorafgegaan door `g_` zo dat ze als waardes van id attributen in HTML gebruikt kunnen worden.

## API

#### `__construct(string $type = 'municipalities')`
Default kaarttype is `municipalities`. Mogelijke kaarttypes zijn: `'municipalities', 'gemeentes', 'corop','provincies', 'provinces', 'municipalities_nl_flanders', 'municipalities_flanders', 'dialectareas'`. `municipalities` is een synoniem van `gemeentes`. `provincies` is een synoniem van `provinces`.
#### Voorbeelden
```php
$kaart = new Kaart(); // zelfde als new Kaart('municipalities');
$kaart = new Kaart('provincies');
```

---
#### `setPathsFile(string $paths_file)`
Files met paden zijn geoJSON files (specificatie 2008) die coördinaten en namen van de onderdelen van een kaart bevatten. Voorbeelden zijn te vinden in de _coords_ directory. Merk op dat het coördinatensysteem EPSG:28992 (Rijksdriehoeksstelsel) moet zijn. Alleen nodig als de default file met paden voor het gegeven maptype niet voldoet. Waarschuwing: accepteer niet zonder meer door gebruikers opgegeven files want de hier ingegeven file wordt zoals hij is gebruikt.
#### Voorbeelden
```php
$kaart = new Kaart();
$kaart->setPathsFile($alternative_paths_file);
```

---
#### `setAdditionalPathsFiles(array $paths_files )`
Voegt meer lagen toe bovenop de basiskaart. Een typische use case: verschillende vlakkenkaarten in één kaart combineren, bijvoorbeeld gemeentes als de basis met grenzen van grotere gebieden daarbovenop. Beperkt tot files in de _coords_ directory.
#### Voorbeelden
```php
// dialectgeboeden toevoegen aan een kaart met gemeentes
$kaart = new Kaart('gemeentes');
$kaart->setAdditionalPathsFiles(array('dialectareas.json'));
```

---
#### `setIniFile(string $ini_file)`
Te gebruiken als onderdelen van de kaart een alternatieve stijl moeten krijgen. Kan een file uit de _ini_ directory zijn (volledig pad niet nodig) of van elders. Zie de _ini_ directory voor voorbeelden.  Waarschuwing: accepteer niet zonder meer door gebruikers opgegeven files want de hier ingegeven file wordt zoals hij is gebruikt. 
#### Voorbeelden
```php
$kaart = new Kaart('gemeentes');
$kaart->setAdditionalPathsFiles(array('municipalities_flanders.json', 'border_nl_be.json'));
$kaart->setIniFile('municipalities_nl_flanders.ini');
```

---
#### `setData(array $data)`
`$data` is een array met gebieden die ingekleurd moeten worden. Gebiedscodes zijn de keys, kleuren zijn de waardes. Zie de geoJSON files in de _coords_ directory voor gebiedscodes of roep `getPossibleAreas()` aan op een `Kaart` object. Kleuren kunnen HTML kleurnamen zijn, #RRGGBB hex strings, of AABBGGRR hex strings. AA (ondoorschijnendheid) heeft alleen effect bij KML-kaarten.
#### Voorbeelden
```php
$kaart = new Kaart();
$data = array('g_0534' => '#FFC513');
$kaart->setData($data);
```

---
#### `setTitle(string $title)`
Bepaalt de titel van de kaart. Wordt in de afbeelding boven de kaart weergegeven.
#### Voorbeelden
```php
$kaart = new Kaart('corop');
$kaart->setTitle('De 40 COROPgebieden');
```

---
#### `setInteractive(bool $value = true)`
Voegt Javascript of title-attributen toe om bij er over heen gaan met de muis namen van gebieden te tonen. In SVG-kaarten is dit onderdeel van de kaart zelf en geîmplementeerd in Javascript. Een lijst van ```<area>``` tags met title-attributen wordt gebruikt om hetzelfde effect te bereiken bij bitmap-kaarten. Deze lijst kan opgevraagd worden met de `getImagemap()` methode, na een aanroep van `setInteractive()`.
#### Voorbeelden
```php
$kaart = new Kaart('provincies');
$kaart->setInteractive();
```

---
#### `setPixelWidth(int $width)`
Bepaalt de breedte van de kaart in pixels. Als deze methode niet wordt gebruikt is de breedte de default in de .ini file van het huidige kaarttype. De default hoogte is in de .ini file gedefinieerd als een factor. Bij de default kaart die het Nederlandse grondgebied laat zien is de hoogte bijvoorbeeld 1,1 maal de breedte.
#### Voorbeelden
```php
// afbeelding met een breedt van 500 pixels
$kaart = new Kaart();
$kaart->setPixelWidth(500);
```

---
#### `setPixelHeight(int $height)`
Bepaalt de hoogte van de kaart in pixele. Als je niet de default hoogte uit de  .ini file wil (bij de default kaart die het Nederlandse grondgebied laat zien is de hoogte bijvoorbeeld 1,1 maal de breedte), kan je die met deze methode vervangen door een absolute waarde. Waarschijnljk is dit echter geen goed idee. Let op: deze methode moet altijd na `setPixelWidth()` aangeroepen worden.
#### Voorbeelden
```php
// maak een vierkante kaart van 500 * 500 pixels
$kaart = new Kaart();
$kaart->setPixelWidth(500);
$kaart->setPixelHeight(500);
```

---
#### `setLink(string $link, mixed $target = NULL)`
Voegt dezelfde link toe aan alle gebieden. Een`%s` variabele zal vervangen worden door de gebiedscode. Bij bitmapkaarten zijn de links href-attributen van  `<area>` elementen, die verkregen kunnen worden door de `getImagemap()` methode, bij andere (tekstuele) formats maken de links onderdeel uit van de kaart. De facultatieve parameter `$target` kan gebruikt worden om bijvoorbeeld `target="_blank"` attributen aan de link toe te voegen. 
#### Voorbeelden
```php
$kaart = new Kaart();
$kaart->setLink('https://www.example.com/?code=%s');
// nu hebben alle gebieden een link
```

---
#### `setLinkHighlighted(string $link, mixed $target = NULL)`
Voegt dezelde link toe aan alle ingekleurde gebieden. Een`%s` variabele zal vervangen worden door de gebiedscode. Bij bitmapkaarten zijn de links href-attributen van  `<area>` elementen, die verkregen kunnen worden door de `getImagemap()` methode, bij andere (tekstuele) formats maken de links onderdeel uit van de kaart. De facultatieve parameter `$target` kan gebruikt worden om bijvoorbeeld `target="_blank"` attributen aan de link toe te voegen. 
#### Voorbeelden
```php
$kaart = new Kaart();
$kaart->setData(array('g_0363' => '#FFC513'));
$kaart->setLinkHighlighted('http://www.example.com/?code=%s');
// Nu heeft alleen gebied g_0363 (Amsterdam) een link
// http://www.example.com/?code=g_0363
```

---
#### `setLinks(array $data, mixed $target = NULL)`
Voegt links toe aan gebieden (potentieel een andere link voor elk gebied). `$data` moet een array zijn met gebiedscodes als keys en links als waardes. 
#### Voorbeelden
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
Voegt tooltips toe aan gebieden. Dit worden 'title' attributen in HTML `<area>` elementen of SVG `<path>` elementen, `<description>` elementen in KML, en 'name' properties in GeoJSON. `$data` moet een array zijn met gebiedscodes als keys en strings als waardes. Merk op dat als `$kaart->setInteractive()` eeder op `TRUE` gezet was de tooltips die hier gegeeven worden de default gebiedsnamen overschrijven. 
#### Voorbeelden
```php
$kaart = new Kaart();
$tooltips = array(
    'g_0003' => 'Een tekst',
    'g_0363' => 'Een andere informatieve tekst'
    );
$kaart->setLinks($tooltips);
```

---
#### `setJavaScript(array $data, string $event = 'onclick')`
Voegt Javascriptcode toe aan gebieden. Mogelijke waardes van de `$event` parameter: onclick, onmouseover, onmouseout. Merk op dat als bij SVG-kaarten eerder `$kaart->setInteractive()` op `TRUE` gezet was, onmouseover of onmouseout events die met deze methode toegevoegd worden de default onmouseover en onmouseout events overschrijven. `$data` moet een array zijn met gebiedscodes als keys en Javascript code als waardes. 
#### Voorbeelden
```php
$kaart = new Kaart();
$kaart->setJavaScript(array('g_0003' => "alert('g_0003');"));
```

---
#### `array getData()`
Geeft het huidige associatieve array met kaartdata (ingekleurde gebieden) terug.
#### Voorbeelden
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
Geeft de breedte van de kaart in pixels terug.

---
#### `int getPixelHeight()`
Geeft de hoogte van de kaart in pixels terug.

---
#### `string getTitle()`
Geeft de titel van de kaart terug. Een lege string als er geen titel gezet is.

---
#### `string getImagemap()`
Alleen voor bitmapkaarten: geeft een string met `<area>` elementen van onderdelen van de kaart terug, om in een `<map>` HTML element te gebruiken. Kan alleen aangeroepen worden **na** het genereren van een kaart met de `show()`, `fetch()` of `saveAsFile()` methodes! 

---
#### `array getPossibleAreas()`
Geeft een associatief array terug (gebiedscodes zijn keys, namen van gebieden zijn waardes) voor het huidige kaarttype.

---
#### `array getPossibleMunicipalities()`
Synoniem van `getPossibleAreas()` bij een kaart van type 'municipalities'/'gemeentes'. 

---
#### `void show(string $format = 'svg')`
Geeft de kaart aan een web browser om verder af te handelen. Afhankelijk van de capaciteiten en de instellingen van de browser zal de kaart op een pagina getoond worden, aan een andere applicatie doorgegeven, of gedownload worden. Mogelijke formats zijn: 'svg', 'png', 'gif','jpeg', 'jpg', 'kml', 'json'.

---
#### `string|blob fetch(string $format = 'svg')`
Geeft de kaart terug als een string (SVG, JSON, KML) of als een binaire blob (bitmapafbeelding). Zie `show()` voor mogelijke formats. 

---
#### `bool saveAsFile(string $filename, string $format = 'svg')`
Bewaart de kaart in een file. Zie `show()` voor mogelijke formats. Geeft `TRUE` terug als het bewaren geslaagd is, `FALSE` als het mislukt is.

---
#### `array Kaart::getAllowedMaptypes()`
Geeft aan array van mogelijke kaarttypen die in de constructor gebruikt worden terug.

#### Voorbeelden
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
Geeft een array van mogelijke kaartformats terug die gebruikt kunnen worden in `show()`, `fetch()` of `saveAsFile()` aanroepen.

#### Voorbeelden
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

## Bibliografie
J. Daan en D.P. Blok (1969). _Van randstad tot landrand. Toelichting bij de kaart: dialecten en naamkunde. Bijdragen en mededelingen der Dialectencommissie van de Koninklijke Nederlandse Akademie van Wetenschappen te Amsterdam 37_, Amsterdam, N.V. Noord-Hollandsche uitgevers maatschappij.

## Dankwoord

* Deze bibliotheek is een afgeleide van de [Meertens Kaartmodule](http://www.meertens.knaw.nl/kaart/downloads.html).
* Deze bibliotheen bevat licht aangepaste versies van de PEAR packages Image_Color en XML_SVG.
* Deze bibliotheek is opgedragen aan Ilse van Gemert (1979-2018).

