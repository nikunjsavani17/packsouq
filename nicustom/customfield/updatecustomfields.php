<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ( ! defined('ABSPATH') ) {
/** Set up WordPress environment */
    require_once( '../../wp-load.php' );
	require_once( '../../wp-config.php' );
}

function _mpLog($data, $includeSep = false)
{
    $fileName = '/log/error.log';
    if ($includeSep) {
        $separator = str_repeat('=', 70);
        file_put_contents($fileName, $separator . '<br />' . PHP_EOL,  FILE_APPEND | LOCK_EX);
    }
    //file_put_contents($fileName, $data . '<br />' .PHP_EOL,  FILE_APPEND | LOCK_EX);
}

function mpLogAndPrint($message, $separator = false)
{
    _mpLog($message, $separator);
    if (is_array($message) || is_object($message)) {
        print_r($message);
    } else {
        echo $message . '<br />' . PHP_EOL;
    }

    if ($separator) {
        echo str_repeat('=', 70) . '<br />' . PHP_EOL;
    }
}

function readCsvRows($csvFile)
{
    $rows = [];
    $fileHandle = fopen($csvFile, 'r');
    while(($row = fgetcsv($fileHandle, 0, ',', '"', '"')) !== false) {
        $rows[] = $row;
    }
    fclose($fileHandle);
    return $rows;
}

function getIndex($field)
{
    global $headers;
    $index = array_search($field, $headers);
    if ( !strlen($index)) {
        $index = -1;
    }
    return $index;
}



function _getIdFromSku($sku)
{
    global $wpdb;

    $product_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku ) );

    //if ( $product_id ) return new WC_Product( $product_id );
	 if ( $product_id ) return  $product_id ;
    return null;
}

function _getIdListFromSku($sku)
{
    global $wpdb;

    $product_ids = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' ", $sku ) );
    return  $product_ids;
}


