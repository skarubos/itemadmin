<?php

return [

    // 個人実績ページで表示する年数
    'sales_howManyYears' => 2,

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
];
