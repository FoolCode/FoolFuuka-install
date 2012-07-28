<div class="navbar navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container">
			<a class="brand" href="<?php echo URI::create('admin') ?>">
				<?php echo Preferences::get('ff.gen.website_title'); ?> - <?php echo __('Control Panel'); ?>
			</a>
			<ul class="nav">
				<li class="active">
					<a href="<?php echo URI::create('admin') ?>">Home</a>
				</li>
			</ul>
			<ul class="nav pull-right">
				<li><a href="<?php echo URI::create('@default') ?>"><?php echo __('Boards') ?></a></li>
				<li class="divider-vertical"></li>
				<?php
				if (\Auth::has_access('maccess.user')):?>
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown">
							<?php echo \Auth::get_screen_name(); ?>
							<b class="caret"></b>
						</a>
						<ul class="dropdown-menu">
							<li>
								<a href="<?php echo URI::create('admin/auth/change_email'); ?>">
									<?php echo __("Your Profile") ?>
								</a>
							</li>
							<li>
								<a href="<?php echo URI::create('/admin/auth/logout'); ?>">
									<?php echo __("Logout") ?>
								</a>
							</li>
							<li>
								<a href="<?php echo URI::create('/admin/auth/logout_all'); ?>">
									<?php echo __("Logout all devices") ?>
								</a>
							</li>
						</ul>
					</li>
				<?php else : ?>
					<li><a href="<?php echo URI::create('admin/auth/login') ?>"><?php echo __('Login') ?></a></li>
				<?php endif; ?>
			</ul>
		</div>
	</div>
</div>