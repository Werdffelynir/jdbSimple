<?php

class Jdbase
{

    const DEBUG = true;
    const AUTOBACKUP = true;
    const AUTOSAVE = true;
    const AUTOSORT = false;

    private $ai;
    private $filePath;
    private $fileData;
    private $fileDataTemp;
    private $fileDataOriginal;
    private $tblColumns;

    public static $timeStart;
    private static $instance = null;
    private static $tbl = null;
    private static $INIT = false;
    private $conf = null;

    public function __construct($tbl, $conf=null)
    {
        $path = (!empty($conf['path'])) ? $conf['path'] : './database/';
        $ext = (!empty($conf['ext'])) ? $conf['ext'] : '.json';

        $this->conf = array(
            'path'=> $path,
            'ext'=> $ext,
        );

        $filePath = $path.$tbl.$ext;

        if (file_exists($filePath)) {
            $this->filePath = $filePath;
            self::$timeStart = microtime(true);
        } else {
            self::Error("Not find file: <b>" . $filePath . "</b>");
        }
    }

    public function init(){
        $fileData = json_decode(file_get_contents($this->filePath), true);
        if(empty($fileData))
            self::Error("Невозможно разпознать JSON формат: <b>" . $this->filePath . "</b>");
        self::$INIT = true;
        $ai = array_shift($fileData);
        $this->ai = $ai['ai'];
        $this->fileData = $fileData;
        $this->tblColumns = array_keys($fileData[0]);
    }

    /**
     * Динамически выберает по параметру id с колонки getColumn и возвращает значение этой Column,
     * если второй аргумент true возвращает весь массив записи.
     *
     * @param  string $cColumn
     * @param  array  $args
     * @return array|null
     */
    public function __call($cColumn, $args)
    {
        if(!self::$INIT) $this->init();

        $command = strtolower(substr($cColumn, 0, 3));
        $column = strtolower(substr($cColumn, 3));
        $id = $args[0];
        $full = $args[1];

        if ($command == "get") {

            if (!in_array($column, $this->tblColumns)) {
                return false;
                //self::Error("Error", "Not find file-table: <b>" . $command . "</b>. __call() use only prefix 'get[ColumnName]'");
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
            self::Error("Error", "Not find command: <b>" . $command . "</b>. __call() use only prefix 'get[ColumnName]'");
        }
    }

    public function select($agr1 = null, $agr2 = null, $once = false)
    {
        if(!self::$INIT) $this->init();

        $newData = array();
        if( func_num_args() == 2 ){
            foreach ($this->fileData as $lineData) {
                if ($lineData[$agr1] == $agr2) {
                    $newData[] = $lineData;
                }
            }
            if($once) return $newData[0];
            else return $newData;
        } else if( func_num_args() == 1 && $agr1!='*'){
            foreach ($this->fileData as $lineData) {
                if ($lineData['id'] == $agr1) {
                    $newData[] = $lineData;
                }
            }
            if($once) return $newData[0];
            else return $newData;
        } else if( $agr1=='*' ){
            return $this->fileData;
        } else
            return $this;
    }

    public function result()
    {

    }

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



    public function where($rule)
    {
        if(empty($this->fileDataOriginal))
            $this->fileDataOriginal = $this->fileData;
        $this->fileData = $this->rules($this->fileData, $rule);
        return $this;
    }

    public function whereAnd($rule)
    {
        if ($this->fileDataOriginal == null)
            die('Error! Method whereAnd(...) must set after where(...)');
        $this->fileData = $this->rules($this->fileData, $rule);
        return $this;
    }

    public function whereOr($rule)
    {
        if ($this->fileDataOriginal == null)
            die('Error! Method whereOr(...) must set after where(...)');
        $arrayOr = $this->rules($this->fileDataOriginal, $rule);

        //var_dump(array_merge($arrayOr, $this->fileData)); die;


        $this->fileData = array_merge($this->fileData, $arrayOr);
        return $this;
    }

    protected function rules($aData, $rule, $info=false)
    {
        $aDataNew = array();
        $col = null;
        $val = null;
        if ($el = stripos($rule, '<=')) {
            $col = mb_strtolower(trim(substr($rule, 0, $el)));
            $val = mb_strtolower(trim(substr($rule, $el + 2)));
            foreach ($aData as $ad) {
                if ($ad[$col] <= $val)
                    $aDataNew[] = $aData;
            }
        } else
        if ($el = stripos($rule, '>=')) {
            $col = mb_strtolower(trim(substr($rule, 0, $el)));
            $val = mb_strtolower(trim(substr($rule, $el + 2)));
            foreach ($aData as $ad) {
                if ($ad[$col] >= $val)
                    $aDataNew[] = $ad;
            }
        } else
        if ($el = stripos($rule, '!=')) {
            $col = mb_strtolower(trim(substr($rule, 0, $el)));
            $val = mb_strtolower(trim(substr($rule, $el + 2)));
            foreach ($aData as $ad) {
                if ($ad[$col] != $val)
                    $aDataNew[] = $ad;
            }
        } else
        if ($el = stripos($rule, '<')) {
            $col = mb_strtolower(trim(substr($rule, 0, $el)));
            $val = mb_strtolower(trim(substr($rule, $el + 1)));
            foreach ($aData as $ad) {
                if ($ad[$col] < $val)
                    $aDataNew[] = $ad;
            }
        } else
        if ($el = stripos($rule, '>')) {
            $col = mb_strtolower(trim(substr($rule, 0, $el)));
            $val = mb_strtolower(trim(substr($rule, $el + 1)));
            foreach ($aData as $ad) {
                if ($ad[$col] > $val)
                    $aDataNew[] = $ad;
            }
        } else
        if ($el = stripos($rule, '=')) {
            $col = mb_strtolower(trim(substr($rule, 0, $el)));
            $val = mb_strtolower(trim(substr($rule, $el + 1)));
            foreach ($aData as $ad) {
                if (mb_strtolower($ad[$col]) == $val)
                    $aDataNew[] = $ad;
            }
        }

        if($info)
            return array($aDataNew, $col, $val);

        return $aDataNew;
    }


    public static function timer() {
        return round(microtime(true) - self::$timeStart, 6);
    }


    public static function Error($msgHeader='Error', $msgText='...'){

        if(self::DEBUG){
            try {
                throw new Exception("TRUE.");
            } catch (Exception $e) {
                echo '<html xmlns="http://www.w3.org/1999/xhtml" lang="ru">
            <head>
            <meta charset="utf-8">
            <title>Jdb error</title><style>*{margin:0;padding:0;}</style></head>
            <body>
            <div style="display:block; background-color:#19115C; color:#FFF; font-family: Arial">
                <h2 style="background-color:#2D1EA6; color:#FF5100; padding:5px">'.$msgHeader.'</h2>
                <p style="padding:5px; font-family: Consolas, Courier New">'.$msgText.'</p>

                <br>
                <h3 style="padding:5px;">Trace As String: </h3>
                <code style="display: block; padding: 10px; font-size: 12px; font-weight: bold; font-family: Consolas, Courier New, monospace; color:#CBFEFF; background: #000066">
                    ' . str_replace("#", "<br>#", $e->getTraceAsString()) . '<br>
                </code>

                <div style="margin-top: 10px; padding:5px; font-family: Consolas, Courier New; background-color:#181821; color:#FFDD00; ">
                    <code><pre>'. $e->getCode() .'</pre></code>
                </div>
            </div>
            </body>
            </html>';
            };
        } else
            return false;
    }
}









































