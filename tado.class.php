<?php

    // created by Dennis Bor (dennis@dennisbor.com)
    // download at https://github.com/DennisBorCom/tado

    // to obtain secret, visit https://app.tado.com/env.js

    class Tado {

        private $username;
        private $password; 
        private $clientSecret;

        private $accessToken;
        private $user;
        private $home;
        private $zones;

        function __construct(string $username, string $password, string $clientSecret) {
            $this->username = $username;
            $this->password = $password;
            $this->clientSecret = $clientSecret;
            $this->getAccessToken();
            $this->getUser();
            $this->getHomeInformation();
            $this->getZoneInformation();
        }

        public function getWeather() : object {
            $cURLResource = curl_init();
            curl_setopt($cURLResource, CURLOPT_URL, "https://my.tado.com/api/v2/homes/" . $this->user->homeId . "/weather");
            curl_setopt($cURLResource, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $this->accessToken));
            curl_setopt($cURLResource, CURLOPT_RETURNTRANSFER, 1);
            if (($output = curl_exec($cURLResource)) === false) {
                return false;
            }
            curl_close($cURLResource);   
            return json_decode($output);
        }

        public function getHome() : object {
            return $this->home;
        }

        public function isHome() : bool {
            $cURLResource = curl_init();
            curl_setopt($cURLResource, CURLOPT_URL, "https://my.tado.com/api/v2/homes/" . $this->user->homeId . "/state");
            curl_setopt($cURLResource, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $this->accessToken));
            curl_setopt($cURLResource, CURLOPT_RETURNTRANSFER, 1);
            if (($output = curl_exec($cURLResource)) === false) {
                return false;
            }
            curl_close($cURLResource);   
            $json = json_decode($output);
            if ($json->presence == "HOME") {
                return true;
            } else {
                return false;
            }
        }

        public function endManualHotWater(object $zone) : void {
            $this->endManualHeating($zone);
        }

        public function setHotWaterFahrenheit(object $zone, float $temperature) : bool {
            return $this->hotWaterOnCelcius($zone, $this->fahrenheitToCelcius($temperature));
        }

        public function getHistoricalData(object $zone, string $dateISO8601) : object {
            if (!isset($zone->id)) {
                throw new Exception('Tado: Invalid zone object');
            }
            $cURLResource = curl_init();
            curl_setopt($cURLResource, CURLOPT_URL, "https://my.tado.com/api/v2/homes/" . $this->user->homeId . "/zones/" . strval($zone->id) . "/dayReport?date=" . $dateISO8601);
            curl_setopt($cURLResource, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $this->accessToken));
            curl_setopt($cURLResource, CURLOPT_RETURNTRANSFER, 1);
            if (($output = curl_exec($cURLResource)) === false) {
                return false;
            }
            curl_close($cURLResource);   
            return json_decode($output);
        }

        public function setHotWaterCelcius(object $zone, float $temperature) : bool {
            if (!isset($zone->type)) {
                throw new Exception('Tado: Invalid zone object');
            }
            if ($zone->type != 'HOT_WATER') {
                throw new Exception('Tado: Non hot water zone');
            }
            $json = array();
            $json['setting']['type'] = 'HOT_WATER';
            $json['setting']['power'] = 'ON';
            $json['setting']['temperature']['celcius'] = $temperature;
            $json['setting']['temperature']['fahrenheit'] = $this->celciusToFahrenheit($temperature);
            $json['termination']['type'] = 'MANUAL';
            $data = json_encode($json, JSON_NUMERIC_CHECK);
            $cURLResource = curl_init();
            curl_setopt($cURLResource, CURLOPT_URL, "https://my.tado.com/api/v2/homes/" . $this->user->homeId . "/zones/" . strval($zone->id) . "/overlay");
            curl_setopt($cURLResource, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer " . $this->accessToken,
                "Content-Type: application/json;charset=utf-8"
            ));
            curl_setopt($cURLResource, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($cURLResource, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($cURLResource, CURLOPT_POSTFIELDS, $data);
            if (($output = curl_exec($cURLResource)) === false) {
                return false;
            }
            curl_close($cURLResource);   
            $json = json_decode($output);
            if (isset($json->errors)) {
                echo $output;
                return false;
            }
            return true;
        }

        public function getHeatingZones() : array {
            $filteredZones = array();
            foreach ($this->zones as $zone) {
                if ($zone->type == 'HEATING') {
                    array_push($filteredZones, $zone);
                }
            }
            return $filteredZones;
        }

        public function getHeatingPower(object $zone) : float {
            if (!isset($zone->state->activityDataPoints->heatingPower->percentage)) {
                return 0;
            }
            return $zone->state->activityDataPoints->heatingPower->percentage;
        }

        public function getOpenWindow(?object $zone) : bool {
            if (!isset($zone->state->openWindow)) {
                return false;
            }
            return $zone->state->openWindow;
        }

        public function getZones() : array {
            return $this->zones;
        }

        private function celciusToFahrenheit(float $valueInDegreesCelcius) : float {
            return ($valueInDegreesCelcius * 1.8) + 32;
        }

        private function fahrenheitToCelcius(float $valueInDegreesFahrenheit) : float {
            return ($valueInDegreesFahrenheit - 32) / 1.8;
        }

        public function setTemperatureFahrenheit(?object $zone, float $temperature) : bool {
            return $this->setTemperatureCelcius($zone, $this->fahrenheitToCelcius($temperature));
        }

        public function setTemperatureCelcius(object $zone, float $temperature) : bool {
            if (!isset($zone->id)) {
                return false;
            }
            $json = array();
            $json['setting']['type'] = 'HEATING';
            $json['setting']['power'] = 'ON';
            $json['setting']['temperature']['celcius'] = $temperature;
            $json['setting']['temperature']['fahrenheit'] = $this->celciusToFahrenheit($temperature);
            $json['termination']['type'] = 'MANUAL';
            $data = json_encode($json, JSON_NUMERIC_CHECK);
            $cURLResource = curl_init();
            curl_setopt($cURLResource, CURLOPT_URL, "https://my.tado.com/api/v2/homes/" . $this->user->homeId . "/zones/" . strval($zone->id) . "/overlay");
            curl_setopt($cURLResource, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer " . $this->accessToken,
                "Content-Type: application/json;charset=utf-8"
            ));
            curl_setopt($cURLResource, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($cURLResource, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($cURLResource, CURLOPT_POSTFIELDS, $data);
            if (($output = curl_exec($cURLResource)) === false) {
                return false;
            }
            curl_close($cURLResource);   
            $json = json_decode($output);
            if (isset($json->errors)) {
                return false;
            }
            return true;
        }

        public function endManualHeating(object $zone) : void {
            if (!isset($zone->id)) {
                return;
            }
            $cURLResource = curl_init();
            curl_setopt($cURLResource, CURLOPT_URL, "https://my.tado.com/api/v2/homes/" . $this->user->homeId . "/zones/" . strval($zone->id) . "/overlay");
            curl_setopt($cURLResource, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $this->accessToken));
            curl_setopt($cURLResource, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_exec($cURLResource);
            curl_close($cURLResource);   
        }

        public function getTemperatureCelcius(?object $zone) : float {
            if (!isset($zone->state->sensorDataPoints->insideTemperature->celsius)) {
                throw new Exception('Tado: Invalid zone object');
            }
            return $zone->state->sensorDataPoints->insideTemperature->celsius;
        }

        public function getTemperatureFahrenheit(?object $zone) : float {
            if (!isset($zone->state->sensorDataPoints->insideTemperature->fahrenheit)) {
                throw new Exception('Tado: Invalid zone object');
            }
            return $zone->state->sensorDataPoints->insideTemperature->fahrenheit; 
        }

        public function getHumidity(?object $zone) : float {
            if (!isset($zone->state->sensorDataPoints->humidity->percentage)) {
                throw new Exception('Tado: Invalid zone object');
            }
            return $zone->state->sensorDataPoints->humidity->percentage;
        }

        public function getZoneByName(string $name) : object {
            foreach ($this->zones as $zone) {
                if (strtoupper($zone->name) == strtoupper($name)) {
                    return $zone;
                }
            }
            throw new Exception("Tado: Invalid zone name '" . $name . "'");
        }

        private function getAccessToken() : bool {
            $cURLResource = curl_init();
            curl_setopt($cURLResource, CURLOPT_URL, "https://auth.tado.com/oauth/token");
            curl_setopt($cURLResource, CURLOPT_POST, 1);
            curl_setopt($cURLResource, CURLOPT_POSTFIELDS, "client_id=tado-web-app&grant_type=password&scope=home.user&username=" . $this->username . "&password=" . $this->password . "&client_secret=" . $this->clientSecret);
            curl_setopt($cURLResource, CURLOPT_RETURNTRANSFER, 1);
            if (($output = curl_exec($cURLResource)) === false) {
                return false;
            }
            curl_close($cURLResource);     
            $jsonData = json_decode($output);
            $this->accessToken = $jsonData->access_token;
            return true;
        }

        private function getUser() : bool {  
            $cURLResource = curl_init();
            curl_setopt($cURLResource, CURLOPT_URL, "https://my.tado.com/api/v1/me");
            curl_setopt($cURLResource, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $this->accessToken));
            curl_setopt($cURLResource, CURLOPT_RETURNTRANSFER, 1);
            if (($output = curl_exec($cURLResource)) === false) {
                return false;
            }
            curl_close($cURLResource);   
            $this->user = json_decode($output);
            return true;
        }

        private function getHomeInformation() : bool {
            $cURLResource = curl_init();
            curl_setopt($cURLResource, CURLOPT_URL, "https://my.tado.com/api/v2/homes/" . $this->user->homeId);
            curl_setopt($cURLResource, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $this->accessToken));
            curl_setopt($cURLResource, CURLOPT_RETURNTRANSFER, 1);
            if (($output = curl_exec($cURLResource)) === false) {
                return false;
            }
            curl_close($cURLResource);   
            $this->home = json_decode($output);
            return true;
        }

        private function getZoneState(int $zoneId) : object {
            $cURLResource = curl_init();
            curl_setopt($cURLResource, CURLOPT_URL, "https://my.tado.com/api/v2/homes/" . $this->user->homeId . "/zones/" . strval($zoneId) . "/state");
            curl_setopt($cURLResource, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $this->accessToken));
            curl_setopt($cURLResource, CURLOPT_RETURNTRANSFER, 1);
            if (($output = curl_exec($cURLResource)) === false) {
                return false;
            }
            curl_close($cURLResource);   
            return json_decode($output);
        }

        private function getZoneInformation() : bool {
            $cURLResource = curl_init();
            curl_setopt($cURLResource, CURLOPT_URL, "https://my.tado.com/api/v2/homes/" . $this->user->homeId . "/zones");
            curl_setopt($cURLResource, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $this->accessToken));
            curl_setopt($cURLResource, CURLOPT_RETURNTRANSFER, 1);
            if (($output = curl_exec($cURLResource)) === false) {
                return false;
            }
            curl_close($cURLResource);   
            $zones =  json_decode($output);
            foreach ($zones as $zone) {
                $zone->state = $this->getZoneState($zone->id);
            }
            $this->zones = $zones;
            return true;
        }
    }
?>