<?php

define('START_TIME',microtime(true));

include_once('./lib/Jdb.php');
$jdb = new Jdb;

//$jdb->createTable('pages', array( 'title', 'content', 'image', 'author', 'date', 'category', 'status' ));
//$jdb->save();
//die;

if(isset($_POST['title'])){

    $jdb->open('pages');

    if(isset($_POST['id']) && strlen($_POST['id'])>0){

        $jdb->update(array(
        				'title' => $_POST['title'],
        				'content' => $_POST['content'],
        				'image' => $_POST['image'],
        				'status' => $_POST['status']
        			), 'id='.$_POST['id']);

    } else {
         $jdb->insert(array(
            'title'	=> $_POST['title'],
            'content' => $_POST['content'],
            'image'=> 'image',
            'author'=> 'Administrator',
            'date' => date('d.m.Y H:i', time()),
            'category' => null,
            'status' => $_POST['status']
        ));
    }

    $result = $jdb->save();
    header("Location: ".$_SERVER['HTTP_REFERER']);
}

if(isset($_GET['edit'])){
    if(isset($_GET['remove'])){
        $jdb->delete('pages', "id=".$_GET['edit'])->save();
        header("Location: ./index.php");
    } else
        $recordsEdit = $jdb->open('pages')->where('id='.$_GET['edit'])->select();

}else{
    $recordsEdit = array('title'=>'','url'=>'','content'=>'','image'=>'','author'=>'','id'=>'', 'oreder'=>'', 'satus'=>'');
}
if(isset($_GET['read'])){
    $recordsRead = $jdb ->open('pages')->select($_GET['read']);
}else{
    $recordsRead = $jdb ->open('pages')->select(1);
}


if(!isset($_GET['page']) || $_GET['page'] == 'home'){
    $page = 'home';
}elseif($_GET['page'] == 'records'){
    $records = $jdb->open('pages')->sortBy("id")->select();
    $page = 'records';
}elseif($_GET['page'] == 'form'){
    $page = 'form';
}else{
    $page = 'home';
}
/* use function */
function limit($str, $int, $end=null){
    $arraylimit = explode(" ", $str);
    $arraylimit = array_slice($arraylimit, 0, $int);
    return strip_tags( join(" ", $arraylimit) ) . $end ;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Jdb demo</title>
    <style type="text/css">
        body, html{ margin:0; padding:0; font-family: Arial, Verana; font-size: 12px; background-color: #333; }
        .wrapper{ width: 860px; margin: 0 auto;}
        .header{ height: 25px; text-align: center; padding: 10px; background-color:  #FFF; }
        .menu ul{ margin:0; padding:0; }
        .menu ul li{ display: inline; }
        .menu ul li a{ background: #686868; color: #FFF; display: inline-block; padding: 3px 20px; text-decoration: none; }
        .menu ul li a:hover{ color: #B2B2B2;  background: #404040; }
        .content{ padding: 10px; background-color: #FFF; margin: 10px 0; min-height: 400px; }
        .footer{ padding: 10px; background-color: #FFF; min-height: 40px; }
        .pageRecords .h{}
        .pageRecords .c{ height: 65px;}
        .pageRecords .c img{ float: left; padding-right: 5px; }
        .pageRecords .i{ color: #747474; background-color: #DDD; padding: 3px 5px;}
        .btn_remove{ padding: 3px 5px; text-align: right;}
        .btn_remove a{ text-decoration: none; color:#AD0000; font-weight: bold; }
        .btn_remove a:hover{ text-decoration: underline; color:#CC9526;  }
		hr{ height: 2px; border:none; background-color: #333; margin-bottom: 15px;}
		h1 a{ font-size: 16px; }
    </style>
</head>
<body>

    <div class="wrapper">
        <div class="header">
            <div class="menu">
                <ul>
                    <li><a href="?page=home">Home</a></li>
                    <li><a href="?page=records">Records</a></li>
                    <li><a href="?page=form">Form</a></li>
                </ul>
            </div>
        </div>
        <div class="content">

        
        
            <?php if($page=='home'): ?>
            <div class="pageHome">
                <h1 class="h"> <?php echo $recordsRead['title'] ?>   </h1>
                <div class="c"> <?php echo nl2br(htmlspecialchars( $recordsRead['content'] )); ?> </div>
                <div class="i"> <?php echo $recordsRead['author'] ?> | <?php echo $recordsRead['date'] ?> | <a href="?page=form&edit=<?php echo $recordsRead['id'] ?>">Редактировать</a> </div>
				<hr>
            </div>
            <?php endif; ?>


            
            <?php if($page=='form'): ?>
            <div class="pageFORM">
                <form action="" method="post">
                    <?php if(!empty($recordsEdit[0]['title']) || $recordsEdit[0]['content']): ?>
                    <div class="row btn_remove">
                        <a href="./index.php?edit=<?php echo $recordsEdit[0]['id']; ?>&remove=true" onclick="return confirm('Удалить?') ? true : false;">Удалить запись</a>
                    </div>
                    <?php endif; ?>
                    <div class="row">
                        <label>title</label><br>
                        <input style="width: 280px; display:block;" type="text" name="title" value="<?php echo $recordsEdit[0]['title']; ?>">
                    </div>

                    <div class="row">
                        <label>image</label><br>
                        <input style="width: 280px; display:block;" type="text" name="image" value="<?php echo $recordsEdit[0]['image']; ?>">
                    </div>

                    <div class="row" style="width: 110px; display:block;">
                        <label>status</label><br>
                        <select name="status">
                            <option <?php echo ($recordsEdit[0]['status']=='private')?'selected':'';?> value="private">private</option>
                            <option <?php echo ($recordsEdit[0]['status']=='public') ?'selected':'';?> value="public" select="select">public</option>
                            <option <?php echo ($recordsEdit[0]['status']=='static') ?'selected':'';?> value="static">static</option>
                        </select>
                    </div>

                    <div class="row">
                        <label></label><br>
                        <textarea name="content" style="width: 835px; min-width:835px; max-width:835px; min-height: 300px"><?php echo $recordsEdit[0]['content']; ?></textarea>
                    </div>

                    <div class="row">
                        <br>
                        <input type="text" hidden="hidden" name="id" value="<?php echo $recordsEdit[0]['id']; ?>">
                        <input type="submit" value="Seve Query">
                    </div>

                </form>
            </div>
            <?php endif; ?>

            
            <?php if($page=='records'): ?>
            <div class="pageRecords">

                <?php foreach($records as $record): ?>
                <h1 class="h">  <a href="?page=home&read=<?php echo $record['id'] ?>"><?php echo $record['title'] ?></a>   </h1>
                <div class="c">
                    <img src="images/<?php echo $record['image'] ?>" width="64" height="64" alt="">
                    <?php echo limit( htmlspecialchars( $record['content'] ), 45, ' ... <a href="?page=home&read='.$record['id'].'">дальше</a>'); ?>
                </div>
                <div class="i"> <?php echo $record['author'] ?> | <?php echo $record['date'] ?> | <a href="?page=form&edit=<?php echo $record['id'] ?>">Редактировать</a> </div>
				<hr>
                <?php endforeach; ?>


            </div>
            <?php endif; ?>
            

        </div>
        <div class="footer"> Время генерациии составило <?php echo round(microtime(true)-START_TIME,4); ?> сек.</div>
    </div>

</body>
</html>