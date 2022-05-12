<?php

namespace tatenmitdaten\dhl;

/**
 * Author: Jens Braeuner [info@taten-mit-daten.de], Idee: Peter Dragicevic [peter@petschko.org]
 * Authors-Website: https:// www.taten-mit-daten.de http://petschko.org/
 * Date: 23.01.2021
 * Version: 0.1.1
 *
 * Notes: Contains all Functions/Values for DHL-Business-Shipment
 */

use Exception;
use SoapClient;
use SoapHeader;
use stdClass;

/**
 * Class BusinessShipment
 *
 * @package tatenmitdaten\dhl
 */
class BusinessShipment extends Version {
    /**
     * DHL-Soap-Header URL
     */
    const DHL_SOAP_HEADER_URI = 'http://dhl.de/webservice/cisbase';

    /**
     * DHL-Sandbox SOAP-URL
     */
    const DHL_SANDBOX_URL = 'https://cig.dhl.de/services/sandbox/soap';

    /**
     * DHL-Live SOAP-URL
     */
    const DHL_PRODUCTION_URL = 'https://cig.dhl.de/services/production/soap';

    /**
     * Newest-Version
     */
    const NEWEST_VERSION = '3.2.2';

    /**
     * Response-Type URL
     */
    const RESPONSE_TYPE_URL = 'URL';

    /**
     * Response-Type Base64
     */
    const RESPONSE_TYPE_B64 = 'B64';

    /**
     * Maximum requests to DHL in one call
     */
    const MAX_DHL_REQUESTS = 30;

    // System-Fields
    /**
     * Contains the Soap Client
     *
     * @var SoapClient|null $soapClient - Soap-Client
     */
    private $soapClient = null;

    /**
     * Contains the error array
     *
     * @var string[] $errors - Error-Array
     */
    private $errors = array();

    // Setting-Fields
    /**
     * Contains if the Object runs in Sandbox-Mode
     *
     * @var bool $test - Is Sandbox-Mode
     */
    private $test;

    // Object-Fields
    /**
     * Contains the Credentials Object
     *
     * Notes: Is required every time! Used to login
     *
     * @var Credentials $credentials - Credentials Object
     */
    private $credentials;

    /**
     * Contains if how the Label-Response-Type will be
     *
     * Note: Optional
     * Values:
     * RESPONSE_TYPE_URL -> Url
     * RESPONSE_TYPE_B64 -> Base64
     *
     * @var string|null $labelResponseType - Label-Response-Type (Can use class constance's) (null uses default)
     */
    private $labelResponseType = null;

    /**
     * Contains all Shipment-Orders
     *
     * Note: Can be up to 30 Shipment-Orders
     *
     * @var ShipmentOrder[] $shipmentOrders - Contains ShipmentOrder Objects
     */
    private $shipmentOrders = array();

    /**
     * Custom-WSDL-File URL
     *
     * @var null|string $customAPIURL - Custom-API URL (null uses default from DHL)
     */
    private $customAPIURL = null;

    /**
     * Pfad zum WSDL-File
     *
     * @var null|string strDhl_wsdl_url - Pfad zur lokalen URL der WSDL
     */
    private $strDhl_wsdl_url = null;


    /**
     * BusinessShipment constructor.
     *
     * @param Credentials $credentials - DHL-Credentials-Object
     * @param bool|string $testMode - Use a specific Sandbox-Mode or Production-Mode
     * 					Test-Mode (Normal): Credentials::TEST_NORMAL, 'test', true
     * 					Test-Mode (Thermo-Printer): Credentials::TEST_THERMO_PRINTER, 'thermo'
     * 					Live (No-Test-Mode): false - default
     * @param null|string $version - Version to use or null for the newest
     */
    public function __construct($credentials, $testMode = false, $version = null) {
        // Set Version
        if($version === null)
            $version = self::NEWEST_VERSION;

        parent::__construct($version);

        // Set Test-Mode
        $this->setTest((($testMode) ? true : false));

        // Set Credentials
        if($this->isTest()) {
            $c = new Credentials($testMode);
            $c->setApiUser($credentials->getApiUser());
            $c->setApiPassword($credentials->getApiPassword());

            $credentials = $c;
        }
        $this->setCredentials($credentials);

        $this->strDhl_wsdl_url = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wsdl'. DIRECTORY_SEPARATOR;
    }