$notfount = array();
try {
    $csvFile        = 'updatecustomfields.csv'; 
    $csvData        = readCsvRows($csvFile);
    $headers        = array_shift($csvData);

    $count   = 0;
	
	$sku  = '';
	$name  = '';
	$features_1 = '';
	$features_2 = '';
	$features_3 = '';
	$features_4 = '';
	$features_5 = '';
	$features_6 = '';	
	$features_7 = '';
	$features_8 = '';
	$features_9 = '';
	$features_10 = '';	
	$features_11 = '';
	$features_12 = '';
	
	
	
	foreach($csvData as $_data) 
	{
		$count++;        
		$sku   = $_data[getIndex('sku')];
		
		$short_description = $_data[getIndex('short_description')];
		$description = $_data[getIndex('description')];
		
		//$description = $_data[getIndex('description')];
		//$isfeatures = $_data[getIndex('features_1')];
		
		/* $features_1 = $_data[getIndex('features_1')];
		$features_2 = $_data[getIndex('features_2')];
		$features_3 = $_data[getIndex('features_3')];
		$features_4 = $_data[getIndex('features_4')];
		$features_5 = $_data[getIndex('features_5')];
		$features_6 = $_data[getIndex('features_6')];
		$features_7 = $_data[getIndex('features_7')];
		$features_8 = $_data[getIndex('features_8')];
		$features_9 = $_data[getIndex('features_9')];
		$features_10 = $_data[getIndex('features_10')];


		$specification_1 = $_data[getIndex('specification_1')];
		$specification_2 = $_data[getIndex('specification_2')];
		$specification_3 = $_data[getIndex('specification_3')];
		$specification_4 = $_data[getIndex('specification_4')];
		$specification_5 = $_data[getIndex('specification_5')];
		$specification_6 = $_data[getIndex('specification_6')];
		$specification_7 = $_data[getIndex('specification_7')];
		$specification_8 = $_data[getIndex('specification_8')];
		$specification_9 = $_data[getIndex('specification_9')];
		$specification_10 = $_data[getIndex('specification_10')];
		$specification_11 = $_data[getIndex('specification_11')];
		$specification_12 = $_data[getIndex('specification_12')];
		$specification_13 = $_data[getIndex('specification_13')];
		$specification_14 = $_data[getIndex('specification_14')];
		$specification_15 = $_data[getIndex('specification_15')];
		
		$part_number = $_data[getIndex('part_number')]; 
		$focus_keyword = $_data[getIndex('focus_keyword')];
		$meta_description = $_data[getIndex('meta_description')];
		
		
		$_supplier_name = $_data[getIndex('_supplier_name')];
		$_supplier_price = $_data[getIndex('_supplier_price')]; */
		
		//$estimated_dispatch = $_data[getIndex('estimated_dispatch')];
		//$datasheet_pdf = $_data[getIndex('datasheet_pdf')];	


		if ( ! _getIdFromSku($sku)) {
            $message =  $count .'. FAILURE:: Product with SKU (' . $sku . ') doesn\'t exist.';
			$notfount[] = $sku;
            mpLogAndPrint($message);
            continue;
        } 
		 
		 foreach(_getIdListFromSku($sku) as $product)
		 { 
			try {  
				
				//$entityId       = _getIdFromSku($sku);
				$entityId       = $product->post_id;
				
				/* update_post_meta( $entityId, 'features_1',addslashes($features_1));
				update_post_meta( $entityId, 'features_2',addslashes($features_2));
				update_post_meta( $entityId, 'features_3',addslashes($features_3));
				update_post_meta( $entityId, 'features_4',addslashes($features_4));
				update_post_meta( $entityId, 'features_5',addslashes($features_5));
				update_post_meta( $entityId, 'features_6',addslashes($features_6));
				update_post_meta( $entityId, 'features_7',addslashes($features_7));
				update_post_meta( $entityId, 'features_8',addslashes($features_8));
				update_post_meta( $entityId, 'features_9',addslashes($features_9));
				update_post_meta( $entityId, 'features_10',addslashes($features_10));
				
				update_post_meta( $entityId, 'specification_1',addslashes($specification_1));
				update_post_meta( $entityId, 'specification_2',addslashes($specification_2));
				update_post_meta( $entityId, 'specification_3',addslashes($specification_3));
				update_post_meta( $entityId, 'specification_4',addslashes($specification_4));
				update_post_meta( $entityId, 'specification_5',addslashes($specification_5));
				update_post_meta( $entityId, 'specification_6',addslashes($specification_6));
				update_post_meta( $entityId, 'specification_7',addslashes($specification_7));
				update_post_meta( $entityId, 'specification_8',addslashes($specification_8));
				update_post_meta( $entityId, 'specification_9',addslashes($specification_9));
				update_post_meta( $entityId, 'specification_10',addslashes($specification_10));
				update_post_meta( $entityId, 'specification_11',addslashes($specification_11));
				update_post_meta( $entityId, 'specification_12',addslashes($specification_12));
				update_post_meta( $entityId, 'specification_13',addslashes($specification_13));
				update_post_meta( $entityId, 'specification_14',addslashes($specification_14));
				update_post_meta( $entityId, 'specification_15',addslashes($specification_15)); 
				
				//update_post_meta( $entityId, 'part_number',addslashes($part_number));
				
				update_post_meta( $entityId, '_yoast_wpseo_focuskw',addslashes($focus_keyword));
				update_post_meta( $entityId, '_yoast_wpseo_metadesc',addslashes($meta_description));
				
				update_post_meta( $entityId, '_supplier_name',addslashes($_supplier_name));
				update_post_meta( $entityId, '_supplier_price',addslashes($_supplier_price)); */
				
				//update_post_meta( $entityId, 'estimated_dispatch',addslashes($estimated_dispatch));
				//update_post_meta( $entityId, 'datasheet_pdf',addslashes($datasheet_pdf));
								
				
				
				wp_update_post( array('ID' => $entityId, 'post_content' => $description) );
				wp_update_post( array('ID' => $entityId, 'post_excerpt' => $short_description ) );
		
		
				
				$message = $count . '. SUCCESS:: Updated ID ('. $entityId .')  SKU (' . $sku . ')  features_1 (' . $features_1 . ')  features_2 (' . $features_2 . ') features_3 (' . $features_3 . ') features_4 (' . $features_4 . ') features_5 (' . $features_5 . ') features_6 (' . $features_6 . ') features_7 (' . $features_7 . ') features_8 (' . $features_8 . ')  features_9 (' . $features_9 . ') features_10 (' . $features_10 . ')  specification_1 (' . $specification_1 . ')  specification_2 (' . $specification_2 . ') focus_keyword (' . $focus_keyword . ') meta_description (' . $meta_description . ')' ;
				
				mpLogAndPrint($message);
			} catch(Exception $e) {
				$message =  $count . '. ERROR:: While updating  SKU (' . $sku . ') with Price (' . $features_1 . ') => ' . $e->getMessage();
				mpLogAndPrint($message);
			}
		}	
    }
} catch (Exception $e) {
    mpLogAndPrint(
        'EXCEPTION::' . $e->getTraceAsString()
    );
}

echo '<br><br>';
echo "Not Found Product List";
echo '<br>';
echo "--------------------------------";
echo '<br><br>';
echo implode('<br>',$notfount);
