<?php

/*
 * Plugin Name: Portable URLs
 * Plugin URI:  http://github.com/xemlock/wp-portable-urls
 * Description: Ever had a problem with your uploaded media files after moving your WordPress blog to a new location? This plugin ensures it's in the past now.
 * Version:     0.1
 * Author:      xemlock
 * Author URI:  http://xemlock.pl
 * License:     GNU General Public License v2.0
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

abstract class wp_portable_urls
{
    /**
     * @param  int $post_id
     * @return void
     */
    public static function save_post($post_id)
    {
        $post = get_post($post_id);

        if (empty($post)) {
            return;
        }

        $content = self::encode_siteurl($post->post_content);

        if ($content !== $post->post_content) {
            // do not use wp_update_post() as it creates a new post revision,
            // we need to directly modify the post record in the database
            global $wpdb;

            $wpdb->query(sprintf(
                "UPDATE {$wpdb->posts} SET post_content = '%s' WHERE ID = %d",
                esc_sql($content), $post_id
            ));

            // delete post cache
            wp_cache_delete($post_id, 'posts');
        }
    }

    /**
     * @param  string $content
     * @return string
     */
    public static function encode_siteurl($content)
    {
        // get_option('siteurl') is equivalent to get_bloginfo('wpurl')
        // Consider using site_url() instead, especially for multi-site
        // configurations using paths instead of subdomains (it will return
        // the root site not the current sub-site). Read more:
        // http://codex.wordpress.org/Function_Reference/get_bloginfo
        $siteurl = preg_replace('#^https?://#i', '', site_url());
        $content = str_ireplace(
            array(
                'http://' . $siteurl,
                'https://' . $siteurl,
            ),
            array(
                '[siteurl]',
                '[siteurl ssl="true"]',
            ),
            $content
        );
        return $content;
    }

    /**
     * @param  string $content
     * @return string
     */
    public static function decode_siteurl($content)
    {
        $siteurl = preg_replace('#^https?://#i', '', site_url());
        $content = str_ireplace(
            array(
                '[siteurl]',
                '[siteurl ssl="true"]',
            ),
            array(
                'http://' . $siteurl,
                'https://' . $siteurl,
            ),
            $content
        );

        return $content;
    }

    public static function register()
    {
        add_action('save_post',         array(__CLASS__, 'save_post'));
        add_action('edit_post_content', array(__CLASS__, 'decode_siteurl'));
        add_action('the_content',       array(__CLASS__, 'decode_siteurl'));
    }
}

if (defined('ABSPATH')) {
    wp_portable_urls::register();
}
