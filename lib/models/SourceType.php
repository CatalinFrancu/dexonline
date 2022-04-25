<?php

class SourceType extends BaseObject {
  public static $_table = 'SourceType';

  const TYPE_DICT_GENERAL_USE = 1;
  const TYPE_DICT_MORPHOLOGICAL = 2;
  const TYPE_DICT_RELATIONAL = 3;
  const TYPE_DICT_ETYMOLOGICAL = 4;
  const TYPE_DICT_SPECIALIZED = 5;
  const TYPE_DICT_ENCYCLOPEDIC = 6;
  const TYPE_DICT_SLANG = 7;
  const TYPE_DICT_REGIONAL = 8;
  const TYPE_DICT_OTHER = 999;
  const TYPE_DICT_UNVERIFIED = 1000;

  const ALL_TYPES = [
    self::TYPE_DICT_GENERAL_USE,
    self::TYPE_DICT_MORPHOLOGICAL,
    self::TYPE_DICT_RELATIONAL,
    self::TYPE_DICT_ETYMOLOGICAL,
    self::TYPE_DICT_SPECIALIZED,
    self::TYPE_DICT_ENCYCLOPEDIC,
    self::TYPE_DICT_SLANG,
    self::TYPE_DICT_REGIONAL,
    self::TYPE_DICT_OTHER,
    self::TYPE_DICT_UNVERIFIED,
  ];

  // used in the search results scrollspy
  static function getShortName($typeId) {
    switch ($typeId) {
      case self::TYPE_DICT_GENERAL_USE:   return _('general use');
      case self::TYPE_DICT_MORPHOLOGICAL: return _('morphological');
      case self::TYPE_DICT_RELATIONAL:    return _('relational');
      case self::TYPE_DICT_ETYMOLOGICAL:  return _('etymological');
      case self::TYPE_DICT_SPECIALIZED:   return _('specialized');
      case self::TYPE_DICT_ENCYCLOPEDIC:  return _('encyclopedic');
      case self::TYPE_DICT_SLANG:         return _('slang');
      case self::TYPE_DICT_REGIONAL:      return _('regional');
      case self::TYPE_DICT_OTHER:         return _('other');
      case self::TYPE_DICT_UNVERIFIED:    return _('unverified');
    }
  }

  static function getName($typeId) {
    switch ($typeId) {
      case self::TYPE_DICT_GENERAL_USE:   return _('General use dictionaries');
      case self::TYPE_DICT_MORPHOLOGICAL: return _('Morphological dictionaries');
      case self::TYPE_DICT_RELATIONAL:    return _('Relational dictionaries');
      case self::TYPE_DICT_ETYMOLOGICAL:  return _('Etymological dictionaries');
      case self::TYPE_DICT_SPECIALIZED:   return _('Specialized dictionaries');
      case self::TYPE_DICT_ENCYCLOPEDIC:  return _('Encyclopedic dictionaries');
      case self::TYPE_DICT_SLANG:         return _('Slang dictionaries');
      case self::TYPE_DICT_REGIONAL:      return _('Regional dictionaries');
      case self::TYPE_DICT_OTHER:         return _('Other dictionaries');
      case self::TYPE_DICT_UNVERIFIED:    return _('Unverified dictionaries');
    }
  }

  static function getDescription($typeId) {
    switch ($typeId) {
      case self::TYPE_DICT_GENERAL_USE:
        return _('The most common sense of the words are explained.');
      case self::TYPE_DICT_MORPHOLOGICAL:
        return _('The correspondences between lemma and lexical forms of words.');
      case self::TYPE_DICT_RELATIONAL:
        return _('These are not definitions, but relations between words.');
      case self::TYPE_DICT_ETYMOLOGICAL:
        return _('The etymology of (family of) words are explained.');
      case self::TYPE_DICT_SPECIALIZED:
        return _('These definitions usually explain only specialized meanings of words.');
      case self::TYPE_DICT_ENCYCLOPEDIC:
        return _('Encyclopedic definitions');
      case self::TYPE_DICT_SLANG:
        return _('Only slang words or senses are defined.');
      case self::TYPE_DICT_REGIONAL:
        return _('Only regional (or archaic) words or senses are defined.');
      case self::TYPE_DICT_OTHER:
        return _('These definitions could explain only certain meanings of words.');
      case self::TYPE_DICT_UNVERIFIED:
        return _('Since they are not made by lexicographers, these definitions may contain errors.');
    }
  }

  /**
   * Returns an array of SourceType objects, each extended with a count field
   * storing the number of times it occurs in $results.
   */
  static function loadForSearchResults(array $results) {
    $sourceTypeIds = [];
    foreach ($results as $result) {
      $stId = $result->source->sourceTypeId;
      $sourceTypeIds[$stId] = ($sourceTypeIds[$stId] ?? 0) + 1;
    }

    $sourceTypes = Model::factory('SourceType')
      ->where_in('id', array_keys($sourceTypeIds) ?: [ 0 ])
      ->find_many();

    foreach ($sourceTypes as $st) {
      $st->count = $sourceTypeIds[$st->id];
    }

    return $sourceTypes;
  }

}
