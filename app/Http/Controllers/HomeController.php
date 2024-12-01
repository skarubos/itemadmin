<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DepoRealtime;

class HomeController extends Controller
{
    public function sales_home() {
        // depo_statusが0ではない行を取得
        $users = User::where('status', 1)
            ->select('id', 'name', 'member_code', 'sales', 'sub_now')
            ->orderBy('priority', 'ASC')
            ->get();
        // ビューにデータを渡す
        return view('sales-home', compact('users'));
    }

    public function depo_home() {
        // depo_statusが0ではない行を取得
        $items = User::where('depo_status', '!=', 0)
            ->select('member_code', 'name', 'depo_status')
            ->orderBy('depo_status', 'DESC')
            ->get();
        // ビューにデータを渡す
        return view('depo-home', compact('items'));
    }
    
    public function depo_detail($member_code){
        $user = User::where('member_code', $member_code)
            ->select('member_code', 'name', 'depo_status')
            ->first();
        $details = DepoRealtime::with('product')
            ->where('member_code', $member_code)
            ->get();
        
        return view('depo-detail', compact('user', 'details'));
    }
}
