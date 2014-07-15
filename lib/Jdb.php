<?php

class Jdb
{
    public $autoSave = false;
    public $autoIncr;
    protected $path;
    protected static $conf;
    protected $jsonFile;
    protected $fileHandle;
    protected $fileDataOri = array();
    protected $fileData = array();

    public function __construct()
    {
        $defaultConfig = array(
            'path' => './databaseJSON/',
            'fileExtension' => '.json',
        );
        self::$conf = $defaultConfig;
    }

    public function __destruct()
    {
	    if($this->autoSave)
        	$this->save();
    }

    public function open($table)
    {
        $jsonFile = self::$conf['path'] . $table . self::$conf['fileExtension'];
        if (file_exists($jsonFile)) {
            $fileData = json_decode(file_get_contents($jsonFile), true);
            $this->jsonFile = $table;
            $tableAI = array_shift($fileData);
            $this->autoIncr = $tableAI['autoincrement'];
            $this->fileData = $fileData;
            //$this->lockFile($jsonFile);
        } else die('Файл не найден ' . $jsonFile);
        return $this;
    }

    public function config(array $userConfig = array())
    {
        self::$conf = array_merge(self::$conf, $userConfig);
        return self::$conf;
    }

    public function getTable()
    {
        return $this->jsonFile;
    }

    protected function lockFile($path)
    {
        /*$handle = fopen($path, "w");
        if (flock($handle, LOCK_EX))
            $this->fileHandle = $handle;
        else die("JsonTable Error: Can't set file-lock");*/
    }

    protected function unlockFile()
    {
	    flock($this->jsonFile, LOCK_UN); // отпираем файл
    }

    protected function autoIncrement() { }

    public function __call($op, $args) { }

    public function __set($op, $args) { }

    public function createTable($table, array $fileData)
    {
        if (!is_dir(self::$conf['path']))
            if (!mkdir(self::$conf['path'], 0, true)) die('Не удалось создать директорию БД.');
        $jsonFile = self::$conf['path'] . $table . self::$conf['fileExtension'];

        if (!file_put_contents($jsonFile, '[]', LOCK_EX)) die('Не удалось создать файл БД.');
        $this->jsonFile = $table;
        $this->autoIncr = 0; //array('autoincrement'=>'0');
        array_unshift($fileData, 'id');
        $this->fileData[] = array_map(function ($val) {
            if ($val > 0) return null; else  return 0;
        }, array_flip($fileData));
    }

    public function select($agr1 = null, $agr2 = null, $agr3 = null)
    {
        if (func_num_args() == 3) {
            $this->open($agr1);
            foreach ($this->fileData as $lineData)
                if ($lineData[$agr2] == $agr3)
                    return $lineData;
        } else if (func_num_args() == 2) {
            foreach ($this->fileData as $lineData)
                if ($lineData[$agr1] == $agr2)
                    return $lineData;
        } else if (func_num_args() == 1) {
            foreach ($this->fileData as $lineData)
                if ($lineData['id'] == $agr1)
                    return $lineData;
        } else {
            return $this->fileData;
        }
        return $this;
    }

    public function selectAll($t = null)
    {
        if ($t == null)
            return $this->fileData;
        else {
            $this->open($t);
            return $this->fileData;
        }
    }

    public function insert($fileData = array())
    {
        if (array_key_exists('id', $fileData)) die('Error, assing "id" interdiction!');
        if (sizeof($this->fileData) == 1) {
            $this->fileData[0] = array_merge($this->fileData[0], $fileData);
        }
        $fileDataId['id'] = $this->autoIncr = $this->autoIncr + 1;
        array_push($this->fileData, array_merge($this->fileData[0], $fileDataId, $fileData));
    }

    protected function getReWriterData ( $col, $val, $where )
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

    public function update($arg1, $arg2, $arg3 = null, $arg4 = null)
    {
        if (func_num_args() == 4) {
            $this->open($arg1);
            $this->fileData = $this->getReWriterData($arg2, $arg3, $arg4);
        } else if (func_num_args() == 3) {
	        
            $dataOri = $this->fileDataOri = $this->fileData;
            $dataResult = $this->rules($this->fileData, $arg3, true);
            
	        if(is_array($arg1)){
		        $countRows = count($arg1);
		        for($iter=0; $iter<$countRows; $iter++){
			        $this->fileData = $this->getReWriterData($arg1[$iter], $arg2[$iter], $arg3);
		        }
		    }else
				$this->fileData = $this->getReWriterData($arg1, $arg2, $arg3);
            
        } else if (func_num_args() == 2) {

            $this->fileDataOri = $this->fileData;
            if(is_array($arg1)){
	            foreach( $arg1 as $key => $value )
	            	$this->fileData = $this->getReWriterData( $key , $value, $arg2);
	        } else {
	            $fileDataResult = array_map(function ($a) use ($arg1, $arg2) {
	                foreach ($a as $key => $value)
	                    if ($key == $arg1) $a[$arg1] = $arg2;
	                return $a;
	            }, $this->fileData);
	            $this->fileData = $fileDataResult;
            }
        } else
            return false;
    }

    public function delete($arg1, $arg2 = null, $remove = true)
    {
        if ($arg2 != null) {
            $this->open($arg1);
            $where = $arg2;
        } else
	        $where = $arg1;
	        
            $fileDataResult = $this->rules($this->fileData, $where, true);
            $result = $fileDataResult[0];
            $keySearch = $fileDataResult[1];
            
            $resultFileData = array_map(function ($a) use ($result, $keySearch, $remove ) {
	            foreach ($result as $rVal){
	            	if($rVal[$keySearch] == $a[$keySearch]){
		            	if($remove){
							unset($a);
		            	} else {
			            	$arKeys = array_keys($a);
			            	foreach( $arKeys as $key )
		    					$a[$key] = null;
	    				}
	            	}
	            }
                return $a;
	            }, $this->fileData);
	            
	        if($remove)
	        	$this->fileData = array_values(array_diff($resultFileData, array(null) ));
	        else
	        	$this->fileData = $resultFileData;
	        	
	    return $this;
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
        $this->fileDataOri = $this->fileData;
        $this->fileData = $this->rules($this->fileData, $rule);
        return $this;
    }

    public function whereAnd($rule)
    {
        if ($this->fileDataOri == null)
            die('Error! Method whereAnd(...) must set after where(...)');
        $this->fileData = $this->rules($this->fileData, $rule);
        return $this;
    }

    public function whereOr($rule)
    {
        if ($this->fileDataOri == null)
            die('Error! Method whereOr(...) must set after where(...)');
        $arrayOr = $this->rules($this->fileDataOri, $rule);
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
        if($info)
            return array($aDataNew, $col, $val);

        return $aDataNew;
    }

    public function result()
    {
        return $this->fileData;
    }

    public function search($agr1 = null, $agr2 = null, $agr3 = null)
    {
        if (func_num_args() > 2) {
			
        } else if (func_num_args() > 1) {
			
        } else if (func_num_args() < 2) {
			
        }
        return $this->fileData;
    }

    public function save($sort = true)
    {
        $fileData = $this->fileData;
        $jsonFile = self::$conf['path'] . $this->jsonFile . self::$conf['fileExtension'];
        array_unshift($fileData, array('autoincrement' => $this->autoIncr));
        if ($sort)
            $this->sortBy('id');
        if (!file_put_contents($jsonFile, json_encode($fileData)))
            die('Error, save file!');
        else return true;
    }

}