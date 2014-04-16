<?php
include_once './lib/core20.php' ;

/* Users configuration
J::config( array(
		'path'=>'./database/',
	    'fileExtension'=>'.json'
	));*/


/* Принудительная инициализация настроек
J::init();*/


/* Открытие файла таблицы */
//$J_users = J::open('users');
//$J_data = J::open('data');
$JU = new Jdb('users');
$JD = new Jdb('data');

/* Получение вывода оновного массива с данными 
$result = J::result();*/
$result = $JD->result();

    /*__call Выберает по параметру id с колонки getColumn и возвращает значение этой Column,
    если второй аргумент true возвращает весь массив записи
    $res = J::tbl()->getContent(2);
    $res = J::tbl()->getTitle(1, true);
    */


// SELECT
// - - - - - - - - - - - - - - - - - - - - - 
/*
// Выборка вариант 1. 1-арг. файл БД вызывает метод ::open(). 2-арг. ко какому полю искать. 3-арг. значение поля 2-арг. для поиска
$res = J::tbl()->select('data','id',5);

// Выборка вариант 2. ::open() необходим.  1-арг. ко какому полю искать. 2-арг. значение поля 1-арг. для поиска
$res = J::tbl()->select('id',5);

// Выборка вариант 3. ::open() необходим. поиск по id
$res = J::tbl()->select(2);

// Выборка всех записей с таблицы массивлм
$res = J::tbl()->select("*");

// Возвращает екземплр класса
$res = J::tbl()->select();
*/

// Выборка вариант 4 с правилами
/*$res = J::tbl()->select()
		      //->where('id<3')
		      //->whereAnd('id=2')
		      //->whereOr('id=5')
		      //->sortBy('date', 'DESC');
			  ->result();

var_dump($res);*/

$a = $JD->select("*");

//var_dump(J::$timeStart);
var_dump($a);
echo "<p>".$JD::timer()."</p>";




























?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Testing code#</title>
</head>
<body>
	



</body>
</html>