<?php

class InflectedForm extends BaseObject {
  public static $_table = 'InflectedForm';

  function setForm($form) {
    $this->form = $form;
    $this->formNoAccent = preg_replace("/(?<!\\\\)'/", '', $form);
    $this->formNoAccent = str_replace("\\'", "'", $this->formNoAccent);
    $this->formUtf8General = $this->formNoAccent;
  }

  static function create($form = null, $lexemeId = null, $inflectionId = null,
                                $variant = null, $recommended = 1) {
    $if = Model::factory('InflectedForm')->create();
    $if->setForm($form);
    $if->lexemeId = $lexemeId;
    $if->inflectionId = $inflectionId;
    $if->variant = $variant;
    $if->recommended = $recommended;
    return $if;
  }

  // drops either î or 'î
  function createApheresis() {
    $short = explode('î', $this->form, 2)[1];
    $new = $this->parisClone();
    $new->setForm($short);
    $new->apheresis = true;
    return $new;
  }

  function createApocope() {
    $short = mb_substr($this->form, 0, -1);
    $short = rtrim($short, "'"); // trim the trailing accent if there is one
    $new = $this->parisClone();
    $new->setForm($short);
    $new->apocope = true;
    return $new;
  }

  function getHtmlForm() {
    $s = Str::highlightAccent($this->form);
    $s = str_replace("\\'", "'", $s);
    if ($this->apheresis) {
      $s = '&#x2011' . $s; // non-breaking hyphens
    }
    if ($this->apocope || $this->isLongForm()) {
      $s .= '&#x2011';
    }
    return $s;
  }

  function getHtmlClasses() {
    $classes = [];
    if (!$this->recommended) {
      $classes[] = 'notRecommended';
      $classes[] = 'notRecommendedHidden';
    }
    if ($this->apheresis || $this->apocope || $this->isLongForm()) {
      $classes[] = 'elision';
      $classes[] = User::can(User::PRIV_EDIT)
        ? 'elisionShown'
        : 'elisionHidden';
    }
    return implode(' ', $classes);
  }

  function getHtmlTitles() {
    $titles = [];
    if (!$this->recommended) {
      $titles[] = _('unrecommended or incorrect form');
    }
    if ($this->apheresis || $this->apocope) {
      $titles[] = _('by apheresis and/or elision');
    }
    if ($this->isLongForm()) {
      $titles[] = _('long verb form');
    }
    return implode('; ', $titles);
  }

  static function mapByInflectionRank($ifs) {
    $result = [];
    foreach ($ifs as $if) {
      $inflection = Inflection::get_by_id($if->inflectionId);
      if (!array_key_exists($inflection->rank, $result)) {
        $result[$inflection->rank] = [];
      }
      $result[$inflection->rank][] = $if;
    }
    return $result;
  }

  // Returns an inflected form of $lexeme that has the same formNoAccent as
  // $chunk. Throws ParadigmException if non exists. Prefers lower-rank
  // inflections, e.g.  înc'urcă-lume over încurc'ă-lume.
  static function getByLexemeChunk($lexeme, $chunk, $inflection) {
    $if = Model::factory('InflectedForm')
      ->table_alias('if')
      ->select('if.*')
      ->join('Inflection', ['if.inflectionId', '=', 'i.id'], 'i')
      ->where('if.lexemeId', $lexeme->id)
      ->where('if.formNoAccent', $chunk)
      ->order_by_asc('i.rank')
      ->find_one();

    if (!$if) {
      throw new ParadigmException(
        $inflection->id,
        "Lexemul „{$lexeme->form}” nu generează forma „{$chunk}”."
      );
    }

    return $if;
  }

  // The inflection ID implies the correct canonical model type
  static function deleteByModelNumberInflectionId($modelNumber, $inflId) {
    // Idiorm doesn't support deletes with joins
    DB::execute(sprintf("
      delete i
      from InflectedForm i
      join Lexeme l on i.lexemeId = l.id
      where l.modelNumber = '%s' and i.inflectionId = %d
    ", addslashes($modelNumber), $inflId));
  }

  function isLongForm() {
    return in_array($this->inflectionId, Constant::LONG_VERB_INFLECTION_IDS);
  }

  static function isStopWord($field, $form) {
    return Model::factory('InflectedForm')
      ->table_alias('i')
      ->join('Lexeme', 'i.lexemeId = l.id', 'l')
      ->where("i.{$field}", $form)
      ->where('l.stopWord', 1)
      ->count();
  }

  // Returns an array of base forms if $s (1) can be generated and (2) can
  // only be generated by elision. Otherwise returns null.
  static function isElision($s) {
    $values = Model::factory('InflectedForm')
      ->table_alias('i')
      ->select('i.apocope')
      ->select('i.apheresis')
      ->select('l.formNoAccent')
      ->distinct()
      ->join('Lexeme', ['i.lexemeId', '=', 'l.id'], 'l')
      ->where('i.formNoAccent', $s)
      ->find_many();

    $map = [];
    foreach ($values as $row) {
      $map[$row->apocope || $row->apheresis][] = $row->formNoAccent;
    }

    // now $map[false] is set if $s can be generated without elision
    // and $map[true] is set if $s can be generated by elision
    if (isset($map[true]) && !isset($map[false])) {
      return $map[true];
    } else {
      return false;
    }
  }

  // Should be faster than calling $if->save() on every record
  static function batchInsert($ifs, $lexemeId) {
    $defaults = [
      'lexemeId' => $lexemeId,
      'apheresis' => 0,
      'apocope' => 0,
    ];

    // collect column names; ensure lexemeId is included
    $colNames = ['lexemeId' => true];
    foreach ($ifs as $if) {
      foreach ($if->as_array() as $name => $ignored) {
        $colNames[$name] = true;
      }
    }
    $colNames = array_keys($colNames);

    // linearize the data to insert; ensure all fields are non-null
    $data = [];
    foreach ($ifs as $if) {
      foreach ($colNames as $name) {
        $value = $if->$name ?? $defaults[$name] ?? null;
        if ($value === null) {
          Log::error('No value and no default set for field %s', $name);
        }
        $data[] = $value;
      }
    }

    // build the SQL query with lots of placeholders
    $rowPlaces = '(' . implode(', ', array_fill(0, count($colNames), '?')) . ')';
    $allPlaces = implode(', ', array_fill(0, count($ifs), $rowPlaces));
    $query = sprintf('insert into InflectedForm (%s) values %s',
                     implode(', ', $colNames),
                     $allPlaces);

    // prepere and execute query
    $stmt = ORM::get_db()->prepare($query);

    try {
      $stmt->execute($data);
    } catch (PDOException $e){
      Log::error('Could not execute batch insert: %s', $e->getMessage());
    }
  }

  /**
   * Usually called from Lexeme::_clone, copies all inflectedforms to a new lexeme
   *
   * @param ORMWrapper $ifs Object with InflectedForm models
   * @param integer $lexemeId ID of destination lexeme
   */
  static function copy($ifs, $lexemeId) {
    if (count($ifs)) {
      foreach ($ifs as $if) {
        $newIf = $if->parisClone();
        $newIf->lexemeId = $lexemeId;
        $newIf->save();
      }
    }
  }

  function save() {
    $this->formUtf8General = $this->formNoAccent;
    parent::save();
  }
}
