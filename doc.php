<?

include_once ("lib/handler.php");

/**/
// Инициализация класса и конфигурация
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
$jdb = new jdb;

// Установить иную конфигурацию
$jdb->config(
	array(
		'path'=>'./dataJdb/',
	    'fileExtension'=>'.json'
	));


// Операции создания новой таблицы-файла БД
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
// Создание файла таблицы базы данных (ФТБД), после обявление неоюходимо сохранить изминения
// методом $jdb->save();
$jdb->createTable('my_table', array(
	'title'=>null, 
	'text'=>null, 
	'author'=>null, 
	'date'=>null
));

$jdb->addColumns('my_table', array(
	'status'=>null,
	'rule'=>1,
));
 
$jdb->removeColumns('my_table', array('status'));

$jdb->save();


// Операции выборки данных с существующей таюдицы-файла
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
$jdb->open('my_table');

// __call Динамически
// - - - - - - - - - - - - - - - - - - - - - 
$jdb->__call();
$status = $jdb->getStatus(1);	// занчение "status" с id=1
$titleData = $jdb->getTitle(2, true); // возвращает массив данных с id=2

// SELECT
// - - - - - - - - - - - - - - - - - - - - - 
// Выборка вариант 1.  open() не нужен
$res = $jdb->select('my_table', 'id', 2);

// Выборка вариант 2.  open() необходим
$res = $jdb->select('id', 2);

// Выборка вариант 3.  open() необходим. выберет по умолчанию по id 
$res = $jdb->select(2);

// Выборка вариант 4 с правилами
$jdb->open('my_table')
    ->select()
    ->where('id<3')
    ->whereAnd('id=2')
    ->whereOr('id=5')
    ->sortBy('date', 'DESC');
$res = $jdb->result();

$jdb->__set();

$jdb->createTable();

$jdb->update();

$jdb->insert();

$jdb->save();

$jdb->sortBy();

$jdb::timer();

$jdb::Error();

$jdb->_();

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

$jPosts = jdb::open('posts');

$allPosts = $jPosts->select()->result();
$newsPosts = $jPosts->select()->where('category=news')->result();
$newsPublicPosts = $jPosts->select()->where('category=news')->whereAnd('disabled=0')->result();
$newsAndBlogPosts = $jPosts->select()->where('category=news')->whereOr('category=blog')->result();

// занчение "status" с id=1
$statusVal = $jPosts->getStatus(1);

// возвращает массив данных с id=2
$titleDataVals = $jPosts->getTitle(2, true);


