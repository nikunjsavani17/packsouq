<?php
if ( ! defined('ABSPATH') ) {
/** Set up WordPress environment */
    require_once( '../../wp-load.php' );
	require_once( '../../wp-config.php' );
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '5G');
error_reporting(E_ALL);


include_once 'Database.php';    
$database= new Database();
$conn=$database->connect();

$query = "SELECT p.ID,
t.name,
p.post_title 'title',
p.post_content 'description',
MAX(CASE WHEN meta.meta_key = '_sku' THEN meta.meta_value END) 'sku',
MAX(CASE WHEN meta.meta_key = '_price' THEN meta.meta_value END) 'price',
MAX(CASE WHEN meta.meta_key = '_sale_price' THEN meta.meta_value END) 'sale_price',
MAX(CASE WHEN meta.meta_key = '_stock_status' THEN meta.meta_value END) 'stock_status',
MAX(CASE WHEN meta.meta_key = '_stock' THEN meta.meta_value END) 'stock',
(SELECT wm2.meta_value FROM wp_posts p1 LEFT JOIN
wp_postmeta wm1 ON (
wm1.post_id = p1.id
AND wm1.meta_value IS NOT NULL
AND wm1.meta_key = '_thumbnail_id'
)
LEFT JOIN wp_postmeta wm2 ON (wm1.meta_value = wm2.post_id AND wm2.meta_key = '_wp_attached_file'
AND wm2.meta_value IS NOT NULL) WHERE p1.ID = p.ID) 'image'

FROM wp_posts AS p
JOIN wp_postmeta AS meta ON p.ID = meta.post_ID
INNER JOIN wp_term_relationships r ON p.ID = r.object_id 
INNER JOIN wp_term_taxonomy tt ON r.term_taxonomy_id = tt.term_taxonomy_id 
INNER JOIN wp_terms t ON t.term_id = tt.term_id 

WHERE (p.post_type = 'product' OR p.post_type = 'product_variation')
AND meta.meta_key IN ('_sku', '_price', '_sale_price', '_stock_status', '_stock')
AND p.post_status = 'publish'
AND meta.meta_value is not null
AND tt.taxonomy = 'product_type' and t.name = 'simple'
GROUP BY p.ID"; 

$stmt= $conn->prepare($query);		
$stmt->execute();
$product_arr=array();
$today = time();


$dom = new DOMDocument();
$dom->encoding = 'utf-8';
$dom->xmlVersion = '1.0';
$dom->formatOutput = true;
	
$xml_file_name = 'simple_product_feed.xml';

$root = $dom->createElement('rss');
$attr_movie_id = new DOMAttr('xmlns:g', 'http://base.google.com/ns/1.0');
$root->setAttributeNode($attr_movie_id);
$attr_movie_id = new DOMAttr('version', '2.0');
$root->setAttributeNode($attr_movie_id);

	$channel_node = $dom->createElement('channel');
	$child_node_title = $dom->createElement('title', 'Pack Souq');
	$channel_node->appendChild($child_node_title);

	$child_node_link = $dom->createElement('link','https://www.packsouq.com');
	$channel_node->appendChild($child_node_link);

	$child_node_desc = $dom->createElement('description','Pack Souq - packsouq.com');
	$channel_node->appendChild($child_node_desc);
	
	$gtotal = 0;
	$ftotal = 0;
	while($row=$stmt->fetch(PDO::FETCH_ASSOC))
	{	
			
		//$simple = wc_get_product( $product_id );
		$price = "";
		if($row['price'] != '' && $row['price'] != 0 && $row['image'] != '')
		{		
			$price = $row['price'];
			if($row['sale_price'] != '' && $row['sale_price'] != 0)
			{
				$price = $row['sale_price'];	
			}	
			$child_node_item = $dom->createElement('item');		
				$child_node_item_condition = $dom->createElement('g:identifier_exists','no');
				$child_node_item->appendChild($child_node_item_condition);	
			
				$child_node_item_id = $dom->createElement('g:id',$row['ID']);
				$child_node_item->appendChild($child_node_item_id);

				$child_node_item_title = $child_node_item->appendChild($dom->createElement('g:title'));
				$child_node_item_title->appendChild($dom->createCDATASection($row['title']));
				
				
				$child_node_item_description = $child_node_item->appendChild($dom->createElement('g:description'));
				$child_node_item_description->appendChild($dom->createCDATASection($row['description']));
				
				
				$cdata_value_link = $child_node_item->appendChild($dom->createElement('g:link'));		
				$cdata_value_link->appendChild($dom->createCDATASection(get_permalink( $row['ID'] )));	
				
				$imageLink = "https://www.packsouq.com/wp-content/uploads/".$row['image'];							
				
				$child_node_item_image_link = $child_node_item->appendChild($dom->createElement('g:image_link'));
				$child_node_item_image_link->appendChild($dom->createCDATASection($imageLink));
				
				$statuslable = '';
				$status = $row['stock_status'];
				if ($status == 'instock') {
					$statuslable = "in stock";
				} elseif ($status == 'outofstock') {
					$statuslable = "out of stock";
				}					
				$child_node_item_availability = $dom->createElement('g:availability',$statuslable);
				$child_node_item->appendChild($child_node_item_availability);
				
				$child_node_item_price = $dom->createElement('g:price', number_format($price + ($price * 0.05),2).' AED');				
				$child_node_item->appendChild($child_node_item_price);						
				
				$child_node_item_brand = $child_node_item->appendChild($dom->createElement('g:brand'));
				$child_node_item_brand->appendChild($dom->createCDATASection('Pack Souq'));

				$child_node_item_shipping = $dom->createElement('shipping');
					$child_node_item_shipping_country = $dom->createElement('g:country','AE');
					$child_node_item_shipping->appendChild($child_node_item_shipping_country);	
		
					$child_node_item_shipping_service = $dom->createElement('g:service','Standard');
					$child_node_item_shipping->appendChild($child_node_item_shipping_service);	
		
					$child_node_item_shipping_price = $dom->createElement('g:price','50.00 AED');
					$child_node_item_shipping->appendChild($child_node_item_shipping_price);	
				$child_node_item->appendChild($child_node_item_shipping);		 			
				
			$channel_node->appendChild($child_node_item);
			
			$ftotal++;
		}	
		$gtotal++;
	}
$root->appendChild($channel_node);
	$dom->appendChild($root);

$dom->save($xml_file_name);

echo '<a target="_blank" href="https://www.packsouq.com/nicustom/googlefeed/'.$xml_file_name.'">'.$xml_file_name.'</a>';
echo "</br>Total Product Collection : ".$gtotal."  Total Feed Product Count:  ".$ftotal;
