<p><?= __('Welcome to the FoolFrame installation.') ?></p>

<p><?= __('FoolFrame is a framework that provides basic capabilities that can be used through several applications.') ?></p>

<p><?= __('This installation will check if your server is capable of running FoolFrame, connect it to a database and install the modules you\'re interested in.') ?></p>

<hr/>

<a href="<?= \Uri::create('install/system_check') ?>" class="btn btn-large btn-success pull-right"><?= __('Next') ?></a>