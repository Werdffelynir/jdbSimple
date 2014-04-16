<?php

/**
 *
 */
class J {

  const DEBUG = true;
  const AUTOBACKUP = true;
  const AUTOSAVE = true;
  const AUTOSORT = false;

  private $ai;
  private $filePath;
  private $fileData;
  private $fileDataOriginal;
  private $tblColumns;

  public static $timeStart;
  private static $instance = NULL;
  private static $tbl = NULL;
  private static $cf = NULL;

  private function __construct() {
  }

  private function __clone() {
  }

  private function __sleep() {
  }

  /**
   *
   **/
  public static function init() {
    self::$cf['path'] = './database/';
    self::$cf['ext'] = '.json';
  }

  /**
   *
   **/
  public static function tbl() {
    if (self::$tbl === NULL) {
      self::$tbl = new self;
    }
    return self::$tbl;
  }

  /**
   *
   **/
  public static function open($tbl) {
    if (empty(self::$cf)) {
      self::init();
    }

    $filePath = self::$cf['path'] . $tbl . self::$cf['ext'];
    self::$cf[$tbl]['path'] = $filePath;
    self::$cf[$tbl]['name'] = $tbl;

    if (file_exists($filePath)) {
      $fileData = json_decode(file_get_contents($filePath), TRUE);
      $ai = array_shift($fileData);
      self::tbl()->ai = $ai['ai'];
      self::tbl()->filePath = $filePath;
      self::tbl()->fileData = $fileData;
      self::tbl()->tblColumns = array_keys($fileData[0]);
      self::$timeStart = microtime(TRUE);
    } else {
      self::Error("Not find file: <b>" . $filePath . "</b>");
    }

      return self::tbl();
  }


  /**
   * Динамически выберает по параметру id с колонки getColumn и возвращает значение этой Column,
   * если второй аргумент true возвращает весь массив записи.
   *
   * @param  string $ccolumn //
   * @param  array  $args    //
   * @return array|null             //
   */
  public function __call($ccolumn, $args) {
    $command = strtolower(substr($ccolumn, 0, 3));
    $column = strtolower(substr($ccolumn, 3));
    $id = $args[0];
    $full = $args[1];

    var_dump($command, $column, $id);

    if ($command == "get") {

      if (!in_array($column, $this->tblColumns)) {
        return FALSE;
      }

      foreach ($this->fileData as $valueFD) {
        if ($valueFD['id'] == $id) {
          if ($full) {
            return $valueFD;
          } else {
            return $valueFD[$column];
          }
        }
      }
      return NULL;
    } else {
      self::Error("Not find command: <b>" . $command . "</b>. __call() use only prefix 'get[ColumnName]'");
    }
  }

  /**
   * @param null $agr1
   * @param null $agr2
   * @param null $agr3
   * @return $this
   */
  public function select($agr1 = NULL, $agr2 = NULL, $agr3 = NULL) {
    if (func_num_args() == 3) {
      if(empty($this->fileData))
        self::open($agr1);
      foreach ($this->fileData as $lineData) {
        if ($lineData[$agr2] == $agr3) {
          return $lineData;
        }
      }
    } else if(func_num_args() == 2 && !empty($this->fileData)){
      foreach ($this->fileData as $lineData) {
        if ($lineData[$agr1] == $agr2) {
          return $lineData;
        }
      }
    } else if(func_num_args() == 1 && $agr1!='*' && !empty($this->fileData)){
      foreach ($this->fileData as $lineData) {
        if ($lineData['id'] == $agr1) {
          return $lineData;
        }
      }
    } else if($agr1=='*' && !empty($this->fileData)){
      return $this->fileData;
    } else if(empty($this->fileData)){
      self::Error("Not open file!");
    }
    return $this;
  }

  /**
   * @param string $attr
   * @param string $asc
   * @param bool   $num
   * @return $this
   */
  public function sortBy($attr = 'id', $asc = 'ACS', $num = false)
  {
    $selectData = $this->fileData;
    if (strtoupper($asc) == 'ACS') {
      if ($num)
        usort($selectData, function ($a, $b) use ($attr) {
              return ($a[$attr] - $b[$attr]);
            });
      else
        uasort($selectData, function ($first, $second) use ($attr) {
              if ($first[$attr] == $second[$attr]) {
                return 0;
              }
              return ($first[$attr] > $second[$attr]) ? 1 : -1;
            });
    } elseif (strtoupper($asc) == 'DESC') {
      if ($num)
        usort($selectData, function ($a, $b) use ($attr) {
              return ($b[$attr] - $a[$attr]);
            });
      else
        uasort($selectData, function ($first, $second) use ($attr) {
              if ($first[$attr] == $second[$attr]) {
                return 0;
              }
              return ($first[$attr] < $second[$attr]) ? 1 : -1;
            });
    }
    $this->fileData = $selectData;
    return $this;
  }

