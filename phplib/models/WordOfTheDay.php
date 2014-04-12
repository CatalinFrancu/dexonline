<?php

class WordOfTheDay extends BaseObject {
  public static $_table = 'WordOfTheDay';
  public static $DEFAULT_IMAGE;
  public static $IMAGE_DESCRIPTION_DIR;

  public static function init() {
    self::$DEFAULT_IMAGE = "generic.jpg";
    self::$IMAGE_DESCRIPTION_DIR = util_getRootPath() . 'docs/imageCredits';
  }

  public static function getRSSWotD($delay = 0) {
      $nowDate = ( $delay == 0 ) ? 'NOW()' : 'DATE_SUB(NOW(), INTERVAL ' . $delay. ' MINUTE)';
    return Model::factory('WordOfTheDay')->where_gt('displayDate', '2011-01-01')->where_raw('displayDate < ' . $nowDate)
      ->order_by_desc('displayDate')->limit(25)->find_many();
  }

  public static function getTodaysWord() {
    return Model::factory('WordOfTheDay')->where_raw('displayDate = curdate()')->find_one();
  }

  public static function updateTodaysWord() {
    db_execute('update WordOfTheDay set displayDate=curdate() where displayDate is null order by priority, rand() limit 1');
  }

  public static function getStatus($refId, $refType = 'Definition') {
    $result = Model::factory('WordOfTheDay')->table_alias('W')->select('W.id')->join('WordOfTheDayRel', 'W.id = R.wotdId', 'R')
      ->where('R.refId', $refId)->where('R.refType', $refType)->find_one();
    return $result ? $result->id : NULL;
  }

  public function getImageUrl() {
    $pic = $this->image ? $this->image : self::$DEFAULT_IMAGE;
    return Config::get('static.url') . 'img/wotd/' . $pic;
  }

  public function getThumbUrl() {
    $pic = $this->image ? $this->image : self::$DEFAULT_IMAGE;
    return Config::get('static.url') . 'img/wotd/thumb/' . $pic;
  }

  public function getImageCredits() {
    if (!$this->image) {
      return null;
    }
    $lines = @file(self::$IMAGE_DESCRIPTION_DIR . "/wotd.desc");
    if (!$lines) {
      return null;
    }
    foreach ($lines as $line) {
      $commentStart = strpos($line, '#');
      if ($commentStart !== false) {
        $line = substr($line, 0, $commentStart);
      }
      $line = trim($line);
      if ($line) {
        $parts = explode('::', trim($line));
        if (preg_match("/{$parts[0]}/", $this->image)) {
          $filename = self::$IMAGE_DESCRIPTION_DIR . '/' . $parts[1];
          return @file_get_contents($filename); // This could be false if the file does not exist.
        }
      }
    }
    return null;
  }

  // Expensive -- this fetches the URL from the static server
  public function imageExists() {
    if (!$this->image) {
      return true; // Not the case since there is no image
    }
    list($ignored, $httpCode) = util_fetchUrl($this->getImageUrl());
    return $httpCode == 200;
  }
}

WordOfTheDay::init();

?>
