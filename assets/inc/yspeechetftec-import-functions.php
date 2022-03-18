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
        $uri_params = $uri_params . 'regions=' . $yspeechetftec_options['yspeechetftec_region_ids'];
    }


    if (strlen($uri_params) > 0) {
        if (isset($yspeechetftec_options['yspeechetftec_license_key']) && strlen(trim($yspeechetftec_options['yspeechetftec_license_key'])) > 0) {
            $uri_params = $uri_params . 'license=' . $yspeechetftec_options['yspeechetftec_license_key'];
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => YSPEECHTFTEC_ET_XML_BASE_URL . $uri_params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            echo "<hr>cURL Error #:" . $err;
        } else {
            $events = json_decode($response);

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
                    print_r($e);
                }
            }
        }
    }
}
