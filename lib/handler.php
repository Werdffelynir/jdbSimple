<?php

class jdbx
{
    const DEBUG = true;
    const AUTOBACKUP = true;
    const AUTOSAVE = true;
    const AUTOSORT = false;
    public $autoBackup = true;
    public $autoSave = true;
    public $autoSort = false;
 
    public static $timeStart;
    public $ai;
    public $filePath;
    public $fileData = array();
    public $fileDataOriginal = array();
    public $tblColumns;

    public function __construct($filePath=null)
    {
        if (file_exists($filePath)) {
            $this->filePath = $filePath;
            $fileData = json_decode(file_get_contents($filePath), true);
            $ai = array_shift($fileData);
            $this->ai = $ai['ai'];
            $this->tblColumns = array_keys($fileData[0]);
            $this->fileData = $fileData;
            self::$timeStart = microtime(true);
        }else{
            die('Файл не найден ' . $filePath);
        }
    }

    public function __destruct()
    {
	    //if($this->autoSave)
        //	$this->save();
        //fclose($this->fileHandle);
    }
    public function open($table)
    {
        //var_dump($table);
        return new jdb; //$this;
        //if($this->autoSave)
        //	$this->save();
        //fclose($this->fileHandle);
    }

    public function __callStatic($table, $param) {
        return $this->open($table);
    }

    public function fetchAll() {
        return $this->fileData;
    }
    public function result() {
        return $this->fileData;
    }



    public function __call($ccolumn, $args) {
        //var_dump($column, $args);

    	// for "getColumn"
    	// for "setColumn"
    	// for "iniColumn"
    	$command = strtolower( substr($ccolumn, 0, 3) );
    	$column = strtolower( substr($ccolumn, 3) );

        $id = $args[0];
        $updateData = (isset($args[1]))?$args[1]:null;

    	if($command=="get"){

            if(!in_array($column, $this->tblColumns)) return false;

    		if(count($args)==1){
		        foreach ($this->fileData as $valueFD) {
		        	if($valueFD['id']==$id){
		        		return $valueFD;
		        	}
		        }
		        return null;
	        }else if(count($args)==2){
	        	return false;
	        }
    	}else if($command=="set"){
            
            if(is_numeric($id)){

                if(!in_array($column, $this->tblColumns)) return false;

                $newData = array();
                $newDataTemp = array();
                if(is_array($updateData)){
                    foreach ($this->fileData as $dKey=>$dValue):
                        if($dValue['id']==$id){
                            foreach ($updateData as $upKey => $upValue) {
                                if(!in_array($upKey, $this->tblColumns)) 
                                    self::Error("Not exists column [".$upKey."]");
                                $newDataTemp[$upKey] = $upValue;
                            }
                            $newData[] = array_merge($dValue, $newDataTemp);
                        }else{
                            $newData[] = $dValue; 
                        }
                    endforeach;
                    $this->fileData = $newData;
                    return $this;
                }
                return null;
            
            } else if(is_string($id) AND $column=='update') {

                $this->update($id, $updateData);
            }

    	}else if($command=="run"){

    	}else{

    	}
    }


    public function __set($column, $args) {
        if(!in_array($column, $this->tblColumns) AND count($agrs)!=2) return false;
        $newData = array();
        if(is_numeric( $args[0]) ){
            foreach ($this->fileData as $valueFD): 
                if($valueFD['id']==$args[0]){
                     $valueFD[$column] = $args[1];
                }
                $newData[] =  $valueFD;
            endforeach;
            $this->fileData = $newData;
            return $this;
        }
        return false;
    }


    public function __get($args) {
        //var_dump($args);
    }


    public function createTable($jsonFile, array $fileData)
    {
        if (!is_file($jsonFile))
            self::Error("Not exists file <b>".$jsonFile."</b>");

        if (!file_put_contents($jsonFile, '[]'))
            self::Error("Not put data into file <b>".$jsonFile."</b>");
        $this->jsonFile = $jsonFile;
        $this->autoIncr = 1;
        array_unshift($fileData, 'id');
        $this->fileData[] = array_map(function ($val) {
            if ($val > 0) return null; else  return 0;
        }, array_flip($fileData));
    }


