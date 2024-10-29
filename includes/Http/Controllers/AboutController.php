<?php

namespace Amwal\Http\Controllers;

use WP_REST_Request;
use WP_REST_Response;

class AboutController extends Controller
{
    protected $namespace = 'wc/amwal/v1';
    /**
     * Route name.
     *
     * @var string
     */
    protected $route = 'about';

    /**
     * Route methods.
     *
     * @var string
     */
    protected $method = 'GET';

    /**
     * @param  WP_REST_Request  $request
     *
     * @return WP_REST_Response
     */
    public function handle($request)
    {
        include_once 'wp-admin/includes/plugin.php';

        $activePlugins = get_option('active_plugins');
        $activePlugins = is_array($activePlugins) ? array_flip($activePlugins) : [];
        $plugins       = [];
        foreach (\get_plugins() as $name => $info) {
            $plugins[] = [
                'name'    => $info['Name'],
                'version' => $info['Version'],
                'active'  => array_key_exists($name, $activePlugins),
            ];
        }

        global $wpdb, $wp_version;
        $response = [
            'WordPress'    => [
                'version' => $wp_version,
                'plugins' => $plugins,
            ],
            'PHP'     => [
                'version'    => phpversion(),
                'extensions' => get_loaded_extensions(),
            ],
            'MySQL'   => $wpdb->db_version(),
        ];

        return new WP_REST_Response($response, 200);
    }


    public function get_permission_callback()
    {
        return $this->WCBasicAuth();
    }
}
