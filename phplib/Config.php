<?php
/**
 * Handles Java-style property files. If a property contains URL-style
 * parameters (var=value1&var2=value2&...), we parse_str it and map the
 * property name to the resulting associative array.
 */

class Config {
  private static $config;
  private static $locVersions = null;

  static function load($fileName) {
    self::$config = parse_ini_file($fileName, true);
    self::traverseRecursivePreferences(self::$config);
  }

  private static function traverseRecursivePreferences(&$a) {
    foreach ($a as $key => $value) {
      if (is_array($value)) {
        self::traverseRecursivePreferences($value);
        $a[$key] = $value;
      } else if (strpos($value, '&') !== false) {
        $a[$key] = Str::parseStr($value);
      }
    }
  }

  static function get($key, $defaultValue = null) {
    list($section, $name) = explode('.', $key, 2);
    if (array_key_exists($section, self::$config) && array_key_exists($name, self::$config[$section])) {
      return self::$config[$section][$name];
    } else {
      return $defaultValue;
    }
  }

  static function getAll() {
    return self::$config;
  }

  /* Returns an array containing all the variables in the given section, or */
  /* the empty array if the section does not exist. */
  static function getSection($section) {
    return array_key_exists($section, self::$config) ? self::$config[$section] : [];
  }

  static function getLocVersions() {
    if (!self::$locVersions) {
      $result = [];
      $versions = self::get('global.locVersions');
      foreach ($versions as $ver) {
        $nameAndDate = preg_split('/ /', $ver);
        assert(count($nameAndDate) == 2);
        $lv = new LocVersion();
        $lv->name = trim($nameAndDate[0]);
        $date = trim($nameAndDate[1]);
        $lv->freezeTimestamp = strtotime($date);
        $result[] = $lv;
      }
      self::$locVersions = array_reverse($result);
    }
    return self::$locVersions;
  }
}

Config::load(Core::getRootPath() . "dex.conf");