    /**
     * Clears Memory
     */
    public function __destruct() {
        parent::__destruct();
        unset($this->soapClient);
        unset($this->errors);
        unset($this->test);
        unset($this->credentials);
        unset($this->labelResponseType);
        unset($this->shipmentOrders);
        unset($this->customAPIURL);
    }

    /**
     * Get the Business-API-URL for this Version
     *
     * @return string - Business-API-URL
     */
    protected function getAPIUrl() {
        // Use own API-URL if set
        if($this->getCustomAPIURL() !== null)
            return $this->getCustomAPIURL();
        return $this->strDhl_wsdl_url . $this->getVersion() . DIRECTORY_SEPARATOR . 'geschaeftskundenversand-api-' . $this->getVersion() . '.wsdl';
    }

    /**
     * Get the Soap-Client if exists
     *
     * @return null|SoapClient - SoapClient or null on error
     */
    protected function getSoapClient() {
        if($this->soapClient === null)
            $this->buildSoapClient();

        return $this->soapClient;
    }

    /**
     * Returns the Last XML-Request or null
     *
     * @return null|string - Last XML-Request or null if none
     */
    public function getLastXML() {
        if($this->soapClient === null)
            return null;

        return $this->getSoapClient()->__getLastRequest();
    }

    /**
     * Returns the last XML-Response from DHL or null
     *
     * @return null|string - Last XML-Response from DHL or null if none
     */
    public function getLastDhlXMLResponse() {
        if($this->soapClient === null)
            return null;

        return $this->getSoapClient()->__getLastResponse();
    }

    /**
     * Set the Soap-Client
     *
     * @param null|SoapClient $soapClient - Soap-Client
     */
    protected function setSoapClient($soapClient) {
        $this->soapClient = $soapClient;
    }

    /**
     * Get Error-Array
     *
     * @return string[] - Error-Array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Set Error-Array
     *
     * @param string[] $errors - Error-Array
     */
    public function setErrors($errors) {
        $this->errors = $errors;
    }

    /**
     * Adds an Error to the Error-Array
     *
     * @param string $error - Error-Message
     */
    private function addError($error) {
        $this->errors[] = $error;
    }

    /**
     * Returns if this instance run in Test-Mode / Sandbox-Mode
     *
     * @return bool - Runs in Test-Mode / Sandbox-Mode
     */
    protected function isTest() {
        return $this->test;
    }

    /**
     * Set if this instance runs in Test-Mode / Sandbox-Mode
     *
     * @param bool $test - Runs in Test-Mode / Sandbox-Mode
     */
    private function setTest($test) {
        $this->test = $test;
    }

    /**
     * Get Credentials-Object
     *
     * @return Credentials - Credentials-Object
     */
    protected function getCredentials() {
        return $this->credentials;
    }

    /**
     * Set Credentials-Object
     *
     * @param Credentials $credentials - Credentials-Object
     */
    public function setCredentials($credentials) {
        $this->credentials = $credentials;
    }


    /**
     * Get the Label-Response type
     *
     * @return null|string - Label-Response type | null means DHL-Default
     */
    public function getLabelResponseType() {
        return $this->labelResponseType;
    }

    /**
     * Set the Label-Response type
     *
     * @param null|string $labelResponseType - Label-Response type | null uses DHL-Default
     */
    public function setLabelResponseType($labelResponseType) {
        $this->labelResponseType = $labelResponseType;
    }

