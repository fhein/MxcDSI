<?php

$text = 'Isch bien ain klainer König';

$corrections = [ 'Isch' => 'Ich', 'bien' => 'bin', 'ain' => 'ein', 'klainer' => 'kleiner'];

$separators = '([\;\,\. \?\!\-\>\<])';
$patternMappings = [
    'start' => [
        'search' => function($value) use ($separators) { return '~^' . $value . $separators . '~'; },
        'replace' => function($value) { return $value . '$1'; },
    ],
    'inline' => [
        'search' => function($value) use ($separators) { return '~' . $separators .  $value . $separators . '~'; },
        'replace' => function($value) { return '$1' . $value . '$2'; },
    ],
    'end'   => [
        'search' => function($value) use ($separators) { return '~' . $separators . $value . '$~'; },
        'replace' => function($value) { return '$1' . $value; },
    ],
];

$search = array_keys($corrections);
$replace = array_values($corrections);

foreach ($patternMappings as $position) {
    $currentSearch = array_map($position['search'], $search);
    $currentReplace = array_map($position['replace'], $replace);
    $text = preg_replace($currentSearch, $currentReplace, $text);
};

var_export($text);