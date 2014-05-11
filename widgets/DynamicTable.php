<?php 

/**
 * @copyright Copyright (c) 2014 Ján Hamrák <snickom@gmail.com>
 * @link https://github.com/snickom/yii2-datatables-widget
 * @package yii2-datatables-widget
 * @version 1.0.0-dev
 */

namespace snickom\datatables;

use Yii;
use yii\web\Response;
use app\components\Tool;

class DynamicTable extends \yii\base\Widget
{
	private $_view;

	public function begin()
	{
		$_view = parent::getView();
		
		$_view->registerAssets();
		echo Html::tag('div', $this->renderInput(), $this->containerOptions);
		parent::begin();
	}

	public function run()
	{
		Yii::$app->response->format = 'json';
		$data = MyModel::find()->asArray()->all();

		self::show
            	return $this->render('mywidget');
	}

	static public function getParam($name,$defaultValue=null,$type='')
	{
		$type = strtoupper(trim($type));

		if ($type == 'GET') 
			return isset($_GET[$name]) ? $_GET[$name] : $defaultValue;
		else if ($type == 'POST') 
			return isset($_POST[$name]) ? $_POST[$name] : $defaultValue;
		else
			return isset($_GET[$name]) ? $_GET[$name] : (isset($_POST[$name]) ? $_POST[$name] : $defaultValue);
	}

	static protected function request($data=[], $eval=[]) {

		Yii::$app->response->format = Response::FORMAT_JSONP;

		if (!isset($data['table'])) return false;
		if (!isset($data['select'])) $data['select'] = '`t`.*';
		if (!isset($data['condition'])) $data['condition'] = '';
		if (!isset($eval['searchOr'])) $eval['searchOr'] = '';
		if (!isset($eval['searchAnd'])) $eval['searchAnd'] = '';

		$cols = [];
		$colsTmp = explode(';',Tool::getParam('columns', ''));
		foreach ($colsTmp as $key => $value) {
			$tmp = explode(',',$value);
			if (count($tmp) == 2) $cols[$tmp[1]] = $tmp[0]; 
		}
		unset($colsTmp);

		$iColumns = (int)Tool::getParam('iColumns', 0);
		$aColumns = [];
		$filterColumn = [];

		if ($iColumns > 0) for($i=0; $i<$iColumns; $i++){
			$aColumns[$i] = "`".str_replace('__','`.`',Tool::getParam('mDataProp_'.$i))."`";
			$filterColumn[$i] = "`".str_replace('__','`.`',Tool::getParam('filterColumn['.$i.']'))."`"; 
		}

		$sLimit = "";
		if ( Tool::getParam('iDisplayLength', '') != '-1')
		{
			$tmpX1 = intval( Tool::getParam('iDisplayStart', 0) );
			$tmpX2 = intval( Tool::getParam('iDisplayLength', 50) );
			$sLimit = " LIMIT {$tmpX1}, {$tmpX2}";
		}

		$sOrder = "";
		if ( Tool::getParam('iSortCol_0', false) )
		{
			$sOrder = " ORDER BY ";
			for ( $i=0 ; $i<intval( Tool::getParam('iSortingCols') ) ; $i++ )
			{
				if ( Tool::getParam( 'bSortable_'.intval(Tool::getParam('iSortCol_'.$i)) ) == "true" )
				{
				
					if ($sOrder != " ORDER BY ") $sOrder .= ', ';

					$sOrder .= substr(Yii::$app->db->quoteValue($aColumns[ intval( Tool::getParam('iSortCol_'.$i) ) ]),1,-1)." ";
					$sOrder .= (Tool::getParam('sSortDir_'.$i, 'asc')==='asc' ? 'asc' : 'desc') ." ";
				}
			}

			if ( $sOrder == " ORDER BY " ) $sOrder = "";
		}

		$sWhere = "";
		if ( Tool::getParam('sSearch','') != '' )
		{
			$sSearchData = Yii::$app->db->quoteValue(trim(Tool::getParam('sSearch')));
			$sSearchColumn = Yii::$app->db->quoteValue('%'.trim(Tool::getParam('sSearch')).'%');

			$sWhere .= " AND (";
			for ( $i=0; $i<$iColumns; $i++ )
			{
				$sSearch = substr(Yii::$app->db->quoteValue(Tool::getParam('filterColumn['.$i.']')),1,-1);

				eval(
				'switch ($sSearch) { 
					'.$eval['searchOr'].'
					default:
						$sWhere .= "`".$sSearch."` LIKE ". $sSearchColumn ." OR ";
						break;
				}'	
				);
			}
			if ($iColumns > 0) $sWhere = substr_replace( $sWhere, "", -3 );
			$sWhere .= ')';
		}


