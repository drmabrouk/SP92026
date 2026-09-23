<?php

if (!defined('ABSPATH')) exit;

class Sportedia_Page_Generator {

    const PAGES_VERSION = '1.0.0';

    public static function get_required_pages() {
        return array(
            'sportedia_dashboard' => array(
                'title'     => 'Sportedia Dashboard',
                'shortcode' => '[sportedia_dashboard]'
            ),
            'player_registration' => array(
                'title'     => 'Player Registration',
                'shortcode' => '[sportedia_registration]'
            ),
            'player_renewal' => array(
                'title'     => 'Player Renewal',
                'shortcode' => '[sportedia_renewal]'
            ),
            'players' => array(
                'title'     => 'Players',
                'shortcode' => '[sportedia_players]'
            ),
            'player_profile' => array(
                'title'     => 'Player Profile',
                'shortcode' => '[sportedia_player]'
            ),
            'attendance' => array(
                'title'     => 'Attendance',
                'shortcode' => '[sportedia_attendance]'
            ),
            'payments' => array(
                'title'     => 'Payments',
                'shortcode' => '[sportedia_payments]'
            ),
            'invoices' => array(
                'title'     => 'Invoices',
                'shortcode' => '[sportedia_invoice]'
            ),
            'reports' => array(
                'title'     => 'Reports',
                'shortcode' => '[sportedia_reports]'
            ),
            'coach_dashboard' => array(
                'title'     => 'Coach Dashboard',
                'shortcode' => '[sportedia_coach_dashboard]'
            ),
            'branch_dashboard' => array(
                'title'     => 'Branch Dashboard',
                'shortcode' => '[sportedia_branch_dashboard]'
            ),
            'player_portal' => array(
                'title'     => 'Player Portal',
                'shortcode' => '[sportedia_player_portal]'
            ),
            'player_card' => array(
                'title'     => 'Player Card',
                'shortcode' => '[sportedia_player_card]'
            )
        );
    }

    public static function generate_pages() {
        if (!function_exists('wp_insert_post') || !function_exists('get_page_by_path')) {
            return;
        }

        $pages = self::get_required_pages();
        $stored_page_ids = get_option('sportedia_generated_page_ids', array());
        if (!is_array($stored_page_ids)) {
            $stored_page_ids = array();
        }

        foreach ($pages as $key => $page_info) {
            $page_title = $page_info['title'];
            $shortcode  = $page_info['shortcode'];
            $page_slug  = sanitize_title($page_title);

            $existing_id = $stored_page_ids[$key] ?? 0;
            $page_obj = null;

            if ($existing_id > 0) {
                $page_obj = get_post($existing_id);
                if (!$page_obj || $page_obj->post_status === 'trash') {
                    $page_obj = null;
                }
            }

            if (!$page_obj) {
                $page_obj = get_page_by_path($page_slug);
            }

            if (!$page_obj) {
                $new_id = wp_insert_post(array(
                    'post_title'     => $page_title,
                    'post_content'   => $shortcode,
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                    'post_author'    => 1,
                    'comment_status' => 'closed'
                ));

                $has_error = function_exists('is_wp_error') ? is_wp_error($new_id) : false;
                if (!$has_error && $new_id > 0) {
                    $stored_page_ids[$key] = $new_id;
                    update_post_meta($new_id, '_sportedia_page_type', $key);
                }
            } else {
                $stored_page_ids[$key] = $page_obj->ID;
                if (strpos($page_obj->post_content, $shortcode) === false) {
                    wp_update_post(array(
                        'ID'           => $page_obj->ID,
                        'post_content' => $page_obj->post_content . "\n\n" . $shortcode
                    ));
                }
            }
        }

        update_option('sportedia_generated_page_ids', $stored_page_ids);
        update_option('sportedia_pages_version', self::PAGES_VERSION);
    }
}
