<?php

class WordOfTheDay extends BaseObject implements DatedObject {
  public static $_table = 'WordOfTheDay';

  const BIG_BANG = '2011-04-29';
  const DEFAULT_IMAGE = 'generic.jpg';
  const OLDER_WOTD_DISPLAY_LIMIT = 5;
  const OLDER_WOTD_DISPLAY_LIMIT_ADMIN = 15;

  // Thumbnail sizes
  const SIZE_S = 48;
  const SIZE_M = 88;
  const SIZE_L = 300;
  const THUMBNAIL_SIZES = [ self::SIZE_S, self::SIZE_M, self::SIZE_L ];

  // delay in minutes
  static function getRSSWotD($delay = 0) {
    $ts = time() - $delay * 60;
    $date = date('Y-m-d', $ts);

    return Model::factory('WordOfTheDay')
      ->where_gte('displayDate', self::BIG_BANG)
      ->where_lte('displayDate', $date)
      ->order_by_desc('displayDate')
      ->limit(25)
      ->find_many();
  }

  static function getTodaysWord() {
    $today = date('Y-m-d');
    return Model::factory('WordOfTheDay')
      ->where('displayDate', $today)
      ->find_one();
  }

  static function updateTodaysWord() {
    // prefer words slotted on this date of ANY year, if available
    $today = date('0000-m-d');
    $wotd = Model::factory('WordOfTheDay')
      ->where_in('displayDate', [$today, '0000-00-00'])
      ->order_by_desc('displayDate')
      ->order_by_asc('priority')
      ->order_by_expr('rand()')
      ->find_one();

    if ($wotd) {
      $wotd->displayDate = date('Y-m-d');
      $wotd->save();
    }
    return $wotd;
  }

  // get words of the day for this day and month in other years, up to and including today
  static function getWotdsInOtherYears($year, $month, $day, $admin = false) {
    $today = date('Y-m-d');
    $limit = $admin ? self::OLDER_WOTD_DISPLAY_LIMIT_ADMIN : self::OLDER_WOTD_DISPLAY_LIMIT;
    return Model::factory('WordOfTheDay')
      ->where_lte('displayDate', $today)
      ->where_raw('year(displayDate) != ?', $year)
      ->where_raw('month(displayDate) = ?', $month)
      ->where_raw('day(displayDate) = ?', $day)
      ->order_by_desc('displayDate')
      ->limit($limit)
      ->find_many();
  }

  function getDefinition() {
    return Definition::get_by_id_status($this->definitionId, Definition::ST_ACTIVE);
  }

  function getUrlDate() {
    return str_replace('-', '/', $this->displayDate);
  }

  // true if displayDate has a definite value, including the year
  function hasFullDate() {
    return $this->displayDate && !Str::startsWith($this->displayDate, '0000');
  }

  function isPast() {
    $today = date('Y-m-d');
    return $this->hasFullDate() && ($this->displayDate < $today);
  }

  function getImageUrl() {
    $pic = $this->image ? $this->image : self::DEFAULT_IMAGE;
    return Config::STATIC_URL . 'img/wotd/' . $pic;
  }

  function getThumbUrl($size) {
    $pic = $this->image ? $this->image : self::DEFAULT_IMAGE;
    StaticUtil::ensureThumb(
      "img/wotd/{$pic}",
      "img/wotd/thumb{$size}/{$pic}",
      $size);
    return sprintf('%simg/wotd/thumb%s/%s',
                   Config::STATIC_URL,  $size, $pic);
  }

  function getSmallThumbUrl() {
    return $this->getThumbUrl(self::SIZE_S);
  }

  function getMediumThumbUrl() {
    return $this->getThumbUrl(self::SIZE_M);
  }

  function getLargeThumbUrl() {
    return $this->getThumbUrl(self::SIZE_L);
  }

  function getArtist() {
    return ($this->image)
      ? WotdArtist::getByDate($this->displayDate)
      : null;
  }

  // Expensive -- this fetches the URL from the static server
  function imageExists() {
    if (!$this->image) {
      return true; // Not the case since there is no image
    }
    list($ignored, $httpCode) = Util::fetchUrl($this->getImageUrl());
    return $httpCode == 200;
  }

  function save() {
    parent::save();
    Log::notice('Saved WotD id=%s, date=%s, image=%s, description=[%s]',
                $this->id, $this->displayDate, $this->image, $this->description);
  }

  function delete() {
    Log::warning('Deleted WotD id=%s date=%s, image=%s, description=[%s]',
                 $this->id, $this->displayDate, $this->image, $this->description);
    parent::delete();
  }
}
