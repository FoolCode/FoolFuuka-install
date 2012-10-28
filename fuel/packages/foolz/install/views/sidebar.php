<div class="sidebar content-rounded pull-left">
	<h2><?= \Config::get('foolframe.main.name') ?></h2>

	<ol>
		<?php $count = 0 ?>
		<?php foreach($sidebar as $key => $item) : ?>
			<?php
			$label = 'green';

			if (!$current)
			{
				$label = '';
			}
			else
			{
				$count ++;
			}

			if ($key == $current)
			{
				$label = 'blue';
				$current = false;
			}

			?>
			<li class="<?= $label ?>"><?= $item ?></li>
		<?php endforeach; ?>
	</ol>

	<?php $percent = floor(($count - 1) / (count($sidebar) - 1) * 100) ?>
	<div class="progress progress-striped  <?= ($percent != 100) ? 'active': 'progress-success' ?>" style="margin-top: 20px">
		<div class="bar" style="width: <?= $percent ?>%;"></div>
		<?= $percent ?>%
	</div>

	<footer class="footer">
		<p>Version <?= \Config::get('foolframe.main.version') ?></p>
	</footer>
</div>