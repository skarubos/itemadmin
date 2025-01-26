<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        View::composer('*', function ($view) {
            $user = Auth::user();
            $navigationArray = [];
    
            if ($user) {
                $memberCode = $user->member_code;
                $menus = [];
    
                if ($user->permission == 1) {
                    // 管理者ユーザー向けメニュー
                    $menus = [
                        [
                            'href' => '/sales',
                            'active' => 'sales*',
                            'text' => '実績',
                        ],
                        [
                            'href' => '/depo',
                            'active' => 'depo*',
                            'text' => '預け',
                        ],
                        [
                            'href' => '/summary',
                            'active' => 'summary*',
                            'text' => '会計',
                        ],
                        [
                            'href' => '/admin',
                            'active' => 'admin*',
                            'text' => '管理用',
                        ],
                        [
                            'href' => '/upload',
                            'active' => 'trade*',
                            'text' => '取引登録',
                        ],
                        [
                            'href' => '/setting',
                            'active' => 'setting*',
                            'text' => '設定',
                        ],
                    ];
                } elseif ($user->permission == 2) {
                    // 一般ユーザー向けメニュー
                    $menus = [
                        [
                            'href' => "/sales/member/{$memberCode}",
                            'active' => 'sales*',
                            'text' => '注文履歴',
                        ],
                        [
                            'href' => "/depo/member/{$memberCode}",
                            'active' => 'depo*',
                            'text' => '預け',
                        ],
                    ];
    
                    // サブリーダーの場合、追加のメニューを表示
                    if ($user->sub_leader != 0) {
                        $menus[] = [
                            'href' => "/sub/{$memberCode}",
                            'active' => 'sub*',
                            'text' => '資格手当',
                        ];
                    }
                }
    
                $navigationArray = $menus;
            }
    
            $view->with('navigationArray', $navigationArray);
        });
    }    
}