    /**
     * Get the list with all Shipment-Orders Objects
     *
     * @return ShipmentOrder[] - List with all Shipment-Orders Objects
     */
    public function getShipmentOrders() {
        return $this->shipmentOrders;
    }

    /**
     * Set the list with all Shipment-Orders Objects
     *
     * @param ShipmentOrder[]|ShipmentOrder $shipmentOrders - Shipment-Order Object-Array or a Single Shipment-Order Object
     */
    public function setShipmentOrders($shipmentOrders) {
        if(! is_array($shipmentOrders)) {
            trigger_error(
                '[DHL-PHP-SDK]: The type of $shipmentOrders is NOT an array, but is required to set as array! Called method ' .
                __METHOD__ . ' in class ' . __CLASS__,
                E_USER_ERROR
            );
            $this->addError(__METHOD__ . ': Non-Array value given');

            return;
        }

        $this->shipmentOrders = $shipmentOrders;
    }

    /**
     * Adds a Shipment-Order to the List
     *
     * @param ShipmentOrder $shipmentOrder - Shipment-Order to add
     */
    public function addShipmentOrder($shipmentOrder) {
        $this->shipmentOrders[] = $shipmentOrder;
    }

    /**
     * Clears the Shipment-Order list
     */
    public function clearShipmentOrders() {
        $this->setShipmentOrders(array());
    }

    /**
     * Returns how many Shipment-Orders are in this List
     *
     * @return int - ShipmentOrder Count
     */
    public function countShipmentOrders() {
        return count($this->getShipmentOrders());
    }

    /**
     * Get the Custom-API-URL
     *
     * @return null|string - Custom-API-URL or null for none
     */
    public function getCustomAPIURL() {
        return $this->customAPIURL;
    }

    /**
     * Set the Custom-API-URL
     *
     * @param null|string $customAPIURL - Custom-API-URL or null for none
     */
    public function setCustomAPIURL($customAPIURL) {
        $this->customAPIURL = $customAPIURL;
    }

    /**
     * Check if the request-Array is to long
     *
     * @param array $array - Array to check
     * @param string $action - Action of the request
     * @param int $maxReq - Maximum-Requests - Default: self::MAX_DHL_REQUESTS
     */
    private function checkRequestCount($array, $action, $maxReq = self::MAX_DHL_REQUESTS) {
        $count = count($array);

        if($count > self::MAX_DHL_REQUESTS)
            $this->addError('There are only ' . $maxReq . ' Request/s for one call allowed for the action "'
                . $action . '"! You tried to request ' . $count . ' ones');
    }

    /**
     * Build SOAP-Auth-Header
     *
     * @return SoapHeader - Soap-Auth-Header
     */
    protected function buildAuthHeader() {
        $auth_params = array(
            'user' => $this->getCredentials()->getUser(),
            'signature' => $this->getCredentials()->getSignature(),
            'type' => 0
        );
        return new SoapHeader(self::DHL_SOAP_HEADER_URI, 'Authentification', $auth_params);
    }

    /**
     * Builds the Soap-Client
     */
    protected function buildSoapClient() {
        $header = $this->buildAuthHeader();

        if($this->isTest())
            $location = self::DHL_SANDBOX_URL;
        else
            $location = self::DHL_PRODUCTION_URL;

        $auth_params = array(
            'login' => $this->getCredentials()->getApiUser(),
            'password' => $this->getCredentials()->getApiPassword(),
            'location' => $location,
            'trace' => 1
        );

        $this->setSoapClient(new SoapClient($this->getAPIUrl(), $auth_params));
        $this->getSoapClient()->__setSoapHeaders($header);
    }

