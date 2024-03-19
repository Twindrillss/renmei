<?php

//INIZIO AGGIUNTE PER FUNZIONALITA' API


add_action('rest_api_init', function () {
    register_rest_route('renmei/v1', '/wp_plugins/', array(
        'methods' => 'POST',
        'callback' => 'get_installed_plugins',
    ));
	
	register_rest_route('renmei/v1', '/wp_core/', array(
        'methods' => 'POST',
        'callback' => 'check_wp_core',
    ));
	
	register_rest_route('renmei/v1', '/wp_core_update/', array(
        'methods' => 'POST',
        'callback' => 'wp_core_update',
    ));
	
	register_rest_route('renmei/v1', '/wp_plugin_update/', array(
        'methods' => 'POST',
        'callback' => 'avvia_aggiornamento_singolo_plugin',
    ));
	
	
});




function get_installed_plugins($request) {
	
    $username = $request->get_header('username');
    $password = $request->get_header('password');
    if (!empty($username) && !empty($password)){
    // usa wp_authenticate per verificare se utente esiste
    $user = wp_authenticate($username, $password);
    
    if (is_wp_error($user)) {
        return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
    }
    
    // Get list of installed plugins
    $plugins = get_plugins();
    
    // Return list of installed plugins
    return new WP_REST_Response($plugins, 200);
		} else {
		return 'Assicurati di fornire tutti i dati necessari per autenticarti';
	}
}

function check_wp_core($request) {
	
    $username = $request->get_header('username');
    $password = $request->get_header('password');
    if (!empty($username) && !empty($password)){
    // usa wp_authenticate per verificare se utente esiste
    $user = wp_authenticate($username, $password);
    
    if (is_wp_error($user)) {
        return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
    }
    
    //controlla il core di wp
    
		$versione_wordpress = get_bloginfo('version');
    
    // Return wp ver
    return new WP_REST_Response($versione_wordpress, 200);
		} else {
		return 'Assicurati di fornire tutti i dati necessari per autenticarti';
	}
}

function wp_core_update($request) {
	
    $username = $request->get_header('username');
    $password = $request->get_header('password');
    if (!empty($username) && !empty($password)){
    // usa wp_authenticate per verificare se utente esiste
    $user = wp_authenticate($username, $password);
    
    if (is_wp_error($user)) {
        return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
    }
    
    //aggiorna il core di wp
    $stato = "";
		//$versione_wordpress_attuale = get_bloginfo('version');
		$stato = aggiorna_core_wp();
    
    // Return list of installed plugins
    return new WP_REST_Response($stato, 200);
		} else {
		return 'Assicurati di fornire tutti i dati necessari per autenticarti';
	}
}



// Funzione di aggiornamento del core di wordpress
function aggiorna_core_wp() {

	// Include necessary files
require_once ABSPATH . 'wp-includes/class-wp-error.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-automatic-updater.php';

// Instantiate the automatic updater
$updater = new WP_Automatic_Updater();

// Perform the core update
$update_result = $updater->run();

if ($update_result === true) {
    // Update successful
    return true;
} 
	elseif ( is_wp_error( $update_result ) ) {
		
		return 'Errore durante aggiornamento di wordpress. Dettagli Errore: ' . $update_result->get_error_message();
		
	}
	else {
    // Update failed
    return 'aggiornamento non riuscito per un errore di sistema o perché sei già alla versione più aggiornata di wordpress.';
}
	
}

