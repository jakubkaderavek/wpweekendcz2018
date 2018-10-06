<?php
?><!DOCTYPE html>
<html dir="ltr" <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
	<style>
		.container {
			margin-left: auto;
			margin-right: auto;
			max-width: 1170px;
		}
		.video__wrap {
			position: relative;
			padding-bottom: 56.25%; /* 16 : 9 */
			height: 0;
		}
        .video__iframe,
        .video__wrap img,
		.video__wrap iframe {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
		}
        .video__wrap img {
            z-index: 3;
        }
        .video__iframe {
            z-index: 6;
            cursor: pointer;
        }
        .video__wrap iframe {
            z-index: 10;
        }
        .video__title {
            color: #fff;
            font-size: 20px;
            z-index: 5;
            position: absolute;
            left: 0;
            width: 100%;
            padding: 10px 30px;
        }
        .video__play {
            font-size: 20px;
            text-align: center;
            position: absolute;
            top: 50%;
            left: 50%;
            margin-top: -10px;
            margin-left: -50px;
            width: 140px;
            background-color: #f00;
            border-radius: 3px;
            color: #fff;
            z-index: 5;
            border-width: 0;
            padding: 10px 20px;
        }
	</style>
</head>
<body <?php body_class(); ?>>
<div class="container">
<?php
// https://youtu.be/tAGnKpE4NCI
