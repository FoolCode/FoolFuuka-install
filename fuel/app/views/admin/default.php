<!DOCTYPE html>
<html>
	<head>
		<title><?php echo Preferences::get('fu.gen.website_title'); ?> <?php echo __('Control Panel') ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<link rel="stylesheet" type="text/css" href="<?= URI::base() ?>assets/bootstrap2/css/bootstrap.min.css?v=<?= \Config::get('foolframe.main.version') ?>" />
		<link rel="stylesheet" type="text/css" href="<?= URI::base() ?>assets/admin/admin.css?v=<?= \Config::get('foolframe.main.version') ?>" />
		<script type="text/javascript" src="<?= URI::base() ?>assets/js/jquery.js?v=<?= \Config::get('foolframe.main.version') ?>"></script>
		<script type="text/javascript" src="<?= URI::base() ?>assets/bootstrap2/js/bootstrap.js?v=<?= \Config::get('foolframe.main.version') ?>"></script>
		<link rel="stylesheet" type="text/css" href="<?= URI::base() ?>assets/font-awesome/css/font-awesome.css?v=<?= \Config::get('foolframe.main.version') ?>" />
		<!--[if lt IE 8]>
			<link href="<?= URI::base() ?>assets/font-awesome/css/font-awesome-ie7.css?v=<?= \Config::get('foolframe.main.version') ?>" rel="stylesheet" type="text/css" />
		<![endif]-->
		<script type="text/javascript" src="<?= URI::base() ?>assets/admin/admin.js?v=<?= \Config::get('foolframe.main.version') ?>"></script>
	</head>

	<body>

		<?= $navbar; ?>

		<div class="container-fluid">
			<div class="row-fluid">
				<div style="width:16%" class="pull-left">
					<?php echo $sidebar ?>
				</div>


				<div style="width:82%" class="pull-right">
					<ul class="breadcrumb">
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

					<?php
					if (isset($method_title))
						echo '<h3>' . $method_title . '</h3>';
					?>

					<div class="alerts">
						<?php
							$notices = array_merge(Notices::get(), Notices::flash());
							foreach($notices as $notice) : ?>
							<div class="alert alert-"<?= $notice['level'] ?>">
								<?= htmlentities($notice['message'], ENT_COMPAT | ENT_IGNORE, 'UTF-8') ?>
							</div>
						<?php endforeach ?>
					</div>

					<?php
					if (isset($main_content_view))
						echo $main_content_view;
					?>


					<footer class="footer">
						<p style="padding-left: 20px;"><?php echo \Config::get('foolframe.main.name') ?> Version <?php
						echo \Config::get('foolframe.main.version');
						if (Auth::member('admin') && (\Config::get('foolframe.main.version') != Preferences::get('ff.cron.autoupgrade_version') && (Preferences::get('ff.cron.autoupgrade_version'))))
							echo ' – <a href="' . site_url('admin/system/upgrade/') . '">' . __('New upgrade available:') . ' ' . Preferences::get('ff.cron.autoupgrade_version') . '</a>';
						?></p>
					</footer>
				</div>
				<div style="clear:both"></div>
			</div>
		</div>

		<?php if(isset($backend_vars)) : ?>
		<script>
			var backend_vars = <?php echo json_encode($backend_vars) ?>;
		</script>
		<?php endif; ?>
	</body>
</html>