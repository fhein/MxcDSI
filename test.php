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


function getCurrentVatPercentage()
{
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

    $currentTime = new DateTimeImmutable();
    foreach ($vatConfig as $vatSetting) {
        $start = DateTimeImmutable::createFromFormat('d.m.Y H:i:s', $vatSetting['start']);
        if ($start < $currentTime) {
            $vat = $vatSetting['vat'];
            $validSince = $vatSetting['start'];
        }
    }
    return $vat;
}


function getNetDiscount(float $grossRetailPrice) {

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

    if ($discounts === null) return 0;
    foreach ($discounts as $discount) {
        if ($grossRetailPrice >= $discount['price']) {
            $result = $discount['discount'];
        }
    }
    return $result;
}

function println (?string  $text = null){
    echo $text . "\n";
}

function correctPrice(float $netPurchasePrice, float $grossRetailPrice, array $priceConfig)
{
    $log = [];
    $vatFactor = (1 + getCurrentVatPercentage() / 100);
    $netRetailPrice = $grossRetailPrice / $vatFactor;

    $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
    // println('Initial Margin: ' . $margin);

    // Ist die minimale prozentuale Marge nicht erreicht, wird der Netto-Verkaufspreis erhöht
    // und die Marge neu berechnet
    $minMarginPercent = $priceConfig['margin_min_percent'];
    if ($minMarginPercent !== null && round($margin,5) < $minMarginPercent) {
        $netRetailPrice = $netPurchasePrice / (1 - ($minMarginPercent / 100));
        $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
        $log[] = 'Minimum margin adjusted to ' . $minMarginPercent . '%.';
    };

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
    $marginAbsolute = round($netRetailPrice - $netPurchasePrice, 5);
    if ($limit !== null && $marginAbsolute > $limit) {
        $netRetailPrice = $netPurchasePrice + $limit;
        $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
        $log[] = 'Maximum absolute margin adjusted to ' . $limit . ' EUR (from ' . (round($marginAbsolute, 2)) . ' EUR). New margin: ' . round($margin, 2) . '.';
    }
    //println ('Margin after rules: ' . $margin);
    $newGrossRetailPrice = $netRetailPrice * $vatFactor;
    //println('New gross retail price: ' . $newGrossRetailPrice);
    $discount = getNetDiscount($newGrossRetailPrice) / 100;
    // println("Discount: " . $discount);
    // $netRetailPrice *= (1 - $discount / 100);

    if ($discount != 0) {
        $discountValue = $netRetailPrice * $discount;
       // println("Discount value: " . $discountValue);
        $discountedNetRetailPrice = $netRetailPrice - $discountValue;

        $netRevenue = $discountedNetRetailPrice - $netPurchasePrice;
        $dropShip = 6.12;
        // Dropship Versandkosten
        $netRevenue -= $dropShip;

        if ($netRevenue < 6.0) {
            $minRevenue = $discountValue + (6.0 + $dropShip) / 0.925;
            $netRetailPrice = $netPurchasePrice + $minRevenue;
            $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
            $log[] = 'Minimum revenue adjusted to ' . round($minRevenue,2) . '. New margin: ' . round($margin, 2). '.';
        }
    }

    // Ist die minimale absolute Marge in EUR nicht erreicht, wird der Netto-Verkaufspreis erhöht
    // und die Marge neu berechnet
    $limit = $priceConfig['margin_min_abs'];
    if ($limit !== null && floatval($netRetailPrice - $netPurchasePrice) < $limit) {
        $netRetailPrice = $netPurchasePrice + $limit;
        $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
        $log[] = 'Minimum absolute margin adjusted to ' . $limit . ' EUR. New margin: ' . round($margin, 2). '.';
    }

    $newGrossRetailPrice = $netRetailPrice * $vatFactor;

    // Rundung des Kundenverkaufspreises auf 5 Cent
    $newGrossRetailPrice = round($newGrossRetailPrice / 0.05) * 0.05;

    // Hier könnten noch weitere psychogische Preisverschönerungen durchgeführt werden

    return [ $newGrossRetailPrice, $log ];
}

function getPaddedRoundedValue(float $value)
{
    return str_pad(sprintf('%.2f', round($value,2)), 15, ' ', STR_PAD_LEFT);
}

