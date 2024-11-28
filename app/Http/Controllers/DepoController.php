<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DepoRealtime;

class DepoController extends Controller
{
    public function depo() {
        // depo_statusが0ではない行を取得
        $items = User::where('depo_status', '!=', 0)
            ->select('member_code', 'name', 'depo_status')
            ->orderBy('depo_status', 'DESC')
            ->get();
        // ビューにデータを渡す
        return view('depo-home', compact('items'));
    }
    
    public function detail($member_code){
        $user = User::where('member_code', $member_code)
            ->select('member_code', 'name', 'depo_status')
            ->first();
        $details = DepoRealtime::with('product')
            ->where('member_code', $member_code)
            ->get();
        
        return view('depo-detail', compact('user', 'details'));
    }
}
