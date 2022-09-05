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
    $csvFile        = 'updateprice.csv'; 
    $csvData        = readCsvRows($csvFile);
    $headers        = array_shift($csvData);

    $count   = 0;
	
	$sku  = '';
	$name  = '';
	$price = '';	
	
	foreach($csvData as $_data) 
	{
		$count++;        
		$sku   = $_data[getIndex('sku')];
		$price = $_data[getIndex('price')];
		
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
				update_post_meta( $entityId, '_price',$price);
				update_post_meta( $entityId, '_regular_price',$price);
				
				
				$message = $count . '. SUCCESS:: Updated ID ('. $entityId .')  SKU (' . $sku . ')  price (' . $price . ')  features_2 (' . $features_2 . ')' ;
				
				mpLogAndPrint($message);
			} catch(Exception $e) {
				$message =  $count . '. ERROR:: While updating  SKU (' . $sku . ') with Price (' . $price . ') => ' . $e->getMessage();
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
