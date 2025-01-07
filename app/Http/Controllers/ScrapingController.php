<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\FunctionsController;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Cookie\SessionCookieJar;
use Symfony\Component\DomCrawler\Crawler;

class ScrapingController extends Controller
{
    // FunctionsControllerのメソッドを$this->functionsControllerで呼び出せるようにする
    protected $functionsController;
    public function __construct(FunctionsController $functionsController)
    {
        $this->functionsController = $functionsController;
    }
    
    public function scrape() {

        $url_base = config('secure.url_base');
        $url_login = config('secure.url_login');
        $url_list = config('secure.url_list');
// dd($url_base);

        $client = new Client([
            'base_uri' => $url_base,
            'cookies' => true,
            'allow_redirects' => true,
        ]);



//         $response = $client->get($url_login);
// // dd($response);
//         $html = $response->getBody()->getContents();
// // dd($html);
//         $crawler = new Crawler($html);
//         $token = $crawler->filter('input[name="_token"]')->attr('value');
// // dd($token);
        
//         // ログインリクエストを送信
//         $response = $client->post($url_login, [
//             'form_params' => [
//                 'dairiten_cd' => config('secure.code'),
//                 'password' => config('secure.password'),
//                 '_token' => $token,
//             ],
//         ]);

//         // ログイン成功を確認
//         if ($response->getStatusCode() !== 200) {
//             die('ログインに失敗しました。');
//         }

//         // Set-Cookieヘッダーからクッキーを取得
//         $setCookieHeader = $response->getHeader('Set-Cookie');
//         $cookieParts = explode(';', $setCookieHeader[0]);
//         $cookieKeyValue = explode('=', $cookieParts[0]);
//         $cookieName = $cookieKeyValue[0];
//         $cookieValue = $cookieKeyValue[1];



        $cookieName = 'laravel_session';
        $cookieValue = 'eyJpdiI6Iit0TCszTk15aFRIQVZCN1BKeUxUSHc9PSIsInZhbHVlIjoiR3ZJVTlBNFBVcDd6a1pNQVh4Vk8wdkVFQUdLNVBIOWxDMmhaaWZZa0V3MFNWUFhBZ3VsQXlsdkc2VTVOcWJWdjdic3grXC9Xb0c2aVRuMHJIdk5qamp3PT0iLCJtYWMiOiIyYTIwNmNkZmI1MmEyNjUxYzE4NDE5YjAyMTNiNDU1M2Q5ZGU3MjgyZDY3MmQzNzQ4MjQ2Y2JiNjY3NzAyYWNmIn0%3D';

        // Secure属性をtrueにしてクッキーを作成
        $setCookie = new SetCookie([
            'Name' => $cookieName,
            'Value' => $cookieValue,
            'Domain' => config('secure.domain'),
            'Path' => '/',
            'Secure' => true,
            'HttpOnly' => true,
        ]);

        // CookieJarにクッキーを追加
        $jar = new CookieJar();
        $jar->setCookie($setCookie);

        // 現在のクッキー情報を取得
        $cookies = $jar->toArray();
// dd($cookies);

        // ログイン後のページにアクセス
        $response = $client->get($url_list, [
            'cookies' => $jar,
        ]);
        $html = $response->getBody()->getContents();
        
// dd($url_list,$cookieValue,$html);


        // DomCrawlerでHTMLを解析
        $crawler = new Crawler($html);

        // titleタグを取得
        $title = $crawler->filter('h3.title')->text();
        if (str_contains($title, 'ログイン')) {
            die("ログインページにリダイレクトされました。");
        }
        // tableの内容を取得
        $tradeData = [];
        $crawler->filter('tbody.table-hover tr')
            ->each(function ($node) use (&$tradeData) {
                $row = [
                    'link' => $node->filter('td')->eq(1)->filter('a')->attr('href'),
                    'date' => $node->filter('td')->eq(3)->text(),
                    'name' => $node->filter('td')->eq(6)->text(),
                    'sales' => $node->filter('td')->eq(7)->text(),
                    'in' => $node->filter('td')->eq(8)->text(),
                    'out' => $node->filter('td')->eq(9)->text(),
                ];
                $tradeData[] = $row;
            });
        // sales, in, outの3つ全てに値が存在しない要素をフィルタリング
        $tradeData = array_filter($tradeData, function($row) {
            return !empty($row['sales']) || !empty($row['in']) || !empty($row['out']);
        });
        // 'link'から'jutyuno'を抽出して追加
        foreach ($tradeData as &$row) {
            $row['check_no'] = $this->functionsController->getNo($row['link']);
        }
// dd($title,$cookieValue,$tradeData,$html);

        // 現在の月と一致する取引のcheck_noカラムの値を取得
        $arr = $this->functionsController->getJutyunoArr();
        
        // 未登録の取引のみ抽出し、member_code,amount,trade_typeを設定
        $newTrade = [];
        foreach ($tradeData as $row) {
            if (in_array($row['check_no'], $arr)) {
                continue;
            }
            // member_codeの取得
            $row['member_code'] = $this->functionsController->getMemberCode($row['name']);
            // amountとtrade_typeの設定
            $this->functionsController->setTradeAttributes($row);
            $newTrade[] = $row;
        }
        if ($newTrade === []) {
            throw new \Exception("未登録の取引は存在しませんでした。");
        }
        
        $details = [];
        foreach ($newTrade as $trade) {
            // 取引詳細ページにアクセス
            $response = $client->get($trade['link'], [
                'cookies' => $jar,
            ]);
            $html = $response->getBody()->getContents();
// dd($html);

            // DomCrawlerでHTMLを解析
            $crawler = new Crawler($html);

            // tableの内容を取得
            $detail = [];
            $crawler->filter('tbody.table-hover')->first()->filter('tr')
            ->each(function ($node) use (&$detail) {
                if (!($node->filter('th')->eq(0)->count() > 0)) {
                    $row = [
                        'name' => $node->filter('td')->eq(0)->text(),
                        'sales' => $node->filter('td')->eq(1)->text(),
                        'in' => $node->filter('td')->eq(2)->text(),
                        'out' => $node->filter('td')->eq(3)->text(),
                    ];
                    
                    // nameからmember_codeを取得
                    $row['product_id'] = $this->functionsController->getProcuctId($row['name']);
                    
                    // amountキーの作成
                    $values = array_filter([
                        'sales' => $row['sales'],
                        'in' => $row['in'],
                        'out' => $row['out']
                    ], function($value) {
                        return !empty($value);
                    });
                    if (count($values) > 1) {
                        throw new \Exception('２種類以上の取引タイプが１つの取引に含まれています。');
                    } elseif (count($values) === 1) {
                        $row['amount'] = reset($values); // 存在する値を$row['amount']に格納
                    } else {
                        $row['amount'] = null; // 値が存在しない場合はnullを設定
                    }
                    $detail[] = $row;
                }
            });
            
            $this->functionsController->update_trade(null, $trade, $detail, 1);

            $details[] = $detail;
        }

        return count($newTrade);

// dd($cookieValue,$tradeData,$arr,$newTrade,$details);

        // return $data = [
        //     'newTrade' => $newTrade,
        //     'details' => $details,
        // ];
    }
}
