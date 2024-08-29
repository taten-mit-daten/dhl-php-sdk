<?php
use tatenmitdaten\dhl\Sender;
use tatenmitdaten\dhl\Receiver;
use tatenmitdaten\dhl\Credentials;
use tatenmitdaten\dhl\ShipmentDetails;
use tatenmitdaten\dhl\ShipmentOrder;
use tatenmitdaten\dhl\BusinessShipment;

require_once __DIR__ . '/vendor/autoload.php';

$credentials = new Credentials(Credentials::TEST_NORMAL);
$credentials->setApiUser('Entwickerl ID'); // <- Entwickler ID aus dem DHL Portal
$credentials->setApiKey('Entwickler PW'); // <- Entwicklerppasswort aus dem DHL Portal

$sender = new Sender();
$sender->setName('Muster AG');
$sender->setStreetName('Erich-Weinert-Strasse');
$sender->setStreetNumber('5');
$sender->setZip('39240');
$sender->setCity('Calbe');
$sender->setCountryISOCode('DE');

$receiver = new Receiver();
$receiver->setName('Test GmbH');
$receiver->setStreetName('Goethestraße');
$receiver->setStreetNumber('2');
$receiver->setZip('39108');
$receiver->setCity('Magdeburg');
$receiver->setCountryISOCode('DE');

$receiver_1 = new Receiver();
$receiver_1->setName('Probe GbR');
$receiver_1->setStreetName('Breiter Weg 21');
$receiver_1->setZip('85635');
$receiver_1->setCity('Siegerts Brunn');
$receiver_1->setCountryISOCode('DE');

$shipdetail = new ShipmentDetails($credentials->getEkp(10) . '0101');
$shipdetail->setWeight(1.2);
$shipdetail->setCustomerReference('0815');

$shipdetail_1 = new ShipmentDetails($credentials->getEkp(10) . '0101');
$shipdetail_1->setWeight(1.5);

$dhl = new BusinessShipment($credentials, $credentials::TEST_NORMAL);
$dhl->setLabelResponseType($dhl::RESPONSE_TYPE_B64);

$shiporder = new ShipmentOrder();
$shiporder->setSender($sender);
$shiporder->setReceiver($receiver);
$shiporder->setShipmentDetails($shipdetail);

$shiporder_1 = new ShipmentOrder();
$shiporder_1->setSender($sender);
$shiporder_1->setReceiver($receiver_1);
$shiporder_1->setShipmentDetails($shipdetail_1);

$dhl->addShipmentOrder($shiporder);
$dhl->addShipmentOrder($shiporder_1);
//$dhl->setLabelResponseType($dhl::RESPONSE_TYPE_URL);
$dhl->setLabelResponseType($dhl::RESPONSE_TYPE_B64);
//$response = $dhl->getVersion(true);

$response = $dhl->createShipment();
if($response === false) {
    // Do your Error-Handling here

    // Just to show all Errors
    var_dump($dhl->getErrors()); // Get the Error-Array
} else {
    // Handle the Response here

    // Just to show the whole Response-Object
    var_dump($response);
    $arrLabeldata = $response->getLabelData();
    foreach ($arrLabeldata as $i => $labelData){
        if ($labelData->getStatusCode() == 0 || $labelData->getStatusCode() == 1) {
            print "Tracking ID " . $labelData->getShipmentNumber() . '<br />';
            $strPdfLabel = base64_decode($labelData->getLabel());
            $strFilename_Label = 'label_0815_' . strval($i + 1) . '.pdf';
            $datei = fopen(
                './' . $strFilename_Label,
                "w"
            );
            $erg = fwrite($datei, $strPdfLabel);
            fclose($datei);
            if ($erg === false) {
                echo 'FEHLER - konnte PDF Label nicht in Datei schreiben! <br />';
            }
        }
        else
            echo 'FEHLER - ' . $labelData->getStatusMessage() . '<br />';
    }
}