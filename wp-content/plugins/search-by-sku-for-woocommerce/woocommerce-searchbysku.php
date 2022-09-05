<?php

/*
  Plugin Name: Search By Model - for Woocommerce
  Plugin URI: 3niinfotech.com/
  Description: The search functionality in woocommerce doesn't search by Model by default.
  Author: 3ni Infotech
  Version: 21.8.0
  Author URI: 3niinfotech.com/
  Text Domain: search-by-model-for-woocommerce
  WC requires at least: 3.0.0
  WC tested up to: 5.5.1
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

//Needs to be after woocommerce has initiated but before posts_search filter has run..
add_filter('init', 'searchbysku_init', 11);

function searchbysku_init()
{
  include_once 'wp-filters-extra.php';
  include_once('wc-searchbysku-register-settings.php');
  include_once(ABSPATH . 'wp-admin/includes/plugin.php');
  //0.6.0 was incompatible with relenvassi plugin and gave impression of "doing nothing"

  if (is_plugin_active('relevanssi/relevanssi.php') || !function_exists('wc_clean')) {
    // Plugin is activated
    // Use the old style of sku searching ...
    include_once 'wc-searchbysku-relevanssi-compat.php';
  } else {
    //If relenvassi is not installed do a more advanced search that works with woo widgets
    include_once 'wc-searchbysku-widget-compat.php';
  }
}
