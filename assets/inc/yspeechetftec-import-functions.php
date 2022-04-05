<?php
define('YSPEECHTFTEC_ET_XML_BASE_URL', 'https://yspeech.de/wp-json/yeta/v1/import?');

/**
 * Import events
 * 
 */
function yspeechetftec_import_events()
{
    $yspeechetftec_options = get_option('yspeechetftec_option_name');

    $uri_params = '';

    if (isset($yspeechetftec_options['yspeechetftec_organizer_ids']) && strlen(trim($yspeechetftec_options['yspeechetftec_organizer_ids'])) > 0) {

        $uri_params = $uri_params . 'vids=' . $yspeechetftec_options['yspeechetftec_organizer_ids'];
    }
    if (isset($yspeechetftec_options['yspeechetftec_region_ids']) && strlen(trim($yspeechetftec_options['yspeechetftec_region_ids'])) > 0) {
        if (strlen($uri_params) > 0) {
            $uri_params = $uri_params . '&';
        }
        $uri_params = $uri_params . 'regions=' . $yspeechetftec_options['yspeechetftec_region_ids'];
    }


    if (strlen($uri_params) > 0) {

        try {
            if (isset($yspeechetftec_options['yspeechetftec_license_key']) && strlen(trim($yspeechetftec_options['yspeechetftec_license_key'])) > 0) {
                $uri_params = $uri_params . '&license=' . $yspeechetftec_options['yspeechetftec_license_key'];
            }

            $response = wp_remote_get(
                YSPEECHTFTEC_ET_XML_BASE_URL  . $uri_params
                    . '&X-API-Key=' . YSPEECHETFTEC_YSPEECH_LICENSE_API_APPLICATION_AUTH,
                array(
                    'timeout' => 30,
                    'redirection' => 10,
                    'method' => 'GET',
                    'httpversion' => 1.1,
                    'headers' => array(
                        'Content-Type'  => 'application/json',
                    )
                )
            );

            $events = json_decode(wp_remote_retrieve_body($response));
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code === 200) {
                foreach ($events as $event) {
                    $event->post_author = $yspeechetftec_options['yspeechetftec_publisher_id'];

                    $categories = explode(', ', (string) $event->categories);

                    $hierarchicalTerms = array();
                    foreach ($categories as $cat) {
                        $cat = trim($cat);
                        if (strlen($cat) >= 2) {
                            $term = term_exists($cat, Tribe__Events__Main::TAXONOMY);
                            if ($term === null) {
                                $term = wp_insert_term($cat, Tribe__Events__Main::TAXONOMY);
                            }
                            array_push($hierarchicalTerms, $term['term_id']);
                        }
                    }

                    if (isset($event->Organizer)) {
                        $rd_args = array(
                            'post_type' => 'tribe_organizer',
                            'numberposts' => -1,
                            'title' => (string) $event->Organizer->Organizer,
                        );

                        $rd_query = get_posts($rd_args);

                        if (count($rd_query) === 1) {
                            $event->Organizer->OrganizerID = $rd_query[0]->ID;
                        }
                    }

                    $event->tax_input = array(Tribe__Events__Main::TAXONOMY => $hierarchicalTerms);
                    $event = json_decode(json_encode($event), true);
                    // Save or update event
                    try {
                        if (tribe_post_exists($event['import_id'])) {
                            tribe_update_event($event['import_id'], $event);
                        } else {
                            tribe_create_event($event);
                        }
                    } catch (Exception $e) {
                        echo (esc_html("<hr>Error #:" . $e));
                    }
                }
            }
        } catch (Exception $e) {
            echo (esc_html("<hr>cURL Error #:" . $e));
        }
    }

    if (is_admin()) {
        wp_safe_redirect(get_admin_url() . 'admin.php?page=yspeechetftec-ev-termine');
        exit;
    }
}
