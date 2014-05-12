<?php 

/**
 * @copyright Copyright (c) 2014 Ján Hamrák <snickom@gmail.com>
 * @link https://github.com/snickom/yii2-datatables-widget
 * @package yii2-datatables-widget
 * @version 1.0.0
 */

namespace snickom\datatables;

use Yii;
use yii\web\Response;

class DynamicTable extends \yii\base\Widget
{
	protected $_view;
	protected $_settings;

	public function __construct($input=[]) 
	{
		$this->_view = parent::getView();

		if (!isset($input['id'])) $input['id'] = 'datatable-grid';
		if (!isset($input['title'])) $input['title'] = Yii::t('app','Table');
		if (!isset($input['table'])) return false;
		if (!isset($input['primaryKey'])) $input['primaryKey'] = 'id';
		if (!isset($input['select'])) $input['select'] = '`t`.*';
		if (!isset($input['condition'])) $input['condition'] = '';
		if (!isset($input['searchOr'])) $input['searchOr'] = '';
		if (!isset($input['searchAnd'])) $input['searchAnd'] = '';
		if (!isset($input['columns'])) $input['columns'] = [
		    ['db' => 'id', 'dt' => 0],
		    ['db' => 'name',  'dt' => 1],
		    [
		        'db'        => 'id',
		        'dt'        => 2,
		        'formatter' => function( $d, $row ) {
		            return date( 'jS M y', strtotime($d));
		        }
		    ]
		];

		$this->_settings = $input;

		if (self::getParam('sdt', false)) {
			return $this->request();
		} else {
			\snickom\datatables\DatatableAsset::register($this->_view);
			return $this->show();
		}
	}

	protected function request() 
	{

		Yii::$app->response->format = Response::FORMAT_JSONP;

/*
		$cols = [];
		$colsTmp = explode(';',self::getParam('columns', ''));
		foreach ($colsTmp as $key => $value) {
			$tmp = explode(',',$value);
			if (count($tmp) == 2) $cols[$tmp[1]] = $tmp[0]; 
		}
		unset($colsTmp);

		$iColumns = (int)self::getParam('iColumns', 0);
		$aColumns = [];
		$filterColumn = [];

		if ($iColumns > 0) for($i=0; $i<$iColumns; $i++){
			$aColumns[$i] = "`".str_replace('__','`.`',self::getParam('mDataProp_'.$i))."`";
			$filterColumn[$i] = "`".str_replace('__','`.`',self::getParam('filterColumn['.$i.']'))."`"; 
		}

		$sLimit = "";
		if ( self::getParam('iDisplayLength', '') != '-1')
		{
			$tmpX1 = intval( self::getParam('iDisplayStart', 0) );
			$tmpX2 = intval( self::getParam('iDisplayLength', 50) );
			$sLimit = " LIMIT {$tmpX1}, {$tmpX2}";
		}

		$sOrder = "";
		if ( self::getParam('iSortCol_0', false) )
		{
			$sOrder = " ORDER BY ";
			for ( $i=0 ; $i<intval( self::getParam('iSortingCols') ) ; $i++ )
			{
				if ( self::getParam( 'bSortable_'.intval(self::getParam('iSortCol_'.$i)) ) == "true" )
				{
				
					if ($sOrder != " ORDER BY ") $sOrder .= ', ';

					$sOrder .= substr(Yii::$app->db->quoteValue($aColumns[ intval( self::getParam('iSortCol_'.$i) ) ]),1,-1)." ";
					$sOrder .= (self::getParam('sSortDir_'.$i, 'asc')==='asc' ? 'asc' : 'desc') ." ";
				}
			}

			if ( $sOrder == " ORDER BY " ) $sOrder = "";
		}

		$sWhere = "";
		if ( self::getParam('sSearch','') != '' )
		{
			$sSearchData = Yii::$app->db->quoteValue(trim(self::getParam('sSearch')));
			$sSearchColumn = Yii::$app->db->quoteValue('%'.trim(self::getParam('sSearch')).'%');

			$sWhere .= " AND (";
			for ( $i=0; $i<$iColumns; $i++ )
			{
				$sSearch = substr(Yii::$app->db->quoteValue(self::getParam('filterColumn['.$i.']')),1,-1);

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
			if ( self::getParam('bSearchable_'.$i, false) && self::getParam('bSearchable_'.$i) == "true" && self::getParam('sSearch_'.$i,'') != '' )
			{
				$sSearchData = Yii::$app->db->quoteValue(trim(self::getParam('sSearch_'.$i)));
				$sSearchColumn = Yii::$app->db->quoteValue('%'.trim(self::getParam('sSearch_'.$i)).'%');
				$sSearch = substr(Yii::$app->db->quoteValue(self::getParam('filterColumn['.$i.']')),1,-1);

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

		$output = [
			"sEcho" => intval(self::getParam('sEcho',1)),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iFilteredTotal,
			"aaData" => $aaData,
			"aaDataAll" => $aaDataAll
		];

		return $output;
		*/
	
		$bindings = [];

		$limit = self::limit( $request, $columns );
		$order = self::order( $request, $columns );
		$where = self::filter( $request, $columns, $bindings );

		// Main query to actually get the data
		$sTable = substr(Yii::$app->db->quoteValue($data['table']),1,-1);
		$sql = "SELECT SQL_CALC_FOUND_ROWS `".implode("`, `", self::pluck($columns, 'db'))."` FROM `{$sTable}` `t` WHERE ".(!empty($data['condition']) ? $data['condition'] : '1=1')." ".$where.$order;

		$data = Yii::$app->db->createCommand($sql.$limit)->queryAll();

		// Data set length after filtering
		$recordsFiltered = Yii::$app->db->createCommand('SELECT FOUND_ROWS();')->queryScalar();

		// Total data set length
		$recordsTotal = Yii::$app->db->createCommand("SELECT COUNT(`t`.{$primaryKey}) FROM `{$sTable}` `t`".(!empty($data['condition']) ? ' WHERE '.$data['condition'] : ''))->queryScalar();

		/*
		 * Output
		 */
		return array(
			"draw"            		=> intval( $request['draw'] ),
			"recordsTotal"    	=> intval( $recordsTotal ),
			"recordsFiltered" 	=> intval( $recordsFiltered ),
			"data"            		=> self::data_output( $columns, $data )
		);

		return [];
	}

