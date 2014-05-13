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
use snickom\datatables\DatatableAsset;

class DynamicTable extends \yii\base\Widget
{
	private static $_view;
	private static $_settings;

	public static function widget($config = [])
	{
		self::$_view = Yii::$app->getView();

		if (!isset($config['db'])) 
			return false;

		if (!isset($config['id'])) 
			$config['id'] = 'datatable-grid';

		if (!isset($config['start'])) 
			$config['start'] = 0;

		if (!isset($config['length'])) 
			$config['length'] = -1;

		if (!isset($config['title'])) 
			$config['title'] = Yii::t('app','Table');

		if (!isset($config['db']['primaryKey'])) 
			$config['db']['primaryKey'] = 't.id';

		if (!isset($config['db']['select'])) 
			$config['db']['select'] = 't.*';

		if (!isset($config['db']['condition'])) 
			$config['db']['condition'] = '';

		if (!isset($config['db']['searchOr'])) 
			$config['db']['searchOr'] = '';

		if (!isset($config['db']['searchAnd'])) 
			$config['db']['searchAnd'] = '';

		if (!isset($config['db']['columns'])) 
			$config['db']['columns'] = [
			[
				'db' => $config['db']['primaryKey'],
				'dt' => str_replace('.', '__', $config['db']['primaryKey']), 
				/*'title' => Yii::t('app','ID'), 
				'searchable' => true, 
				'orderable' => true, 
				'filter' => true*/
			],
			[
				'db' => 't.name',
				'dt' => 't__name',
				/*'title' => Yii::t('app','Name'), 
				'searchable' => true, 
				'orderable' => true, 
				'filter' => true*/
			]
		];

		$config['ajax_delimiter'] = ((strpos(Yii::$app->request->getUrl(), '?') !== false) ? '&' : '?');
		if (isset($config['ajax']) && !empty($config['ajax'])) 
			$config['ajax_delimiter'] = ((strpos($config['ajax'], '?') !== false) ? '&' : '?');

		if (!isset($config['html'])) 
			$config['html'] = [];

		if (!isset($config['html']['header'])) 
			$config['html']['header'] = '<div class="panel panel-default"><div class="panel-heading"><h6 class="panel-title"><i class="icon-table"></i> '.$config['title'].'</h6></div><div  id="'.$config['id'].'_parent" class="datatable-generated">';

		if (!isset($config['html']['footer'])) 
			$config['html']['footer'] = '</div></div>';

		self::$_settings = $config;

		if (self::getParam('callback', false)) {
			Yii::$app->response->format = Response::FORMAT_JSONP;
			echo json_encode(self::request());
			exit();
		} else {
			DatatableAsset::register(self::$_view);
			return self::show();
		}
	}

