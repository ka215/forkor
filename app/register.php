<?php
namespace Forkor;

trait register
{

    /*
     * Preparation process before rendering the register page
     * @access public
     */
    public function register() {
        /*
        $redirect_to = 'https://'. $_SERVER['HTTP_HOST'] .'/app/make.php';
        if ( ! $this->httppost ) {
            $redirect_to .= '?uid=' . $this->uid;
        }
        header( 'Location: ' . $redirect_to, true, $this->httppost ? 307 : 302 );
        */
        self::send_header( true );
        self::view_register();
    }

    /*
     * Render register page
     * @access public
     */
    public function view_register() {
        $internal_styles = <<<EOS

EOS;
        self::head( null, $internal_styles );
        $partial_main = <<<EOD
<div id="main">
    <h1 class="txt-center"><span class="forkor-logo"></span>Forkor Register</h1>
    <div class="flx-row item-start">
        <div class="w-full">
            <h2 class="line-right txt-darkgray">Register shorten URL</h2>
        </div>
    </div>

</div>
EOD;
        echo $partial_main;
        $inline_scripts = <<<EOS
EOS;
        self::footer( true, $inline_scripts );
    }
}
