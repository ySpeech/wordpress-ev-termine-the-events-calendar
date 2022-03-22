<?php
/*
*
*	***** Evangelische Termine for The Events Calendar *****
*
*	YSPEECHETFTEC Core Functions
*	
*/
// If this file is called directly, abort. //
if (!defined('WPINC')) {
    die;
} // end if

define('ET_DONATION_URL', 'https://www.paypal.com/donate/?hosted_button_id=3PW7J5C6R4QC6&locale.x=de_DE');
define('YSPEECHETFTEC_YSPEECH_LICENSE_BASE_URL', 'https://yspeech.de/wp-json/lmfwc/v2/licenses/');
define('YSPEECHETFTEC_YSPEECH_BASE_URL', 'https://yspeech.de/');
define('YSPEECHETFTEC_YSPEECH_API_AUTH', 'Basic ' . base64_encode('ck_da85e37cfb901c692ada8a1aa0fecedc5b4f1805:cs_5a1db144db15362dc3b6fdeba3814d4853e80619'));


function yspeechetftec_cron_exec()
{
    yspeechetftec_import_events();
}
add_action('yspeechetftec_cron_hook',  'yspeechetftec_cron_exec');

function yspeechetftec_cron_registration()
{
    if (!wp_next_scheduled('yspeechetftec_cron_hook')) {
        wp_schedule_event(time(), 'hourly', 'yspeechetftec_cron_hook');
    }
    if (wp_next_scheduled('mycronjob')) {
        $timestamp = wp_next_scheduled('mycronjob');
        wp_unschedule_event($timestamp, 'mycronjob');
    }
}
add_action('wp', 'yspeechetftec_cron_registration');

/*
*
* Run on plugin activation
*
*/
function yspeechetftec_activate_plugin()
{
}
register_activation_hook(__FILE__, 'yspeechetftec_activate_plugin');

/*
*
* Run on plugin deactivation
*
*/
function yspeechetftec_deactivate_plugin()
{
    $timestamp = wp_next_scheduled('yspeechetftec_cron_hook');
    wp_unschedule_event($timestamp, 'yspeechetftec_cron_hook');
}
register_deactivation_hook(__FILE__, 'yspeechetftec_deactivate_plugin');
register_uninstall_hook(__FILE__, 'yspeechetftec_deactivate_plugin');

class YSPEECHETFTEC_Admin
{
    private $yspeechetftec_import;
    private $yspeechetftec_license;

    public function __construct()
    {
        add_action('admin_menu', array($this, 'yspeechetftec_add_plugin_page'));
        add_action('admin_init', array($this, 'yspeechetftec_page_init'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__),  array($this, 'yspeechetftec_add_plugin_page_links'));

        add_action('admin_post_yspeechetftec_manual_import', array($this, 'yspeechetftec_manual_import_events'));
    }

    public function yspeechetftec_add_plugin_page_links($links)
    {
        $links[] = '<a href="' .
            admin_url('admin.php?page=yspeechetftec-ev-termine') .
            '">' . __('Einstellungen') . '</a>';

        $links[] = '<strong><a href="' .
            ET_DONATION_URL .
            '" target="_blank">' . __('Spenden') . '</a></strong>';
        return $links;
    }

    public function yspeechetftec_add_plugin_page()
    {
        add_menu_page(
            __('Einstellungen', 'yspeechetftec'), // page_title
            __('Ev. Termine', 'yspeechetftec'), // menu_title
            'manage_options', // capability
            'yspeechetftec-ev-termine', // menu_slug
            array($this, 'yspeechetftec_create_admin_page'), // function
            'dashicons-calendar', // icon_url
            6 // position
        );
    }

