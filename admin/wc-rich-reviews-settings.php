<?php
require_once dirname( __FILE__ ) . '/class.mdc-settings-api.php';

if ( ! class_exists( 'WC_Rich_Reviews_settings' ) ) :

class WC_Rich_Reviews_settings {

    private $settings_api;

    function __construct() {
        $this->settings_api = new MDC_Settings_API;

        add_action( 'admin_init', array($this, 'admin_init') );
        add_action( 'admin_menu', array($this, 'admin_menu'), 51 );
    }

    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    function admin_menu() {
        add_submenu_page('WooCommerce', 'WC Rich Reviews Settings', 'Rich Reviews', 'manage_options', 'wc-rich-reviews-settings', array($this, 'option_page') );
    }

    function get_settings_sections() {
        $sections = array(
            array(
                'id' => 'wc_segmented_ratings',
                'title' => 'Segmented Rating',
            ),
            array(
                'id' => 'wc_rich_editor',
                'title' => 'Rich Editor',
            ),
            
        );
        return $sections;
    }


    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        global $wrr_pro;
        $settings_fields = array(
            
            'wc_segmented_ratings' => array(
                array(
                    'name'    =>  'enable',
                    'label'     =>  'Enable Segmented Ratings?',
                    'type'    =>    'checkbox',
                    'desc'  =>  'Check to enable. Please note, it will not work, if product rating is disabled from <a href="' . admin_url( 'admin.php?page=wc-settings&tab=products' ) . '">WooCommerce setting</a>.',
                ),
                array(
                    'name'    =>  'member_only',
                    'label'     =>  'Verified buyers only?',
                    'desc'  =>  'Check to enable segmented ratings for users only who already purchased the item <i><a href="' . $wrr_pro . '">(Pro Feature)</a></i>',
                    'type'    =>    'checkbox',
                    'disabled'  =>  true,
                ),
                array(
                    'name'   =>  'params_per',
                    'label'  =>  'Parameter Base',
                    'desc'   =>  'How do you want to set rating parameters for your products? <i><a href="' . $wrr_pro . '">(Pro Feature)</a></i>',
                    'type'    =>    'radio',
                    'options'   => array(
                        'site'  =>  'Same parameters for all products',
                        'category'  =>  'Different parameters for each category',
                        'product'  =>  'Different parameters for each product',
                        ),
                    'default'           =>  'site',
                    'disabled'  =>  true,
                ),
                array(
                    'name'              => 'fields',
                    'label'             => 'Rating Parameters',
                    'desc'              => 'If \'<strong><label for="mdc-wc_segmented_ratings[params_per][site]">Same parameters for all products</label></strong>\' choosen for \'Parameter Base\' above. Ignore otherwise.<br />Type one segment per line. Separate \'key\' and \'label\' with a pipe symbol (\'|\').',
                    'type'              => 'textarea',
                    'default'           =>  'price|Price'.PHP_EOL.'quality|Quality',
                ),
            ),
            'wc_rich_editor'   =>  array(
                array(
                    'name'    =>  'enable',
                    'label'     =>  'Enable Rich Editor?',
                    'type'    =>    'checkbox',
                    'desc'  =>  'Check to enable rich editor (<a href="https://en.wikipedia.org/wiki/WYSIWYG">WYSIWYG</a>) in product review <i><a href="' . $wrr_pro . '">(Pro Feature)</a></i>',
                    'disabled'  =>  true,
                ),
                array(
                    'name'    =>  'member_only',
                    'label'     =>  'Verified buyers only?',
                    'desc'  =>  'Check to enable rich editor for users only who already purchased the item <i><a href="' . $wrr_pro . '">(Pro Feature)</a></i>',
                    'type'    =>    'checkbox',
                    'disabled'  =>  true,
                ),
                array(
                    'name'    =>  'teeny',
                    'label'     =>  'Teeny Mode?',
                    'desc'  =>  'Check to enable teeny mode. It\'ll hide some features of WYSIWYG editor. <i><a href="' . $wrr_pro . '">(Pro Feature)</a></i>',
                    'type'    =>    'checkbox',
                    'default'   =>  'off',
                    'disabled'  =>  true,
                ),
                array(
                    'name'    =>  'quicktags',
                    'label'     =>  'Enable Text editor?',
                    'desc'  =>  'Check to enable quick tag mode. It\'ll show \'Text\' tab in WYSIWYG editor to write/view HTML. <i><a href="' . $wrr_pro . '">(Pro Feature)</a></i>',
                    'type'    =>    'checkbox',
                    'default'   =>  'off',
                    'disabled'  =>  true,
                ),
                array(
                    'name'    =>  'media_buttons',
                    'desc'  =>  'Check to allow reviewers to upload/attach media files with review. <i><a href="' . $wrr_pro . '">(Pro Feature)</a></i>',
                    'label'     =>  'Enable Media Uploader?',
                    'type'    =>    'checkbox',
                    'default'   =>  'off',
                    'disabled'  =>  true,
                ),
            ),         
        );

        return $settings_fields;
    }

    function option_page() {
        echo '<div class="wrap">';
        ?>
        
            <div class="scroll-to-up-setting-page-title">
                <h1>Rich Reviews Settings</h1>
            </div>

        <div class="stp-col-left">
            <?php 
            $this->settings_api->show_navigation();
            $this->settings_api->show_forms(); ?>
        </div>


    <?php echo '</div>';
    }

}

new WC_Rich_Reviews_settings;
endif;

if( ! function_exists( 'mdc_get_option' ) ) :
function mdc_get_option( $option, $section, $default = '' ) {
 
    $options = get_option( $section );
 
    if ( isset( $options[$option] ) ) {
        return $options[$option];
    }
 
    return $default;
}
endif;