    /**
     * Gets the current (local)-Version or Request it via SOAP from DHL
     *
     * @param bool $viaSOAP - Request the Version from DHL (Default: false - get local-version as string)
     * @param bool $getBuildNumber - Return the Build number as well (String look then like this: 2.2.12) Only possible via SOAP - Default false
     * @param bool $returnAsArray - Return the Version as Array - Default: false
     * @return bool|array|string - Returns the Version as String|array or false on error
     */
    public function getVersion($viaSOAP = false, $getBuildNumber = false, $returnAsArray = false) {
        if(! $viaSOAP) {
            if($returnAsArray)
                return array(
                    'mayor' => parent::getMayor(),
                    'minor' => parent::getMinor()
                );
            else
                return parent::getVersion();
        }

        switch($this->getMayor()) {
            case 1:
                trigger_error('[DHL-PHP-SDK]: Called Version 1 Method: ' . __METHOD__ . ' is incomplete (does nothing)!', E_USER_WARNING);
                $this->addError('Version 1 SOAP-Method "' . __METHOD__ . '" is not implemented or removed!');

                return false;
            case 2:
            default:
                $data = $this->getVersionClass();
        }

        try {
            $response = $this->sendGetVersionRequest($data);
        } catch(Exception $e) {
            $this->addError($e->getMessage());

            return false;
        }

        if(is_soap_fault($response)) {
            $this->addError($response->faultstring);

            return false;
        } else {
            if($returnAsArray)
                return array(
                    'mayor' => $response->Version->majorRelease,
                    'minor' => $response->Version->minorRelease,
                    'build' => $response->Version->build
                );
            else
                return $response->Version->majorRelease . '.' . $response->Version->minorRelease .
                    (($getBuildNumber) ? '.' . $response->Version->build : '');
        }
    }

    /**
     * Creates the getVersion-Request via SOAP
     *
     * @param Object|array $data - Version-Data
     * @return Object - DHL-Response
     */
    private function sendGetVersionRequest($data) {
        return $this->getSoapClient()->getVersion($data);
    }

    /**
     * Creates the doManifest-Request via SOAP
     *
     * @param Object|array $data - Manifest-Data
     * @return Object - DHL-Response
     */
    private function sendDoManifestRequest($data) {
        return $this->getSoapClient()->doManifest($data);
    }

    /**
     * Creates the doManifest-Request
     *
     * @param string|string[] $shipmentNumbers - Shipment-Number(s) for Manifest (up to 30 Numbers)
     * @return bool|Response - false on error or DHL-Response Object
     */
    public function doManifest($shipmentNumbers) {
        $data = $this->createDoManifestClass_v3($shipmentNumbers);

        try {
            $response = $this->sendDoManifestRequest($data);
        } catch(Exception $e) {
            $this->addError($e->getMessage());

            return false;
        }

        if(is_soap_fault($response)) {
            $this->addError($response->faultstring);

            return false;
        } else
            return new Response($this->getVersion(), $response);
    }

    /**
     * Creates the Data-Object for Manifest
     *
     * @param string|string[] $shipmentNumbers - Shipment-Number(s) for the Manifest (up to 30 Numbers)
     * @return StdClass - Data-Object
     */
    private function createDoManifestClass_v3($shipmentNumbers) {
        $data = new StdClass;

        $data->Version = $this->getVersionClass();

        if(is_array($shipmentNumbers)) {
            $this->checkRequestCount($shipmentNumbers, 'doManifest');

            foreach($shipmentNumbers as $key => &$number)
                $data->shipmentNumber[$key] = $number;
        } else
            $data->shipmentNumber = $shipmentNumbers;

        return $data;
    }

