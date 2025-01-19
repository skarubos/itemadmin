<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepoRealtime extends Model
{
    use HasFactory;

    protected $table = 'depo_realtime';
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    protected $primaryKey = 'id';

    protected $fillable = [
        'member_code',
        'product_id',
        'amount'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * 該当ユーザーの預け詳細を取得するメソッド
     *
     * @param int $member_code
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getDepoRealtime($member_code)
    {
        $details = DepoRealtime::with(['product' => function($query) {
                $query->select('id', 'name');
            }])
            ->where('member_code', $member_code)
            ->where('amount', '!=', 0)
            ->select('product_id', 'amount')
            ->orderBy('product_id', 'ASC')
            ->get();
        return $details;
    }

}
