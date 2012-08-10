<!DOCTYPE html>
<html>
	<head>
		<title><?= $title ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<link rel="stylesheet" type="text/css" href="<?= \URI::base().'assets/bootstrap2/css/bootstrap.min.css?v='.\Config::get('foolframe.main.version') ?>" />
		<link rel="stylesheet" type="text/css" href="<?= \URI::base().'assets/font-awesome/css/font-awesome.css?v='.\Config::get('foolframe.main.version') ?>" />
		<!--[if lt IE 8]>
			<link href="<?= \URI::base().'assets/font-awesome/css/font-awesome-ie7.css?v='.\Config::get('foolframe.main.version') ?>" rel="stylesheet" type="text/css" />
		<![endif]-->
		<link rel="stylesheet" type="text/css" href="<?= \URI::base().'assets/install/style.css?v='.\Config::get('foolframe.main.version') ?>" />
		<script type="text/javascript" src="<?= \URI::base().'assets/js/jquery.js?v='.\Config::get('foolframe.main.version') ?>"></script>
		<script type="text/javascript" src="<?= \URI::base().'assets/admin/admin.js?v='.\Config::get('foolframe.main.version') ?>"></script>
		<script type="text/javascript" src="<?= \URI::base().'assets/bootstrap2/js/bootstrap.js?v='.\Config::get('foolframe.main.version') ?>"></script>
	</head>

	<body>

		<div class="container-fluid clearfix">

			<?= $sidebar ?>

			<div class="row-fluid" style="margin-top: 10px;">
				<div>
					<div class="satin content-rounded clearfix">
						<?php if (isset($method_title)) : ?>
							<h3><?= $method_title ?></h3>
						<?php endif; ?>

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

						<?php if (isset($main_content_view)) : ?>
							<?= $main_content_view ?>
						<?php endif; ?>
					</div>
				</div>
				<div style="clear: both;"></div>
			</div>

		</div>
	</body>
</html>