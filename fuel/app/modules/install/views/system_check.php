<p><?= __('Will you be able to run FoolFrame? Here\'s a rundown:') ?></p>

<p>
<ul style="margin: 20px 40px">

	<?php $error = false ?>



	<?php foreach ($check as $key => $item) : ?>
		<h3><?= e($item['string']) ?></h3>

		<table style="width:80%; margin: 0 auto 10px;">
			<thead></thead>
			<tbody>
			<?php foreach ($item['checks'] as $k => $i) : ?>
				<tr style="border-bottom: 1px solid #ddd">
					<td style="padding:2px 0 2px 10px; width:200px; text-align:left"><?= e($i['string']) ?></td>
					<td style="padding:2px 10px 2px 0;  text-align:right">
					<?php if ($i['result']) : ?>
						<span class="label label-success"><i class="icon-ok"></i> <?= __('Available!') ?></i></span>
					<?php else : ?>
						<?php $error = true ?>
						<?php if ($i['level'] == 'crit') : ?>
							<span class="label label-important"><i class="icon-remove"></i>
						<?php else : ?>
							<span class="label label-warning"><i class="icon-warning-sign"></i>
						<?php endif; ?>
						<?= __('Not available') ?>
						</span></td>
						</tr>
						<tr style="border-bottom: 1px solid #ddd"><td style="font-size:0.8em"><?= e($i['error']) ?></td></tr>
						<tr>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endforeach; ?>

</ul>
</p>

<br/>

<?php if (!$error) : ?>
	<p><?= e(__('Congratulations! Your server is able to run FoolFrame. Next, we\'ll check if we can connect to a database.')) ?></p>
	<hr/>
	<a href="<?= \Uri::create('install/database_connection') ?>" class="btn btn-large btn-success pull-right"><?= __('Next') ?></a>
<?php else : ?>
	<p><?= e(__('FoolFrame won\'t be able to run if the above isn\'t available. You will have to install and update the components to be able to run FoolFrame.')) ?>
<?php endif; ?>