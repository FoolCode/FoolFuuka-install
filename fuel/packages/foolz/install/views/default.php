<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title><?= $title ?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<link rel="stylesheet" type="text/css" href="<?= \Uri::base().'assets/bootstrap2/css/bootstrap.min.css?v='.\Foolz\Config\Config::get('foolz/foolframe', 'package', 'main.version') ?>" />
		<link rel="stylesheet" type="text/css" href="<?= \Uri::base().'assets/bootstrap2/css/bootstrap-responsive.min.css?v='.\Foolz\Config\Config::get('foolz/foolframe', 'package', 'main.version') ?>" />
		<script type="text/javascript" src="<?= \Uri::base().'assets/js/jquery.js?v='. \Foolz\Config\Config::get('foolz/foolframe', 'package', 'main.version') ?>"></script>
		<script type="text/javascript" src="<?= \Uri::base().'assets/bootstrap2/js/bootstrap.min.js?v='.\Foolz\Config\Config::get('foolz/foolframe', 'package', 'main.version') ?>"></script>
		<link rel="stylesheet" type="text/css" href="<?= \Uri::base().'assets/font-awesome/css/font-awesome.css?v='.\Foolz\Config\Config::get('foolz/foolframe', 'package', 'main.version') ?>" />
		<!--[if lt IE 8]>
			<link href="<?= \Uri::base().'assets/font-awesome/css/font-awesome-ie7.css?v='.\Foolz\Config\Config::get('foolz/foolframe', 'package', 'main.version') ?>" rel="stylesheet" type="text/css" />
		<![endif]-->
		<style type="text/css">
			body {
				padding-top: 40px;
				padding-bottom: 60px;
			}
			.well {
				padding: 1px;
			}
			.system-check-container {
				position: relative;
				margin: 15px 0;
				padding: 15px 15px 10px;
				background-color: #fff;
				border: 1px solid #ddd;
				-webkit-border-radius: 4px;
				-moz-border-radius: 4px;
				border-radius: 4px;
			}

			.system-check-container-header {
				position: relative;
				top: -16px;
				left: -16px;
				padding: 3px 7px;
				font-size: 12px;
				font-weight: bold;
				background-color: #f5f5f5;
				border: 1px solid #ddd;
				color: #9da0a4;
				-webkit-border-radius: 4px 0 4px 0;
				-moz-border-radius: 4px 0 4px 0;
				border-radius: 4px 0 4px 0;
			}
			.install-sidebar {
				margin-top: 70px;
			}
		</style>
	</head>

	<body>
		<div class="container">
			<div class="row">
				<div class="span3 install-sidebar">
					<?= $sidebar ?>
				</div>

				<div class="span9">
					<h2><?= $method_title ?></h2>

					<hr/>

					<div class="alerts">
						<?php if (isset($errors)) : ?>
							<div class="alert alert-error">
								<strong><?= __('Error!') ?></strong>

								<?php if (is_array($errors)) : ?>
									<?= __('Please resolve the following errors:') ?>

									<ul>
									<?php foreach ($errors as $error) : ?>
										<li><?= $error ?></li>
									<?php endforeach; ?>
									</ul>
								<?php else : ?>
									<?= $errors ?>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<?php $notices = array_merge(\Notices::get(), \Notices::getFlash()); ?>
						<?php foreach ($notices as $notice) : ?>
							<div class="alert alert-<?= $notice['level'] ?>">
								<?php if (is_array($notice['message'])) : ?>
									<ul>
										<?php foreach ($notice['message'] as $message) : ?>
											<li><?= htmlentities($message, ENT_COMPAT | ENT_IGNORE, 'UTF-8') ?></li>
										<?php endforeach; ?>
									</ul>
								<?php else : ?>
									<?= htmlentities($notice['message'], ENT_COMPAT | ENT_IGNORE, 'UTF-8') ?>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>

					<?php if (isset($main_content_view)) : ?>
						<?= $main_content_view ?>
					<?php endif; ?>
				</div>
				<div style="clear:both"></div>
			</div>
		</div>

		<?= \Security::js_set_token(); ?>
	</body>
</html>