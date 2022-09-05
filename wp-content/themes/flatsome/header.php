<!DOCTYPE html>
<!--[if IE 9 ]> <html <?php language_attributes(); ?> class="ie9 <?php flatsome_html_classes(); ?>"> <![endif]-->
<!--[if IE 8 ]> <html <?php language_attributes(); ?> class="ie8 <?php flatsome_html_classes(); ?>"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--><html <?php language_attributes(); ?> class="<?php flatsome_html_classes(); ?>"> <!--<![endif]-->
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<link rel="profile" href="http://gmpg.org/xfn/11" />
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
	<link href="<?php echo get_parent_theme_file_uri() ?>/assets/fonts/font-awesome/4.5.0/css/font-awesome.min.css" rel = "stylesheet" type="text/css" />

	<?php wp_head(); ?>
	
	<script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer></script>

	<meta name="google-site-verification" content="3sUO701kUa5PXj9Krts1qTouUoXHq0_F04sikHYB4lM" />
	
	<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-213635420-1"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-213635420-1');
</script>

	
<script>
  window.renderOptIn = function() {
    window.gapi.load('surveyoptin', function() {
      window.gapi.surveyoptin.render(
        {
          // REQUIRED FIELDS
          "merchant_id": 521753300,
          "order_id": "ORDER_ID",
          "email": "CUSTOMER_EMAIL",
          "delivery_country": "COUNTRY_CODE",
          "estimated_delivery_date": "YYYY-MM-DD",

          // OPTIONAL FIELDS
          "products": [{"gtin":"GTIN1"}, {"gtin":"GTIN2"}]
        });
    });
  }
</script>
	
	<!-- Global site tag (gtag.js) - Google Ads: AW-10813899650 -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-10813899650"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-10813899650');
</script>

	
</head>

<body <?php body_class(); ?>>

<?php do_action( 'flatsome_after_body_open' ); ?>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e( 'Skip to content', 'flatsome' ); ?></a>

<div id="wrapper">

	<?php do_action( 'flatsome_before_header' ); ?>

	<header id="header" class="header <?php flatsome_header_classes(); ?>">
		<div class="header-wrapper">
			<?php get_template_part( 'template-parts/header/header', 'wrapper' ); ?>
		</div>
	</header>

	<?php do_action( 'flatsome_after_header' ); ?>

	<main id="main" class="<?php flatsome_main_classes(); ?>">
