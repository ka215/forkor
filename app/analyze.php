<?php
namespace Forkor;

trait analyze
{

    /*
     * Property for analyze only
     * @access public
     * @var array<assoc>
     */
    public $analyze_location_list = [];

    /*
     * Property for analyze only
     * @access public
     * @var int
     */
    public $analyze_total_logged_locations;

    /*
     * Property for analyze only
     * @access public
     * @var int
     */
    public $analyze_total_redirections;

    /*
     * Property for analyze only
     * @access public
     * @var int
     */
    public $analyze_total_referrers;

    /*
     *
     * @access public
     */
    public function analyze() {
        //
        self::connect_db();

        //self::test( 'location_seeds', 100 );
        self::test( 'check_seeds' );

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

        //self::test( 'fetch_data' );
        //self::test( 'delete_data' );
        //self::test( 'remove_location' );
        //self::test( 'fetch_data' );

        //self::test( 'add_log' );
        //self::test( 'get_logged_ids' );
        //self::test( 'get_logs' );
        //self::test( 'aggregate_logs' );

        //self::test( 'sanitize_path' );
        //self::test( 'is_datetime' );
        //self::test( 'datetime_val' );
        //self::test( 'die' );

        // Initialize
        $this->analyze_total_redirections = 0;
        $this->analyze_total_referrers = 1;// for unknown only
        $logged_ids = self::get_logged_ids( null, true, true );
        arsort( $logged_ids, SORT_NUMERIC );
        foreach ( $logged_ids as $_lid => $_cnt ) {
            $_tmp = self::aggregate_logs( $_lid );
            $this->analyze_location_list[] = $_tmp;
            $this->analyze_total_redirections += $_tmp['total'];
            $this->analyze_total_referrers += count( $_tmp['referrers'] ) - 1;
        }
        $this->analyze_total_logged_locations = count( $logged_ids );
        unset( $_tmp, $_cnt, $_lid );
        
        self::send_header( true );
        self::view_analyze();

    }

    /*
     *
     * @access public
     */
    public function create_list_html() {
        $location_root_uri = str_replace( ANALYZE_PATH, '', $this->request_uri );
        $rows = [];
        foreach ( $this->analyze_location_list as $_line ) {
            $_row  = sprintf( '<td><a href="%s">/%s</a></td>', $location_root_uri . $_line['location_id'], $_line['location_id'] );
            $_row .= sprintf( '<td><a href="%s">%s</a></td>', $_line['url'], $_line['url'] );
            $_row .= sprintf( '<td class="txt-center">%d</td>', $_line['total'] );
            $_row .= sprintf( '<td class="txt-center">%d</td>', count( $_line['referrers'] ) );
            $rows[] = $_row;
        }
        return '<tr>'. implode( '</tr>'."\n".'<tr>', $rows ) .'</tr>';
    }

    /*
     * Rendoer Analyze page
     * @access public
     */
    public function view_analyze() {
        if ( empty( $this->analyze_location_list ) ) {
            $view_list = '<tr><td colspan="4">'. 'No analytical data are available.' .'</td></tr>';
        } else {
            $view_list = self::create_list_html();
        }
        $internal_styles = <<<EOS
EOS;
        self::head( null, $internal_styles );
        $partial_main = <<<EOD
<div id="main">
    <h1 class="txt-center mb2"><span class="forkor-logo"></span>Forkor</h1>
    <div>
        <p>Get Start Analyzing!</p>
        <p>Total Logged Locations: {$this->analyze_total_logged_locations} / Total Redirections: {$this->analyze_total_redirections} / Total Referrers: {$this->analyze_total_referrers}</p>
        <table>
            <thead>
                <th>Shorten URI</th>
                <th>Redirect URL</th>
                <th>Redirections</th>
                <th>Referrers</th>
            </thead>
            <tbody>
                {$view_list}
            </tbody>
            <tfoot>
                <th>Shorten URI</th>
                <th>Redirect URL</th>
                <th>Redirections</th>
                <th>Referrers</th>
            </tfoot>
        </table>
    </div>
    <div class="txt-center">
        <button type="button" id="btn-refresh" onclick="location.href='{$this->self_path}'">Refresh</button>
    </div>
</div>
EOD;
        echo $partial_main;
        $inline_scripts = <<<EOS
EOS;
        self::footer( true, $inline_scripts );
    }

}
