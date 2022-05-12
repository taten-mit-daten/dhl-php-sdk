<?php

namespace tatenmitdaten\dhl;

/**
 * Author: Jens Braeuner [info@taten-mit-daten.de], Idee: Peter Dragicevic [peter@petschko.org]
 * Authors-Website: https:// www.taten-mit-daten.de http://petschko.org/
 * Date: 19.01.2021
 * Version: 0.1.0
 *
 * Notes: Contains the DHL-Address Class
 */

/**
 * Class Address
 *
 * @package tatenmitdaten\DHL
 */
abstract class Address_rec {
    /**
     * Contains the Street Name (without number)
     *
     * Min-Len: -
     * Max-Len: 35
     *
     * @var string $streetName - Street Name (without number)
     */
    private $streetName = '';


    /**
     * Contains other Info about the Address like if its hard to find or where it is exactly located
     *
     * Note: Optional
     * Min-Len: -
     * Max-Len: 35
     *
     * @var string|null $addressAddition - Address-Addition | null for none
     */
    private $addressAddition = null;

    /**
     * Contains Optional Dispatching Info
     *
     * Note: Optional
     * Min-Len: -
     * Max-Len: 35
     *
     * @var string|null $dispatchingInfo - Optional Dispatching Info | null for none
     */
    private $dispatchingInfo = null;

    /**
     * Contains the ZIP-Code
     *
     * Min-Len: -
     * Max-Len: 10
     *
     * @var string $zip - ZIP-Code
     */
    private $zip = '';

    /**
     * Contains the City/Location
     *
     * Min-Len: -
     * Max-Len: 35
     *
     * @var string $location - Location
     */
    private $location = '';

    /**
     * Contains the Country
     *
     * Note: Optional
     * Min-Len: -
     * Max-Len: 30
     *
     * @var string|null $country - Country | null for none
     */
    private $country = null;

    /**
     * Contains the country ISO-Code
     *
     * Note: Optional
     * Min-Len: 2
     * Max-Len: 2
     *
     * @var string|null $countryISOCode - Country-ISO-Code | null for none
     */
    private $countryISOCode = null;

    /**
     * Contains the Name of the State (Geo-Location)
     *
     * Note: Optional
     * Min-Len: -
     * Max-Len: 30
     *
     * @var string|null $state - Name of the State (Geo-Location) | null for none
     */
    private $state = null;

    /**
     * Address constructor.
     */
    public function __construct() {
        // VOID
    }

    /**
     * Clears the Memory
     */
    public function __destruct() {
        unset($this->streetName);
        unset($this->addressAddition);
        unset($this->dispatchingInfo);
        unset($this->zip);
        unset($this->location);
        unset($this->country);
        unset($this->countryISOCode);
        unset($this->state);
    }

    /**
     * Get the Street name
     *
     * @return string - Street name
     */
    public function getStreetName() {
        return $this->streetName;
    }

    /**
     * Set the Street name
     *
     * @param string $streetName - Street name
     */
    public function setStreetName($streetName) {
        $this->streetName = $streetName;
    }

    /**
     * Get the Address addition
     *
     * @return null|string - Address addition or null for none
     */
    public function getAddressAddition() {
        return $this->addressAddition;
    }

    /**
     * Set the Address addition
     *
     * @param null|string $addressAddition - Address addition or null for none
     */
    public function setAddressAddition($addressAddition) {
        $this->addressAddition = $addressAddition;
    }

    /**
     * Get the Dispatching-Info
     *
     * @return null|string - Dispatching-Info or null for none
     */
    public function getDispatchingInfo() {
        return $this->dispatchingInfo;
    }

    /**
     * Set the Dispatching-Info
     *
     * @param null|string $dispatchingInfo - Dispatching-Info or null for none
     */
    public function setDispatchingInfo($dispatchingInfo) {
        $this->dispatchingInfo = $dispatchingInfo;
    }

    /**
     * Get the ZIP
     *
     * @return string - ZIP
     */
    public function getZip() {
        return $this->zip;
    }

    /**
     * Set the ZIP
     *
     * @param string $zip - ZIP
     */
    public function setZip($zip) {
        $this->zip = $zip;
    }

    /**
     * Get the Location
     *
     * @return string - Location
     */
    public function getLocation() {
        return $this->location;
    }

    /**
     * Set the Location
     *
     * @param string $location - Location
     */
    public function setLocation($location) {
        $this->location = $location;
    }

    /**
     * Alias for $this->getLocation
     *
     * @return string - Location
     */
    public function getCity() {
        return $this->location;
    }

    /**
     * Alias for $this->setLocation
     *
     * @param string $city - Location
     */
    public function setCity($city) {
        $this->location = $city;
    }

    /**
     * Get the Country
     *
     * @return string|null - Country or null for none
     */
    public function getCountry() {
        return $this->country;
    }

    /**
     * Set the Country
     *
     * @param string|null $country - Country or null for none
     */
    public final function setCountry($country) {
        if($country !== null)
            $this->country = mb_strtolower($country);
        else
            $this->country = null;
    }

    /**
     * Get the Country-ISO-Code
     *
     * @return string|null - Country-ISO-Code or null for none
     */
    public function getCountryISOCode() {
        return $this->countryISOCode;
    }

    /**
     * Set the Country-ISO-Code
     *
     * @param string|null $countryISOCode - Country-ISO-Code or null for none
     */
    public final function setCountryISOCode($countryISOCode) {
        if($countryISOCode !== null)
            $this->countryISOCode = mb_strtoupper($countryISOCode);
        else
            $this->countryISOCode = null;
    }

    /**
     * Get the State (Geo-Location)
     *
     * @return null|string - State (Geo-Location) or null for none
     */
    public function getState() {
        return $this->state;
    }

    /**
     * Set the State (Geo-Location)
     *
     * @param null|string $state - State (Geo-Location) or null for none
     */
    public function setState($state) {
        $this->state = $state;
    }


}