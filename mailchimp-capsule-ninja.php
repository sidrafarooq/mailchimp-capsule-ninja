<?php
/*
 * Plugin Name:       Mailchimp & Capsule CRM Sync
 * Plugin URI:        http://github.com/signalfire/mailchimp-capsule-ninja
 * Description:       Plugin takes a submission from Ninja Forms and adds a subscriber to a mailchimp list and adds details to Capsule CRM
 * Version:           0.0.1
 * Author:            Robert Coster
 * Author URI:        http://www.signalfire.co.uk
 * Text Domain:       mailchimp-capsule-ninja-locale
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-mailchimp-capsule-ninja.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-mailchimp-capsule-ninja-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/capsule/Services/Capsule.php';

function setup_mailchimp_capsule_ninja() {
    $mcn = new MailChimp_Capsule_Ninja();
    $mcn->setup();
}

function setup_mailchimp_capsule_ninja_settings(){
    $mcns = new MailChimp_Capsule_Ninja_Settings();   
    $mcns->setup(); 
}

setup_mailchimp_capsule_ninja();

if(is_admin()){
    setup_mailchimp_capsule_ninja_settings();
}
