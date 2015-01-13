<?php 

/**
 * @copyright Copyright (c) 2014 Ján Hamrák <snickom@gmail.com>, 2015 John Pistonsky <pistonsky@icloud.com>
 * @link https://github.com/pistonsky/yii2-datatables-widget
 * @package yii2-datatables-widget
 * @version 1.0.0
 */

namespace pistonsky\datatables;

use Yii;
use yii\web\Response;
use yii\helpers\Html;
use yii\helpers\Url;
use pistonsky\datatables\DatatableAsset;

class DynamicTable extends \yii\base\Widget
{
	private static $_view;
	private static $_settings;

	public function init()
	{
	    parent::init();
	    $this->registerTranslations();
	}

	public function registerTranslations()
	{
		Yii::$app->i18n->translations['pistonsky/datatables/*'] = [
			'class' => 'yii\i18n\PhpMessageSource',
			'sourceLanguage' => Yii::$app->language,
			'basePath' => '@vendor/pistonsky/datatables/messages',
			'fileMap' => [
				'pistonsky/datatables/widget' => 'widget.php',
			],
		];
	}

	public static function widget($config = [])
	{

		if (!file_exists(Yii::getAlias('@runtime').'/eval/')) mkdir(Yii::getAlias('@runtime').'/eval/');

		self::$_view = Yii::$app->getView();

		if (!isset($config['db'])) 
			return false;

		if (!isset($config['dt'])) 
			$config['dt'] = [];

		if (!isset($config['id'])) 
			$config['id'] = 'datatable-grid';

		if (!isset($config['title'])) 
			$config['title'] = Yii::t('pistonsky/datatables/widget','Table');

		if (!isset($config['dt']['length'])) 
			$config['dt']['length'] = 25;

		if (!isset($config['dt']['sDom'])) 
			$config['dt']['sDom'] =  '<\'datatable-header\'fl><\'datatable-scroll\'rt><\'datatable-footer\'ip>';

		if (!isset($config['dt']['order'])) 
			$config['dt']['order'] = [[0, 'asc']];

		if (!isset($config['dt']['lengthMenu'])) 
			$config['dt']['lengthMenu'] = [[10, 25, 50, 100], [10, 25, 50, 100]];

		if (!isset($config['db']['primaryKey'])) 
			$config['db']['primaryKey'] = 't.id';

		if (!isset($config['db']['condition'])) 
			$config['db']['condition'] = '';

		if (!isset($config['db']['condition_where'])) 
			$config['db']['condition_where'] = '';

		if (!isset($config['db']['searchOr'])) 
			$config['db']['searchOr'] = '';

		if (!isset($config['db']['searchAnd'])) 
			$config['db']['searchAnd'] = '';

		if (!isset($config['db']['columns'])) 
			$config['db']['columns'] = [
			[
				'db' => $config['db']['primaryKey'],
				'dt' => str_replace('.', '__', $config['db']['primaryKey']), 
				'title' => Yii::t('pistonsky/datatables/widget','ID'), 
				/*'searchable' => true, 
				'orderable' => true, 
				'filter' => []*/
			],
			[
				'db' => 't.name',
				'dt' => 't__name',
				'title' => Yii::t('pistonsky/datatables/widget','Name'), 
				/*'searchable' => true, 
				'orderable' => true, 
				'filter' => []*/
			],
			[
				'db' => $config['db']['primaryKey'],
				'dt' => 'options',
				'title' => Yii::t('pistonsky/datatables/widget','Options'), 
				'opt' => ['view','update','delete'], 
				/*'searchable' => true, 
				'orderable' => true, 
				'filter' => []*/
			]
		];

		$tmp = self::pluck( $config['db']['columns'], 'db' );
		$config['db']['primaryKeyAlias'] = $config['db']['columns'][array_search($config['db']['primaryKey'], $tmp)]['dt'];
		 
		$config['ajax_delimiter'] = ((strpos(Yii::$app->request->getUrl(), '?') !== false) ? '&' : '?');
		if (isset($config['ajax']) && !empty($config['ajax'])) 
			$config['ajax_delimiter'] = ((strpos($config['ajax'], '?') !== false) ? '&' : '?');

		if (!isset($config['html'])) 
			$config['html'] = [];

		if (!isset($config['html']['class'])) 
			$config['html']['class'] = 'table';

		if (!isset($config['html']['header'])) 
			$config['html']['header'] = '<div class="panel panel-default"><div class="panel-heading"><h6 class="panel-title"><i class="icon-table"></i> '.$config['title'].'</h6></div><div  id="'.$config['id'].'_parent" class="datatable-generated">';

		if (!isset($config['html']['footer'])) 
			$config['html']['footer'] = '</div></div>';

		self::$_settings = $config;

		if (self::getParam('callback', false)) {
			Yii::$app->response->format = Response::FORMAT_JSONP;

			header('Content-Type: text/javascript; charset=utf8');
			header('Access-Control-Allow-Origin: '.Yii::$app->request->getUrl());
			header('Access-Control-Max-Age: '.(60*60));
			header('Access-Control-Allow-Methods: GET, POST'); // GET, POST, PUT, DELETE

			echo self::getParam('callback').'('.json_encode(self::request()).');';
			exit();
		} else {
			DatatableAsset::register(self::$_view);
			return self::show();
		}
	}