    public function yspeechetftec_create_admin_page()
    {
        $this->yspeechetftec_import = get_option('yspeechetftec_import');
        $this->yspeechetftec_license = get_option('yspeechetftec_license');

        //Get the active tab from the $_GET param
        $default_tab = null;
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : $default_tab;

?>

        <div class="wrap">
            <h2><?php esc_html_e('Evangelische Termine', 'yspeechetftec') ?></h2>
            <p></p>
            <?php settings_errors(); ?>
            <!-- Here are our tabs -->
            <nav class="nav-tab-wrapper">
                <a href="?page=yspeechetftec-ev-termine" class="nav-tab <?php if ($tab === null) : ?>nav-tab-active<?php endif; ?>">Import</a>
                <a href="?page=yspeechetftec-ev-termine&tab=license" class="nav-tab <?php if ($tab === 'license') : ?>nav-tab-active<?php endif; ?>">Lizenz</a>
            </nav>
            <div class="tab-content">
                <?php switch ($tab):
                    case 'license':

                        echo (esc_html('<form method="post" action="options.php">'));
                        echo (esc_html('<input type="hidden" name="sent" value="1" /> '));

                        settings_fields('yspeechetftec_license_group');
                        do_settings_sections('yspeechetftec-settings-license');
                        submit_button(__('Speichern', 'yspeechetftec'), 'primary', 'Update');
                        $show_license_info = true;
                        if (isset($this->yspeechetftec_license['yspeechetftec_license_key'])) {
                            if ($this->yspeechetftec_check_license($this->yspeechetftec_license['yspeechetftec_license_key'])) {
                                $show_license_info = false;
                            }
                        }

                        if ($show_license_info) {
                ?>
                            <hr />

                            <div class="yspeechetftec-license-info-block">

                                <p>
                                    &starf; Holen Sie sich jetzt unsere Premium-Version und deaktivieren Sie das Importlimit von 5 Events.

                                </p>
                                <a href="<?php echo (esc_url(YSPEECHETFTEC_YSPEECH_BASE_URL)); ?>shop/evangelische-termine-fuer-the-events-calendar/" target="_blank">
                                    <div class="button button-primary">
                                        Lizenz kaufen
                                    </div>
                                </a>
                            </div>
                        <?php
                        }

                        break;
                    default:
                        echo (esc_html('<form method="post" action="options.php">'));
                        echo (esc_html('<input type="hidden" name="sent" value="1" /> '));

                        settings_fields('yspeechetftec_import_group');
                        do_settings_sections('yspeechetftec-settings-import');
                        submit_button(__('Speichern', 'yspeechetftec'), 'primary', 'Update');
                        ?>
                        </form>
                        <hr />
                        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                            <input type="hidden" name="action" value="yspeechetftec_manual_import">
                            <?php
                            submit_button(__('Jetzt importieren', 'yspeechetftec'), 'primary', 'Update');
                            ?>
                        </form>
                <?php
                        break;
                endswitch; ?>
            </div>
        </div>
<?php
    }

    public function yspeechetftec_manual_import_events()
    {
        yspeechetftec_import_events();
    }

    public function yspeechetftec_page_init()
    {
        register_setting(
            'yspeechetftec_import_group', // option_group
            'yspeechetftec_import', // option_name
            array($this, 'yspeechetftec_sanitize') // sanitize_callback
        );

        register_setting(
            'yspeechetftec_license_group', // option_group
            'yspeechetftec_license', // option_name
            array($this, 'yspeechetftec_sanitize') // sanitize_callback
        );

        // section for import settings
        add_settings_section(
            'yspeechetftec_import_section', // id
            __('Import Einstellungen', 'yspeechetftec'), // title
            array($this, 'yspeechetftec_section_info'), // callback
            'yspeechetftec-settings-import' // page
        );

        add_settings_field(
            'yspeechetftec_publisher_id', // id
            __('Publisher', 'yspeechetftec'), // title
            array($this, 'yspeechetftec_publisher_id_callback'), // callback
            'yspeechetftec-settings-import', // page
            'yspeechetftec_import_section' // section
        );

        add_settings_field(
            'yspeechetftec_organizer_ids', // id
            __('Veranstalter IDs:', 'yspeechetftec'), // title
            array($this, 'yspeechetftec_organizer_ids_callback'), // callback
            'yspeechetftec-settings-import', // page
            'yspeechetftec_import_section' // section
        );


        add_settings_field(
            'yspeechetftec_region_ids', // id
            __('Region IDs', 'yspeechetftec'), // title
            array($this, 'yspeechetftec_region_ids_callback'), // callback
            'yspeechetftec-settings-import', // page
            'yspeechetftec_import_section' // section
        );

        // section for license settings
        add_settings_section(
            'yspeechetftec_license_section', // id
            __('Lizenz Einstellungen', 'yspeechetftec'), // title
            array($this, 'yspeechetftec_section_info'), // callback
            'yspeechetftec-settings-license' // page
        );

        add_settings_field(
            'yspeechetftec_license_key', // id
            __('Lizenzschlüssel', 'yspeechetftec'), // title
            array($this, 'yspeechetftec_license_key_callback'), // callback
            'yspeechetftec-settings-license', // page
            'yspeechetftec_license_section' // section
        );
    }

