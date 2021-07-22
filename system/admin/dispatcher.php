<?php declare(strict_types=1);

use html_go\exceptions\InternalException;
use html_go\model\Config;
require_once ADMIN_SYS_ROOT.DS.'functions.php';

/**
 * Main entry point for admin console requests.  This is call from there <code>route(...)</code>
 * function in the 'core/dispatcher.php' file.
 * @param string $method
 * @param string $context
 * @param string $uri
 * @throws InternalException If the HTTP Method is not supported.
 * @return \stdClass|NULL
 */
function admin_route(string $method, string $context, string $uri): ?\stdClass {
    $routes = require_once __DIR__.DS.'routes.php';

    if (isset($routes[$method])) {
        $content = admin_get_content_object($method, $context, $uri, $routes);
    } else {
       throw new InternalException("Unsupported HTTP Method [$method]");
    }

    return $content;
}

/**
 * Returns the content object for the given URI (if there is one), otherwise returns <code>null</code>.
 * @param string $method
 * @param string $context
 * @param string $uri
 * @param array<mixed> $routes
 * @throws InternalException
 * @return \stdClass|NULL
 */
function admin_get_content_object(string $method, string $context, string $uri, array $routes): ?\stdClass {
    $slug = \substr($uri, \strlen($context));
    $slug = normalize_uri($slug);

    $content = null;
    switch ($method) {
        case HTTP_GET:
            if (\array_key_exists($slug, $routes[$method])) {
                $object = $routes[$method][$slug];
                $content = \call_user_func($object->cb, ['context' => $context]);

                /*
                switch ($object->action) {
                    case 'view':
                        if (empty($content->cb) === false && \is_callable($content->cb)) {
                            $content->list = \call_user_func($content->cb, get_pagination_pagenumber(), get_config()->getInt(Config::KEY_ADMIN_ITEMS_PER_PAGE));
                        }
                        break;
                    case 'new':
                        break;
                    case 'edit':
                        if (empty($content->cb) === false && \is_callable($content->cb)) {
                            $content = \call_user_func($content->cb, get_query_parameter('id'));
                        }
                        break;
                    case 'delete':
                        break;
                    default:
                        throw new InternalException("Unknown content action [$content->action]");
                }

                $content->site = get_site_object();
                $content->context = $context;
                */
            }
            break;
        case HTTP_POST:
            break;
        default:
            throw new InternalException("Unsupported HTTP Method [$method]");
    }
    return $content;
}