	protected static function show() 
	{
		
		$jsId = str_replace(['-'],['_'],self::$_settings['id']);

		if (isset(self::$_settings['dt']['scrollX']) && self::$_settings['dt']['scrollX']) 
			self::$_view->registerCss("#".self::$_settings['id']." th, #".self::$_settings['id']." td { white-space: nowrap; }"); 

		$jsColTmp = '';
		foreach (self::$_settings['db']['columns'] as $key => $value) {

			if (!empty($jsColTmp)) $jsColTmp .= ',';

			$jsColTmp .= '{';
			$jsColTmp .= "'data': ".(!empty($value['dt']) ? "'".$value['dt']."'" : 'null');

			if (isset($value['type']) && !empty($value['type'])) 
				$jsColTmp .= ",'type': '".$value['type']."'";

			if (isset($value['class']) && !empty($value['class'])) 
				$jsColTmp .= ",'class': '".$value['class']."'";

			if (isset($value['defaultContent']) && !empty($value['defaultContent'])) 
				$jsColTmp .= ",'defaultContent': '".$value['defaultContent']."'";

			$jsColTmp .= '}';
                        }

		self::$_view->registerJs("var ".$jsId."; 
		//
		// Pipelining function for DataTables. To be used to the `ajax` option of DataTables
		//
		$.fn.dataTable.pipeline = function ( opts ) {
		    // Configuration options
		    var conf = $.extend( {
		        pages: 5,     // number of pages to cache
		        url: '',      // script url
		        data: null,   // function or object with parameters to send to the server
		                      // matching how `ajax.data` works in DataTables
		        dataType: 'jsonp',
		        type: 'POST' // Ajax HTTP method
		    }, opts );
		 
		    // Private variables for storing the cache
		    var cacheFixLower = 0;
		    var cacheLower = -1;
		    var cacheUpper = null;
		    var cacheLastRequest = null;
		    var cacheLastJson = null;
		 
		    return function ( request, drawCallback, settings ) {
		        var ajax          = false;
		        var requestStart  = request.start;
		        var requestLength = request.length;
		        var requestEnd    = requestStart + requestLength;
	
		        if ( settings.clearCache ) {
		            // API requested that the cache be cleared
		            ajax = true;
		            settings.clearCache = false;
		        } else if ( cacheLower < 0 || requestStart < cacheLower || requestEnd > cacheUpper ) {
		            // outside cached data - need to make a request
		            ajax = true;
		        } else if ( JSON.stringify( request.order )   !== JSON.stringify( cacheLastRequest.order ) ||
		                  JSON.stringify( request.columns ) !== JSON.stringify( cacheLastRequest.columns ) ||
		                  JSON.stringify( request.search )  !== JSON.stringify( cacheLastRequest.search )
		        ) {
		            // properties changed (ordering, columns, searching)
		            ajax = true;
		        }
		         
		        // Store the request for checking next time around
		        cacheLastRequest = $.extend( true, {}, request );
		 
		        if ( ajax ) {
		            // Need data from the server

		            if ( requestStart < cacheLower ) {

		                requestStart = requestStart - (requestLength*(conf.pages-1));
		 
		                if ( requestStart < 0 ) {
		                    requestStart = 0;
		                } else {
		                    cacheFixLower = requestStart + (requestLength*(conf.pages-1));
		                }
		            } 

		            cacheLower = requestStart;
		            cacheUpper = requestStart + (requestLength * conf.pages);

		            request.start = requestStart;
		            request.length = requestLength*conf.pages;
		 
		            // Provide the same `data` options as DataTables.
		            if ( $.isFunction ( conf.data ) ) {
		                // As a function it is executed with the data object as an arg
		                // for manipulation. If an object is returned, it is used as the
		                // data object to submit
		                var d = conf.data( request );
		                if ( d ) {
		                    $.extend( request, d );
		                }
		            } else if ( $.isPlainObject( conf.data ) ) {
		                // As an object, the data given extends the default
		                $.extend( request, conf.data );
		            }
		 
		            settings.jqXHR = $.ajax( {
		                'type':     conf.type,
		                'url':      conf.url,
		                'data':     request,
		                'dataType': conf.dataType,
		                'cache':    false,
		                'success':  function ( json ) {
		                    cacheLastJson = $.extend(true, {}, json);
		
		                    if ( cacheLower != requestStart || cacheFixLower > 0) {
		                        if (cacheFixLower > 0) json.data.splice( 0, cacheFixLower ); 
		                        else json.data.splice( 0, requestStart-cacheLower );
		                    }

		                    json.data.splice( requestLength, json.data.length );
		        	
		                    drawCallback( json );
		                }
		            } );
		        } else {
		            json = $.extend( true, {}, cacheLastJson );
		            json.draw = request.draw; // Update the echo for each response
		            json.data.splice( 0, requestStart-cacheLower );
		            json.data.splice( requestLength, json.data.length );
		            drawCallback(json);
		        }
		    }
		};

		// Register an API method that will empty the pipelined data, forcing an Ajax
		// fetch on the next draw (i.e. `table.clearPipeline().draw()`)
		$.fn.dataTable.Api.register( 'clearPipeline()', function () {
		    return this.iterator( 'table', function ( settings ) {
		        settings.clearCache = true;
		    } );
		} );

		function filterGlobal () {
		    $('#".self::$_settings['id']."').DataTable().search(
		        $('#".self::$_settings['id']."_global_filter').val(),
		        $('#".self::$_settings['id']."_global_regex').prop('checked'),
		        $('#".self::$_settings['id']."_global_smart').prop('checked')
		    ).draw();
		}
		 
		function filterColumn ( i ) {
		    $('#".self::$_settings['id']."').DataTable().column( i ).search(
		        $('#".self::$_settings['id']."_col'+i+'_filter').val(),
		        $('#".self::$_settings['id']."_col'+i+'_regex').prop('checked'),
		        $('#".self::$_settings['id']."_col'+i+'_smart').prop('checked')
		    ).draw();
		}

		$(document).ready(function() {

    			var selected = [];
			var rails_csrf_token = $('meta[name=csrf-token]').attr('content');
			var rails_csrf_param = $('meta[name=csrf-param]').attr('content');
			var rails_csrf_param_obj = {};
			rails_csrf_param_obj[rails_csrf_param] = rails_csrf_token;

		        	var ".$jsId." = $('#".self::$_settings['id']."').DataTable( {
		        		'paging': ".((!isset(self::$_settings['dt']['paging']) || self::$_settings['dt']['paging']) ? 'true' : 'false').", 
		        		'searching': ".((!isset(self::$_settings['dt']['searching']) || self::$_settings['dt']['searching']) ? 'true' : 'false').", 
		        		'ordering': ".((!isset(self::$_settings['dt']['ordering']) || self::$_settings['dt']['ordering']) ? 'true' : 'false').", 
		        		'info': ".((!isset(self::$_settings['dt']['info']) || self::$_settings['dt']['info']) ? 'true' : 'false').", 
				'scrollX': ".((isset(self::$_settings['dt']['scrollX']) && self::$_settings['dt']['scrollX']) ? 'true' : 'false').", 
				'lengthMenu': ".json_encode(self::$_settings['dt']['lengthMenu']).",
				'pageLength': ".intval(self::$_settings['dt']['length']).",
				'language': {
					'decimal': '".(isset(self::$_settings['dt']['decimal']) ? self::$_settings['dt']['decimal'] : ',')."',
					'thousands': '".(isset(self::$_settings['dt']['thousands']) ? self::$_settings['dt']['thousands'] : ' ')."',
					'lengthMenu': '".Yii::t('pistonsky/datatables/widget','Display _MENU_ records per page')."',
					'zeroRecords': '".Yii::t('pistonsky/datatables/widget','Nothing found - sorry')."',
					'info': '".Yii::t('pistonsky/datatables/widget','Showing page from _START_ to _END_ from _TOTAL_)')."',
					'infoEmpty': '".Yii::t('pistonsky/datatables/widget','No records available')."',
					'infoFiltered': '".Yii::t('pistonsky/datatables/widget','(filtered from _MAX_ total records)')."',
					'processing': '".Yii::t('pistonsky/datatables/widget','Please wait...')."',
					'infoPostFix': '',
					'search': '".Yii::t('pistonsky/datatables/widget','Search')."',
					'url': '',
					'paginate': {
						'first':    '".Yii::t('pistonsky/datatables/widget','First')."',
						'previous': '".Yii::t('pistonsky/datatables/widget','Back')."',
						'next':     '".Yii::t('pistonsky/datatables/widget','Next')."',
						'last':     '".Yii::t('pistonsky/datatables/widget','Last')."'
					}
				}, 
				".(isset(self::$_settings['dt']['stateSave']) ? "
		        		'stateSave': true,".
				(isset(self::$_settings['dt']['stateSave']['save']) ? "
				'stateSaveCallback': function (settings, data) {".self::$_settings['dt']['stateSave']['save']."},
				" : '').
				(isset(self::$_settings['dt']['stateSave']['load']) ? "
				'stateLoadCallback': function (settings) {".self::$_settings['dt']['stateSave']['load']."},
				" : '') : "
				'stateSave': false,
				")."
				'createdRow': function ( row, data, index ) {
					".((isset(self::$_settings['dt']['createdRow']) && !empty(self::$_settings['dt']['createdRow'])) ? self::$_settings['dt']['createdRow'] : '')."
				},
				'footerCallback': function( row, data, start, end, display ) {            
					var api = this.api();            
					// $( api.column( 4 ).footer() ).html('$0 ( $0 total)');
					".((isset(self::$_settings['dt']['footerCallback']) && !empty(self::$_settings['dt']['footerCallback'])) ? self::$_settings['dt']['footerCallback'] : '')."
				},
				'headerCallback': function( thead, data, start, end, display ) {
					var api = this.api();
					// $(thead).find('th').eq(0).html( 'Displaying '+(end-start)+' records' );
					".((isset(self::$_settings['dt']['headerCallback']) && !empty(self::$_settings['dt']['headerCallback'])) ? self::$_settings['dt']['headerCallback'] : '')."
				},
				'drawCallback': function( settings ) {  
					var api = this.api();
 					// console.log( api.rows( {page:'current'} ).data() );
					".((isset(self::$_settings['dt']['drawCallback']) && !empty(self::$_settings['dt']['drawCallback'])) ? self::$_settings['dt']['drawCallback'] : '')."
				},
				'rowCallback': function( row, data, displayIndex ) {
				    if ( $.inArray(data.DT_RowId, selected) !== -1 ) {
				        $(row).addClass('selected');
				    }
				    ".((isset(self::$_settings['dt']['rowCallback']) && !empty(self::$_settings['dt']['rowCallback'])) ? self::$_settings['dt']['rowCallback'] : '')."
				},

        				'deferRender': true,
			            'pagingType': 'full_numbers',
			            'dom': '".addslashes(self::$_settings['dt']['sDom'])."',
			            'processing': true,
			            'serverSide': true,
			            'ajax': $.fn.dataTable.pipeline( {
					'url': '".((isset(self::$_settings['ajax']) && !empty(self::$_settings['ajax'])) ? self::$_settings['ajax'] : Yii::$app->request->getUrl())."',
					'type': 'POST', 
					'dataType': 'jsonp',
					'data': function ( d ) { d = $.extend(d, rails_csrf_param_obj); },
					'pages': '".((isset(self::$_settings['dt']['pipelinePages']) && !empty(self::$_settings['dt']['pipelinePages'])) ? self::$_settings['dt']['pipelinePages'] : 1)."' 
				}),
				'columns': [".$jsColTmp."],         
				'order': ".json_encode(self::$_settings['dt']['order']).", 
			});

			".((isset(self::$_settings['detail']) && self::$_settings['detail']) ? "
			// Array to track the ids of the details displayed rows
			var detailRows = [];
			$('#".self::$_settings['id']." tbody').on( 'click', 'tr td:first-child', function () {
			    var tr = $(this).parents('tr');

			    if (!!".$jsId.".api) {
			    	var row = ".$jsId.".api( true ).row( tr );
			    } else {
			    	var row = ".$jsId.".row( tr );
			    }
			    var idx = $.inArray( tr.attr('id'), detailRows );

			    if ( row.child.isShown() ) {
			        tr.removeClass( 'details' );
			        row.child.hide();

			        // Remove from the 'open' array
			        detailRows.splice( idx, 1 );
			    } else {
			        tr.addClass( 'details' );
			        if (typeof row.data() != 'undefined') {
				        row.child( '".Yii::t('pistonsky/datatables/widget','Loading ...')."' ).show();
				        var dataRow = row.data();
				        $.ajax({
					type: 'GET',
					url: '".((isset(self::$_settings['detail']['ajax']) && !empty(self::$_settings['ajax'])) ? self::$_settings['ajax'] : '?detail=')."'+dataRow.".self::$_settings['db']['primaryKeyAlias'].",
				        }).done(function( msg ) {
				        	row.child( msg ).show();
				        }).fail(function( jqXHR, textStatus ) {
					row.child( '".Yii::t('pistonsky/datatables/widget','Request failed:')."' + textStatus ).show();
				        });

				        // Add to the 'open' array
				        if ( idx === -1 ) {
				            detailRows.push( tr.attr('id') );
				        }
			        }
			    }
			} );

			// On each draw, loop over the `detailRows` array and show any child rows
			".$jsId.".on( 'draw', function () {
			    $.each( detailRows, function ( i, id ) {
			        $('#'+id+':not(.details) td:first-child').trigger( 'click' );
			    } );
			} );
 			" : '')."

			".((isset(self::$_settings['selector']) && self::$_settings['selector']) ? "
			$('#".self::$_settings['id']." tbody').on('click', 'tr td".((isset(self::$_settings['detail']) && self::$_settings['detail']) ? ":not(:first-child)" : '')."', function () {
			    var id = $(this).parent().attr('id');
			    var index = $.inArray(id, selected);

			    if ( index === -1 ) {
			        selected.push( id );
			    } else {
			        selected.splice( index, 1 );
			    }

			    $(this).parent().toggleClass('selected');
			});
 			" : '')."

			$('a.".self::$_settings['id']."-toggle-vis').on( 'click', function (e) { 
			    // <a class=".self::$_settings['id']."toggle-vis data-column=0>Name</a>
			    e.preventDefault();

			    // Get the column API object
			    var column = ".$jsId.".column( $(this).attr('data-column') );

			    // Toggle the visibility
			    column.visible( ! column.visible() );
			} );

			$('input.".self::$_settings['id']."_global_filter').on( 'keyup click', function () {
			    filterGlobal();
			} );

			$('input.".self::$_settings['id']."_column_filter').on( 'keyup click', function () {
			    filterColumn( $(this).parents('tr').attr('data-column') );
			} );

		});");
		
		/*
<table style="width: 67%; margin: 0 auto 2em auto;" border="0" cellpadding="3" cellspacing="0">
        <thead>
            <tr>
                <th>Target</th>
                <th>Filter text</th>
                <th>Treat as regex</th>
                <th>Use smart filter</th>
            </tr>
        </thead>
 
        <tbody>
            <tr id="filter_global">
                <td>Global filtering</td>
                <td align="center"><input class="global_filter" id="global_filter" type="text"></td>
                <td align="center"><input class="global_filter" id="global_regex" type="checkbox"></td>
                <td align="center"><input class="global_filter" id="global_smart" checked="checked" type="checkbox"></td>
            </tr>
            <tr id="filter_col1" data-column="0">
                <td>Column - Name</td>
                <td align="center"><input class="column_filter" id="col0_filter" type="text"></td>
                <td align="center"><input class="column_filter" id="col0_regex" type="checkbox"></td>
                <td align="center"><input class="column_filter" id="col0_smart" checked="checked" type="checkbox"></td>
            </tr>
            <tr id="filter_col2" data-column="1">
                <td>Column - Position</td>
                <td align="center"><input class="column_filter" id="col1_filter" type="text"></td>
                <td align="center"><input class="column_filter" id="col1_regex" type="checkbox"></td>
                <td align="center"><input class="column_filter" id="col1_smart" checked="checked" type="checkbox"></td>
            </tr>
            <tr id="filter_col3" data-column="2">
                <td>Column - Office</td>
                <td align="center"><input class="column_filter" id="col2_filter" type="text"></td>
                <td align="center"><input class="column_filter" id="col2_regex" type="checkbox"></td>
                <td align="center"><input class="column_filter" id="col2_smart" checked="checked" type="checkbox"></td>
            </tr>
            <tr id="filter_col4" data-column="3">
                <td>Column - Age</td>
                <td align="center"><input class="column_filter" id="col3_filter" type="text"></td>
                <td align="center"><input class="column_filter" id="col3_regex" type="checkbox"></td>
                <td align="center"><input class="column_filter" id="col3_smart" checked="checked" type="checkbox"></td>
            </tr>
            <tr id="filter_col5" data-column="4">
                <td>Column - Start date</td>
                <td align="center"><input class="column_filter" id="col4_filter" type="text"></td>
                <td align="center"><input class="column_filter" id="col4_regex" type="checkbox"></td>
                <td align="center"><input class="column_filter" id="col4_smart" checked="checked" type="checkbox"></td>
            </tr>
            <tr id="filter_col6" data-column="5">
                <td>Column - Salary</td>
                <td align="center"><input class="column_filter" id="col5_filter" type="text"></td>
                <td align="center"><input class="column_filter" id="col5_regex" type="checkbox"></td>
                <td align="center"><input class="column_filter" id="col5_smart" checked="checked" type="checkbox"></td>
            </tr>
        </tbody>
    </table>
		 */

		$return = self::$_settings['html']['header'].'<table class="'.self::$_settings['html']['class'].'" id="'.self::$_settings['id'].'" cellspacing="0" width="100%"><thead><tr>';
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
                        	} elseif (isset($value['opt'])) {
                        		$return .= '<th><button type="button" id="clearDTfilter">'.Yii::t('pistonsky/datatables/widget','Clear filter').'</button></th>'; 
                        	} else {
                        		$return .= '<th></th>'; 
                        	}
                        }
                        $return .= '</tr></tfoot>';
		if (isset(self::$_settings['html']['data'])) {
			echo '<tbody>';
			foreach (self::$_settings['html']['data'] as $k => $v) {
                        		$return .= '<tr id="row_'.$k.'">';
                        		foreach ($v as $prm => $opt) {
					$return .= '<td'.(isset($opt['class']) ? ' class="'.$opt['class'].'"' : '').'>'.(isset($opt['value']) ? $opt['value'] : '&nbsp;').'</td>';
                        		}
                        		$return .= '</tr>';
			}
			echo '</tbody>';
		}
		$return .='</table>'.self::$_settings['html']['footer'];
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


		if (!empty(self::$_settings['db']['condition_where'])) {
			if (!empty($where)) $where .= ' AND '.self::$_settings['db']['condition_where'];
			else $where .= ' WHERE '.self::$_settings['db']['condition_where'];
		}

		// Main query to actually get the data
		$sTable = substr(Yii::$app->db->quoteValue(self::$_settings['db']['table']),1,-1);
		$sColumns = [];
		foreach (self::pluck(self::$_settings['db']['columns'], 'db') as $k => $v) {
			$sColumns[] = '`'.str_replace('.','`.`',substr(Yii::$app->db->quoteValue($v),1,-1)).'` as `'.self::$_settings['db']['columns'][$k]['dt'].'`';
		}

		$sql = "SELECT SQL_CALC_FOUND_ROWS ".implode(',',$sColumns)." FROM `{$sTable}` `t` ".(!empty(self::$_settings['db']['condition']) ? self::$_settings['db']['condition'] : '')." ".$where." ".$order;

		$data = Yii::$app->db->createCommand($sql.' '.$limit)->queryAll();

		// Data set length after filtering
		$recordsFiltered = Yii::$app->db->createCommand('SELECT FOUND_ROWS();')->queryScalar();

		// Total data set length
		$primaryKey = '`'.str_replace('.','`.`',substr(Yii::$app->db->quoteValue(self::$_settings['db']['primaryKey']),1,-1)).'`';
		$recordsTotal = Yii::$app->db->createCommand("SELECT COUNT({$primaryKey}) FROM `{$sTable}` `t`".(!empty(self::$_settings['db']['condition']) ? self::$_settings['db']['condition'] : ''))->queryScalar();

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

			$row = [
				 'DT_RowId' => 'row_'.$data[$i][ self::$_settings['db']['primaryKeyAlias'] ]
			];

			for ( $j=0, $jen=count($columns) ; $j<$jen ; $j++ ) {
				$column = $columns[$j];

				$column_db = $column['dt'];

				// Is there a button set?
				if (isset($column['opt'])) {
					$row[ $column_db ] = '';
					foreach ($column['opt'] as $ck => $cv) {
						if (intval($ck) >= 0) {

							if ($cv == 'view') {
								$row[ $column_db ] .= ' '.Html::a(
									'<span class="icon-info"></span>', 
									Url::toRoute(['view', 'id' => $data[$i][ self::$_settings['db']['primaryKeyAlias']] ]), 
									['title' => Yii::t('yii', 'View')] 
								).' ';
							} elseif ($cv == 'update') {
								$row[ $column_db ] .= ' '.Html::a(
									'<span class="icon-pencil"></span>', 
									Url::toRoute(['update', 'id' => $data[$i][ self::$_settings['db']['primaryKeyAlias']] ]), 
									['title' => Yii::t('yii', 'Update')] 
								).' ';
							} elseif ($cv == 'delete') {
								$row[ $column_db ] .= ' '.Html::a(
									'<span class="icon-remove"></span>', 
									Url::toRoute(['delete', 'id' => $data[$i][ self::$_settings['db']['primaryKeyAlias']] ]), 
									['title' => Yii::t('yii', 'Delete'), 'data-confirm' => Yii::t('yii', 'Are you sure to delete this item?'), 'data-method' => 'post'] 
								).' ';
							}

						} else {
							file_put_contents($tmp = Yii::getAlias('@runtime').'/eval/'.time().'-'.$ck, '<?php return "'.str_replace('"','\"',$cv).'"; ?>');
							if (file_exists($tmp)) { 
								$row[ $column_db ] .= include($tmp);
								unlink($tmp);
							}
						}
					}
				// Is there a formatter?
				} elseif ( isset( $column['formatter'] ) ) {
					$row[ $column_db ] = $column['formatter']( $data[$i][ $column_db ], $data[$i] );
				} else {
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

		if ( isset($request['start']) && intval($request['length']) > 0 ) {
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

			if (isset(self::$_settings['db']['searchAnd']) && !empty(self::$_settings['db']['searchAnd'])) {
				file_put_contents($tmp = Yii::getAlias('@runtime').'/eval/'.time().'-searchAnd', '<?php return "'.str_replace('"','\"',self::$_settings['db']['searchAnd']).'"; ?>');
				if (file_exists($tmp)) { 
					include($tmp);
					unlink($tmp);
				}
			}
		}

		// Individual column filtering
		for ( $i=0, $ien=count($request['columns']) ; $i<$ien ; $i++ ) {
			$requestColumn = $request['columns'][$i];
			$columnIdx = array_search( $requestColumn['data'], $dtColumns );
			$column = $columns[ $columnIdx ];

			$str = $requestColumn['search']['value'];

			if ( $requestColumn['searchable'] == 'true' &&	$str != '' ) {
				$binding = self::bind( $bindings, '%'.$str.'%', PDO::PARAM_STR );
				$columnSearch[] = "`".$column['db']."` LIKE ".$binding;
			}

			if (isset(self::$_settings['db']['searchOr']) && !empty(self::$_settings['db']['searchOr'])) {
				file_put_contents($tmp = Yii::getAlias('@runtime').'/eval/'.time().'-searchOr', '<?php return "'.str_replace('"','\"',self::$_settings['db']['searchOr']).'"; ?>');
				if (file_exists($tmp)) { 
					include($tmp);
					unlink($tmp);
				}
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