	protected function show() {
		/*
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
		            / * 'bStateSave': true,
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
		            'aaSorting': [ [ 1, 'desc' ] ],* /
		            'bJQueryUI': false,
		            'bAutoWidth': false,
		            'sPaginationType': 'full_numbers',
		            'sDom': '<\'datatable-header\'Tfl><\'datatable-scroll\'rt><\'datatable-footer\'ip>',
			/ *'oTableTools': {
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
			},* /
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
		            / *'aoColumns': aoColumns,
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

		            }, * /

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







			/ *$('#".$data['id']." tfoot input').keyup( function () {
			    oTable.fnFilter( this.value, $('#".$data['id']." tfoot input').index(this) );
			});

			$('#".$data['id']." tfoot input[type=text]').attr('placeholder','Type to filter...');


		        $('.tableRefresher').click(function(){
		            ".$data['jsid'].".fnDraw();
		            $.jGrowl('Tabuľka bola aktualizovaná.');
		        });* /
		});");
		
	*/
	return '
	<div class="panel panel-default">
		<div class="panel-heading">
			<h6 class="panel-title"><i class="icon-table"></i> '.$this->_settings['title'].'</h6>
		</div>
		<div  id="'.$this->_settings['id'].'_parent" class="datatable-generated">
			<table class="table" id="'.$this->_settings['id'].'">
				<thead>
					<tr>';
			                        foreach ($this->_settings['columns'] as $key => $value) {
			                            echo '<th>'; 
			                            if (isset($value['options'])) 
			                            	echo (isset($value['title']) ? $value['title'] : Yii::t('app','Options'));
			                            else 
			                            	echo (isset($value['title']) ? $value['title'] : $value['name']);
			                            echo '</th>';
			                        }
			                        echo '
					</tr>
				</thead>
				<tfoot>
					<tr class="dFilter">';
	                        foreach ($this->_settings['columns'] as $key => $value) {
	                        	if (isset($value['filter'])) {
		                        	switch ($value['filter']) {
		                        		/* case 'dateFromTo':
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
		                        			break;*/
		                        		default:
		                        			echo '<th><input type="text" class="form-control" data-id="'.$key.'" data-name="'.$value['name'].'"></th>';
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

	static protected function getParam($name,$defaultValue=null,$type='')
	{
		$type = strtoupper(trim($type));

		if ($type == 'GET') 
			return isset($_GET[$name]) ? $_GET[$name] : $defaultValue;
		else if ($type == 'POST') 
			return isset($_POST[$name]) ? $_POST[$name] : $defaultValue;
		else
			return isset($_GET[$name]) ? $_GET[$name] : (isset($_POST[$name]) ? $_POST[$name] : $defaultValue);
	}


	static protected function data_output ( $columns, $data )
	{
		$out = array();

		for ( $i=0, $ien=count($data) ; $i<$ien ; $i++ ) {
			$row = array();

			for ( $j=0, $jen=count($columns) ; $j<$jen ; $j++ ) {
				$column = $columns[$j];

				// Is there a formatter?
				if ( isset( $column['formatter'] ) ) {
					$row[ $column['dt'] ] = $column['formatter']( $data[$i][ $column['db'] ], $data[$i] );
				}
				else {
					$row[ $column['dt'] ] = $data[$i][ $columns[$j]['db'] ];
				}
			}

			$out[] = $row;
		}

		return $out;
	}


	/**
	 * Paging
	 *
	 * Construct the LIMIT clause for server-side processing SQL query
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @param  array $columns Column information array
	 *  @return string SQL limit clause
	 */
	static protected function limit ( $request, $columns )
	{
		$limit = '';

		if ( isset($request['start']) && $request['length'] != -1 ) {
			$limit = "LIMIT ".intval($request['start']).", ".intval($request['length']);
		}

		return $limit;
	}


	/**
	 * Ordering
	 *
	 * Construct the ORDER BY clause for server-side processing SQL query
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @param  array $columns Column information array
	 *  @return string SQL order by clause
	 */
	static protected function order ( $request, $columns )
	{
		$order = '';

		if ( isset($request['order']) && count($request['order']) ) {
			$orderBy = array();
			$dtColumns = self::pluck( $columns, 'dt' );

			for ( $i=0, $ien=count($request['order']) ; $i<$ien ; $i++ ) {
				// Convert the column index into the column data property
				$columnIdx = intval($request['order'][$i]['column']);
				$requestColumn = $request['columns'][$columnIdx];

				$columnIdx = array_search( $requestColumn['data'], $dtColumns );
				$column = $columns[ $columnIdx ];

				if ( $requestColumn['orderable'] == 'true' ) {
					$dir = $request['order'][$i]['dir'] === 'asc' ?
						'ASC' :
						'DESC';

					$orderBy[] = '`'.$column['db'].'` '.$dir;
				}
			}

			$order = 'ORDER BY '.implode(', ', $orderBy);
		}

		return $order;
	}


	/**
	 * Searching / Filtering
	 *
	 * Construct the WHERE clause for server-side processing SQL query.
	 *
	 * NOTE this does not match the built-in DataTables filtering which does it
	 * word by word on any field. It's possible to do here performance on large
	 * databases would be very poor
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @param  array $columns Column information array
	 *  @param  array $bindings Array of values for PDO bindings, used in the
	 *    sql_exec() function
	 *  @return string SQL where clause
	 */
	static protected function filter ( $request, $columns, &$bindings )
	{
		$globalSearch = array();
		$columnSearch = array();
		$dtColumns = self::pluck( $columns, 'dt' );

		if ( isset($request['search']) && $request['search']['value'] != '' ) {
			$str = $request['search']['value'];

			for ( $i=0, $ien=count($request['columns']) ; $i<$ien ; $i++ ) {
				$requestColumn = $request['columns'][$i];
				$columnIdx = array_search( $requestColumn['data'], $dtColumns );
				$column = $columns[ $columnIdx ];

				if ( $requestColumn['searchable'] == 'true' ) {
					$binding = self::bind( $bindings, '%'.$str.'%', PDO::PARAM_STR );
					$globalSearch[] = "`".$column['db']."` LIKE ".$binding;
				}
			}
		}

		// Individual column filtering
		for ( $i=0, $ien=count($request['columns']) ; $i<$ien ; $i++ ) {
			$requestColumn = $request['columns'][$i];
			$columnIdx = array_search( $requestColumn['data'], $dtColumns );
			$column = $columns[ $columnIdx ];

			$str = $requestColumn['search']['value'];

			if ( $requestColumn['searchable'] == 'true' &&
			 $str != '' ) {
				$binding = self::bind( $bindings, '%'.$str.'%', PDO::PARAM_STR );
				$columnSearch[] = "`".$column['db']."` LIKE ".$binding;
			}
		}

		// Combine the filters into a single string
		$where = '';

		if ( count( $globalSearch ) ) {
			$where = '('.implode(' OR ', $globalSearch).')';
		}

		if ( count( $columnSearch ) ) {
			$where = $where === '' ?
				implode(' AND ', $columnSearch) :
				$where .' AND '. implode(' AND ', $columnSearch);
		}

		if ( $where !== '' ) {
			$where = 'WHERE '.$where;
		}

		return $where;
	}


	/**
	 * Pull a particular property from each assoc. array in a numeric array, 
	 * returning and array of the property values from each item.
	 *
	 *  @param  array  $a    Array to get data from
	 *  @param  string $prop Property to read
	 *  @return array        Array of property values
	 */
	static protected function pluck ( $a, $prop )
	{
		$out = array();

		for ( $i=0, $len=count($a) ; $i<$len ; $i++ ) {
			$out[] = $a[$i][$prop];
		}

		return $out;
	}
}