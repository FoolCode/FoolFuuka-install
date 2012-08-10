<p>
	<?= __('FoolFrame is checking to ensure that your server meets the minimum requirements to run the framework. It will also determine if your server environment is properly configured to run the framework properly.') ?>
</p>

<p>
	<ul style="margin: 20px 40px">
	<?php $error = false ?>
	<?php foreach ($system as $key => $item) : ?>
		<h4><?= e($item['string']) ?></h4>

		<table style="width: 90%; margin: 0 auto 10px;">
			<tbody>
			<?php foreach ($item['checks'] as $k => $check) : ?>
				<tr<?php if ($check['result']) : ?> style="border-bottom: 1px solid #ddd;"<?php endif; ?>>
					<td style="width: 10px">
						<?php if (isset($check['debug'])) : ?>
							<a href="#<?= $k ?>" rel="popover" data-title="<?= htmlspecialchars($check['string']) ?>" data-content="<?= htmlspecialchars($check['debug']) ?>" data-placement="left"><i class="icon-info-sign" style="color: #d3d3d3"></i></a>
						<?php endif; ?>

					</td>

					<td style="padding: 2px 0 2px 5px; width: 200px; text-align: left">
						<?= e($check['string']) ?>
					</td>

					<td style="padding: 2px 10px 2px 0; text-align: right">
						<?= e($check['value']) ?>

						<?php
						if ($check['result'])
						{
							$icon = array('label' => 'label-success', 'sign' => 'icon-ok');
						}
						else
						{
							switch ($check['level'])
							{
								case 'crit':
									$error = true;
									$icon = array('label' => 'label-important', 'sign' => 'icon-remove');
									break;
								default:
									$icon = array('label' => 'label-warning', 'sign' => 'icon-warning-sign');
							}
						}
						?>
						<span class="label <?= $icon['label'] ?>"><i class="<?= $icon['sign'] ?>"</span>
					</td>
				</tr>

				<?php if ( ! $check['result'] && isset($check['error'])) : ?>
					<tr style="border-bottom: 1px solid #ddd;">
						<td style="padding: 0 0 0 20px; font-size: 0.8em; color: #ff0000;" colspan="3">
							<?= e($check['error']) ?>
						</td>
					</tr>
				<?php endif; ?>
			<?php endforeach; ?>
			</tbody>
		</table>

		<br class="clearfix" />
	<?php endforeach; ?>
	</ul>
</p>

<?php if ( ! $error) : ?>
	<p style="text-align:center;"><?= e(__('Congratulations! It seems that your server passed all system requirements to run FoolFrame.')) ?></p>

	<hr />

	<a href="<?= \Uri::create('install/database_setup') ?>" class="btn btn-large btn-success pull-right"><?= __('Next') ?></a>
<?php else : ?>
	<p style="text-align:center;"><?= e(__('Sorry, your server failed to pass all of the essential requirements to run FoolFrame. Please view the information above and ensure that your environment is properly configured.')) ?></p>
<?php endif; ?>