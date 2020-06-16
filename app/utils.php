<?php
namespace Forkor;

trait utils
{

    /*
     * Returns a string with backslashes stripped off the string given
     * @access public
     * @param mixed $value (required)
     * @return mixed
     */
    public static function unslash( $value = '' ) {
        return is_string( $value ) ? stripslashes( $value ) : $value;
    }

    /*
     * Check if the value is a valid date
     * @access public
     * @param mixed $value (required)
     * @return boolean
     */
    public static function is_datetime( $value = '' ) {
        if ( empty( $value ) ) {
            return false;
        }
        try {
            new \DateTime( $value );
            return true;
        } catch ( \Exception $e ) {
            return false;
        }
    }

    /*
     * Get the datetime string of a variable
     * @access public
     * @param mixed $var (optional) Defaults to 'now'
     * @param string $timezone (optional) 
     * @param string $format (optional) 
     * @return mixed Returns the datetime string casted, returns null if cannot casted
     */
    public static function datetime_val( $var = 'now', $timezone = null, $format = null ) {
        $now_date = new \DateTime();
        $current_timezone = $now_date->getTimezone();
        if ( empty( $timezone ) ) {
            $timezone =$current_timezone->getName();
        }
        if ( empty( $format ) ) {
            // Defaults to correspond to the datetime format of the database
            $format = 'Y-m-d H:i:s';
        }
        if ( ! empty( $var ) && 'now' === strtolower( $var ) ) {
            $var = '';
        }
        if ( ! is_null( $var ) && ! is_bool( $var ) && empty( $var ) ) {
            $now_date->setTimezone( new \DateTimeZone( $timezone ) );
            return $now_date->format( $format );
        } else
        if ( is_int( $var ) ) {
            $now_date->setTimezone( new \DateTimeZone( $timezone ) );
            $now_date->setTimestamp( $var );
            return $now_date->format( $format );
        } else
        if ( self::is_datetime( $var ) ) {
            $date = new \DateTime( $var, new \DateTimeZone( $timezone ) );
            return $date->format( $format );
        } else {
            return null;
        }
    }

    /*
     * Sanitize to URL-safe strings for location path
     * @access public
     * @param string $value (required)
     * @param boolean $to_lower (optional) Defaults to false
     * @return mixed If a non-string value is given, no done and the value is returned as is
     */
    public function sanitize_path( $value, $to_lower = false ) {
        // Cached origin value
        $origin_value = $value;
        if ( is_string( $value ) ) {
            $value = is_string( $value ) ? $value : @strval( $value );
            $value = preg_replace( '|[^\\pL0-9_]+|u', '-', $value );
            $value = trim( $value, '-' );
            $value = iconv( 'utf-8', 'us-ascii//TRANSLIT', $value );
            if ( $to_lower ) {
                $value = strtolower( $value );
            }
            $value = preg_replace( '|[^-a-zA-Z0-9_]+|', '', $value );
        }
        /*
         * Filter handler: "sanitized_path"
         * @since v1.0
         */
        return self::call_filter( 'sanitized_path', $value, $origin_value );
    }

    /*
     * Get the location hash string that automatically generated with URL-safe and specified length
     * @access public
     * @param string $str (required)
     * @param int $length (optional) defaults to 0; maximum is length of $str if zero
     * @return string
     */
    public function make_hash( $str, $length = 0 ) {
        $base_hash = password_hash( $str, PASSWORD_DEFAULT );
        $_tmp = explode( '$', $base_hash );
        $base_hash = preg_replace( '/[^0-9a-zA-Z]/', '', $_tmp[count( $_tmp ) - 1] );

        /*
         * Filter handler: "hash_codeset"
         * - Defines single-byte alphanumeric characters that exclude characters (0,O,1,l) that are easy to mistype as the default value
         * - Even if symbols other than single-byte alphanumeric characters are included in the codeset, they are ignored.
         * @since v1.0
         */
        $codeset = self::call_filter( 'hash_codeset', '23456789abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ', $str );
        $base = strlen( $codeset );
        $converted = '';

        $converted = '0';
        for ( $i = strlen( $base_hash ); $i; $i-- ) {
            $converted = bcadd( $converted, bcmul( strpos( $codeset, substr( $base_hash, ( -1 * ( $i - strlen( $base_hash ) ) ), 1 ) ), bcpow( $base, $i - 1 ) ) );
        }
        $num = bcmul( $converted, 1, 0 );

        while ( $num > 0 ) {
            $converted = substr( $codeset, bcmod( $num, $base ), 1 ) . $converted;
            $num = bcmul( bcdiv( $num, $base ), '1', 0 );
        }

        if ( (int) $length > 0 && (int) $length < strlen( $str ) ) {
            $converted = substr( $converted, 0, $length );
        } else {
            $converted = substr( $converted, 0, strlen( $str ) );
        }
        return $converted;
    }

