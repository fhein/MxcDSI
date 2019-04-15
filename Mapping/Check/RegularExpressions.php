<?php


namespace MxcDropshipInnocigs\Mapping\Check;


use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Toolbox\Regex\RegexChecker;

class RegularExpressions
{
    /** @var array */
    protected $config;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var RegexChecker $regexChecker */
    protected $regexChecker;

    public function __construct(RegexChecker $regexChecker, array $config, LoggerInterface $log)
    {
        $this->config = $config;
        $this->regexChecker = $regexChecker;
        $this->log = $log;
    }

    /**
     * Check if the regular expressions from the configuration are
     * syntactically and semantically correct.
     *
     * This function is available through the GUI, Check regular expressions.
     *
     * @return bool
     */
    public function check()
    {
        $errors = [];
        foreach ($this->config['categories'] as $entry) {
            $entries = $entry['preg_match'];
            if (!is_array($entries)) {
                continue;
            }
            if (false === $this->regexChecker->validate(array_keys($entry['preg_match']))) {
                $errors = array_merge($errors, $this->regexChecker->getErrors());
            }
        }
        foreach (['name_prepare', 'name_cleanup', 'product_name_replacements'] as $entry) {
            $entries = $this->config[$entry]['preg_replace'];
            if (!is_array($entries)) {
                continue;
            }
            if (false === $this->regexChecker->validate(array_keys($entries))) {
                $errors = array_merge($errors, $this->regexChecker->getErrors());
            }
        }
        foreach ($this->config['product_names'] as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            if (false === $this->regexChecker->validate($entry)) {
                $errors = array_merge($errors, $this->regexChecker->getErrors());
            }
        }

        if (false === $this->regexChecker->validate(array_keys($this->config['name_type_mapping']))) {
            $errors = array_merge($errors, $this->regexChecker->getErrors());
        }

        $result = empty($errors);
        if (false === $result) {
            foreach ($errors as $error) {
                $this->log->err('Invalid regular expression: \'' . $error . '\'');
            }
        }
        return $result;
    }
}