		for ( $i=0 ; $i<$iColumns; $i++ )
		{
			if ( Tool::getParam('bSearchable_'.$i, false) && Tool::getParam('bSearchable_'.$i) == "true" && Tool::getParam('sSearch_'.$i,'') != '' )
			{
				$sSearchData = Yii::$app->db->quoteValue(trim(Tool::getParam('sSearch_'.$i)));
				$sSearchColumn = Yii::$app->db->quoteValue('%'.trim(Tool::getParam('sSearch_'.$i)).'%');
				$sSearch = substr(Yii::$app->db->quoteValue(Tool::getParam('filterColumn['.$i.']')),1,-1);

				eval(
				'switch ($sSearch) { 
					'.$eval['searchOr'].'
					default:
						$sWhere .= " AND `".$sSearch."` LIKE ". $sSearchColumn;
						break;
				}'	
				);
			}
		}

		$sTable = substr(Yii::$app->db->quoteValue($data['table']),1,-1);
		$sql = "SELECT SQL_CALC_FOUND_ROWS ".$data['select']." FROM `{$sTable}` `t` WHERE ".(!empty($data['condition']) ? $data['condition'] : '1=1')." ".$sWhere.$sOrder;

		$aaData = Yii::$app->db->createCommand($sql.$sLimit)->queryAll();
		$aaDataAll = Yii::$app->db->createCommand($sql)->queryAll();

		foreach ($aaData as $key => $value) {
			foreach ($value as $k => $v) {
			 	if (isset($cols[$k])) {
			 		unset($aaData[$key][$k]);
			 		$aaData[$key][$cols[$k]] = $v;
			 	}
			 } 
		}
		foreach ($aaDataAll as $key => $value) {
			foreach ($value as $k => $v) {
			 	if (isset($cols[$k])) {
			 		unset($aaData[$key][$k]);
			 		$aaData[$key][$cols[$k]] = $v;
			 	}
			 } 
		}

		$iFilteredTotal = Yii::$app->db->createCommand('SELECT FOUND_ROWS();')->queryScalar();
		$iTotal = Yii::$app->db->createCommand("SELECT COUNT(*) FROM `{$sTable}` `t` WHERE ".(!empty($data['condition']) ? $data['condition'] : '1=1'))->queryScalar();

		$output = [
			"sEcho" => intval(Tool::getParam('sEcho',1)),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iFilteredTotal,
			"aaData" => $aaData,
			"aaDataAll" => $aaDataAll
		];

		// header('Content-type: application/json; charset='.Yii::$app->charset);
		// echo json_encode( $output ); 
		// Yii::$app->end(); 
		return $output;
	}

	static protected function show($data=[]) {

		$asset = \app\assets\AppAsset::register($data['view']);
				
		$data['view']->registerJsFile($asset->baseUrl.'/js/plugins/interface/jquery.dataTables.min.js',[\yii\web\JqueryAsset::className()]);
		$data['view']->registerJsFile($asset->baseUrl.'/js/plugins/interface/dataTables/TableTools/js/dataTables.tableTools.min.js',[\yii\web\JqueryAsset::className()]);

		$xtraChar = ((strpos(Yii::$app->request->getUrl(), '?') !== false) ? '&' : '?');
		if (isset($data['ajax']) && !empty($data['ajax'])) $xtraChar = ((strpos($data['ajax'], '?') !== false) ? '&' : '?');

		if (!isset($data['primaryId'])) $data['primaryId'] = 't__id';
		if (!isset($data['id'])) $data['id'] = 'datatable-grid';
		if (isset($data['row_detail']) && empty($data['row_detail'])) $data['row_detail'] = Yii::$app->request->getUrl().$xtraChar.'dt=';

		$data['jsid'] = str_replace(['-'],['_'],$data['id']);

		if (!isset($data['title'])) $data['title'] = Yii::t('app','Zoznam');
		if (!isset($data['columns'])) $data['columns'] = [
			[
				'sName' => 't__id',
				'sTitle' => Yii::t('app','ID'),
				'bVisible' => true,
				'bSearchable' => true, 
				'bSortable' => true, 
				'sFilter' => true
			],
			[
				'sName' => 't__name',
				'sTitle' => Yii::t('app','Názov'),
				'bVisible' => true,
				'bSearchable' => true, 
				'bSortable' => true,
				'sFilter' => true
			]
		];

		$cols = '';
		if (count($data['columns']) > 0) foreach ($data['columns'] as $key => $value) {
			$data['columns'][$key]['mData'] = $value['sName'];
			if (!isset($value['sClass'])) $value['sClass'] = '';
			if (isset($value['options'])) $value['sClass'] .= ' options';
			$cols .= $value['sName'].','.$value['sColumn'].';';
		}

		$data['view']->registerJs("var ".$data['jsid']."; 
		$(document).ready(function() {

		        var asInitVals = new Array();
		        var aoColumns = ".json_encode($data['columns']).";

		        var ".$data['jsid']."_first = false;
		        var ".$data['jsid']." = $('#".$data['id']."').dataTable( {
		            /* 'bStateSave': true,
		            'fnStateSave': function (oSettings, oData) {
		                localStorage.setItem( '".$data['id']."-pt-'+window.location.pathname, JSON.stringify(oData) );
		            },
		            'fnStateLoad': function (oSettings) {
		                return JSON.parse( localStorage.getItem('".$data['id']."-pt-'+window.location.pathname) );
		            }, 
		            'iDisplayLength': 50,
		            'aLengthMenu': [
		                [10,25,50,100,250,500, -1],
		                [10,25,50,100,250,500, 'Všetko']
		            ],
		            'aaSorting': [ [ 1, 'desc' ] ],*/
		            'bJQueryUI': false,
		            'bAutoWidth': false,
		            'sPaginationType': 'full_numbers',
		            'sDom': '<\'datatable-header\'Tfl><\'datatable-scroll\'rt><\'datatable-footer\'ip>',
			/*'oTableTools': {
				'sRowSelect': 'multi',
				'sSwfPath': '".$asset->baseUrl."/media/swf/copy_csv_xls_pdf.swf',
				'aButtons': [
					{
						'sExtends':    'copy',
						'sButtonText': 'Copy',
						'sButtonClass': 'btn'
					},
					{
						'sExtends':    'print',
						'sButtonText': 'Print',
						'sButtonClass': 'btn'
					},
					{
						'sExtends':    'collection',
						'sButtonText': 'Save <span class=\'caret\'></span>',
						'sButtonClass': 'btn btn-primary',
						'aButtons':    [ 'csv', 'xls', 'pdf' ]
					},
					{
						'sExtends': 'collection',
						'sButtonText': 'Tools <span class=\'caret\'></span>',
						'sButtonClass': 'btn btn-primary',
						'aButtons':    [ 'select_all', 'select_none' ]
					}
				]
			},*/
		            'processing': true,
		            'serverSide': true,
		            'ajax': {
			    'url': '".((isset($data['ajax']) && !empty($data['ajax'])) ? $data['ajax'] : Yii::$app->request->getUrl().$xtraChar.'dt')."',
			    'dataType': 'jsonp',
			    'type': 'POST',
			    'data': function ( d ) {
			    	d.YII_CSRF_TOKEN = '".Yii::$app->request->getCsrfToken()."';
			    	d.columns = '".$cols."' ;
			    ]}
			},
			'columns': [
			            {
			                'class': 'details-control',
			                'orderable': false,
			                'data': null,
			                'defaultContent': ''
			            },
				{ 'data': 'id' },
				{ 'data': 'name' }
			],        
			'rowCallback': function( row, data, displayIndex ) {
			    if ( $.inArray(data.DT_RowId, selected) !== -1 ) {
			        $(row).addClass('selected');
			    }
			},
		            /*'aoColumns': aoColumns,
		            'fnRowCallback': function( nRow, aData, iDisplayIndex ) {
		                $(nRow).attr('data-ajax',aData.".$data['primaryId'].");
		                return nRow;
		            },
		            'fnDrawCallback': function( oSettings ) {

		                $('#".$data['id']." tbody tr td:not(.options)').click( function () {
		                    var nTr = $(this).parents('tr')[0];
		                    var aId = $(this).parent().attr('data-ajax');
		                    if ( ".$data['jsid'].".fnIsOpen(nTr) ) { 
		                        ".$data['jsid'].".fnClose( nTr );
		                    } else {
		                        ".$data['jsid'].".fnOpen( nTr, '<img src=\"".$asset->baseUrl."/images/loader.gif\"> Načítava sa detail s ID '+aId+' ...', 'details'+aId ); $('td.details'+aId ).addClass('nopadmar'); 
		                       $.ajax({
		                            type: 'POST',
		                            url: '".$data['row_detail']."',
		                            data: { show_id: aId }
		                        }).done(function( msg ) {
		                            ".$data['jsid'].".fnOpen( nTr, msg, 'details'+aId ); $('td.details'+aId ).addClass('nopadmar'); 
		                            $.jGrowl('Detail s ID '+aId+' bol načítaný.');
		                        });
		                    }
		                }).css('cursor','pointer');

		            },
		            'fnServerParams': function ( aoData ) {
		                aoData = jQuery.merge( aoData, jQuery('#module_main_filter').serialize() );

		                for(var c=0; c<aoColumns.length; c++){ 
		                    aoData.push( { 'name': 'filterColumn['+c+']', 'value': aoColumns[c]['mData'] } );
		                }
		                
		                window.serverParams = aoData;
		            },
		            'fnInitComplete': function(oSettings, json) {

		            }, */

		            'oLanguage': {
		                'sUrl'  : '".$asset->baseUrl."/i18n/datatables.".Yii::$app->language.".json'
		            }
		        } );

			var ".$data['jsid']."DetailRows = [];
 
			$('#example tbody').on('click', 'tr td:not(:first-child)', function () {
			    var id = this.id;
			    var index = $.inArray(id, selected);

			    if ( index === -1 ) {
			        selected.push( id );
			    } else {
			        selected.splice( index, 1 );
			    }

			    $(this).toggleClass('selected');
			} );

			$('#".$data['id']." tbody').on( 'click', 'tr td:first-child', function () {
			    var tr = $(this).parents('tr');
			    var row = dt.row( tr );
			    var idx = $.inArray( tr.attr('id'), ".$data['jsid']."DetailRows );

			    if ( row.child.isShown() ) {
			        tr.removeClass( 'details' );
			        row.child.hide();

			        ".$data['jsid']."DetailRows.splice( idx, 1 );
			    }
			    else {
			        tr.addClass( 'details' );
			        console.log( row.data() );
			        var xcontent = 'test';
			        row.child( xcontent ).show();

			        if ( idx === -1 ) {
			            ".$data['jsid']."DetailRows.push( tr.attr('id') );
			        }
			    }
			} );

			".$data['jsid'].".on( 'draw', function () {
			    $.each( ".$data['jsid']."DetailRows, function ( i, id ) {
			        $('#'+id+' td:first-child').trigger( 'click' );
			    } );
			} );







			/*$('#".$data['id']." tfoot input').keyup( function () {
			    oTable.fnFilter( this.value, $('#".$data['id']." tfoot input').index(this) );
			});

			$('#".$data['id']." tfoot input[type=text]').attr('placeholder','Type to filter...');


		        $('.tableRefresher').click(function(){
		            ".$data['jsid'].".fnDraw();
		            $.jGrowl('Tabuľka bola aktualizovaná.');
		        });*/
		});");
		
		echo '
	<div class="panel panel-default">
		<div class="panel-heading"><h6 class="panel-title"><i class="icon-table"></i> '.$data['title'].'</h6></div>
		<div  id="'.$data['id'].'_parent" class="datatable-generated">
			<table class="table" id="'.$data['id'].'">
				<thead>
					<tr>';
	                        foreach ($data['columns'] as $key => $value) {
	                            if (isset($value['options'])) echo '<th>'.(isset($value['sTitle']) ? $value['sTitle'] : Yii::t('app','Možnosti')).'</th>';
	                            else echo '<th>'.(isset($value['sTitle']) ? $value['sTitle'] : $value['sName']).'</th>';
	                        }
	                        echo '
					</tr>
				</thead>
				<tfoot>
					<tr class="dFilter">';
	                        foreach ($data['columns'] as $key => $value) {
	                        	if (isset($value['sFilter'])) {
		                        	switch ($value['sFilter']) {
		                        		case 'dateFromTo':
		                        			echo '
							<th>
							    <ul class="dates-range">
							        <li><input placeholder="'.Yii::t('app','Od').'" type="text" class="form-control fromDate" data-id="'.$key.'" data-name="'.$value['sName'].'" /></li>
							        <li class="sep">-</li>
							        <li><input placeholder="'.Yii::t('app','Do').'" type="text" class="form-control toDate" data-id="'.$key.'" data-name="'.$value['sName'].'" /></li>
							    </ul>
							</th>';
		                        			break;
		                        		case 'fromTo':
		                        			echo '
							<th>
							    <ul class="dates-range">
							        <li><input placeholder="'.Yii::t('app','Od').'" type="text" class="form-control fromNb" data-id="'.$key.'" data-name="'.$value['sName'].'" /></li>
							        <li class="sep">-</li>
							        <li><input placeholder="'.Yii::t('app','Do').'" type="text" class="form-control toNb" data-id="'.$key.'" data-name="'.$value['sName'].'" /></li>
							    </ul>
							</th>';
		                        			break;
		                        		case 'select':
		                        			echo '
							<th>
								<select data-id="'.$key.'" class="form-control" data-name="'.$value['sName'].'">
								    <option value=""></option>';
								    $model = $value['sFilterData']['model'];
								    if (isset($value['sFilterData'])) 
									    foreach (CHtml::listData($model::model()->findAll(),$value['sFilterData']['id'],$value['sFilterData']['name']) as $id => $name) {
									     	echo '<option value="'.$id.'">'.$name.'</option>';
									     } 
								echo '</select>
							</th>';
		                        			break;
		                        		case 'multiSelect':
		                        			echo '
							<th>
								<select data-id="'.$key.'" class="form-control" data-name="'.$value['sName'].'" multiple="multiple">
								    <option value=""></option>';
								    if (isset($value['sFilterData'])) 
								    	foreach (CHtml::listData($value['sFilterData']['model']::model()->findAll(),$value['sFilterData']['id'],$value['sFilterData']['name']) as $id => $name) {
									     	echo '<option value="'.$id.'">'.$name.'</option>';
									     } 
								echo '</select>
							</th>';
		                        			break;
		                        		case 'math':
		                        			echo '
							<th>
							    <ul class="dates-range">
							        <li><select data-id="'.$key.'" class="form-control keyNb" data-name="'.$value['sName'].'">
								        <option value="">=</option>
								        <option value="<">&lt;</option>
								        <option value="<=">&lt;=</option>
								        <option value="<>">&lt;&gt;</option>
								        <option value=">=">&gt;=</option>
								        <option value=">">&gt;</option>
							        </select></li>
							        <li class="sep"></li>
							        <li><input type="text" class="form-control valNb" data-id="'.$key.'" data-name="'.$value['sName'].'" /></li>
							    </ul>
							</th>';
		                        			break;
		                        		default:
		                        			echo '<th><input type="text" class="form-control" data-id="'.$key.'" data-name="'.$value['sName'].'"></th>';
		                        			break;
		                        	}
	                        	} elseif (isset($value['options'])) {
	                        		// echo '<th><button type="button" id="DTFilClear"><img src="'.$asset->baseUrl.'/images/icons/delete.png" alt="'.Yii::t('app','Zrušiť filter').'"></button></th>'; 
	                        		echo '<th></th>'; 
	                        	} else {
	                        		echo '<th></th>'; 
	                        	}
	                        }
	                        echo '
					</tr>
				</tfoot>
				<tbody></tbody>
			</table>
		</div>
	</div>';
	}
}