<?php

declare(strict_types=1);

// https://github.com/FriendsOfPHP/PHP-CS-Fixer
// https://mlocati.github.io/php-cs-fixer-configurator/
// https://qiita.com/ucan-lab/items/b9c41024c3a16830e85f
// pharフォルダを参照
// 下記の設定はVersion3の設定

$finder = \PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->name('*.php')
    // 除外パス
    ->exclude([
        '.history',
        'bootstrap',
        'public',
        'resources',
        'storage',
        'vendor',
        'node_modules',
    ])
    // 除外ファイル
    ->notPath('server.php')
    ->notPath('_ide_helper.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
;

$rules = [
    '@PhpCsFixer'            => true, // PhpCsFixer のルールセットを適用する
    '@PhpCsFixer:risky'      => true, // PhpCsFixer のルールセットを適用する
    '@PHP74Migration'        => true, // PHP8.0 のマイグレーションルールを適用する
    '@PHP74Migration:risky'  => true, // PHP8.0 のマイグレーションルールを適用する
    'binary_operator_spaces' => [
        'operators' => [
            '='  => 'align_single_space_minimal', // (=) の位置をそろえる
            '=>' => 'align_single_space_minimal', // (=>) の位置をそろえる
            '|'  => 'no_space',                   // (|)の前後にスペースを入れない
        ],
    ],
    'phpdoc_no_empty_return' => false, // PhpDocument から @return void と @return null を削除しない
    'yoda_style'             => [
        'always_move_variable' => false, // 変数が常に代入不可能な側にあるべきかどうか
        'equal'                => false, // 等しい（==、!=）文のスタイル
        'identical'            => false, // 同一の（===、!==）文のスタイル
        'less_and_greater'     => null,  // より少ないと大きいのスタイル（<、<=、>、>=）文のスタイル
    ],
    'php_unit_strict'          => [], // aassertSame を強制しない
    'php_unit_test_annotation' => [
        'style' => 'annotation', // @test アノテーションを使用する
    ],
];

return (new \PhpCsFixer\Config())
    ->setRiskyAllowed(true) // risky ルールを適用する
    ->setRules($rules)
    ->setFinder($finder)
    ->setUsingCache(false)
    ->setLineEnding("\n")
;