    /**
     * Creates the getManifest-Request
     *
     * @param string|int $manifestDate - Manifest-Date as String (YYYY-MM-DD) or the int time() value of the date
     * @param bool $useIntTime - Use the int Time Value instead of a String
     * @return bool|Response - false on error or DHL-Response Object
     */
    public function getManifest($manifestDate, $useIntTime = false) {
        if($useIntTime) {
            // Convert to Date-Format for DHL
            $oldDate = $manifestDate;
            $manifestDate = date('Y-m-d', $manifestDate);

            if($manifestDate === false) {
                $this->addError('Could not convert given time() value "' . $oldDate . '" to YYYY-MM-DD... Called method: ' . __METHOD__);

                return false;
            }

            unset($oldDate);
        }

        $data = $this->createGetManifestClass_v3($manifestDate);

        try {
            $response = $this->sendGetManifestRequest($data);
        } catch(Exception $e) {
            $this->addError($e->getMessage());

            return false;
        }

        if(is_soap_fault($response)) {
            $this->addError($response->faultstring);

            return false;
        } else
            return new Response($this->getVersion(), $response);
    }

    /**
     * Creates the Data-Object for getManifest
     *
     * @param string $manifestDate - Manifest Date (String-Format: YYYY-MM-DD)
     * @return StdClass - Data-Object
     */
    private function createGetManifestClass_v3($manifestDate) {
        $data = new StdClass;

        if(is_array($manifestDate))
            $this->addError('You can only request 1 date on getManifest - multiple requests in 1 call are not allowed here');

        $data->Version = $this->getVersionClass();
        $data->manifestDate = $manifestDate;

        return $data;
    }

    /**
     * Creates the getManifest-Request via SOAP
     *
     * @param Object|array $data - Manifest-Data
     * @return Object - DHL-Response
     */
    private function sendGetManifestRequest($data) {
        return $this->getSoapClient()->getManifest($data);
    }

    /**
     * Creates the Shipment-Order Request via SOAP
     *
     * @param Object|array $data - Shipment-Data
     * @return Object - DHL-Response
     */
    private function sendCreateRequest($data) {
        //$client = $this->getSoapClient();
        //$resp = $client->createShipmentOrder($data);
        //return $resp;
        return $this->getSoapClient()->createShipmentOrder($data);
    }

    /**
     * Alias for createShipmentOrder
     *
     * Creates the Shipment-Request
     *
     * @return bool|Response - false on error or DHL-Response Object
     */
    public function createShipment() {
        return $this->createShipmentOrder();
    }

    /**
     * Creates the Shipment-Request
     *
     * @return bool|Response - false on error or DHL-Response Object
     */
    public function createShipmentOrder() {
        /*
         * die Funktion habe ich geloescht
         *
        if($this->countShipmentOrders() < 1)
            $data = $this->createShipmentClass_v3_legacy();
        else
        */
        $data = $this->createShipmentClass_v3();

        $response = null;

        // Create Shipment
        try {
            $response = $this->sendCreateRequest($data);
        } catch(Exception $e) {
            $this->addError($e->getMessage());

            return false;
        }

        if(is_soap_fault($response)) {
            $this->addError($response->faultstring);

            return false;
        } else
            return new Response($this->getVersion(), $response);
    }

    /**
     * Creates the Data-Object for the Request
     *
     * @param null|string $shipmentNumber - Shipment Number which should be included or null for none
     * @return StdClass - Data-Object
     */
    private function createShipmentClass_v3($shipmentNumber = null) {
        $shipmentOrders = $this->getShipmentOrders();

        $this->checkRequestCount($shipmentOrders, 'createShipmentClass');

        $data = new StdClass;

        $data->Version = $this->getVersionClass();

        if($shipmentNumber !== null)
            $data->shipmentNumber = (string) $shipmentNumber;

        foreach($shipmentOrders as $key => &$shipmentOrder) {
            /**
             * @var ShipmentOrder $shipmentOrder
             */
            $data->ShipmentOrder[$key] = $shipmentOrder->getShipmentOrderClass_v3();
        }

        if ( $this->getLabelResponseType() !== null)
            $data->labelResponseType = $this->getLabelResponseType();
        return $data;
    }

    /**
     * Creates the Shipment-Order-Delete Request via SOAP
     *
     * @param Object|array $data - Delete-Data
     * @return Object - DHL-Response
     */
    private function sendDeleteRequest($data) {
        return $this->getSoapClient()->deleteShipmentOrder($data);
    }

