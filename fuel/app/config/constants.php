<?php

define('FOOL_VERSION', '0.7.6');
define('FOOL_NAME', 'FoOlFuuka');
define('FOOL_MANUAL_INSTALL_URL', 'http://ask.foolrulez.com');
define('FOOL_GIT_TAGS_URL', 'https://api.github.com/repos/foolrulez/foolfuuka/tags');
define('FOOL_GIT_CHANGELOG_URL', 'https://raw.github.com/foolrulez/FoOlFuuka/master/CHANGELOG.md');
define('FOOL_REQUIREMENT_PHP', '5.3.0');
define('FOOL_PLUGIN_DIR', 'content/plugins/');
define('FOOL_PROTECTED_RADIXES', serialize(array('content', 'assets', 'admin', 'install', 'feeds', 'api', 'cli', 'functions', 'search')));

// files
define('FOOL_FILES_DIR_MODE', 0755);

// preferences from get_setting('value', FOOL_PREF_ETC);
define('FOOL_GEN_WEBSITE_TITLE', 'FoOlFuuka');
define('FOOL_GEN_INDEX_TITLE', 'FoOlFuuka');

define('FOOL_PREF_SYS_SUBDOMAIN', FALSE);

// sphinx search syste
define('FOOL_PREF_SPHINX_LISTEN', '127.0.0.1:9306');
define('FOOL_PREF_SPHINX_LISTEN_MYSQL', '127.0.0.1:9306');
define('FOOL_PREF_SPHINX_DIR', '/usr/local/sphinx/var');
define('FOOL_PREF_SPHINX_MIN_WORD', 3);
define('FOOL_PREF_SPHINX_MEMORY', 2047);

// application paths
define('FOOL_PREF_SERV_JAVA_PATH', 'java');

// languages
define('FOOL_LANG_DEFAULT', 'en_EN');

// theme
define('FOOL_THEME_DEFAULT', 'default');
define('FOOL_PREF_THEMES_THEME_DEFAULT_ENABLED', TRUE);
define('FOOL_PREF_THEMES_THEME_FUUKA_ENABLED', TRUE);
define('FOOL_PREF_THEMES_THEME_YOTSUBA_ENABLED', FALSE);

define('FOOL_RADIX_THREADS_PER_PAGE', 10);
define('FOOL_RADIX_THUMB_OP_WIDTH', 250);
define('FOOL_RADIX_THUMB_OP_HEIGHT', 250);
define('FOOL_RADIX_THUMB_REPLY_WIDTH', 125);
define('FOOL_RADIX_THUMB_REPLY_HEIGHT', 125);
define('FOOL_RADIX_MAX_IMAGE_SIZE_KILOBYTES', 3072);
define('FOOL_RADIX_MAX_IMAGE_SIZE_WIDTH', 5000);
define('FOOL_RADIX_MAX_IMAGE_SIZE_HEIGHT', 5000);
define('FOOL_RADIX_MAX_POSTS_COUNT', 400);
define('FOOL_RADIX_MAX_IMAGES_COUNT', 250);
define('FOOL_RADIX_MIN_IMAGE_REPOST_HOURS', 0);
define('FOOL_RADIX_MYISAM_SEARCH', FALSE);
define('FOOL_RADIX_ANONYMOUS_DEFAULT_NAME', 'Anonymous');

define('FOOLFUUKA_BOARDS_DIRECTORY', 'content/boards');


define('FOOL_AUTH_GROUP_ID_MEMBER', 0);
define('FOOL_AUTH_GROUP_ID_ADMIN', 1);
define('FOOL_AUTH_GROUP_ID_MOD', 2);


define(
	'FOOLFUUKA_SECURE_TRIPCODE_SALT', '
	FW6I5Es311r2JV6EJSnrR2+hw37jIfGI0FB0XU5+9lua9iCCrwgkZDVRZ+1PuClqC+78FiA6hhhX
	U1oq6OyFx/MWYx6tKsYeSA8cAs969NNMQ98SzdLFD7ZifHFreNdrfub3xNQBU21rknftdESFRTUr
	44nqCZ0wyzVVDySGUZkbtyHhnj+cknbZqDu/wjhX/HjSitRbtotpozhF4C9F+MoQCr3LgKg+CiYH
	s3Phd3xk6UC2BG2EU83PignJMOCfxzA02gpVHuwy3sx7hX4yvOYBvo0kCsk7B5DURBaNWH0srWz4
	MpXRcDletGGCeKOz9Hn1WXJu78ZdxC58VDl20UIT9er5QLnWiF1giIGQXQMqBB+Rd48/suEWAOH2
	H9WYimTJWTrK397HMWepK6LJaUB5GdIk56ZAULjgZB29qx8Cl+1K0JWQ0SI5LrdjgyZZUTX8LB/6
	Coix9e6+3c05Pk6Bi1GWsMWcJUf7rL9tpsxROtq0AAQBPQ0rTlstFEziwm3vRaTZvPRboQfREta0
	9VA+tRiWfN3XP+1bbMS9exKacGLMxR/bmO5A57AgQF+bPjhif5M/OOJ6J/76q0JDHA=='
);