<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $requiredPermission, ?int $memberCodeRouteParam = null): Response
    {
        // ログインユーザーの情報を取得
        $userPermission = Auth::user()->permission;
        $userMemberCode = Auth::user()->member_code;
        $userSub = Auth::user()->sub_leader;

        // 管理者(permission=1)は常にアクセス許可
        if ($userPermission === 1) {
            return $next($request);
        }

        // 必要なpermissionレベルを満たしていない場合はアクセス拒否
        if ($userPermission > $requiredPermission) {
            abort(403, 'アクセス権限がありません。');
        }

        // ルートパラメータからmember_codeを取得
        $requestMemberCode = $request->route('member_code');

        // member_codeがルートパラメータとして存在し、かつログインユーザーのmember_codeと一致する場合アクセス許可
        if ($requestMemberCode !== null && $userMemberCode === (int)$requestMemberCode) {
            return $next($request);
        }

        // ルートが'sub_trade'の時、グループメンバーのmember_codeの場合はアクセス許可
        $currentRouteName = $request->route()->getName();
        if ($currentRouteName === 'sub.trade'){
            // ログインユーザーのsub_leaderとsub_numberが一致するユーザーを取得
            $groupUsers = User::where('sub_number', $userSub)
            ->select('member_code')
            ->get();

            // 取得したユーザーのmember_codeの配列を作成
            $groupMemberCodes = $groupUsers->pluck('member_code')->toArray();

            // ログインユーザーのmember_codeが、取得したユーザーのmember_codeのいずれかと一致する場合もアクセス許可
            if ($requestMemberCode !== null && in_array((int)$requestMemberCode, $groupMemberCodes)) {
                return $next($request);
            }
        }

        // 不正なアクセス（$requestMemberCodeがnullの時など）
        abort(403, 'アクセス権限がありません。(不明なアクセス)');
    }
}