	protected static function show() 
	{
		
		$jsId = str_replace(['-'],['_'],self::$_settings['id']);

		self::$_view->registerJs("var ".$jsId."; 
		$(document).ready(function() {

			var rails_csrf_token = $('meta[name=csrf-token]').attr('content');
			var rails_csrf_param = $('meta[name=csrf-param]').attr('content');

			var rails_csrf_param_obj = {};
			rails_csrf_param_obj[rails_csrf_param] = rails_csrf_token;

			var asInitVals = new Array();
			var aoColumns = ".json_encode(self::$_settings['db']['columns']).";

		        	var ".$jsId."_first = false;
		        	var ".$jsId." = $('#".self::$_settings['id']."').dataTable( {
			            'bJQueryUI': false,
			            'bAutoWidth': false,
			            'sPaginationType': 'full_numbers',
			            'sDom': '<\'datatable-header\'fl><\'datatable-scroll\'rt><\'datatable-footer\'ip>',
			            'processing': true,
			            'serverSide': true,
			            'ajax': {
				    'url': '".((isset(self::$_settings['ajax']) && !empty(self::$_settings['ajax'])) ? self::$_settings['ajax'] : Yii::$app->request->getUrl())."',
				    'type': 'POST', 
				    'dataType': 'jsonp',
				    'data': function ( d ) { d = $.extend(d, rails_csrf_param_obj); }
				},
				 'columns': [
					{ 'data': 't__id' },
					{ 'data': 't__name' }
				], 
				'rowCallback': function( row, data, displayIndex ) {
				    if ( $.inArray(data.DT_RowId, selected) !== -1 ) {
				        $(row).addClass('selected');
				    }
				}
			});
		});");
		

		$return = self::$_settings['html']['header'].'<table class="table" id="'.self::$_settings['id'].'"><thead><tr>';
                        foreach (self::$_settings['db']['columns'] as $key => $value) {
                            $return .= '<th>'; 
                            if (isset($value['options'])) 
                            	$return .= (isset($value['title']) ? $value['title'] : '&nbsp;');
                            else 
                            	$return .= (isset($value['title']) ? $value['title'] : $value['db']);
                            $return .= '</th>';
                        }
		$return .= '</tr></thead><tfoot><tr>';
                        foreach (self::$_settings['db']['columns'] as $key => $value) {
                        	if (isset($value['filter'])) {
	                        	switch ($value['filter']) {
	                        		/* case 'dateFromTo':
	                        			$return .= '
						<th>
						    <ul class="dates-range">
						        <li><input placeholder="'.Yii::t('app','Od').'" type="text" class="form-control fromDate" data-id="'.$key.'" data-name="'.$value['sName'].'" /></li>
						        <li class="sep">-</li>
						        <li><input placeholder="'.Yii::t('app','Do').'" type="text" class="form-control toDate" data-id="'.$key.'" data-name="'.$value['sName'].'" /></li>
						    </ul>
						</th>';
	                        			break;
	                        		case 'fromTo':
	                        			$return .= '
						<th>
						    <ul class="dates-range">
						        <li><input placeholder="'.Yii::t('app','Od').'" type="text" class="form-control fromNb" data-id="'.$key.'" data-name="'.$value['sName'].'" /></li>
						        <li class="sep">-</li>
						        <li><input placeholder="'.Yii::t('app','Do').'" type="text" class="form-control toNb" data-id="'.$key.'" data-name="'.$value['sName'].'" /></li>
						    </ul>
						</th>';
	                        			break;
	                        		case 'select':
	                        			$return .= '
						<th>
							<select data-id="'.$key.'" class="form-control" data-name="'.$value['sName'].'">
							    <option value=""></option>';
							    $model = $value['sFilterData']['model'];
							    if (isset($value['sFilterData'])) 
								    foreach (CHtml::listData($model::model()->findAll(),$value['sFilterData']['id'],$value['sFilterData']['name']) as $id => $name) {
								     	$return .= '<option value="'.$id.'">'.$name.'</option>';
								     } 
							$return .= '</select>
						</th>';
	                        			break;
	                        		case 'multiSelect':
	                        			$return .= '
						<th>
							<select data-id="'.$key.'" class="form-control" data-name="'.$value['sName'].'" multiple="multiple">
							    <option value=""></option>';
							    if (isset($value['sFilterData'])) 
							    	foreach (CHtml::listData($value['sFilterData']['model']::model()->findAll(),$value['sFilterData']['id'],$value['sFilterData']['name']) as $id => $name) {
								     	$return .= '<option value="'.$id.'">'.$name.'</option>';
								     } 
							$return .= '</select>
						</th>';
	                        			break;
	                        		case 'math':
	                        			$return .= '
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
	                        			$return .= '<th><input type="text" class="form-control" data-id="'.$key.'" data-name="'.$value['db'].'"></th>';
	                        			break;
	                        	}
                        	} elseif (isset($value['options'])) {
                        		$return .= '<th><button type="button" id="clearDTfilter">CLEAR</button></th>'; 
                        	} else {
                        		$return .= '<th></th>'; 
                        	}
                        }
                        $return .= '</tr></tfoot><tbody>';
		if (isset(self::$_settings['html']['data'])) {
			foreach (self::$_settings['html']['data'] as $k => $v) {
                        		$return .= '<tr id="'.$k.'">';
                        		foreach ($v as $prm => $opt) {
					$return .= '<td'.(isset($opt['class']) ? ' class="'.$opt['class'].'"' : '').'>'.(isset($opt['value']) ? $opt['value'] : '&nbsp;').'</td>';
                        		}
                        		$return .= '</tr>';
			}
		}
		$return .='</tbody></table>'.self::$_settings['html']['footer'];
		return $return;
	}

	protected static function request() 
	{

		$request = (Yii::$app->request->getIsPost() ? $_POST : $_GET);

/*
		Yii::$app->db->quoteValue($aColumns[ intval( self::getParam('iSortCol_'.$i) ) ]),1,-1)
		*/
	
		$bindings = [];

		$limit = self::limit( $request, self::$_settings['db']['columns'] );
		$order = self::order( $request, self::$_settings['db']['columns'] );
		$where = self::filter( $request, self::$_settings['db']['columns'], $bindings );

		// Main query to actually get the data
		$sTable = substr(Yii::$app->db->quoteValue(self::$_settings['db']['table']),1,-1);
		$sColumns = [];
		foreach (self::pluck(self::$_settings['db']['columns'], 'db') as $k => $v) {
			$sColumns[] = '`'.str_replace('.','`.`',substr(Yii::$app->db->quoteValue($v),1,-1)).'` as `'.str_replace('.','__',$v).'`';
		}

		$sql = "SELECT SQL_CALC_FOUND_ROWS ".implode(',',$sColumns)." FROM `{$sTable}` `t` ".(!empty(self::$_settings['condition']) ? (!empty($where) ? $where : 'WHERE').' '.self::$_settings['condition'] : $where)." ".$order;

		$data = Yii::$app->db->createCommand($sql.' '.$limit)->queryAll();

		// Data set length after filtering
		$recordsFiltered = Yii::$app->db->createCommand('SELECT FOUND_ROWS();')->queryScalar();

		// Total data set length
		$primaryKey = '`'.str_replace('.','`.`',substr(Yii::$app->db->quoteValue(self::$_settings['db']['primaryKey']),1,-1)).'`';
		$recordsTotal = Yii::$app->db->createCommand("SELECT COUNT({$primaryKey}) FROM `{$sTable}` `t`".(!empty(self::$_settings['condition']) ? ' WHERE '.self::$_settings['condition'] : ''))->queryScalar();

		/*
		 * Output
		 */
		return [
			"draw"            		=> intval( $request['draw'] ),
			"recordsTotal"    	=> intval( $recordsTotal ),
			"recordsFiltered" 	=> intval( $recordsFiltered ),
			"data"            		=> self::data_output( self::$_settings['db']['columns'], $data )
		];
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
		$out = [];

		for ( $i=0, $ien=count($data) ; $i<$ien ; $i++ ) {
			$row = [];

			for ( $j=0, $jen=count($columns) ; $j<$jen ; $j++ ) {
				$column = $columns[$j];

				$column_db = str_replace('.','__',$column['db']);

				// Is there a formatter?
				if ( isset( $column['formatter'] ) ) {
					$row[ $column_db ] = $column['formatter']( $data[$i][ $column_db ], $data[$i] );
				}
				else {
					$row[ $column_db ] = $data[$i][ $column_db ];
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
			$orderBy = [];
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

					$colTmp = '`'.str_replace('.','`.`',substr(Yii::$app->db->quoteValue($column['db']),1,-1)).'`';
					$orderBy[] = $colTmp .' '.$dir;
				}
			}

			if (count($orderBy) > 0) $order = 'ORDER BY '.implode(', ', $orderBy);
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
		$globalSearch = $columnSearch = [];
		$dtColumns = self::pluck( $columns, 'dt' );

		if ( isset($request['search']) && $request['search']['value'] != '' ) {
			$str = $request['search']['value'];

			for ( $i=0, $ien=count($request['columns']) ; $i<$ien ; $i++ ) {
				$requestColumn = $request['columns'][$i];
				$columnIdx = array_search( $requestColumn['data'], $dtColumns );
				$column = $columns[ $columnIdx ];

				if ( $requestColumn['searchable'] == 'true' ) {
					$binding = self::bind( $bindings, '%'.$str.'%', PDO::PARAM_STR );
					$colTmp = '`'.str_replace('.','`.`',substr(Yii::$app->db->quoteValue($column['db']),1,-1)).'`';
					$globalSearch[] = $colTmp." LIKE ".$binding;
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
		$out = [];

		for ( $i=0, $len=count($a) ; $i<$len ; $i++ ) {
			$out[] = $a[$i][$prop];
		}
		return $out;
	}
}