    public function yspeechetftec_sanitize($input)
    {
        $sanitary_values = array();
        if (isset($input['yspeechetftec_publisher_id'])) {
            $sanitary_values['yspeechetftec_publisher_id'] = $input['yspeechetftec_publisher_id'];
        }

        if (isset($input['yspeechetftec_organizer_ids'])) {
            $sanitary_values['yspeechetftec_organizer_ids'] = sanitize_text_field($input['yspeechetftec_organizer_ids']);
        }

        if (isset($input['yspeechetftec_region_ids'])) {
            $sanitary_values['yspeechetftec_region_ids'] = sanitize_text_field($input['yspeechetftec_region_ids']);
        }

        if (isset($input['yspeechetftec_license_key'])) {
            $sanitary_values['yspeechetftec_license_key'] = sanitize_text_field($input['yspeechetftec_license_key']);
        }

        return $sanitary_values;
    }

    public function yspeechetftec_section_info()
    {
        $next_scheduled = wp_next_scheduled('yspeechetftec_cron_hook');
        if ($next_scheduled != false && $next_scheduled > 0) {
            echo esc_html(('<div class="info"><strong>Nächster automatischer Import: </strong>'
                . date("d.m.Y H:i:s", wp_next_scheduled('yspeechetftec_cron_hook'))
                . ' UTC</div>'));
        }
    }

    public function yspeechetftec_publisher_id_callback()
    {
        $users = get_users(array('role__in' => array('administrator', 'editor', 'author')));

        echo esc_html(('<select name="yspeechetftec_import[yspeechetftec_publisher_id]" id="yspeechetftec_publisher_id">'));
        foreach ($users as $user) {
            if (isset($this->yspeechetftec_import['yspeechetftec_publisher_id']) && $this->yspeechetftec_import['yspeechetftec_publisher_id'] == $user->ID) {
                echo esc_html(('<option value="' . esc_html($user->ID) . '" selected>' . esc_html($user->display_name) . '</option>'));
            } else {
                echo esc_html(('<option value="' . esc_html($user->ID) . '">' . esc_html($user->display_name) . '</option>'));
            }
        }
        echo esc_html(('</select>'));
    }

    public function yspeechetftec_organizer_ids_callback()
    {
        printf(esc_html(
            '<input class="regular-text" type="text" name="yspeechetftec_import[yspeechetftec_organizer_ids]" id="yspeechetftec_organizer_ids" value="%s">
            <label class="screen-reader-text">' . __('Die Veranstalter IDs kommaseperiert. Zum Beispiel: 123 Komma 456', 'yspeechetftec') . '</label>
            <p class="tooltip description">' . __('Die Veranstalter IDs kommaseperiert (z.B. "123, 456").', 'yspeechetftec') . '</p>',
            isset($this->yspeechetftec_import['yspeechetftec_organizer_ids']) ? esc_attr($this->yspeechetftec_import['yspeechetftec_organizer_ids']) : ''
        ));
    }

