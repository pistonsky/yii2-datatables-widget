Yii2 - jQuery DataTables - NOT WORKING NOW!!!
========================
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