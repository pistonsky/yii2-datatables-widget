<?php

/**
 * @copyright Copyright (c) 2014 Ján Hamrák <snickom@gmail.com>
 * @link https://github.com/snickom/yii2-datatables-widget
 * @package yii2-datatables-widget
 * @version 1.0.0-dev
 */

namespace snickom\datatables;

use yii\web\AssetBundle;

class DatatableAsset extends AssetBundle
{
    public $sourcePath = '@vendor/snickom/yii2-datatables-widget/assets';
    public $css = [
        'css/jquery.dataTables.min.css',
    ];
    public $js = [
        'js/jquery.dataTables.min.js',
    ];
    public $publishOptions = [
        'forceCopy' => true,
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}
