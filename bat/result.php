<?php

require_once "../core/init.php";
db::_init_mysql();
$info = [
    '肉类' => 3,
    '奶类' => 4,
    '酒类' => 2
];

function saveToDb($label, $md5url)
{
    $table = 'cs_article_search';
    $data = [
        'label' => $label,
    ];
    $where = [
        "article_md5url = '{$md5url}'"
    ];
    var_dump(db::update($table, $data, $where));
    echo "$md5url : $label". PHP_EOL;
}

function saveInfo($path, $label)
{
    $dh = opendir($path);//打开目录
    while(($d = readdir($dh)) != false){
        //逐个文件读取，添加!=false条件，是为避免有文件或目录的名称为0
        if($d=='.' || $d == '..'){ //判断是否为.或..，默认都会有
            continue;
        }
        $md5url = substr($d, 0, -4);
//        echo $md5url. '<br />';
        saveToDb($label, $md5url);
        if(is_dir($path.'/'.$d)){ //如果为目录
            continue;
        }
    }
}

$path1 = '../../classify.search.teach-food.com/酒类/'; //文本路径
$path1 = iconv("UTF-8","gb2312",$path1);
saveInfo($path1, $info['酒类']);

$path2 = '../../classify.search.teach-food.com/奶类/'; //文本路径
$path2 = iconv("UTF-8","gb2312",$path2);
saveInfo($path2, $info['奶类']);

$path3 = '../../classify.search.teach-food.com/肉类/'; //文本路径
$path3 = iconv("UTF-8","gb2312",$path3);
saveInfo($path3, $info['肉类']);