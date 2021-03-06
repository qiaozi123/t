<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use QL\Ext\Baidu;
use QL\QueryList;
use Symfony\Component\DomCrawler\Crawler;

class SearchController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index(Request $request)
    {
        set_time_limit(0);
//        $keyword ='seo';
//        $dohost = 'www.zhantengwang.com';
        $keyword = $request->input('keyword');
        $dohost = $request->input('dohost');
        $ql = QueryList::getInstance();
        $ql->use(Baidu::class);
        $baidu = $ql->baidu(10);
        $searcher = $baidu->search($keyword);
        $countPage = 10;  // 获取搜索结果总页数
        for ($page = 1; $page <= $countPage; $page++)
        {
            $data= $searcher->page($page);
            foreach ($data as $key=>$url){
                $reallink = $this->get_real_url($url['link']);
                if (strpos($reallink,$dohost) !== false){
                    if ($page==1){
                        $page=0;
                    }
                    $rank = $page*10+$key+1;
                    break ;
                }
            }
            if (!empty($rank)){
                break ;
            }
        }
        if (empty($rank)){
            return '100+';
        }else{
            return $rank;
        }

    }

    public function get_real_url($data)
    {
        $info = parse_url($data);
        $fp = fsockopen($info['host'], 80,$errno, $errstr, 30);
        fputs($fp,"GET {$info['path']}?{$info['query']} HTTP/1.1\r\n");
        fputs($fp, "Host: {$info['host']}\r\n");
        fputs($fp, "Connection: close\r\n\r\n");
        $rewrite = '';
        while(!feof($fp)) {
            $line = fgets($fp);
            if($line != "\r\n" ) {
                if(strpos($line,'Location:') !== false) {
                    $rewrite = str_replace(array("\r","\n","Location: "),'',$line);
                }
            }else {
                break;
            }
        }
        return $rewrite ;//输出http://tieba.baidu.com/p/1668410830
    }

    public function baidu_move(Request $request)
    {
        $keyword = urlencode($request->input('keyword'));
        $url = 'https://m.baidu.com/s?word='.$keyword.'&sa=vs_tab&sectab=video&isid=96DECE300483C5B315849&mod=0&async=1';
        $html = QueryList::get($url)->getHtml();
        $crawler = new Crawler($html);
        $nodeValues = $crawler->filter(' #results > div:nth-child')->each(function (Crawler $node, $i) {
            return $node->text();
        });
        dd($nodeValues);
        $res = $crawler->filter('#results')->html();
        dd($res);
    }




}
