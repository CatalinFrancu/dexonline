<?php

class LocaleUtil {
  const COOKIE_NAME = 'locale';

  static $current = null;

  static function init() {
    self::set(self::getCurrent());
  }

  static function getFromConfig() {
    return Config::TEST_MODE ? Config::TEST_LOCALE : Config::DEFAULT_LOCALE;
  }

  // Returns the locale as dictated, in order of priority, by
  // 1. anonymous user preference (cookie)
  // 2. config file
  static function getCurrent() {
    if (!self::$current) {
      self::$current = self::getFromConfig();

      $cookie = $_COOKIE[self::COOKIE_NAME] ?? null;
      if ($cookie && isset(Config::LOCALES[$cookie])) { // sanity check
        self::$current = $cookie;
      }
    }

    return self::$current;
  }

  // Returns the locale with the encoding stripped off, e.g. en_US instead of en_US.utf8
  static function getShort() {
    $l = self::getCurrent();
    return explode('.', $l)[0];
  }

  private static function set($locale) {
    mb_internal_encoding('UTF-8');

    // workaround for Windows lovers
    if (OS::getOS() === OS::OS_WIN) {
      putenv("LC_ALL=$locale");
    }

    setlocale(LC_ALL, $locale);
    $domain = "messages";
    bindtextdomain($domain, Config::ROOT . '/locale');
    bind_textdomain_codeset($domain, 'UTF-8');
    textdomain($domain);
  }

  // changes the locale and stores it in a cookie
  static function change($id) {
    if (!isset(Config::LOCALES[$id])) {
      return;
    }

    // delete the existing cookie if it matches the config value
    if ($id == self::getFromConfig()) {
      Session::unsetCookie(self::COOKIE_NAME);
    } else {
      setcookie(self::COOKIE_NAME, $id, time() + Session::ONE_YEAR_IN_SECONDS, '/');
    }

    self::set($id);
  }

  // Weeks always start on Monday -- this is not localized yet.
  static function getWeekDayNames() {
    $result = [];
    foreach (range(0, 6) as $d) {
      // 2018-01-01 fell on a Monday
      $result[] = strftime('%A', strtotime("2018-01-01 +{$d} days"));
    }
    return $result;
  }

  // formats a number according to the current locale
  static function number($x, $decimals = 0) {
    $locale = localeconv();
    return number_format(
      $x, $decimals, $locale['decimal_point'], $locale['thousands_sep']);
  }

  static function date($timestamp, $format = "%e %b %Y") {
    return strftime($format, $timestamp);
  }

}