    public function getReWriterData ( $col, $val, $where )
    {
            $dataOri = $this->fileData;
            $dataResult = $this->rules($this->fileData, $where, true);

            $fileDataResult = array_map(function ($a) use ($col, $val) {
                foreach ($a as $key => $value)
                    if ($key == $col) $a[$col] = $val;
                return $a;
            }, $dataResult[0]);

            $keySearch = $dataResult[1];
            $fileNewResult = array_map(function ($a) use ($fileDataResult, $keySearch) {
                foreach ($fileDataResult as $value){
                    if($value[$keySearch] == $a[$keySearch])
                        $a = $value;
                }
                return $a;
            }, $dataOri);
            return $fileNewResult;
    }


    public function update($rule, $upData)
    {
        if (func_num_args() == 2) {

            $this->fileDataOriginal = $this->fileData;
            if(is_array($upData)){
                foreach( $upData as $key => $value )
                    $this->fileData = $this->getReWriterData( $key , $value, $rule);
            } else {
                $fileDataResult = array_map(function ($a) use ($upData, $rule) {
                    foreach ($a as $key => $value)
                        if ($key == $upData) $a[$upData] = $rule;
                    return $a;
                }, $this->fileData);
                $this->fileData = $fileDataResult;
            }
        } else
            return false;
    }


    public function insert($fileData = array())
    {
        foreach ($fileData as $isKey => $isValue) {
            if(!in_array($isKey, $this->tblColumns)) 
                self::Error("Not exists column [".$isKey."]");
        }

        if (array_key_exists('id', $fileData)) die('Error, assing "id" interdiction!');

        if (sizeof($this->fileData) == 1) {
            $this->fileData[0] = array_merge($this->fileData[0], $fileData);
        }
        $fileDataId['id'] = $this->ai += 1;
        array_push($this->fileData, array_merge($this->fileData[0], $fileDataId, $fileData));
        return $this->ai;
    }


    public function save()
    {
        $fileData = $this->fileData;
        array_unshift($fileData, array('ai' => $this->ai));
        if (self::AUTOSORT)
            $this->sortBy('id');
        if (!file_put_contents($this->filePath, json_encode($fileData)))
            die('Ошибка сохранения');
        else
            return true;
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


    /** 
     * @param $aData массив данных
     * @param $rule правила "id<val", 
     *                      "id>val", 
     *                      "id=<val", 
     *                      "id=>val", 
     *                      "id=val",
     * @param $full вывод в массиве с дополнительной информацией
     * @return array|null
     */
    protected function rules($aData, $rule, $full=false)
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
        }
        if ($el = stripos($rule, '>=')) {
            $col = mb_strtolower(trim(substr($rule, 0, $el)));
            $val = mb_strtolower(trim(substr($rule, $el + 2)));
            foreach ($aData as $ad) {
                if ($ad[$col] >= $val)
                    $aDataNew[] = $ad;
            }
        }
        if ($el = stripos($rule, '!=')) {
            $col = mb_strtolower(trim(substr($rule, 0, $el)));
            $val = mb_strtolower(trim(substr($rule, $el + 2)));
            foreach ($aData as $ad) {
                if ($ad[$col] != $val)
                    $aDataNew[] = $ad;
            }
        }
        if ($el = stripos($rule, '<')) {
            $col = mb_strtolower(trim(substr($rule, 0, $el)));
            $val = mb_strtolower(trim(substr($rule, $el + 1)));
            foreach ($aData as $ad) {
                if ($ad[$col] < $val)
                    $aDataNew[] = $ad;
            }
        }
        if ($el = stripos($rule, '>')) {
            $col = mb_strtolower(trim(substr($rule, 0, $el)));
            $val = mb_strtolower(trim(substr($rule, $el + 1)));
            foreach ($aData as $ad) {
                if ($ad[$col] > $val)
                    $aDataNew[] = $ad;
            }
        }
        if ($el = stripos($rule, '=')) {
            $col = mb_strtolower(trim(substr($rule, 0, $el)));
            $val = mb_strtolower(trim(substr($rule, $el + 1)));
            foreach ($aData as $ad) {
                if (mb_strtolower($ad[$col]) == $val)
                    $aDataNew[] = $ad;
            }
        }
        if($full)
            return array($aDataNew, $col, $val);

        return $aDataNew;
    }


    public static function timer()
    {
        return round(microtime(true)-self::$timeStart,4);
    }


    public static function Error($msgHeader, $msgText=''){
        if(self::DEBUG){
            $page = '
            <div style="display:block; background-color:#A0341E; color:#FFF;">
                <h2>'.$msgHeader.'</h2>
                <p>'.$msgText.'</p>
                <div>
                    <code><pre></pre></code>
                </div>
            </div>';
            die($page);
        } else return false;
    }

}
















