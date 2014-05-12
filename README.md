Yii2 - jQuery DataTables 
========================
[![Latest Stable Version](https://poser.pugx.org/snickom/yii2-datatables-widget/v/stable.png)](https://packagist.org/packages/snickom/yii2-datatables-widget) [![Total Downloads](https://poser.pugx.org/snickom/yii2-datatables-widget/downloads.png)](https://packagist.org/packages/snickom/yii2-datatables-widget) [![Latest Unstable Version](https://poser.pugx.org/snickom/yii2-datatables-widget/v/unstable.png)](https://packagist.org/packages/snickom/yii2-datatables-widget) [![License](https://poser.pugx.org/snickom/yii2-datatables-widget/license.png)](https://packagist.org/packages/snickom/yii2-datatables-widget) 

Yii2 extension implements jQuery DataTables

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist snickom/yii2-datatables-widget "*"
```

or add

```
"snickom/yii2-datatables-widget": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
<?= \snickom\datatables\DynamicTable::widget([
	'id'=>'datatable-grid',
	'db'=>[],
	'title'=>Yii::t('app','Table'),
	'columns'=>[],
]); ?>```