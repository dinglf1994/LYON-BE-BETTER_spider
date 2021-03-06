<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

define('APP_PATH', dirname(__FILE__));
define('DATA_PATH', APP_PATH. '/../../search.tech-food.com/data/');

$pathinfo = pathinfo(DATA_PATH.'readme.md');
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
$w = '奶制品';
$word = requests::$searchWorld = urlencode($w);

$configs = array(
    'name' => "中国食品搜索-{$w}",
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
        "http://search.tech-food.com/ns.aspx\?q=$word&t=p&l=c&start=\d+",
    ),
    'content_url_regexes' => array(
        "http://www.tech-food.com/news/detail/\.*",
    ),
    'export' => array(
        'type' => 'db',
        'table' => 'cs_article_search',
    ),
    'fields' => array(
        // 时间
        array(
            'name' => "article_pubtime_str",
            'selector' => "//div[@class='biaoti1x']",
            'required' => false,
            'repeated'	=>	true,
        ),
        // 来源
        array(
            'name' => "article_source",
            'selector' => "//div[@class='biaoti1x']",
            'required' => false,
            'repeated'	=>	true,
        ),
        // 编辑
        array(
            'name' => "article_author",
            'selector' => "//div[@class='dibu_sr']",
            'required' => false,
            'repeated'	=>	true,
        ),
        // 标题
        array(
            'name' => "article_title",
            'selector' => "//div[@class='biaoti1']/h1",
            'required' => false,
            'repeated'	=>	true,
        ),
        // 新闻内容
        array(
            'name' => "article_content",
            'selector' => "//div[@id='zoom']/p",
            'required' => true,
            'repeated'	=>	true,
        ),
    ),
);

$spider = new phpspider($configs);

$spider->on_scan_page = function($page, $content, $phpspider)
{
    $word = requests::$searchWorld;
    // 添加列表页
    $i = 0;
    $j = 0;
    $stop = true;
    while ($i < 10) {
        $j = $i * 10;
        $url = "http://search.tech-food.com/ns.aspx?q=$word&t=p&l=c&start={$j}";
        $phpspider->add_url($url);
        $i++;
    }
};
$spider->on_list_page = function ($page, $content, $phpspider)
{
    // 提取内容页
    if (preg_match("#&t=p&l=c&start=#i", $page['request']['url'])) {
        $content_list = selector::select($content, "//div[@class='ly_ns1']/a/@href");
//        var_dump($content_list);
        foreach ($content_list as $url) {
            $phpspider->add_url($url);
        }
    }
};
$spider->on_extract_field = function ($fieldname, $data, $page)
{
    // 去除内容页的html标签
    $contentSave = '';
    if ($fieldname == 'article_author') {
        $data = str_replace('责任编辑：', '', $data);
        return $data;
    }
    if ($fieldname == 'article_source' || $fieldname == 'article_content' || $fieldname == 'article_title' || $fieldname == 'article_pubtime_str') {
        // 如果内容为空，判断为其他类型需另外
        if ($fieldname == 'article_pubtime_str') {
            preg_match('/\d{4}-\d+-\d+ \d+:\d+:\d+/', $data, $out);
            $data = $out;
        }
        if ($fieldname == 'article_source') {
            $data = str_replace('来源：', '', strstr($data, '来源：'));
        }
        if (is_array($data)) {
            foreach ($data as $value) {
                $contentSave .= strip_tags(html_entity_decode($value, ENT_QUOTES));
            }
        } else {
            $contentSave = strip_tags(html_entity_decode($data, ENT_QUOTES));
        }
        return $contentSave;
    } else {
        return strip_tags(html_entity_decode($data), ENT_QUOTES);
    }
};
$spider->on_extract_page = function($page, $fileds) {
    $fileds['article_url'] = $page['request']['url'];
    $fileds['article_md5url'] = md5($page['request']['url']);
    $fileds['label'] = 4;
	$data = $content = iconv("utf-8","gb2312//IGNORE",$fileds['article_content']);
    file_put_contents(DATA_PATH. $fileds['article_md5url']. '.txt', $data);
    return $fileds;
};
$spider->start();
