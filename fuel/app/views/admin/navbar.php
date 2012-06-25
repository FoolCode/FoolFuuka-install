<div class="navbar navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container">
			<a class="brand" href="<?php echo URI::create('admin') ?>">
				<?php echo Preferences::get('fs_gen_site_title', FOOL_NAME); ?> - <?php echo __('Control Panel'); ?>
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
				if (Auth::member('user')):?>
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown">
							<?php echo $this->auth->get_username(); ?>
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
						</ul>
					</li>
				<?php else : ?>
					<li><a href="<?php echo URI::create('admin/auth/login') ?>"><?php echo __('Login') ?></a></li>
				<?php endif; ?>
			</ul>
		</div>
	</div>
</div>