println();
println();
$h0 = str_pad('Brutto EK', 15, ' ', STR_PAD_LEFT);
$h1 = str_pad('Brutto VK alt', 15, ' ', STR_PAD_LEFT);
$h2 = str_pad('Brutto VK neu', 15, ' ', STR_PAD_LEFT);
$h3 = str_pad('Rabatt VK', 15, ' ', STR_PAD_LEFT);
$h4 = str_pad('Netto Marge %', 15, ' ', STR_PAD_LEFT);
$h5 = str_pad('Abs Marge', 15, ' ', STR_PAD_LEFT);
$h6 = '    ' . 'rules applied';
println ($h0. $h1 . $h2 . $h3 . $h4 . $h5. $h6);

$priceConfig = array(
    'price' => null,
    'margin_min_percent' => 27,
    'margin_min_abs' => 0.85,
    'margin_max_percent' => 40,
    'margin_max_abs' => 16,
);

$vatFactor = 1 + getCurrentVatPercentage() / 100;


$grossRetailPriceStart = 70;
$netPurchasePriceFactor = 0.5;
$step = 1;
//$margin_abs = 10;
$end = 130;

$grossRetailPrice = $grossRetailPriceStart;
$netRetailPrice = $grossRetailPrice / $vatFactor;
$netPurchasePrice = $netRetailPrice * ( 1 - $netPurchasePriceFactor);

$fraction = round(20.05 - floor(20.05), 2);
println( $fraction);
println (strval(floatval('1.21')));

die();



while (true) {
    $log = [];
    list($newPrice, $log) = correctPrice($netPurchasePrice, $grossRetailPrice, $priceConfig);
    $discountFactor = 1 - getNetDiscount($newPrice) / 100;

    $nrp = $newPrice / $vatFactor * $discountFactor;
    $margin = ($nrp - $netPurchasePrice) / $nrp * 100;
    $margin_abs = $nrp - $netPurchasePrice;
    $margin_abs = getPaddedRoundedValue($margin_abs);

    $margin = getPaddedRoundedValue($margin);


    $gross = getPaddedRoundedValue($grossRetailPrice);
    $new = getPaddedRoundedValue($newPrice);
    $dn = getPaddedRoundedValue($newPrice * $discountFactor);
    $grossPurchasePrice = $netPurchasePrice * $vatFactor;
    $gp = getPaddedRoundedValue($grossPurchasePrice);
    if (empty($log)) $log = '    No changes.'; else
    $log = '    ' . implode(' ', $log);

    println($gp . $gross . $new . $dn . $margin . $margin_abs . $log);

    $grossRetailPrice += $step;
    if ($grossRetailPrice > $end) break;
    $netRetailPrice = $grossRetailPrice / $vatFactor;
    $netPurchasePrice = $netRetailPrice * ( 1 - $netPurchasePriceFactor);
}

die();

list($newPrice, $log) = correctPrice($netPurchasePrice, $grossRetailPrice, $priceConfig);

println();
println();
println("Results:");
println();

println('New price: ' . $newPrice);
$newPrice = round( $newPrice / 0.05) * 0.05;
println('Beautified new price: ' . $newPrice);

println('Old price: ' . round($grossRetailPrice, 2));
println('New Price: ' . round($newPrice, 2));
$discountFactor  = 1 - getNetDiscount($grossRetailPrice / $vatFactor) / 100;
$dropShipCost = 6.12;

if ($grossRetailPrice > 75) {
    println('Discounted new price: ' . ($newPrice * $discountFactor));
    println('Margin abs on discounted price: ' . round( $newPrice / $vatFactor * $discountFactor - $netPurchasePrice, 2 ));
    println('Net Revenue on discounted price: ' . round($newPrice / $vatFactor * $discountFactor - $netPurchasePrice - $dropShipCost, 2));
} else {
    println('Margin abs on discounted price: ' . round($newPrice / $vatFactor - $netPurchasePrice, 2));
    println('Net Revenue on discounted price: ' . round($newPrice / $vatFactor - $netPurchasePrice - $dropShipCost, 2));
}

println(var_export($log, true));



