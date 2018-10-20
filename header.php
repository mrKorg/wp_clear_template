<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />

		<meta name="format-detection" content="telephone=no">

        <link href="<?= get_template_directory_uri() ?>/favicon.ico" rel="icon" type="image/x-icon">

		<title>
		<?php echo wp_title(''); ?>
		</title>
		
        <?php wp_enqueue_script("jquery"); ?>
		<?php wp_head(); ?>

		<style type="text/css">
            #page-preloader {
                position: fixed;
                left: 0;
                top: 0;
                right: 0;
                bottom: 0;
                width: 100vw;
                height: 100vh;
                background: #ed77b4;
                z-index: 100500;
            }
            #page-preloader .spinner {
                width: 270px;
                height: 175px;
                position: absolute;
                left: 50%;
                top: 50%;
                margin: -87px 0 0 -135px;
                color: #FFF;
                text-align: center;
            }
        </style>
		
	</head>
	<body ontouchstart="" <?php body_class(); ?>>

        <div id="page-preloader">
            <span class="spinner">
                <svg width="270" height="175">
                    <use xlink:href="#logo"></use>
                </svg>
            </span>
        </div>

        <!-- load combined svg file (with symbols) into body-->
        <script>
            (function (doc) {
                var scripts = doc.getElementsByTagName('script');
                var script = scripts[scripts.length - 1];
                var xhr = new XMLHttpRequest();
                xhr.onload = function () {
                    var div = doc.createElement('div');
                    div.innerHTML = this.responseText;
                    div.style.display = 'none';
                    script.parentNode.insertBefore(div, script)
                };
                xhr.open('get', '<?php echo get_template_directory_uri(); ?>/assets/img/sprites.svg', true);
                xhr.send()
            })(document)
        </script>

		<div class="wrapper">

			<header class="header">
			</header>
