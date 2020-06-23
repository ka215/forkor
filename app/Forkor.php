<?php

namespace Forkor;

class Forkor
{
    /*
     * Default configuration file path
     * @access protected
     * @var string
     */
    protected const DEFAULT_CONFIG = '../config.php';

    /*
     * Loadable configuration file path
     * @access protected
     * @var string
     */
    protected $config;

    /*
     * Locale language
     * @access protected
     * @var string
     */
    protected $language;

    /*
     * Database handler
     * @access protected
     * @var object As the PDO class
     */
    protected $dbh;

    /*
     * Escape quotation string for database query
     * @access protected
     * @var string
     */
    protected $dbeq;

    /*
     * The binary attribute value given to narrow condition of a specific column
     * @access protected
     * @var string
     */
    protected $binary_attr;

    /*
     * State of ready database
     * @access protected
     * @var boolean
     */
    protected $is_ready_db;

    /*
     * Last inserted location id as \PDO::lastInsertId()
     * @access protected
     * @var int
     */
    public $last_location_id;

    /*
     * Whether the current request method is post
     * @access protected
     * @var boolean
     */
    protected $httppost;

    /*
     * Referrer URL
     * @access protected
     * @var string
     */
    protected $referrer;

    /*
     * Scheme of request to server
     * @access protected
     * @var string
     */
    protected $server_request_scheme;

    /*
     * Request path without query strings
     * @access protected
     * @var string
     */
    protected $self_path;

    /*
     * Request URI
     * @access protected
     * @var string
     */
    protected $request_uri;

    /*
     * Remote IP address of request origin
     * @access protected
     * @var string
     */
    protected $remote_addr;

    /*
     * Unique ID created when requesting
     * @access protected
     * @var string
     */
    protected $uid;

    /*
     * Cookie name that Forkor will set
     * @access protected
     * @var string
     */
    protected $cookie_name;

    /*
     * Location ID for redirection via Forkor
     * @access protected
     * @var string
     */
    protected $location_id;

    /*
     * Whether do logging the current location when redirection
     * @access protected
     * @var boolean
     */
    protected $current_logging;

    /*
     * Reserved words that cannot be used as location IDs
     * @access protected
     * @var array
     */
    protected $reserved_words = [ '403', '404' ];

    /*
     * Data submitted by the POST method
     * @access protected
     * @var array<assoc>
     */
    protected $post_vars;

    /*
     * Array of response contents when responding in JSON format
     * @access public
     * @var array<assoc>
     */
    public $json_response = [];

    /*
     * Associative array for error handling
     * (key: error code, value: error message)
     * @access public
     * @var array<assoc>
     */
    public $errors = [];

    /*
     * For measuring the process execution time
     * @access public
     * @var array
     */
    public $exec_time = [];

    /*
     * Buffering for debugging
     * @access public
     * @var string
     */
    public $buffer;

    /*
     * Constructor
     * @access public
     * @param string $config (optional)
     */
    public function __construct( $config = '' ) {
        $this->exec_time[] = microtime( true );

        // Load configuration
        $this->config = empty( $config ) || ! @file_exists( $config ) ? self::DEFAULT_CONFIG : $config;
        if ( @file_exists( $this->config ) ) {
            require_once $config;
        } else {
            die( '<b>Error</b>: Can not load config!' );
        }

        // Error Settings for debug
        if ( DEBUG_MODE ) {
            $error_log_path = APP_ROOT . '/err.log';
            ini_set( 'display_errors', 1 );
            ini_set( 'log_errors', 'On' );
            ini_set( 'error_log', $error_log_path );
            error_reporting( E_ALL );
            if ( ! @file_exists( $error_log_path ) ) {
                @file_put_contents( $error_log_path, '' );
                @chmod( $error_log_path, 0666 );
            }
        }

        // Language settings
        $this->language = self::get_language();
        self::set_locale( $this->language );

        self::init();
    }

    /*
     * Initialize application
     * @access protected
     */
    protected function init() {
        // Initialize each variables
        $this->httppost = ( 'post' === strtolower( $_SERVER['REQUEST_METHOD'] ) );
        $this->referrer = $_SERVER['HTTP_REFERER'] ?? '';
        if ( ( ! empty( $_SERVER['REQUEST_SCHEME'] ) && $_SERVER['REQUEST_SCHEME'] === 'https' ) ||
             ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) ||
             ( ! empty( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] == '443' ) ) {
            $this->server_request_scheme = 'https';
        } else {
            $this->server_request_scheme = 'http';
        }
        list( $this->self_path ) = explode( '?', self::unslash( $_SERVER['REQUEST_URI'] ) );
        $this->request_uri = $this->server_request_scheme . '://' . $_SERVER['HTTP_HOST'] . $this->self_path;
        $this->remote_addr = $_SERVER['REMOTE_ADDR'];
        $this->uid = $_SERVER['UNIQUE_ID'];
        $this->cookie_name = strtolower( APP_NAME ) .'-'. md5( $_SERVER['HTTP_HOST'] );
        if ( ! empty( $_REQUEST ) ) {
            foreach ( $_REQUEST as $_k => $_v ) {
                if ( 'lid' === $_k ) {
                    $this->location_id = trim( filter_var( $_v, FILTER_SANITIZE_STRING ), '/' );
                } else
                if ( $this->httppost ) {
                    // Do not filter at this process yet
                    $this->post_vars[trim( $_k )] = filter_input( INPUT_POST, $_k );
                }
            }
        }
        $this->current_logging = false;
        /*
         * Filter handler: "reserved_words"
         * @since v1.0
         */
        $this->reserved_words = self::call_filter( 'reserved_words', $this->reserved_words );

