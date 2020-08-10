<?php

namespace MxcDropshipIntegrator\Mapping\Pullback;

use MxcCommons\Plugin\Service\ClassConfigAwareInterface;
use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;

class SpellChecker implements LoggerAwareInterface, ModelManagerAwareInterface, ClassConfigAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;
    use ClassConfigAwareTrait;

    private $separators = '([\;\,\. \?\!\-\>\<\n])';

    private $patternMappings;

    public function __construct()
    {
        // Der einfache SpellChecker sucht und ersetzt Worte, keine Wortbestandteile. Diese Funktionen bilden die Liste
        // der falsch geschriebenen Worte und deren Ersetzungen auf enstprechende reguläre Ausdrücke ab. Die drei
        // Varianten start, inline und end unterscheiden, ob das Wort am Anfang, in der Mitte oder am Ende des zu
        // korrigierenden Strings vorkommt. Per array_map werden aus der Ersetzungsliste entsprechend drei
        // search-replace Muster, die nacheinander angewandt werden. Die Liste der Wortersetzungen ist in der
        // Konfiguration SpellChecker.config.php hinterlegt.
        $this->patternMappings = [
            'start' => [
                'search' => function($value) { return '~^' . $value . $this->separators . '~'; },
                'replace' => function($value) { return $value . '$1'; },
            ],
            'inline' => [
                'search' => function($value) { return '~' . $this->separators .  $value . $this->separators . '~'; },
                'replace' => function($value) { return '$1' . $value . '$2'; },
            ],
            'end'   => [
                'search' => function($value) { return '~' . $this->separators . $value . '$~'; },
                'replace' => function($value) { return '$1' . $value; },
            ],
        ];
    }

    public function check(string $text): string
    {
        $corrections = $this->classConfig['corrections'];

        $search = array_keys($corrections);
        $replace = array_values($corrections);

        foreach ($this->patternMappings as $position) {
            $currentSearch = array_map($position['search'], $search);
            $currentReplace = array_map($position['replace'], $replace);
            $text = preg_replace($currentSearch, $currentReplace, $text);
        }
        return $text;
    }
}