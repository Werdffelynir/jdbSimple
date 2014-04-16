<?php
include_once './lib/core30.php' ;


$JD = new Jdbase('data');
$JU = new Jdbase('users');


// CONFIG
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
$JP = new Jdbase('posts', array(
    'path'=>'./database/',
    'ext'=>'.jdb',
));

// __CALL
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
//$res = $JD->getTitle(2,true);
//$res = $JP->getText(4);

// SELECT
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
//$res = $JP->select('id',5);
//$res = $JP->select('category','php');
//$res = $JP->select(2);
//$res = $JP->select('*');
//$res = $JP->select()->result();

$res = $JP->select()
		      ->where('id>=2')
		      ->whereAnd('category=php')
		      ->whereOr('id=5')
		      //->sortBy('date', 'DESC')
			  ->result();




var_dump($res);
//var_dump($JP->result());
//var_dump($JD->result());

echo "<p>".$JP::timer()."</p>";