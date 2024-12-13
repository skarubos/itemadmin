<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $requiredPermission, ?int $memberCodeRouteParam = null): Response
    {
        // ログインユーザーのpermissionを取得
        $userPermission = Auth::user()->permission;

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

        // member_codeがルートパラメータとして存在し、かつログインユーザーのmember_codeと一致しない場合アクセス拒否
        if ($requestMemberCode !== null && Auth::user()->member_code !== (int)$requestMemberCode) {
            abort(403, 'アクセス権限がありません。');
        }

        return $next($request);
    }
}