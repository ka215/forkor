<?php
namespace Forkor;

trait partials {

    /*
     * Send header before rendering page
     * @access public
     * @param boolean $no_cache (optional) default false
     * @param string  $type (optional) default 'text/html'
     * @param string  $options (optional) default 'charset=UTF-8'
     */
    public function send_header( $no_cache = false, $type = 'text/html', $options = 'charset=UTF-8' ) {
        if ( headers_sent() ) {
            return;
        }
        if ( $no_cache ) {
            // Send no-cache headers
            header( 'Cache-Control: no-cache, must-revalidate' );
            header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
        }
        $content_type = $type .';'. ( ! empty( $options ) ? ' ' . $options : '' );
        if ( 'text/html' === $type ) {
            header( 'X-Robots-Tag: noindex' );
        }
        header( 'Content-Type: '. $content_type );
        // header( 'Content-Language: '. str_replace( '_', '-', $this->language ) );
    }

    /*
     * Render the head HTML up to the start tag of the body
     * @access public
     * @param string $title (optional) default null
     * @param string $add_internal_styles (optional) default ''
     */
    public function head( $title = null, $add_internal_styles = '' ) {
        if ( empty( $title ) ) {
            /*
             * Filter handler: "haed_title"
             * @since v1.0
             */
            $title = self::call_filter( 'head_title', APP_NAME . ' ─ Shortener URL Generator', 'default' );
        }
        $internal_css = <<<EOS
/* Internal CSS */
html { overflow-x: hidden; }
[data-standby="shown"] { visibility: hidden; opacity: 0; transition: opacity 0.3s linear; }
body > * { margin: 0 auto; width: calc(100% - 2rem); max-width: 960px; }
.forkor-logo { position: relative; display: inline-block; width: 1em; height: 1em; margin-right: 0.5rem; line-height: 1.5; }
.forkor-logo::after { position: absolute; content: ''; left: 50%; top: 50%; width: 100%; height: 100%; background-image: url(./assets/forkor.svg); background-size: contain; background-position: center center; background-repeat: no-repeat; transform: translate(-50%, -50%); }
h1 .forkor-logo { margin-right: 1rem; }
h1 .forkor-logo::after { top: calc((100% / 3) * 2); }
${add_internal_styles}
EOS;
        if ( ! empty( $internal_css ) ) {
            // Minify internal css
            $internal_css = '<style>'. preg_replace( [ '@\s*([{}|:;,])\s+|\s*(\!)|/\*.+?\*\/|\R@is', '@;(})@' ], '$1$2', $internal_css ) .'</style>';
        }
        $html_lang = str_replace( '_', '-', $this->language );
        /*
         * Filter handler: "body_atts"
         * @since v1.0
         */
        $body_attributes = self::call_filter( 'body_atts', 'class="sloth"' );
        $partial_head = <<<EOD
<!DOCTYPE html>
<html lang="{$html_lang}">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta charset="UTF-8">
    <title>{$title}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Forkor generates the shorten URL from entered something URL">
    <link rel="stylesheet" href="./assets/sloth.min.css">
    <link rel="shortcut icon" href="./assets/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="./assets/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="144x144" href="./assets/apple-touch-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="180x180" href="./assets/apple-touch-icon-180x180.png">
    {$internal_css}
</head>
<body {$body_attributes}>
EOD;
        echo $partial_head;
    }

    /*
     * Render the HTML from the footer tag to until the final HTML tag
     * @access public
     * @param boolean $display_copyright (optional) default true
     * @param string $add_inline_scripts (optional) default ''
     */
    public function footer( $display_copyright = true, $add_inline_scripts = '' ) {
        $copyright = '';
        if ( $display_copyright ) {
            $repo_url  = 'https://github.com/ka215/forkor';
            /*
             * Filter handler: "display_author_footer"
             * @since v1.0
             */
            $author    = self::call_filter( 'display_author_footer', ' by <a href="https://ka2.org/">ka2</a>' );
            $copyright = sprintf( '<p class="txt-darkgray">Ver. %s powered by <a href="%s">Forkor</a>; &copy; 2020 MAGIC METHODS%s</p>', VERSION, $repo_url, $author );
        }
        if ( ! empty( $add_inline_scripts ) ) {
            if ( preg_match( '|^\<script.*?\>.*\</script\>$|s', $add_inline_scripts, $matches ) !== false && empty( $matches ) ) {
                $add_inline_scripts = '<script>'. $add_inline_scripts .'</script>';
            }
        }
        $partial_footer = <<<EOD
    <footer class="txt-center">
        {$copyright}
    </footer>
    <script async src="./assets/sloth.extension.min.js"></script>
    {$add_inline_scripts}
</body>
</html>
EOD;
        echo $partial_footer;
    }

    /*
     * Filter the attributes output into the body tag
     * @access public
     * @param string $default_atts (required)
     * @return string
     */
    public function body_atts( $default_atts ) {
        $_atts = [ $default_atts, 'data-standby="shown"' ];
        return implode( ' ', $_atts );
    }
}
