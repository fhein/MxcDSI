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
//var_export($value);

$vatConfig = [
    [
        'start' => '01.01.2020 00:00:00',
        'vat'   => 19.0,
    ],
    [
        'start' => '01.07.2020 00:00:00',
        'vat' => 16.0,
    ],
    [
        'start' => '01.01.2021 00:00:00',
        'vat' => 19.0,
    ]
];

$currentTime = DateTimeImmutable::createFromFormat('d.m.Y H:i:s', '02.07.2020 00:00:00');

foreach ($vatConfig as $vatSetting) {
    $start = DateTimeImmutable::createFromFormat('d.m.Y H:i:s', $vatSetting['start']);
    if ($start < $currentTime) {
        $vat = $vatSetting['vat'];
        echo $vatSetting['start'] . ': ' . $vat . "\n";
        $validSince = $vatSetting['start'];
    }
}
echo 'Current tax valid since ' . $validSince . ': ' . $vat . "\n";
echo "\n";

$priceConfig = array(
    'price' => null,
    'margin_min_percent' => 25,
    'margin_min_abs' => 1.85,
    'margin_max_percent' => 40,
    'margin_max_abs' => 15,
);

function getNetDiscount(float $netRetailPrice) {

    $discounts = [
        [
            'price' => 0,
            'discount' => 0,
        ],
        [
            'price' => 75,
            'discount' => 7.5
        ]
    ];
    $vatFactor = 0.19;
    $grossRetailPrice = $netRetailPrice / ( 1 + $vatFactor);
    echo $grossRetailPrice . "\n";

    if ($discounts === null) return 0;
    foreach ($discounts as $discount) {
        if ($grossRetailPrice > $discount['price']) {
            $result = $discount['discount'];
            echo 'Discount set to ' . $result . "\n";
        }
    }
    return $result;
}

function correctPrice(float $netPurchasePrice, float $grossRetailPrice, array $priceConfig)
{
    $log = [];
    $netRetailPrice = $grossRetailPrice / ( 1 + 0.19);
    $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;

    // Ist die minimale prozentuale Marge nicht erreicht, wird der Netto-Verkaufspreis erhöht
    // und die Marge neu berechnet
    $minMarginPercent = $priceConfig['margin_min_percent'];
    if ($minMarginPercent !== null && $margin < $minMarginPercent) {
        $netRetailPrice = $netPurchasePrice / (1 - ($minMarginPercent / 100));
        $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
        $log[] = 'Minimum margin adjusted to ' . $minMarginPercent . '%.';
    };

    // Ist die minimale absolute Marge in EUR nicht erreicht, wird der Netto-Verkaufspreis erhöht
    // und die Marge neu berechnet
    $limit = $priceConfig['margin_min_abs'];
    if ($limit !== null && $netRetailPrice - $netPurchasePrice < $limit) {
        $netRetailPrice = $netPurchasePrice + $limit;
        $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
        $log[] = 'Minimum absolute margin adjusted to ' . $limit . ' EUR.';
    }

    // Ist die maximale prozentuale Marge überschritten, wird der Netto-Verkaufspreis gesenkt
    $maxMarginPercent = $priceConfig['margin_max_percent'];
    if ($maxMarginPercent !== null && $margin > $maxMarginPercent) {
        $netRetailPrice = $netPurchasePrice / (1 - ($maxMarginPercent / 100));
        $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
        $log[] = 'Maximim margin adjusted to ' . $maxMarginPercent . '%.';
    }

    // Ist die maximale absolute Marge in EUR (dennoch) überschritten, wird der Netto-Verkaufspreis gesenkt
    // und die Marge neu berechnet
    $limit = $priceConfig['margin_max_abs'];
    if ($limit !== null && $netRetailPrice - $netPurchasePrice > $limit) {
        $netRetailPrice = $netPurchasePrice + $limit;
        $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
        $log[] = 'Maximum absolute margin adjusted to ' . $limit . ' EUR.';
    }

    // Stelle einen mininalen Ertrag von 6 EUR sicher, auch dann, wenn das Produkt rabattiert wird, weil es
    // mehr als 75 EUR kostet
    $discount = getNetDiscount($netRetailPrice);
    echo 'Discount: ' . $discount . "\n";

    if ($discount != 0) {
        $discountValue = $netRetailPrice * $discount / 100;
        $discountedNetRetailPrice = $netRetailPrice - $discountValue;

        $netRevenue = $discountedNetRetailPrice - $netPurchasePrice;
        // Dropship Versandkosten
        $netRevenue -= 6.12;

        if ($netRevenue < 6.0) {
            $netRetailPrice = $netPurchasePrice + $discountValue + 6.0 / 0.925 + 6.12;
            $log[] = 'Minimum revenue adjusted';
        }
    }

    $vatFactor = 0.19;
    $newGrossRetailPrice = $netRetailPrice * (1 + $vatFactor);

    // Rundung des Kundenverkaufspreises auf 5 Cent
    //$newGrossRetailPrice = round($newGrossRetailPrice / 0.05) * 0.05;

    // Hier könnten noch weitere psychogische Preisverschönerungen durchgeführt werden

    return [ $newGrossRetailPrice, $log ];
}

$netPurchasePrice = 92.90;
$grossRetailPrice = 179.90;

list($newPrice, $log) = correctPrice($netPurchasePrice, $grossRetailPrice, $priceConfig);

echo $newPrice . "\n";
var_export($log);

echo 'Margin abs: ' . (($newPrice / 1.19) - $netPurchasePrice). "\n";

echo 'Net Revenue: ' . (($newPrice / 1.19 * 0.925) - $netPurchasePrice - 6.12) . "\n";



