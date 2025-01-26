<?php

return [

    // 個人実績ページで表示する年数
    'sales_howManyYears' => 2,

    // 商品種別
    'product_types' => ['食品', '日用品', '化粧品', 'その他'],

    // 代理店価格
    'price_dairiten' => [
        'above100' => 4100,
        'less100' => 4400,
        'karibarai' => 300,
    ],
    // 営業所管理手当
    'price_eighosho' => [
        'above80' => 3000,
        'above40' => 2900,
        'above20' => 2600,
    ],
    // 会員価格
    'price_member' => 8500,


    // 【実績】
    // 実績の対象となる取引タイプ
    'sales_tradeTypes' => [10, 11, 12, 20, 110, 111],
    'sales_tradeTypesEigyosho' => [10, 11, 12, 20],
    // 最新注文の対象となる最小移動合計セット数
    'idou_minSet' => 20,

    // 【預け】
    // 預け関連の取引タイプ
    'depo_tradeTypes' => [11, 21, 111, 121],
    'depo_tradeTypesIn' => [11, 111],
    'depo_tradeTypesOut' => [21, 121],

    // 【資格手当】
    // 何か月間が対象となるか（注文日から遡る）
    'sub_monthsCovered' => 6,
    // 対象となる取引タイプ
    'sub_tradeTypes' => [10, 11, 12],
    // 対象となる最小合計セット数
    'sub_minSet' => 20,

    // 自動登録取引の確認時に表示する取引種別
    'tradeTypes_for_checkTrade' => [10, 11, 12, 21, 110, 111, 121],
];
