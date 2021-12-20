Simple PHP class to control Tado.

To use this class, you need to obtain a secret key. visit https://app.tado.com/env.js

Usage:

<code>
$tado = new Tado('email', 'password', 'secret');
</code>

<br/>To find out if home mode is on:

<code>
$tado->isHome();
</code>

<br/>To get home information:

<code>
$tado->getHome();
</code>

<br/>To get all zones (including states):

<code>
$tado->getZones();
</code>

<br/>To get a single zone:

<code>
$zone = $tado->getZoneByName('Living Room');
</code>

<br/>To get zones by type:

<code>$zones = $tado->getHeatingZones();</code><br/>
<code>$zones = $tado->getHotWaterZones();</code><br/>

<br/>To get zone details:

<code>$tado->getHumidity($zone);</code><br/>
<code>$tado->getTemperatureCelcius($zone);</code><br/>
<code>$tado->getTemperatureFahrenheit($zone);</code><br/>
<code>$tado->getOpenWindow($zone);</code><br/>
<code>$tado->getHeatingPower($zone);</code><br/>

<br/>To set zone temperature

<code>$tado->setTemperatureCelcius($zone, 20);</code><br/>
<code>$tado->setTemperatureFahrenheit($zone, 68);</code><br/>

<br/>To end manual temperature settings (continue program)

<code>$tado->endManualHeating($zone);</code>

<br/>To set hot water temperature

<code>$tado->setHotWaterCelcius($zone, 20);</code><br/>
<code>$tado->setHotWaterFahrenheit($zone, 68);</code><br/>

<br/>To end manual hot water settings (continue program)

<code>$tado->endManualHotWater($zone);</code><br/>

<br/>To get current weather information

<code>$tado->getWeather();</code><br/>

<br/>To get historical data (use ISO8601 date format)

<code>$tado->getHistoricalData($zone, '2020-12-30');</code>
