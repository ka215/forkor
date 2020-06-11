<?php
namespace Forkor;

trait analyze
{

    /*
     *
     * @access public
     */
    public function analyze() {
        //
        self::connect_db();

        //self::test( 'test_seeds' );
        //self::test( 'check_seeds' );

        //self::test( 'tables_exists' );
        //self::test( 'get_table_columns' );
        //self::test( 'data_exists' );
        //self::test( 'make_hash' );
        //self::test( 'get_redirect_url' );
        //self::test( 'upsert_data' );
        //self::test( 'remove_error' );
        //self::test( 'add_location' );
        //self::test( 'get_locations' );
        //self::test( 'is_usable_location_id' );

        self::test( 'fetch_data' );
        //self::test( 'delete_data' );
        //self::test( 'remove_location' );
        //self::test( 'fetch_data' );

        //self::test( 'sanitize_path' );
        //self::test( 'is_datetime' );
        //self::test( 'die' );

        self::send_header( true );
        self::view_analyze();

    }

    /*
     * Rendoer Analyze page
     * @access public
     */
    public function view_analyze() {
        $internal_styles = <<<EOS
EOS;
        self::head( null, $internal_styles );
        $partial_main = <<<EOD
<div id="main">
    <h1 class="txt-center mb2"><span class="forkor-logo"></span>Forkor</h1>
    <div>
        Get Start Analyzing!
    </div>
</div>
EOD;
        echo $partial_main;
        $inline_scripts = <<<EOS
EOS;
        self::footer( true, $inline_scripts );
    }

}
