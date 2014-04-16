<?

include_once ("lib/handler.php");

$jdb = new jdb("database/data.json");

//var_dump($jdb->fetchAll());

//var_dump($jdb->getStatus(1),$jdb->getTitle(2));

/*
$jdb->title = array(1, "new Title");
$jdb->content = array(1, "new Content");
$jdb->date = array(1, date('d,m,Y'));
*/
//$jdb->title = array(array('id==1'), "new Title");

/*$jdb->setId(2, array(
	'title'=>'n--- T',
	'content'=>'n--- C',
	'date'=>'n--- D',
	));
*/

/*
$jdb->setUpdate("rule=2", array(
	'title'=>'n--- T',
	'content'=>'n--- C',
	'date'=>'n--- D',
	));
*/
/**/$jdb->update("rule=2", array(
	'title'=>'new Test title',
	'content'=>'new Test content',
	'date'=>date('d,m,Y'),
	));
$jdb->save();

/*
$newId = $jdb->insert(array(
    'title'	=>'Some Title 9',
    'content'	=>'Some Text Content 9',
    'rule'=>'1',
    'date'	=>date('d.m.Y H:i:s', time())
));
$jdb->save();*/




var_dump($jdb->fetchAll());

var_dump($jdb::timer());