    /**
     * Alias for deleteShipmentOrder
     *
     * Deletes a Shipment
     *
     * @param string|string[] $shipmentNumbers - Shipment-Number(s) of the Shipment(s) to delete (up to 30 Numbers)
     * @return bool|Response - Response
     */
    public function deleteShipment($shipmentNumbers) {
        return $this->deleteShipmentOrder($shipmentNumbers);
    }

    /**
     * Deletes a Shipment
     *
     * @param string|string[] $shipmentNumbers - Shipment-Number(s) of the Shipment(s) to delete (up to 30 Numbers)
     * @return bool|Response - Response
     */
    public function deleteShipmentOrder($shipmentNumbers) {
        $data = $this->createDeleteClass_v3($shipmentNumbers);

        try {
            $response = $this->sendDeleteRequest($data);
        } catch(Exception $e) {
            $this->addError($e->getMessage());

            return false;
        }

        if(is_soap_fault($response)) {
            $this->addError($response->faultstring);

            return false;
        } else
            return new Response($this->getVersion(), $response);
    }

    /**
     * Creates Data-Object for Deletion
     *
     * @param string|string[] $shipmentNumbers - Shipment-Number(s) of the Shipment(s) to delete (up to 30 Numbers)
     * @return StdClass - Data-Object
     */
    private function createDeleteClass_v3($shipmentNumbers) {
        $data = new StdClass;

        $data->Version = $this->getVersionClass();

        if(is_array($shipmentNumbers)) {
            $this->checkRequestCount($shipmentNumbers, 'deleteShipmentOrder');

            foreach($shipmentNumbers as $key => &$number)
                $data->shipmentNumber[$key] = $number;
        } else
            $data->shipmentNumber = $shipmentNumbers;

        return $data;
    }

    /**
     * Requests a Label again via SOAP
     *
     * @param Object $data - Label-Data
     * @return Object - DHL-Response
     */
    private function sendGetLabelRequest($data) {
        return $this->getSoapClient()->getLabel($data);
    }

    /**
     * Alias for getLabel
     *
     * Requests a Shipment-Label again
     *
     * @param string|string[] $shipmentNumbers - Shipment-Number(s) of the Label(s) (up to 30 Numbers)
     * @return bool|Response - Response or false on error
     */
    public function getShipmentLabel($shipmentNumbers) {
        return $this->getLabel($shipmentNumbers);
    }

    /**
     * Requests a Shipment-Label again
     *
     * @param string|string[] $shipmentNumbers - Shipment-Number(s) of the Label(s) (up to 30 Numbers)
     * @return bool|Response - Response or false on error
     */
    public function getLabel($shipmentNumbers) {
        $data = $this->getLabelClass_v3($shipmentNumbers);

        try {
            $response = $this->sendGetLabelRequest($data);
        } catch(Exception $e) {
            $this->addError($e->getMessage());

            return false;
        }

        if(is_soap_fault($response)) {
            $this->addError($response->faultstring);

            return false;
        } else
            return new Response($this->getVersion(), $response);
    }

    /**
     * Creates Data-Object for Label-Request
     *
     * @param string|string[] $shipmentNumbers - Number(s) of the Shipment(s) (up to 30 Numbers)
     * @return StdClass - Data-Object
     */
    private function getLabelClass_v3($shipmentNumbers) {
        $data = new StdClass;

        $data->Version = $this->getVersionClass();

        if(is_array($shipmentNumbers)) {
            $this->checkRequestCount($shipmentNumbers, 'getLabel');

            foreach($shipmentNumbers as $key => &$number)
                $data->shipmentNumber[$key] = $number;
        } else
            $data->shipmentNumber = $shipmentNumbers;

        if($this->getLabelResponseType() !== null)
            $data->labelResponseType = $this->getLabelResponseType();

        return $data;
    }