  public function _updateById($column, $id, array $updateData) {
    if (is_numeric($id)) {

      if (!in_array($column, $this->tblColumns)) {
        return FALSE;
      }

      $newData = array();
      $newDataTemp = array();
      if (is_array($updateData)) {
        foreach ($this->fileData as $dKey => $dValue):
          if ($dValue['id'] == $id) {
            foreach ($updateData as $upKey => $upValue) {
              if (!in_array($upKey, $this->tblColumns)) {
                self::Error("Not exists column [" . $upKey . "]");
              }
              $newDataTemp[$upKey] = $upValue;
            }
            $newData[] = array_merge($dValue, $newDataTemp);
          } else {
            $newData[] = $dValue;
          }
        endforeach;
        $this->fileData = $newData;
        return $this;
      }
      return NULL;

    } else {
      if (is_string($id) AND $column == 'update') {
        $this->update($id, $updateData);
      }
    }
  }

  public function update($rule, $upData) {
    if (func_num_args() == 2) {

      $this->fileDataOriginal = $this->fileData;
      if (is_array($upData)) {
        foreach ($upData as $key => $value) {
          $this->fileData = $this->getReWriterData($key, $value, $rule);
        }
      } else {
        $fileDataResult = array_map(
            function ($a) use ($upData, $rule) {
              foreach ($a as $key => $value) {
                if ($key == $upData) {
                  $a[$upData] = $rule;
                }
              }
              return $a;
            },
            $this->fileData
        );
        $this->fileData = $fileDataResult;
      }
    } else {
      return FALSE;
    }
  }


  /**
   *
   **/
  public static function config(array $cf) {
    if (empty(self::$cf)) {
      self::init();
    }

    self::$cf = array_merge(self::$cf, $cf);
  }

  public static function result() {
    return self::tbl()->fileData;
  }

  public static function timer() {
    return round(microtime(TRUE) - self::$timeStart, 6);
  }


  /**
   * @param $aData        массив данных
   * @param $rule         правила "id<val",
   *                      "id>val",
   *                      "id=<val",
   *                      "id=>val",
   *                      "id=val",
   * @param $full         вывод в массиве с дополнительной информацией
   * @return array|null
   */
  /**
   * @param      $aData
   * @param      $rule
   * @param bool $full
   * @return array
   */
  protected function rules($aData, $rule, $full = FALSE) {
    $aDataNew = array();
    $col = NULL;
    $val = NULL;
    if ($el = stripos($rule, '<=')) {
      $col = mb_strtolower(trim(substr($rule, 0, $el)));
      $val = mb_strtolower(trim(substr($rule, $el + 2)));
      foreach ($aData as $ad) {
        if ($ad[$col] <= $val) {
          $aDataNew[] = $aData;
        }
      }
    }
    if ($el = stripos($rule, '>=')) {
      $col = mb_strtolower(trim(substr($rule, 0, $el)));
      $val = mb_strtolower(trim(substr($rule, $el + 2)));
      foreach ($aData as $ad) {
        if ($ad[$col] >= $val) {
          $aDataNew[] = $ad;
        }
      }
    }
    if ($el = stripos($rule, '!=')) {
      $col = mb_strtolower(trim(substr($rule, 0, $el)));
      $val = mb_strtolower(trim(substr($rule, $el + 2)));
      foreach ($aData as $ad) {
        if ($ad[$col] != $val) {
          $aDataNew[] = $ad;
        }
      }
    }
    if ($el = stripos($rule, '<')) {
      $col = mb_strtolower(trim(substr($rule, 0, $el)));
      $val = mb_strtolower(trim(substr($rule, $el + 1)));
      foreach ($aData as $ad) {
        if ($ad[$col] < $val) {
          $aDataNew[] = $ad;
        }
      }
    }
    if ($el = stripos($rule, '>')) {
      $col = mb_strtolower(trim(substr($rule, 0, $el)));
      $val = mb_strtolower(trim(substr($rule, $el + 1)));
      foreach ($aData as $ad) {
        if ($ad[$col] > $val) {
          $aDataNew[] = $ad;
        }
      }
    }
    if ($el = stripos($rule, '=')) {
      $col = mb_strtolower(trim(substr($rule, 0, $el)));
      $val = mb_strtolower(trim(substr($rule, $el + 1)));
      foreach ($aData as $ad) {
        if (mb_strtolower($ad[$col]) == $val) {
          $aDataNew[] = $ad;
        }
      }
    }
    if ($full) {
      return array($aDataNew, $col, $val);
    }

    return $aDataNew;
  }

  public static function Error($msgHeader, $msgText = '') {
    if (self::DEBUG) {
      $page = '
            <div style="display:block; background-color:#A0341E; color:#FFF;">
                <h2>' . $msgHeader . '</h2>
                <p>' . $msgText . '</p>
                <div>
                    <code><pre></pre></code>
                </div>
            </div>';
      die($page);
    } else {
      return FALSE;
    }
  }
}