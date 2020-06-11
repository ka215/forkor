<?php
namespace Forkor;

trait redirect
{

    /*
     * Respond as file not found
     * @access public
     * @param string $message (optional)
     */
    public function not_found( $message = '' ) {
        header( 'Cache-Control: no-cache, must-revalidate' );
        header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
        header( 'HTTP/1.0 404 Not Found' );
        if ( ! empty( $message ) ) {
            die( $message );
        } else {
            exit;
        }
    }

    /*
     * Redirect to a URL matching the specified location ID
     * @access public
     */
    public function redirect() {
        $redirect_to = self::get_redirect_url();
        if ( ! $redirect_to ) {
            self::not_found();
        }
        
        // Logging before redirection
        self::redirect_logging();
        
        if ( ! headers_sent() ) {
            header( 'Cache-Control: no-cache, must-revalidate' );
            header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
            $http_code = $this->httppost ? 307 : 302;
            header( 'Location: '. $redirect_to, true, $http_code );
        } else {
            $redirect_html  = <<<EOD
<script>
var meta1 = document.createElement('meta'),meta2 = document.createElement('meta');
meta1.httpEquiv = 'Pragma';
meta1.content   = 'no-cache';
meta2.httpEquiv = 'Cache-Control';
meta2.content   = 'no-cache';
document.getElementsByTagName('head')[0].appendChild(meta1);
document.getElementsByTagName('head')[0].appendChild(meta2);
window.location.href='{$redirect_to}';
</script>
<noscript><meta http-equiv="Pragma" content="no-cache" /><meta http-equiv="Cache-control" content="no-cache" /><meta http-equiv="refresh" content="0;url={$redirect_to}" /></noscript>
EOD;
            echo $redirect_html;
        }
        exit;
    }

    /*
     * Logging to the `location_log` table before redirection
     * @access protected
     */
    protected function redirect_logging() {
        if ( ! $this->current_logging ) {
            return;
        }
        die( 'Logging!' );
        
    }

}