    /**
     * Requests the Export-Document again via SOAP
     *
     * @param Object $data - Export-Doc-Data
     * @return Object - DHL-Response
     */
    private function sendGetExportDocRequest($data) {
        return $this->getSoapClient()->getExportDoc($data);
    }

    /**
     * Requests a Export-Document again
     *
     * @param string|string[] $shipmentNumbers - Shipment-Number(s) of the Export-Document(s) (up to 30 Numbers)
     * @return bool|Response - Response or false on error
     */
    public function getExportDoc($shipmentNumbers) {
        $data = $this->getExportDocClass_v3($shipmentNumbers);

        try {
            $response = $this->sendGetExportDocRequest($data);
        } catch(Exception $e) {
            $this->addError($e->getMessage());

            return false;
        }

        if(is_soap_fault($response)) {
            $this->addError($response->faultstring);

            return false;
        } else
            return new Response($this->getVersion(), $response);
    }

    /**
     * Creates Data-Object for Export-Document-Request
     *
     * @param string|string[] $shipmentNumbers - Number(s) of the Shipment(s) (up to 30 Numbers)
     * @return StdClass - Data-Object
     */
    private function getExportDocClass_v3($shipmentNumbers) {
        $data = new StdClass;

        $data->Version = $this->getVersionClass();

        if(is_array($shipmentNumbers)) {
            $this->checkRequestCount($shipmentNumbers, 'getExportDoc');

            foreach($shipmentNumbers as $key => &$number)
                $data->shipmentNumber[$key] = $number;
        } else
            $data->shipmentNumber = $shipmentNumbers;

        if($this->getLabelResponseType() !== null)
            $data->exportDocResponseType = $this->getLabelResponseType();

        return $data;
    }

    /**
     * Validates a Shipment
     *
     * @return bool|Response - Response or false on error
     */
    public function validateShipment() {
        $data = $this->createShipmentClass_v3();

        try {
            $response = $this->sendValidateShipmentRequest($data);
        } catch(Exception $e) {
            $this->addError($e->getMessage());

            return false;
        }

        if(is_soap_fault($response)) {
            $this->addError($response->faultstring);

            return false;
        } else
            return new Response($this->getVersion(), $response);
    }

    /**
     * Requests the Validation of a Shipment via SOAP
     *
     * @param Object|array $data - Shipment-Data
     * @return Object - DHL-Response
     * @throws Exception - Method doesn't exists for Version
     */
    private function sendValidateShipmentRequest($data) {
        return $this->getSoapClient()->validateShipment($data);
    }

    /**
     * Updates the Shipment-Request
     *
     * @param string $shipmentNumber - Number of the Shipment, which should be updated
     * @return bool|Response - false on error or DHL-Response Object
     */
    public function updateShipmentOrder($shipmentNumber) {
        if(is_array($shipmentNumber) || $this->countShipmentOrders() > 1) {
            $this->addError(__FUNCTION__ . ': Updating Shipments is a Single-Operation only!');

            return false;
        }

        /**
         *
        if($this->countShipmentOrders() < 1)
            $data = $this->createShipmentClass_v3_legacy($shipmentNumber);
        else
         */
        $data = $this->createShipmentClass_v3($shipmentNumber);

        $response = null;

        // Create Shipment
        try {
            $response = $this->sendUpdateRequest($data);
        } catch(Exception $e) {
            $this->addError($e->getMessage());

            return false;
        }

        if(is_soap_fault($response)) {
            $this->addError($response->faultstring);

            return false;
        } else
            return new Response($this->getVersion(), $response);
    }

    /**
     * Requests the Update of a Shipment via SOAP
     *
     * @param Object|array $data - Shipment-Data
     * @return Object - DHL-Response
     * @throws Exception - Method doesn't exists for Version
     */
    private function sendUpdateRequest($data) {
        return $this->getSoapClient()->updateShipmentOrder($data);
    }
}