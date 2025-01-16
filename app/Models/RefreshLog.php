<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefreshLog extends Model
{
    use HasFactory;

    protected $table = 'refresh_logs';

    protected $fillable = ['method', 'caption', 'status', 'error_message'];

    /**
     * 最終更新ログ（メソッドごと）を取得するメソッド
     *
     * @param string|array $methods メソッド名、またはその配列
     * @param bool $onlySuccess 成功したログのみを取得するかどうかのフラグ
     * @return Model|array 最新のログのメソッド名をキーとした連想配列
     */
    public static function getLastUpdate($methods, $onlySuccess = false)
    {
        // ログの取得ロジックをコールバック関数として定義し、それを使ってクエリを実行
        $query = function($method) use ($onlySuccess) {
            $q = self::where('method', $method);
            if ($onlySuccess) {
                $q->where('status', 'success');
            }
            return $q->orderBy('created_at', 'DESC')->first();
        };

        // 単体の時
        if (is_string($methods)) {
            return $query($methods);
        }

        // メソッド複数の時
        $logs = [];
        foreach ($methods as $method) {
            $logs[$method] = $query($method) ?: "自動更新ログが存在しません。";
        }
        return $logs;
    }

}
