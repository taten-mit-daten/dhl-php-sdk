<?php

namespace tatenmitdaten\dhl;

/**
 * Author: Jens Braeuner [info@taten-mit-daten.de], Idee: Peter Dragicevic [peter@petschko.org]
 * Authors-Website: https:// www.taten-mit-daten.de http://petschko.org/
 * Date: 19.01.2021
 * Version: 0.1.0
 *
 * Notes: Contains the Sender Class
 */

use stdClass;

/**
 * Class Sender
 *
 * @package tatenmitdaten\DHL
 */
class Sender extends SendPerson {
    /**
     * Returns a Class for the DHL-SendPerson
     *
     * @return StdClass - DHL-SendPerson-class
     */
    public function getClass_v3() {
        $class = new StdClass;

        // Set Name
        $class->Name = new StdClass;
        $class->Name->name1 = $this->getName();
        if($this->getName2() !== null)
            $class->Name->name2 = $this->getName2();
        if($this->getName3() !== null)
            $class->Name->name3 = $this->getName3();

        // Address
        $class->Address = new StdClass;
        $class->Address->streetName = $this->getStreetName();
        $class->Address->streetNumber = $this->getStreetNumber();
        if($this->getAddressAddition() !== null)
            $class->Address->addressAddition = $this->getAddressAddition();
        if($this->getDispatchingInfo() !== null)
            $class->Address->dispatchingInformation = $this->getDispatchingInfo();
        $class->Address->zip = $this->getZip();
        $class->Address->city = $this->getLocation();

        // Origin
        if($this->getCountryISOCode() !== null) {
            $class->Address->Origin = new StdClass;
            if($this->getCountry() !== null)
                $class->Address->Origin->country = $this->getCountry();
            $class->Address->Origin->countryISOCode = $this->getCountryISOCode();
            if($this->getState() !== null)
                $class->Address->Origin->state = $this->getState();
        }

        // Communication
        $class->Communication = new StdClass;
        if($this->getPhone() !== null)
            $class->Communication->phone = $this->getPhone();
        if($this->getEmail() !== null)
            $class->Communication->email = $this->getEmail();
        if($this->getContactPerson() !== null)
            $class->Communication->contactPerson = $this->getContactPerson();

        return $class;
    }
}