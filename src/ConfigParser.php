<?php

namespace leadingfellows\utils;

class ConfigParser {
	
	protected $configuration;
	protected $history;

	function __construct() {
		$this->configuration = array();
	}


	public static function env_to_array_custom(array $environment) {
        $result = array();
        foreach ($environment as $flat => $value) {
            $keys = explode('__', $flat);
            $keys = array_reverse($keys);
            $child = array(
                $keys[0] => $value
            );
            array_shift($keys);
            foreach ($keys as $k) {
                $child = array(
                    $k => $child
                );
            }
            $stack = array(array($child, &$result));
            while (!empty($stack)) {
                foreach ($stack as $curKey => &$curMerge) {
                    foreach ($curMerge[0] as $key => &$val) {
                        $hasKey = !empty($curMerge[1][$key]);
                        if ($hasKey && (array)$curMerge[1][$key] === $curMerge[1][$key] && (array)$val === $val) {
                            $stack[] = array(&$val, &$curMerge[1][$key]);
                        } else {
                            $curMerge[1][$key] = $val;
                        }
                    }
                    unset($stack[$curKey]);
                }
                unset($curMerge);
            }
        }
        return $result;
    }

	/**
     *
     */
	function addYaml($yml_filepath) {
		if (!file_exists($yml_filepath)) {
			return null;
		}
		$yml_cfg = (array)\Symfony\Component\Yaml\Yaml::parse(file_get_contents($yml_filepath), true, false, false);
		$this->history [] = array("path" => $yml_filepath, "type" => "yaml", "value" => $yml_cfg);
		$this->configuration = array_replace_recursive($this->configuration, $yml_cfg);
		return $yml_cfg;
	}

	/**
     *
     */
	function addDotEnv($dotenv_filepath, $prefix=null) {
		if (!file_exists($yml_filepath)) {
			return null;
		}
		$loader = new \josegonzalez\Dotenv\Loader([$dotenv_filepath]);
		//	$loader->setFilters(['josegonzalez\Dotenv\Filter\UnderscoreArrayFilter']);
		$loader->setFilters([ConfigParser::env_to_array_custom]);
		$loader->parse();
		$loader->filter();
		if($prefix !== null) {
			$loader->prefix($prefix);
		}
		$dotenv_cfg = $loader->toArray();
		$this->history [] = array("path" => $dotenv_filepath, "type" => "dotenv", "value" => $dotenv_cfg);
		$this->configuration = array_replace_recursive($this->configuration, $dotenv_cfg);
		return $dotenv_cfg;
	}


	function add($type, $path) {
		switch($type) {
			case "yaml":
				$this->addYaml($path);
				break;
			case "dotenv":
				$this->addDotEnv($path);
				break;
			default:
				throw new \Exception("invalid type '" . $type . "'");
		}
	}

	function toArray() {
		return (array)$this->configuration;
	}

}
