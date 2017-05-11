<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

define('APP_PATH', dirname(__FILE__));
define('DATA_PATH', APP_PATH. '/../../search.tech-food.com/milk/');
define('HTML_PATH', DATA_PATH. '/html/');

$pathinfo = pathinfo(HTML_PATH.'readme.md');
if (!empty($pathinfo['dirname']))
{
    if (file_exists($pathinfo['dirname']) === false)
    {
        if (@mkdir($pathinfo['dirname'], 0777, true) === false)
        {
            echo '目录生成失败';
            exit;
        }
    }
}

// 测试
/*$html = requests::get("http://search.tech-food.com/ns.aspx?q=%E5%A5%B6%E5%88%B6%E5%93%81&t=a&l=c&start=0");
$html2 = requests::get("http://www.tech-food.com/news/detail/n0382341.htm");
file_put_contents(DATA_PATH. "search_test.html", $html);
file_put_contents(DATA_PATH. "search_content_test.html", $html2);
exit;*/
//测试
//$list = file_get_contents(DATA_PATH. "search_test.html");
//$content = selector::select($list, "//div[@class='ly_ns1']/a/@href");
//$list = file_get_contents(DATA_PATH. "search_content_test.html");
// 标题
//$content = selector::select($list, "//div[@class='biaoti1']/h1");
// 内容
//$content = selector::select($list, "//div[@id='zoom']/p[2]");
//var_dump($content);
//exit;

$configs = array(
    'name' => '中国食品搜索-奶制品',
    'tasknum' => 1,
    //'save_running_state' => true,
    'log_show' => false,
    'interval' => 100,
    'max_depth' => 1,
    'user_agents' => array(
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.11 Safari/537.36",
    ),
    'domains' => array(
        'search.tech-food.com',
    ),
    'scan_urls' => array(
        'http://search.tech-food.com',
    ),
    'list_url_regexes' => array(
        "http://search.tech-food.com/ns.aspx\?q=%E5%A5%B6%E5%88%B6%E5%93%81&t=a&l=c&start=\d+",
    ),
    'content_url_regexes' => array(
        "http://www.tech-food.com/news/detail/\.*",
    ),
    'export' => array(
        'type' => 'txt',
        'file' => DATA_PATH,
    ),
    'fields' => array(
        // 标题
        array(
            'name' => "title",
            'selector' => "//div[@class='biaoti1']/h1",
            'required' => true,
            'repeated'	=>	true,
        ),
        // 新闻内容
        array(
            'name' => "content",
            'selector' => "//div[@id='zoom']/p",
            'required' => true,
            'repeated'	=>	true,
        ),
    ),
);

$spider = new phpspider($configs);

$spider->on_scan_page = function($page, $content, $phpspider)
{
    // 添加列表页
    $i = 0;
    $j = 0;
    $stop = true;
    while ($i < 1001) {
        $j = $i * 10;
        $url = "http://search.tech-food.com/ns.aspx?q=%E5%A5%B6%E5%88%B6%E5%93%81&t=a&l=c&start={$j}";
        $phpspider->add_url($url);
        $i++;
    }
};
$spider->on_list_page = function ($page, $content, $phpspider)
{
    // 提取内容页
    if (preg_match("#&t=a&l=c&start=#i", $page['request']['url'])) {
        $content_list = selector::select($content, "//div[@class='ly_ns1']/a/@href");
//        var_dump($content_list);
        foreach ($content_list as $url) {
            $phpspider->add_url($url);
        }
    }
};
$spider->on_download_page = function ($page, $phpspider)
{
    $id = requests::$id;
	$fieldname = md5($page['request']['url']);
    if (!preg_match("#http://search.tech-food.com/ns.aspx\?q=%E5%A5%B6%E5%88%B6%E5%93%81&t=a&l=c&start=\d+#i", $page['request']['url'])) {
        file_put_contents(HTML_PATH. $fieldname . '.html', $page['raw']);
        requests::$id++;
    }
    return $page;
};
$spider->on_extract_field = function ($fieldname, $data, $page)
{
    // 去除内容页的html标签
    $num = requests::$getTime;
    $contentSave = '';
    if ($fieldname == 'content') {
        if (is_array($data)) {
            foreach ($data as $value) {
                $contentSave .= strip_tags($value);
            }
        } else {
            $contentSave = strip_tags($data);
        }
//        file_put_contents(DATA_PATH. "cnfood_{$num}_content.txt", $contentSave);
        requests::$getTime++;
        return $contentSave;
    } else {
        return $data;
    }
};
$spider->start();
