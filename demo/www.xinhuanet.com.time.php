<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

define('APP_PATH', dirname(__FILE__));
define('DATA_PATH', APP_PATH. '/../../search.tech-food.com/');
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
//$html = file_get_contents(DATA_PATH. "search_test.html");
//$arr = json_decode($html, true);
//var_dump(array_slice($arr, 0, 3, true));exit;
//var_dump($arr['data']['list'][0]['LinkUrl']);exit;
//$html = substr(requests::get("http://qc.wa.news.cn/nodeart/list?nid=11109464&pgnum=1&cnt=10&tp=1&orderby=1"), 1, -1);

//$html2 = requests::get("http://news.xinhuanet.com/food/2017-05/05/c_1120921237.htm");
//file_put_contents(DATA_PATH. "search_test.html", $html);
//file_put_contents(DATA_PATH. "search_content_test.html", $html2);
//exit;
//测试
/*$list = file_get_contents(DATA_PATH. "search_content_test.html");
// 标题
$content[] = selector::select($list, "//div[@class='h-title']");
$content[] = selector::select($list, "//span[@class='h-time']");
$content[] = selector::select($list, "//em[@id='source']");
$content[] = selector::select($list, "//div[@id='p-detail']/p");
var_dump($content);exit;*/


$configs = array(
    'name' => '新华网-食品数据',
    'tasknum' => 1,
    //'save_running_state' => true,
    'log_show' => false,
    'interval' => 100,
    'max_depth' => 1,
    'user_agents' => array(
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.11 Safari/537.36",
    ),
    'domains' => array(
        'www.news.cn',
        'qc.wa.news.cn',
        'news.xinhuanet.com',
    ),
    'scan_urls' => array(
        'http://www.news.cn/food/rd.htm',
    ),
    'list_url_regexes' => array(
        "http://qc.wa.news.cn/nodeart/list\?nid=11109464&pgnum=\d+&cnt=10&tp=1&orderby=1",
    ),
    'content_url_regexes' => array(
        "http://news.xinhuanet.com/food/\.*",
    ),
    'export' => array(
        'type' => 'db',
        'table' => 'cs_article_search',
    ),
    'fields' => array(
        // 标题
        array(
            'name' => "article_title",
            'selector' => "//div[@class='h-title']",
            'required' => false,
            'repeated'	=>	true,
        ),
        // 时间
        array(
            'name' => "article_pubtime_str",
            'selector' => "//span[@class='h-time']",
            'required' => false,
            'repeated'	=>	true,
        ),
        // 来源
        array(
        'name' => "article_source",
        'selector' => "//em[@id='source']",
        'required' => false,
        'repeated'	=>	true,
        ),
        // 编辑
        array(
            'name' => "article_author",
            'selector' => "//span[@class='p-jc']",
            'required' => false,
            'repeated'	=>	true,
        ),
        // 新闻内容
        array(
            'name' => "article_content",
            'selector' => "//div[@id='p-detail']/p",
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
    $stop = true;
    while ($i < 10) {
        $url = "http://qc.wa.news.cn/nodeart/list?nid=11109464&pgnum={$i}&cnt=10&tp=1&orderby=1";
        $phpspider->add_url($url);
        $i++;
    }
};
$spider->on_list_page = function ($page, $content, $phpspider)
{
    // 提取内容页
    if (preg_match("#&cnt=10&tp=1&orderby=1#i", $page['url'])) {
        $content = substr($content, 1, -1);
        $content_list = json_decode($content, true);
        foreach ($content_list['data']['list'] as $item) {
            $phpspider->add_url($item['LinkUrl']);
        }
    }
};
$spider->on_extract_field = function($fieldname, $data, $page)
{
    // 去除内容页的html标签
    $contentSave = '';
    if ($fieldname == 'article_author') {
        $data = str_replace('：', '', substr(util::trimall(strip_tags(html_entity_decode($data), ENT_QUOTES)), -9));
        return $data;
    }
    if ($fieldname == 'article_source' || $fieldname == 'article_content' || $fieldname == 'article_title' || $fieldname == 'article_pubtime_str') {
        // 如果内容为空，判断为其他类型需另外
        if ($fieldname == 'article_title' && empty($data)) {
            $data = html_entity_decode(selector::select($page['raw'], "//h1[@id='title']"), ENT_QUOTES);
        }
        if ($fieldname == 'article_content' && empty($data)) {
            $data = html_entity_decode(selector::select($page['raw'], "//div[@class='article']/p/span"), ENT_QUOTES);
        }
        if ($fieldname == 'article_pubtime_str' && empty($data)) {
            $data = html_entity_decode(selector::select($page['raw'], "//span[@class='time']"), ENT_QUOTES);
        }
        if ($fieldname == 'article_source' && empty($data)) {
            $data = html_entity_decode(selector::select($page['raw'], "//div[@class='h-info']"), ENT_QUOTES);
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
    $fileds['label'] = 0;
    file_put_contents(DATA_PATH. $fileds['article_md5url']. '.txt', $fileds['article_content']);
    return $fileds;
};
$spider->start();
