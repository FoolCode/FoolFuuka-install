<!DOCTYPE html>
<html>
	<head>
		<title><?= $title ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<link rel="stylesheet" type="text/css" href="<?= \URI::base() ?>assets/bootstrap2/css/bootstrap.min.css?v=<?= \Config::get('foolframe.main.version') ?>" />
		<script type="text/javascript" src="<?= \URI::base() ?>assets/js/jquery.js?v=<?= \Config::get('foolframe.main.version') ?>"></script>
		<script type="text/javascript" src="<?= \URI::base() ?>assets/bootstrap2/js/bootstrap.js?v=<?= \Config::get('foolframe.main.version') ?>"></script>
		<link rel="stylesheet" type="text/css" href="<?= \URI::base() ?>assets/font-awesome/css/font-awesome.css?v=<?= \Config::get('foolframe.main.version') ?>" />
		<!--[if lt IE 8]>
			<link href="<?= \URI::base() ?>assets/font-awesome/css/font-awesome-ie7.css?v=<?= \Config::get('foolframe.main.version') ?>" rel="stylesheet" type="text/css" />
		<![endif]-->
		<link rel="stylesheet" type="text/css" href="<?= \URI::base() ?>assets/install/style.css?v=<?= \Config::get('foolframe.main.version') ?>" />
		<script type="text/javascript" src="<?= \URI::base() ?>assets/admin/admin.js?v=<?= \Config::get('foolframe.main.version') ?>"></script>
	</head>

	<body>

		<div class="container-fluid clearfix">

			<?= $sidebar ?>
			<div class="row-fluid" style="margin-top:10px;">


				<div>
					<ul class="breadcrumb" style="display:none">
						<?php
						echo '<li>' . $controller_title . '</li>';

						if (isset($method_title))
							echo ' <span class="divider">/</span> <li>' . $method_title . '</li>';
						if (isset($extra_title) && !empty($extra_title))
						{
							$breadcrumbs = count($extra_title);
							$count = 1;
							foreach ($extra_title as $item)
							{
								echo ' <span class="divider">/</span> ';
								if ($count == $breadcrumbs)
									echo '<li class="active">' . $item . '</li>';
								else
									echo '<li>' . $item . '</li>';
							}
						}
						?>
					</ul>

					<div class="satin content-rounded clearfix">
						<?php
						if (isset($method_title))
							echo '<h3>' . $method_title . '</h3>';
						?>

						<div class="alerts">
							<?= isset($error) ? '<div class="alert">'.$error.'</div>' : '' ?>
						</div>

						<?php
						if (isset($main_content_view))
							echo $main_content_view;
						?>
					</div>
				</div>
				<div style="clear:both"></div>
			</div>
		</div>
	</body>
</html>