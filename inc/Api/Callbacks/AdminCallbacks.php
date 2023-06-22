<?php
    /**
     * @package dilve
     */

    namespace Inc\Dilve\Api\Callbacks;
    use Inc\Dilve\Base\BaseController;

    class AdminCallbacks extends BaseController {

    public function adminDashboard() {
        
        return require_once("{$this->plugin_templates_path}/adminDashboard.php");
    }

    public function textSanitize( $input ) {
        $output = get_option('dilve_settings');
        $output['dilve_user'] = sanitize_text_field( $input['dilve_user'] );
        $output['dilve_pass'] = sanitize_text_field( $input['dilve_pass'] );
        return $output;
    }
    public function adminSectionManager() {
        echo 'manage the Sections and Features of this plugin by activating the checkboxes in the list below';
      }
    public function textField( $args ){
        //return the input
        $name = $args['label_for'];
        $option_name = $args['option_name'];
        $options = get_option($option_name);
        $value = isset($options[$name]) ? $options[$name] : '';
        echo '<input
            type="text"
            class="'.$args['class'].'"
            id="'.$name.'"
            name="' . $option_name . '[' . $name . ']"
            value="' . esc_attr($value) . '"
            placeholder="Tell us the name of the Dilve folder"
            required>';
    }
}