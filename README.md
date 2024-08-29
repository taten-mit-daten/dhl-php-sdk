# DHL PHP SDK

Eine inoffizielle Bibliothek fuer die Verwendung der DHL Geschaeftskunden API mit PHP
Bitte habt Nachsicht mit mir, mein erstes Projekt auf Github...

## Voraussetzungen

- du benoetigst einen [DHL developer Account](https://entwickler.dhl.de/) und  in produktiven Umgebungen einen  DHL Intraship Zugang
- PHP-Version 7.4 oder hoeher _(nicht darunter getestet)_
- PHP-SOAP-Client installiert auf dem WEB Server. [mehr Infos auf php.net](http://php.net/manual/en/soap.setup.php)

## Installation

Bitte verwende [Composer](https://getcomposer.org/) zur Installation in deinem Projekt:

```
composer require taten-mit-daten/dhl-php-sdk
```

## Kompatiblität

Das Projekt ist getestet mit der DHL-SOAP-API **Version 3.2.2**.
Ältere 3er Versionen sollten auch gehen, unterstuetzen aber nicht die DHL Warenpost 

## Usage / Getting started

- Link zur Doku der ursrünglichen Version [Getting started (Just a quick guide how you have to use it)](https://github.com/Petschko/dhl-php-sdk/blob/master/examples/getting-started.md), bitte dort taten-mit-daten als Namespace ersetzen
- in den Ordner src/wsdl/Versionsnummer kommen das passende wsdl File und die xsd Files rein, Die kann man auf der DHL Seite downloaden. Ein direktes Laden während der Programmausführung unterbindet DHL für die aktuellen Versionen. D.h. für die Version 3.2.2 müssen in das Verzeichnis `src/wsdl/3.2.2` die Files
   - geschaeftskundenversand-api-3.2.2-schema-bcs_base.xsd
   - geschaeftskundenversand-api-3.2.2-schema-cis_base.xsd
   - geschaeftskundenversand-api-3.2.2.wsdl
   - in der Datei `src/BusinessShipment.php` muss dazu passend die Version eingetragen werden:

```
...

/**
* Newest-Version
*/
const NEWEST_VERSION = '3.2.2';

...
```
## Updates

v0.2.1 - In der Zollanmeldung (d.h. Class ExportDocument) kann jetzt eine function setCustomsCurrency() mit der die Waehrung der Zollanmeldung übergeben werden kann.

## Motivation

Für mein Lagerprogramm benötigte mein Lieblingskunde eine DHL Versandanbindung und ich eine einfach zu handhabende Lösung. Kann es selber kaum glauben, damit werden jetzt > 100k Paket pro Jahr in die Welt verschickt...

## Credits

- 1000x Dank an [Petschko](https://github.com/Petschko) dessen Projekt ich hier geforkt habe und was leider nicht mehr weiter gepflegt wurde

## Contact

- meine E-Mail für Fragen (keine Fehlermeldungen bitte!): info(at)@taten-mit-daten(Punkt)de
- [Fehlermeldungen](https://github.com/taten-mit-daten/dhl-php-sdk/issues)
