<?php

include("funzioni.php");

/**
 * Plugin Name: Renmei
 * Description: Gestisci gli aggiornamenti del tuo sito Wordpress da remoto
 * Version: 0.0.1
 * Text Domain: options-plugin
 */

if (!defined("ABSPATH")) {
    exit; // esci se questo file viene letto direttamente
}

if (!class_exists("renmei")) {

    class renmei
    {

        public function __construct()
        {
            add_action('admin_menu', array($this, 'crea_voci'));
            
        }


        public function crea_voci()
        {
            // Add main menu entry
            add_menu_page(
                'Renmei',
                'Renmei',
                'manage_options',
                'unique-slug-for-renmei', // corrected slug
                array($this, 'renmei'), // corrected callback function
                'dashicons-hammer', // Icon for the menu entry (replace with your preferred icon)
                10 // Position on the menu
            );

            add_submenu_page(
                'unique-slug-for-renmei',
                'Internet & Idee',
                'Internet & Idee',
                'manage_options',
                'unique-slug-for-iei-menu-external',
                array($this, 'iei_menu_external_page')
            );
        }

        // Callback functions per menu e submenu del plugin
        public function renmei()
        {
            // Main dashboard page content
            echo '<h2>Renmei</h2><br>';
            echo '<p>In costruzione</p>';
        }


        public function iei_menu_external_page()
        {
            echo '<h2>Internet & Idee</h2>';
            echo '<p>Questo plugin è stato sviluppato da <a target="_blank" href="https://internet-idee.net">Internet & Idee</a></p>';
            echo '<img style="width:250px;" src="https://www.internet-idee.net/assets/img/colore.svg"/>';
            // Contenuto
        }


    } 

}


new renmei();
