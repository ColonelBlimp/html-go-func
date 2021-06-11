<?php declare(strict_types=1);

// Application paths
\define('DS', DIRECTORY_SEPARATOR);
\define('APP_ROOT', \realpath(__DIR__.DS.'..'.DS.'..'));
\define('CONFIG_ROOT', APP_ROOT.DS.'config');
\define('THEMES_ROOT', APP_ROOT.DS.'themes');
\define('CACHE_ROOT', APP_ROOT.DS.'cache');
\define('LANG_ROOT', APP_ROOT.DS.'lang');
\define('AUTHOR_ROOT', CONFIG_ROOT.DS.'users');

\define('FWD_SLASH', '/');
\define('CONTENT_FILE_EXT', '.json');
\define('CONTENT_FILE_EXT_LEN', \strlen(CONTENT_FILE_EXT));
\define('MODE', 0777);
\define('DEFAULT_TEMPLATE', 'main.html');

\define('ENUM_PAGE', 'page');
\define('ENUM_CATEGORY', 'category');
\define('ENUM_POST', 'post');
\define('ENUM_TAG', 'tag');

\define('POST_LIST_TYPE', 0);
\define('CAT_LIST_TYPE', 1);
\define('TAG_LIST_TYPE', 2);
\define('EMPTY_VALUE', '');

// Special cases
\define('HOME_INDEX_KEY', '/');
\define('BLOG_INDEX_KEY', 'blog');
\define('CAT_INDEX_KEY', 'category');
\define('TAG_INDEX_KEY', 'tag');

// dispatcher.php
\define('HTTP_GET', 'GET');
\define('HTTP_POST', 'POST');
\define('REGEX', 'regex');
\define('HANDLER', 'handler');
\define('POST_REQ_REGEX', '/^\d{4}\/\d{2}\/.+/i');
