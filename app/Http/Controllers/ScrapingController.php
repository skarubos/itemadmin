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
// use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\DomCrawler\Crawler;

class ScrapingController extends Controller
{
    // FunctionsControllerのメソッドを$this->functionsで呼び出せるようにする
    protected $functionsController;
    public function __construct(FunctionsController $functionsController)
    {
        $this->functions = $functionsController;
    }

    public function testScrape(Request $request) {
        // $month = '202412';
        $month = $request->input('month');
        $cookieValue = $request->input('cookie') ?? null;
        
        DB::beginTransaction();
        try {
            $howMany = $this->scrape($month, $cookieValue);
// dd($howMany);
            DB::commit();
            RefreshLog::create(['method' => 'scrape', 'caption' => '新規取引の取得', 'status' => 'success', 'error_message' => '登録件数：' . $howMany]);
            return redirect()->route('admin')
                ->with('success', '新規取引取得（scrape）が正常に行われました。登録件数：' . $howMany);
        } catch (Exception $e) {
            DB::rollBack();
            $error_message = explode("\n", $e->getMessage())[0];
            RefreshLog::create(['method' => 'scrape', 'caption' => '新規取引の取得', 'status' => 'failure', 'error_message' => $error_message]);
            \Log::error('新規取引の取得(scrape)に失敗: ' . $e->getMessage());
            return back()->withErrors(['error' => '新規取引取得（scrape）に失敗しました。', 'エラーログ保存先：\storage\logs\laravel.log']);
        }
    }
    public function scrape($month, $cookieValue) {
    // public function testScrape(Request $request) {
    //     $month = $request->input('month');

        $url_base = config('secure.url_base');
        $url_login = config('secure.url_login');
        $url_list = config('secure.url_list');
        $domain = config('secure.domain');
// dd($url_base);

        $client = new Client([
            'base_uri' => $url_base,
            'cookies' => true,
            'allow_redirects' => true,
        ]);

        $cookieName = 'laravel_session';

        // クッキーが入力されていなければ、ログインして取得
        if (!$cookieValue) {
            $response = $client->get($url_login);
            $html = $response->getBody()->getContents();
    // dd($html);
            $crawler = new Crawler($html);
            $token = $crawler->filter('input[name="_token"]')->attr('value');
    // dd($token);
            
            // ログインリクエストを送信
            $response = $client->post($url_login, [
                'form_params' => [
                    'dairiten_cd' => config('secure.code'),
                    'password' => config('secure.password'),
                    '_token' => $token,
                ],
            ]);
    
            // ログイン成功を確認
            if ($response->getStatusCode() !== 200) {
                throw new Exception('ログインに失敗しました。');
            }
    
            // Set-Cookieヘッダーからクッキーを取得
            $setCookieHeader = $response->getHeader('Set-Cookie');
            $cookieParts = explode(';', $setCookieHeader[0]);
            $cookieKeyValue = explode('=', $cookieParts[0]);
            $cookieName = $cookieKeyValue[0];
            $cookieValue = $cookieKeyValue[1];
        }
 
        // Secure属性をtrueにしてクッキーを作成
        $setCookie = new SetCookie([
            'Name' => $cookieName,
            'Value' => $cookieValue,
            'Domain' => $domain,
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
            // 'form_params' => [ 'nentuki' => $month, ]
        ]);
        $html = $response->getBody()->getContents();
// dd($month,$cookies,$html);

        // DomCrawlerでHTMLを解析
        $crawler = new Crawler($html);

        // titleタグを取得
        $title = $crawler->filter('h3.title')->text();
        if (str_contains($title, 'ログイン')) {
            throw new Exception("ログインページにリダイレクトされました。");
        } elseif (!str_contains($title, '月間取引一覧')) {
            throw new Exception("「月間取引一覧」ページを取得できませんでした");
        }

        // table-hoverのtbodyが存在しない場合（月間取引なしの場合）
        if ($crawler->filter('tbody.table-hover')->count() == 0) {
            return "今月の取引は存在しませんでした。";
        }
// dd($title, $html);

        // 新規取引のリストを取得
        $newTrade = $this->functions->getNewTrade($crawler, $month);
        if ($newTrade === []) {
            return "未登録の取引は存在しませんでした。";
        }
// dd($newTrade);

        // 新規取引それぞれの取引詳細を取得、DBに保存
        $detailsArr = [];
        foreach ($newTrade as $trade) {
            // 取引詳細ページにアクセス
            $response = $client->get($trade['link'], [
                'cookies' => $jar,
            ]);
            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);

            // 取引詳細をHTMLから取得
            $details = $this->functions->getTradeData($crawler, $trade['type']);
            
            // 取引をDBに保存
            $trade['status'] = 2; // （status=2:確認が必要な取引）
            $this->functions->update_trade(null, $trade, $details, 1);

            $detailsArr[] = $details;
        }
// dd($cookieValue,$newTrade,$detailsArr);

        return count($newTrade);
        // return $cookieValue;
    }



//     // Node.jsスクリプトファイル'resources/js/getWeb.js'を用いたスクレイピングメソッド(仮)
//     public function testScrape_getWeb(Request $request)
//     {
//         $month = $request->input('month'); // フォームからの月の値を取得
//         $dairiten_cd = config('secure.code');
//         $password = config('secure.password');
        
//         $process = new Process(['node', 'resources/js/getWeb.js', $dairiten_cd, $password, $month]);
//         $process->run();

//         // エラーチェック
//         if (!$process->isSuccessful()) {
//             throw new ProcessFailedException($process);
//         }

//         // 取得したHTMLを解析
//         $htmlContent = $process->getOutput();
//         $crawler = new Crawler($htmlContent);
// // dd($htmlContent);
//         // getNewTradeメソッドを利用してデータを整理・取得
//         $newTrade = $this->getNewTrade($crawler);
//     }
}
