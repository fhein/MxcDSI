<?php

$text = '<table frame="void" rules="rows" cellspacing="0" cellpadding="2" border="5">
<tbody>
<tr>
<td valign="middle">Stromversorgung</td>
<td>2 x 20700er oder 21700er Akkuzelle, 18650er Akkuzelle über Adapter<br> (Akkuzellen sind nicht im Lieferumfang enthalten)</td>
</tr>
<tr>
<td>Ausgabemodi</td>
<td>POWER | TEMP (SS304, SS316, SS317, Ti, Ni200) | TCR | TFR | CURVE</td>
</tr>
<tr>
<td>Prozessor</td>
<td>GX-100UTC</td>
</tr>
<tr>
<td>Ausgangsleistung</td>
<td>5 – 100 Watt (POWER) | 5 - 60 Watt (TEMP)</td>
</tr>
<tr>
<td>Ausgangsspannung</td>
<td>1 - 7,5 Volt</td>
</tr>
<tr>
<td>Temperaturbereich</td>
<td>100 °C – 300 °C | 212° - 572° F</td>
</tr>
<tr>
<td>Widerstandsbereich</td>
<td>0,1 – 3 Ohm</td>
</tr>
<tr>
<td>Ladestrom</td>
<td>DC 5V/1A</td>
</tr>
<tr>
<td>Gewindetyp</td>
<td>510</td>
</tr>
<tr>
<td>Besondere Merkmale</td>
<td>HD Farb Touch Display, update-fähig</td>
</tr>
<tr>
<td>Maße</td>
<td>88 mm x 51,4 mm x 31,1 mm</td>
</tr>
</tbody>
</table>
<p>&nbsp;</p>
<h2>Eigenschaften asMODus Viento Verdampfer</h2>
<table frame="void" rules="rows" cellspacing="0" cellpadding="2" border="5">
<tbody>
<tr>
<td>Tankvolumen</td>
<td>3,5 ml</td>
</tr>
<tr>
<td>Durchmesser</td>
<td>26,9 mm</td>
</tr>
<tr>
<td>Gewindetyp</td>
<td>510</td>
</tr>
<tr>
<td>Mundstück</td>
<td>810</td>
</tr>
<tr>
<td>Material</td>
<td>Pyrex-Glas und Edelstahl</td>
</tr>
<tr>
<td>Airflow Control</td>
<td>3 Einlässe, Einstellring an der Basis</td>
</tr>
<tr>
<td>Befüllung</td>
<td>Top Filling, Befüllung von oben</td>
</tr>
<tr>
<td>Stil</td>
<td>Direct Lung, Direkte Lungeninhalation</td>
</tr>
</tbody>
</table>
';

$matches = [];
$value = null;
if (preg_match('~(\d+) ?x.*Akkuzelle~', $text, $matches) === 1) {
    $value = $matches[1];
}
var_export($value);