        // Routing as dispatcher
        if ( empty( $this->location_id ) ) {
            // When the application root is accessed
            if ( SHOW_INDEX ) {
                self::introduct();
            } else {
                self::not_found();
            }
        } else
        if ( in_array( $this->location_id, $this->reserved_words, true ) ) {
            // When the invalid path is accessed
            self::not_found();
        } else
        if ( REGISTER_PATH === $this->location_id ) {
            // Register shortener URL
            if ( ! empty( REGISTER_ALLOWED_IPS ) && ! in_array( $this->remote_addr, REGISTER_ALLOWED_IPS, true ) ) {
                self::not_found( '403 Forbidden' );// 'You can not register! ('. $this->remote_addr .')'
            }
            self::register();
        } else
        if ( ANALYZE_PATH === $this->location_id ) {
            // Analyze redirection via shorten URL
            if ( ! empty( ANALYZE_ALLOWED_IPS ) && ! in_array( $this->remote_addr, ANALYZE_ALLOWED_IPS, true ) ) {
                self::not_found( '403 Forbidden' );// 'You can not Analyze! ('. $this->remote_addr .')'
            }
            self::analyze();
        } else {
            // Redirection
            self::redirect();
        }
        exit;
    }

    /*
     * Bind as callback for each custom filter methods
     * - Call the method in the class or the function prepared in the global scope
     * with the same name as the handler to be filtered then return the return value.
     * @access public
     * @param string $handler (required) is the first argument
     * @param mixed $values (required) is second and subsequent arguments
     * @return mixed
     */
    public function call_filter() {
        $numargs = func_num_args();
        if ( $numargs >= 1 ) {
            $handler = func_get_arg( 0 );
        }
        $arg_list = func_get_args();
        unset( $arg_list[0] );
        if ( function_exists( $handler ) ) {
            return call_user_func_array( $handler, $arg_list );
        } else
        if ( method_exists( $this, $handler ) ) {
            return call_user_func_array( [ $this, $handler ], $arg_list );
        } else {
            return func_get_arg( 1 );
        }
    }

    /*
     * Try to retrieve users browser locale then set default language if can not retrieve
     * @access protected
     */
    protected function get_language() {
        if ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
            // Retrieve user's browser language
            $langs = explode( ',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
            foreach ( $langs as $_i => $_lang ) {
                if ( strpos( $_lang, ';' ) !== false ) {
                    list( $_lang ) = explode( ';', $_lang );
                }
                $_lang = trim( $_lang );
                $_lang = $_lang === 'en' ? 'en_US' : $_lang;
                $_lang = $_lang === 'ja' ? 'ja_JP' : $_lang;
                $langs[$_i] = str_replace( '-', '_', $_lang );
            }
            $langs = array_unique( $langs );
        } else {
            // Set default language
            $langs = [ 'en_US' ];
        }
        /*
         * Filter handler: "get_locale_lang"
         * @since v1.0
         */
        return self::call_filter( 'get_locale_lang', $langs[0], $langs );
    }

    /*
     * Set locale by specific language that got
     * @access protected
     * @param string $language (required)
     * @param string $locale_dirpath (optional) directory path to translation files
     */
    protected function set_locale( $language, $locale_dirpath = './assets/langs' ) {
        $_ls = [];
        getenv( 'LC_ALL' ) or putenv( 'LC_ALL=' . $language );
        $_ls[] = setlocale( LC_ALL, $language . '.UTF8' );
        if ( @file_exists( $locale_dirpath ) && is_dir( $locale_dirpath ) ) {
            $_ls[] = bindtextdomain( APP_NAME, $locale_dirpath );
            $_ls[] = textdomain( APP_NAME );
        }
        //var_dump( $_ls );
    }

    /*
     * Wrapper for instance when the process dies
     * @access public
     * @param string $error_code (optional)
     */
    public function die( $error_code = null ) {
        if ( ! empty( $this->json_response ) ) {
            header( 'Content-Type: application/json; charset=utf-8' );
            die( json_encode( $this->json_response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK ) );
        } else
        if ( self::has_error( $error_code ) ) {
            die( self::get_error_messages( $error_code ) );
        } else {
            die();
        }
    }

    /*
     * Called as destructor when the script exits
     * @access public
     */
    public function __destruct() {
        $this->exec_time[] = microtime( true );
        if ( DEBUG_MODE ) {
            if ( ! empty( $this->buffer ) ) {
                echo $this->buffer . PHP_EOL;
                $this->exec_time[] = microtime( true );
            }
            $total_exec_time = end( $this->exec_time ) - reset( $this->exec_time );
            if ( empty( $this->json_response ) ) {
                echo "<pre disabled><code>Total Execution Time: {$total_exec_time}ms</code></pre>" . PHP_EOL;
            }
        }
    }

    // Include database trait
    use database;

    // Include introduct trait
    use introduct;

    // Include register trait
    use register;

    // Include analize trait
    use analyze;

    // Include redirect trait
    use redirect;

    // Include partials trait
    use partials;

    // Include utility methods
    use utils;

    // Include test trait only when do development
    use test;

}