    /*
     *
     * @access public
     */
    public static function response( $notices ) {
        echo json_encode( $notices, true );
        die();
    }

    // Error Handling

    /*
     * Add an error
     * @access public
     * @param string $error_code (required)
     * @param string|array $error_message (optional)
     * @param boolean $replacement (optional) defaults to false
     * @return void
     */
    public function add_error( $error_code, $error_message = '', $replacement = false ) {
        if ( ! array_key_exists( $error_code, $this->errors ) ) {
            $this->errors[$error_code][] = $error_message;
        } else
        if ( $replacement ) {
            $this->errors[$error_code][] = $error_message;
        } else {
            $already_messages = self::get_errors( $error_code );
            if ( ! empty( $already_messages ) && ! empty( $error_message ) ) {
                $already_messages[] = $error_message;
            }
            $this->errors[$error_code] = array_unique( $already_messages );
        }
    }

    /*
     * Get the error(s)
     * @access public
     * @param string $error_code (optional) defaults to NULL; then get all errors when NULL
     * @param boolean $code_only (optional) defaults to false; then get only error messages when false
     * @return array
     */
    public function get_errors( $error_code = null, $code_only = false ) {
        if ( empty( $error_code ) ) {
            // Get all errors
            if ( $code_only ) {
                return array_keys( $this->errors );
            } else {
                $all_messages = [];
                foreach ( $this->errors as $_msg ) {
                    $all_messages = array_merge( $all_messages, $_msg );
                }
                return $all_messages;
            }
        } else
        if ( array_key_exists( $error_code, $this->errors ) ) {
            // Get one error
            if ( $code_only ) {
                return [ $error_code ];
            } else {
                return $this->errors[$error_code];
            }
        } else {
            return [];
        }
    }

    /*
     * Remove the error(s)
     * @access public
     * @param string $error_code (optional) Remove all errors if omitted this argument
     * @return void
     */
    public function remove_error( $error_code = null ) {
        if ( empty( $error_code ) ) {
            $this->errors = [];
        } else
        if ( array_key_exists( $error_code, $this->errors ) ) {
            unset( $this->errors[$error_code] );
        }
    }

    /*
     * Determine if the (specific) error exists
     * @access public
     * @param string $error_code (optional) defaults to NULL; then check all errors when NULL
     * @return boolean
     */
    public function has_error( $error_code = null ) {
        if ( empty( $error_code ) ) {
            return ! empty( $this->errors );
        } else {
            return array_key_exists( $error_code, $this->errors );
        }
    }

    /*
     * Get error messages
     * @access public
     * @param string $error_code (optional) defaults to NULL; then get all error messages when NULL
     * @param string $delimiter (optional) defaults to "<br>"
     * @return string
     */
    public function get_error_messages( $error_code = null, $delimiter = '<br>' ) {
        $error_messages = array_unique( self::get_errors( $error_code, false ) );
        return implode( $delimiter, $error_messages );
    }

    // For debug mode

    /*
     * Save any outputs to buffer for debugging
     * @access public
     * @param mixed $content (required)
     * @param string $output_type (optional) Defaults to "export"; or "dump", "echo" etc.
     * @param string $prepend_tag (optional) Defaults to "<pre>"
     * @param string $append_tag  (optional) Defaults to "</pre>"
     */
    public function set_buffer( $content, $output_type = 'export', $prepend_tag = '<pre class="mbh">', $append_tag = '</pre>' ) {
        ob_start();
        echo $prepend_tag;
        switch ( true ) {
            case preg_match( '/^(var_|)export$/', $output_type ):
                var_export( $content );
                break;
            case preg_match( '/^(var_|)dump$/', $output_type ):
                var_dump( $content );
                break;
            case preg_match( '/^print_r$/', $output_type ):
                print_r( $content );
                break;
            default:
                echo $content;
                break;
        }
        echo $append_tag;
        $this->buffer .= ob_get_contents();
        ob_end_clean();
    }

}
