<?php

/**
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

namespace NRFramework\Assignments;

defined('_JEXEC') or die;

use NRFramework\Assignment;

/**
 *  IP addresses sample
 *
 *  Greece:  94.67.238.3
 *  Belgium: 37.62.255.255
 *  USA:     72.229.28.185
 */
class GeoIP extends Assignment
{
    /**
     *  GeoIP Class
     *
     *  @var  class
     */
    private $geo;

    /**
     *  Class constructor
     *
     *  @param  object  $assignment
     *  @param  object  $request     
     *  @param  object  $date        
     */
    public function __construct($assignment, $request = null, $date = null)
    {
        $this->loadGeo();

        if (!$this->geo)
        {
            return false;
        }

        parent::__construct($assignment, $request, $date);

        // convert a comma/newline separated selection string into an array
        if (!is_array($this->selection))
        {
            $this->selection = $this->splitKeywords($this->selection);
        }
        else
        {
            $this->selection = $this->splitKeywords($this->selection[0]);
        }
    }

    /**
     *  Load GeoIP Classes
     *
     *  @return  void
     */
    private function loadGeo()
    {
        if (!class_exists('TGeoIP'))
        {
            $path = JPATH_PLUGINS . '/system/tgeoip';

            if (@file_exists($path . '/helper/tgeoip.php'))
            {
                if (@include_once($path . '/vendor/autoload.php'))
                {
                    @include_once $path . '/helper/tgeoip.php';
                }
            }
        }

        $this->geo = new \TGeoIP();
    }

    /**
     *  Pass Countries
     */
    public function passCountries()
    {
        // try to convert country names to codes
        $this->selection = array_map(function($c) {
            if (strlen($c) > 2)
            {
                $c = \NRFramework\Countries::getCode($c);
            }
            return $c;
        }, $this->selection);

        return $this->passSimple($this->geo->getCountryCode(), $this->selection);
    }

    /**
     *  Pass Continents
     */
    public function passContinents()
    {
        // try to convert continent names to codes
        $this->selection = array_map(function($c) {
            if (strlen($c) > 2)
            {
                $c = \NRFramework\Continents::getCode($c);
            }
            return $c;
        }, $this->selection);

        return $this->passSimple($this->geo->getContinentCode(), $this->selection);
    }

    /**
     *  Pass Cities
     */
    public function passCities()
    {
        return $this->passSimple($this->geo->getCity(), $this->selection);
    }

    /**
     *  Pass Regions
     *
     *  Input($this->selection) should be a comma/newline separated list of ISO 3611 country-region codes, i.e.GR-I (Greece - Attica)
     */
    public function passRegions()
    {
        $countryRegionCode = $this->geo->getCountryCode() . '-' . $this->geo->getRegionCode();
        return $this->passSimple($countryRegionCode, $this->selection);
    }
}