    public function yspeechetftec_region_ids_callback()
    {
        printf(esc_html(
            '<input class="regular-text" type="text" name="yspeechetftec_import[yspeechetftec_region_ids]" id="yspeechetftec_region_ids" value="%s">
            <label class="screen-reader-text">' . __('Die Region IDs kommaseperiert. Zum Beispiel: 123 Komma 456', 'yspeechetftec') . '</label>
            <p class="tooltip description">' . __('Die Region IDs kommaseperiert (z.B. "123, 456").', 'yspeechetftec') . '</p>',
            isset($this->yspeechetftec_import['yspeechetftec_region_ids']) ? esc_attr($this->yspeechetftec_import['yspeechetftec_region_ids']) : ''
        ));
    }

    public function yspeechetftec_license_key_callback()
    {
        $icon = '';
        if (isset($this->yspeechetftec_license['yspeechetftec_license_key'])) {
            if ($this->yspeechetftec_check_license($this->yspeechetftec_license['yspeechetftec_license_key'])) {
                $icon = '&#9989;';
            } else {
                $icon = '&#8855;';
            }
        }
        printf(esc_html(
            '<input class="regular-text" type="text" name="yspeechetftec_license[yspeechetftec_license_key]" id="yspeechetftec_license_key" value="%s"><i class="yspeechetftec-suffix-icon">' . $icon . '</i>
            <label class="screen-reader-text">' . __('Lizenzschlüssel um Zusatzfunktionen freizuschalten.', 'yspeechetftec') . '</label>
            <p class="tooltip description">' . __('Lizenzschlüssel um Zusatzfunktionen freizuschalten', 'yspeechetftec') . '</p>',
            isset($this->yspeechetftec_license['yspeechetftec_license_key']) ? esc_attr($this->yspeechetftec_license['yspeechetftec_license_key']) : ''
        ));
    }

    /**
     * Check if the license key is valid
     * 
     * @param string license_key The license key to check
     * @return bool
     */
    public function yspeechetftec_check_license($license_key)
    {
        $res = false;
        try {
            $response = wp_remote_get(
                YSPEECHETFTEC_YSPEECH_LICENSE_BASE_URL . $license_key,
                array(
                    'timeout' => 30,
                    'redirection' => 10,
                    'method' => 'GET',
                    'httpversion' => 1.1,
                    'headers' => array(
                        'Content-Type'  => 'application/json',
                        'Authorization' => YSPEECHETFTEC_YSPEECH_API_AUTH
                    )
                )
            );
            $response_body = json_decode(wp_remote_retrieve_body($response));
            $license = $response_body->data;
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code === 404) {
                $res = false;
            } else if ($response_code === 200) {
                if (
                    $license->timesActivated > 0
                    && $license->timesActivated <= $license->timesActivatedMax
                    && $license->productId == 782
                ) {
                    $res = true;
                } elseif (
                    $license->timesActivated < $license->timesActivatedMax
                    && $license->productId == 782
                ) {
                    $this->yspeechetftec_activate_license($license_key);
                    $res = $this->yspeechetftec_check_license($license_key);
                }
            } else {
                $res = false;
            }
        } catch (Exception $err) {
            echo (esc_html("<hr>Error #:" . $err));
            $res = false;
        }
        return $res;
    }
    public function yspeechetftec_activate_license($license_key)
    {
        try {
            wp_remote_get(
                YSPEECHETFTEC_YSPEECH_LICENSE_BASE_URL . 'activate/' . $license_key,
                array(
                    'timeout' => 30,
                    'redirection' => 10,
                    'method' => 'GET',
                    'httpversion' => 1.1,
                    'headers' => array(
                        'Content-Type'  => 'application/json',
                        'Authorization' => YSPEECHETFTEC_YSPEECH_API_AUTH
                    )
                )
            );
        } catch (Exception $e) {
            echo (esc_html("<hr>Error #:" . $e));
        }
    }
}
if (is_admin()) {
    new YSPEECHETFTEC_Admin();
}
