<?php

class WPCFM_Helper
{

    /**
     * Load all bundles (DB + file)
     */
    function get_bundles() {
        $output = array();

        // Get DB bundles first
        $opts = WPCFM()->options->get( 'wpcfm_settings' );
        $opts = json_decode( $opts, true );
        foreach ( $opts['bundles'] as $bundle ) {
            $bundle['is_db'] = true;
            $bundle['is_file'] = false;
            $output[ $bundle['name'] ] = $bundle;
        }

        // Then merge file bundles
        $file_bundles = array_merge($this->get_file_bundles(), $this->get_path_file_bundles());
        foreach ( $file_bundles as $bundle_name => $bundle ) {
            if ( isset( $output[ $bundle_name ] ) ) {
                $output[ $bundle_name ]['is_file'] = true;
                $output[ $bundle_name ]['url'] = $this->get_bundle_url( $bundle_name );
            }
            else {
                $bundle['is_db'] = false;
                $bundle['is_file'] = true;
                $bundle['url'] = $this->get_bundle_url( $bundle_name );
                $output[ $bundle_name ] = $bundle;
            }
        }

        return $output;
    }

    /**
     * Get bundle paths.
     */
    function get_bundle_paths() {
        $opts = WPCFM()->options->get( 'wpcfm_settings' );
        $opts = json_decode( $opts, true );

        $paths = array();
        foreach ($opts['bundles'] as $bundle) {
            if (isset($bundle['path'])) {
                $paths[$bundle['name']] = $bundle['path'];
            }
        }

        return $paths;
    }

    /**
     * Get bundle URL
     */
    function get_bundle_url( $bundle_name ) {
        $paths = $this->get_bundle_paths();
        if ($paths[$bundle_name]) {
            return $paths[$bundle_name] . '/' . basename( WPCFM()->readwrite->bundle_filename( $bundle_name ) );
        }

        return WPCFM_CONFIG_URL . '/' . basename( WPCFM()->readwrite->bundle_filename( $bundle_name ) );
    }


    /**
     * Get file bundles
     */
    function get_file_bundles() {

        $output = array();
        $filenames = scandir( WPCFM()->readwrite->folder );
        $filenames = array_diff( $filenames, array( '.', '..' ) );

        foreach ( $filenames as $filename ) {
            // Check is .json file
            if ( stripos( $filename, '.json')) {
                // Default to single site bundle
                $bundle_name = str_replace( '.json', '', $filename );

                // read_file checks if .json file has .label.
                if ($bundle_data = WPCFM()->readwrite->read_file( $bundle_name )) {
                    if ( is_multisite() ) {
                        $filename_parts = explode( '-', $filename, 2 );

                        // Only accept multi-site bundles
                        if ( 2 > count( $filename_parts ) ) {
                            continue;
                        }

                        $bundle_name = str_replace( '.json', '', $filename_parts[1] );

                        if ( WPCFM()->options->is_network ) {
                            if ( 'network' != $filename_parts[0] ) {
                                continue;
                            }
                        }
                        elseif ( $filename_parts[0] != 'blog' . get_current_blog_id() ) {
                            continue;
                        }

                    }

                    $bundle_label = $bundle_data['.label'];
                    unset( $bundle_data['.label'] );

                    $output[ $bundle_name ] = array(
                        'label'     => $bundle_label,
                        'name'      => $bundle_name,
                        'config'    => $bundle_data,
                    );
                }
            }
        }
        return $output;
    }

    /**
     * Get file bundles with specified path.
     */
    function get_path_file_bundles() {
        $output = array();
        $paths = $this->get_bundle_paths();
        foreach ($paths as $bundle_name => $bundle_path) {
            $bundle_data = WPCFM()->readwrite->read_file( $bundle_name );
            if ( !empty( $bundle_data ) ) {
                $bundle_label = $bundle_data['.label'];
                unset( $bundle_data['.label'] );
                $output[ $bundle_name ] = array(
                    'label'     => $bundle_label,
                    'name'      => $bundle_name,
                    'config'    => $bundle_data,
                );
            }
        }
        return $output;
    }


    /**
     * Load all bundle names
     */
    function get_bundle_names() {
        if (WPCFM()->readwrite->folder != WPCFM_CONFIG_DIR) {
            return array_keys($this->get_file_bundles());
        }
        else {
            return array_keys( $this->get_bundles() );
        }
    }


    /**
     * Get bundle by name
     */
    function get_bundle_by_name( $bundle_name ) {
        $bundles = $this->get_bundles();
        return isset( $bundles[ $bundle_name ] ) ?
            $bundles[ $bundle_name ] :
            array();
    }


    /**
     * Put configuration items into groups
     */
    function group_items( $items ) {
        $output = array();

        // Sort by array key
        ksort( $items );

        foreach ( $items as $key => $item ) {
            $group = isset( $item['group'] ) ? $item['group'] : __( 'WP Options', 'wpcfm' );
            $output[ $group ][ $key ] = $item;
        }

        return $output;
    }
}
