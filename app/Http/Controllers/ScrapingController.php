<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\FunctionsController;
use App\Models\RefreshLog;
use Exception;

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
    
    public function testScrape() {
        DB::beginTransaction();
        try {
            $howMany = $this->scrape();
            DB::commit();
            RefreshLog::create(['method' => 'scrape', 'caption' => '新規取引の取得', 'status' => 'success', 'error_message' => '登録件数：' . $howMany]);
            return redirect()->route('admin')
                ->with('success', '新規取引取得（scrape）が正常に行われました。登録件数（' . $howMany . '）');
        } catch (Exception $e) {
            DB::rollBack();
            $error_message = explode("\n", $e->getMessage())[0];
            RefreshLog::create(['method' => 'scrape', 'caption' => '新規取引の取得', 'status' => 'failure', 'error_message' => $error_message]);
            \Log::error('新規取引の取得(scrape)に失敗: ' . $e->getMessage());
            return back()->withErrors(['error' => '新規取引取得（scrape）に失敗しました。', 'エラーログ保存先：\storage\logs\laravel.log']);
        }
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
//             throw new Exception('ログインに失敗しました。');
//         }

//         // Set-Cookieヘッダーからクッキーを取得
//         $setCookieHeader = $response->getHeader('Set-Cookie');
//         $cookieParts = explode(';', $setCookieHeader[0]);
//         $cookieKeyValue = explode('=', $cookieParts[0]);
//         $cookieName = $cookieKeyValue[0];
//         $cookieValue = $cookieKeyValue[1];



        $cookieName = 'laravel_session';
        $cookieValue = 'eyJpdiI6IjVMOHhcLzM3KytJSWNhVzhJRExmbFN3PT0iLCJ2YWx1ZSI6IkNkV0dQUXYrdjA4amlqSGZqUnNpeUNLNEhwMnFiM0YrOHpmM3F2QnNOY1d3YUhxQ1BNSG55TWNXcDdrdFM4WVlkS0pBeGJ6T3BDT0xuOFwvSmMrNElkdz09IiwibWFjIjoiZDcyZWJiMjA1NTE5MjJiMGE3ZGNjYzY1NDMwM2E0MjBlNmYyNmM2ZDQyNmM1YmZhOWU4OWMxNDBiNTVlMTJjNiJ9';

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
        $cookies = $jar->toArray(); // 現在のクッキー情報を取得（デバック表示用）
// dd($cookies);

        // 「月間取引一覧」ページにアクセス
        $response = $client->get($url_list, [
            'cookies' => $jar,
        ]);
        $html = $response->getBody()->getContents();
// dd($url_list,$cookies,$html);

        // DomCrawlerでHTMLを解析
        $crawler = new Crawler($html);

        // titleタグを取得
        $title = $crawler->filter('h3.title')->text();
        if (str_contains($title, 'ログイン')) {
            throw new Exception("ログインページにリダイレクトされました。");
        } elseif (!str_contains($title, '月間取引一覧')) {
            throw new Exception("「月間取引一覧」ページを取得できませんでした");
        }
// dd($title);

        // table-hoverのtbodyが存在しない場合（月間取引なしの場合）
        if ($crawler->filter('tbody.table-hover')->count() == 0) {
            return "今月の取引は存在しませんでした。";
        }
// dd($html);

        // 新規取引のリストを取得
        $newTrade = $this->getNewTrade($crawler);

        if ($newTrade === []) {
            return "未登録の取引は存在しませんでした。";
        }
        
        $detailsArr = [];
        foreach ($newTrade as $trade) {
            // 取引詳細ページにアクセス
            $response = $client->get($trade['link'], [
                'cookies' => $jar,
            ]);

            // 取引データをHTMLから取得
            $details = $this->getTradeData($response);
            
            // 取引データをデータベースに保存
            $trade['status'] = 2;
            $this->functionsController->update_trade(null, $trade, $details, 1);

            $detailsArr[] = $details;
        }
// dd($cookieValue,$newTrade,$detailsArr);

        return count($newTrade);
        // return $cookieValue;

        // return $data = [
        //     'newTrade' => $newTrade,
        //     'details' => $details,
        // ];
    }

    public function getNewTrade($crawler) {
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
// dd($tradeData);
        // sales, in, outの3つ全てに値が存在しない要素をフィルタリング
        $tradeData = array_filter($tradeData, function($row) {
            return !empty($row['sales']) || !empty($row['in']) || !empty($row['out']);
        });
        // 'link'から'jutyuno'を抽出して追加
        foreach ($tradeData as &$row) {
            $row['check_no'] = $this->functionsController->getNo($row['link']);
        }
// dd($tradeData);

        // 現在の月と一致する取引のcheck_noカラムの値を取得
        $arr = $this->functionsController->getJutyunoArr();
        
        // 未登録の取引のみ抽出し、member_code,amount,trade_typeを設定
        $newTrade = [];
        foreach ($tradeData as &$row) {
            if (in_array($row['check_no'], $arr)) {
                continue;
            }
            // member_codeの取得
            $row['member_code'] = $this->functionsController->getMemberCode($row['name']);
            // amountとtrade_typeの設定
            $this->functionsController->setTradeAttributes($row);
            $newTrade[] = $row;
        }
// dd($arr,$tradeData, $newTrade);
        return $newTrade;
    }

    public function getTradeData($response) {
        $html = $response->getBody()->getContents();

        // DomCrawlerでHTMLを解析
        $crawler = new Crawler($html);

        // tableの内容を取得
        $details = [];
        $crawler->filter('tbody.table-hover')->first()->filter('tr')
        ->each(function ($node) use (&$details) {
            if (!($node->filter('th')->eq(0)->count() > 0)) {
                $row = [
                    'name' => $node->filter('td')->eq(0)->text(),
                    'sales' => $node->filter('td')->eq(1)->text(),
                    'in' => $node->filter('td')->eq(2)->text(),
                    'out' => $node->filter('td')->eq(3)->text(),
                ];
                
                // nameからmember_codeを取得
                $row['product_id'] = $this->functionsController->getProductId($row['name']);
                
                // amountキーの作成
                $values = array_filter([
                    'sales' => $row['sales'],
                    'in' => $row['in'],
                    'out' => $row['out']
                ], function($value) {
                    return !empty($value);
                });
                if (count($values) > 1) {
                    throw new Exception('２種類以上の取引タイプが１つの取引に含まれています。');
                } elseif (count($values) === 1) {
                    $row['amount'] = reset($values); // 存在する値を$row['amount']に格納
                } else {
                    $row['amount'] = null; // 値が存在しない場合はnullを設定
                }
                $details[] = $row;
            }
        });
        return $details;
    }
}
