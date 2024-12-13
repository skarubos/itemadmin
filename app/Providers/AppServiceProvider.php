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
        // ログインユーザーのpermissionに基づいて配列データを共有する
        View::composer('*', function ($view) {
            $user = Auth::user();
                     
            $navigationArray = [];
            
            if ($user) {
                
                $href1 = ["/sales", "/depo", "/admin", "/upload"];
                $activ1 = ["sales_home", "depo_home", "admin", "upload"];
                $text1 = ["実績", "預け", "管理用", "取引登録"];
                $href22 = '/sales_detail/' . $user->member_code;
                $href2 = ["/dashboard", $href22];
                $activ2 = ["dashboard", "sales_detail"];
                $text2 = ["預け", "注文履歴"];

                if ($user->permission == 1) {
                    for ($i = 0; $i < count($href1); $i++) {
                        $navigationArray[] = [
                            'href' => $href1[$i],
                            'active' => $activ1[$i],
                            'text' => $text1[$i],
                        ];
                    }
                } elseif ($user->permission == 2) {
                    for ($i = 0; $i < count($href2); $i++) {
                        $navigationArray[] = [
                            'href' => $href2[$i],
                            'active' => $activ2[$i],
                            'text' => $text2[$i],
                        ];
                    }
                }
            }

            $view->with('navigationArray', $navigationArray);
        });
    }
}