function aggiorna_plugin($nomeplugin){

    $statooperazione = false;

    $plugin_slug = "";
	$plugins = get_plugins();
	//trova il plugin tra quelli installati
	
	foreach ($plugins as $plugin_path => $plugin_info) {
	
		if ($plugin_info['Name'] === $nomeplugin) {
		
			//prenditi lo slug
			$plugin_slug = basename(dirname($plugin_path));
			break;
		}
		
	}
	
	//slug trovato
	//return $plugin_slug;
	$downloaded = "";
	if (!empty($plugin_slug)){
		
		// ottieni url download zip

        //array $args con dentro le info da richiedere

        $args = array(
            'slug' => $plugin_slug, // or any plugin slug
            'fields' => array(
                'version' => true
            )
        );

        // Make request and extract plug-in object. Action is query_plugins
        $response = wp_remote_post(
        'http://api.wordpress.org/plugins/info/1.0/',
        array(
            'body' => array(
            'action' => 'plugin_information',
            'request' => serialize((object)$args)
            )
        )
    );
    $dati_api_wp = true;

    if ( !is_wp_error($response) ) {
    
        $returned_object = unserialize(wp_remote_retrieve_body($response)); 
        $dati_api_wp = false;
        if ($returned_object) {
            $downloaded = $returned_object->download_link;
            
        } else {
            return 'Errore, il body non contiene un oggetto valido';
        }

    } else {
        return 'Errore nella richiesta';
    }

	// fine ottenimento url download zip
	
		
	} else {
		return 'Nessun plugin trovato con questo nome nel catalogo di wordpress.';
	}
    $checkdownload = false;
    if ($dati_api_wp){
    
    //scarica lo zip nella cartella wp-contents/plugins

    //predisponi directory completa di nome preso da $downloaded per scaricare file
    $target_dir = $_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/".basename($downloaded);

    //scarica lo zip e controlla se ci sei riuscito
    if (file_put_contents($target_dir, file_get_contents($downloaded))) {

        $checkdownload = true;

    }
    }
    //fine scarica zip nella cartella wp-contents/plugins
	
    //inizio estrazione da zip, sostituzione e scrittura cartella plugin
    if ($checkdownload) {
        // se sei riuscito a scaricare, allora il processo di sostituzione può cominciare

        //rinomina la cartella con vecchia versione plugin
        rename($_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/".$plugin_slug, $_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/".$plugin_slug.'_old');

        $extractTo = $_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/".$plugin_slug.'_new';

        $zip = new ZipArchive;
        $folderToExtract = $plugin_slug;
        

        if ($zip->open($target_dir)){

           $zip->extractTo($extractTo); 
           $zip->close();
           rename($_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/".$plugin_slug .'_new/'.$plugin_slug, $_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/".$plugin_slug);
           
            //cancella le cartelle che non servono più
            deleteDirectory($_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/".$plugin_slug .'_new/');
            deleteDirectory($_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/".$plugin_slug.'_old');

            //cancella il file zip con cui hai eseguito aggiornamento
            unlink($_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/".basename($downloaded));

            attivaplugin($plugin_slug);

           $statooperazione = true;

        }

        return $statooperazione;

    } 

    //fine estrazione da zip, sostituzione e scrittura cartella plugin

}

function avvia_aggiornamento_singolo_plugin($request) {
	
    $username = $request->get_header('username');
    $password = $request->get_header('password');
	$plugindaaggiornare = $request->get_header('plugin');
    if (!empty($username) && !empty($password)){
    // usa wp_authenticate per verificare se utente esiste
    $user = wp_authenticate($username, $password);
    
    if (is_wp_error($user)) {
        return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
    }
    
 //callback a funzione aggiornamento plugin
 
	if (!empty($plugindaaggiornare)){
	$statement = aggiorna_plugin($plugindaaggiornare);	//stampa qualsiasi cosa ti dia la function
	} else {
	$statement = 'Specificare un plugin da aggiornare.';
	}
 //callback a funzione aggiornamento plugin

		} else {
		$statement = 'Assicurati di fornire tutti i dati necessari per autenticarti';
	}
return new WP_REST_Response($statement, 200);
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }

    // Open the directory
    $handle = opendir($dir);

    // Loop through the directory
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            $path = $dir . "/" . $entry;
            if (is_dir($path)) {
                // Recursively delete subdirectories
                deleteDirectory($path);
            } else {
                // Delete files
                unlink($path);
            }
        }
    }

    // Close the directory handle
    closedir($handle);

    // Delete the directory itself
    return rmdir($dir);
}

function attivaplugin ($directoryplugin){

    require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-admin/includes/plugin.php';

    activate_plugin($directoryplugin.'/'.$directoryplugin.'.php');

}

?>
