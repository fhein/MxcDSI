<?php

$str = 'Elli\'s Aromen';
$str = preg_replace('~([^ ])\'([^ ])~', '$1$2', $str);
echo $str . PHP_EOL;

$str = 'Elli\' s Aromen';
$str = preg_replace('~([^ ])\'([^ ])~', '$1$2', $str);
echo $str . PHP_EOL;
