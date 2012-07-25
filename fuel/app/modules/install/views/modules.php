<p><?= __('Congratulations, you have installed FoolFrame.'); ?></p>

<p><?= __('Now you can choose which modules would you like to run.') ?></p>

<br/>

<?= \Form::open() ?>

<label class="checkbox"><input type="checkbox" name="foolfuuka"> FoolFuuka imageboard</label>

<p style="font-size: 0.8em"><?= __('FoolFuuka is the most advanced imageboard ever created. You will be able to deal with hundreds of millions of posts, thousands of users at the same time, or spend your time loving your community.') ?></p>

<br/>

<label class="checkbox"><input type="checkbox" name="foolfuuka"> FoolPod software updater</label>

<p style="font-size: 0.8em"><?= __('FoolPod is the distribution system that delivers updates to FoolFrame. We use this to let you download FoolFrame upgrades, modules, themes and plugins. You don\'t need to install this, it\'s our internal software') ?></p>

<br/>

<label class="checkbox"><input type="checkbox" name="foolfuuka" disabled="disabled"> FoolSlide2 comic reader</label>

<p style="font-size: 0.8em"><?= __('FoolSlide is the slickest comic visualizer for your readers. Use it with FoolFuuka to create a community, or standalone to offer the best comic reading experience available inside a browser.') ?></p>

<br/>

<?= \Form::submit(array(
	'name' => 'submit',
	'value' => __('Install these'),
	'class' => 'btn btn-success btn-large pull-right',
)); ?>

<?= \Form::close() ?>