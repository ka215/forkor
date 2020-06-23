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
        if ( $this->httppost ) {
            $result = [];
            if ( array_key_exists( 'location_id', $this->post_vars ) ) {
                $_tmp = self::aggregate_logs( $this->post_vars['location_id'] );
                $result = $_tmp['referrers'];
                arsort( $result, SORT_NUMERIC );
                /*
                $total = array_sum( $result );
                foreach ( $result as $_key => $_val ) {
                    $result[$_key] = round( ( $_val / $total * 100 ), 2 );
                }
                */
            } else {
                self::add_error( 'invalid_post', 'Posted no location ID.' );
            }
            $this->json_response = [
                'result' => $result,
                'error'  => self::has_error() ? self::get_error_messages() : '',
            ];
            self::die();
        } else {
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
    }

    /*
     *
     * @access public
     */
    public function create_summary_html() {
        $summary_html  = '<div id="analyze-summary"><table class="mb0"><tbody>';
        $summary_html .= '<th>Total Logged Locations</th><td class="txt-center">'. $this->analyze_total_logged_locations .'</td>';
        $summary_html .= '<th>Total Redirections</th><td class="txt-center">'. $this->analyze_total_redirections .'</td>';
        $summary_html .= '<th>Total Referrers</th><td class="txt-center">'. $this->analyze_total_referrers .'</td>';
        $summary_html .= '</tbody></table></div>';
        return $summary_html;
    }

    /*
     *
     * @access public
     */
    public function create_list_html() {
        $location_root_uri = str_replace( ANALYZE_PATH, '', $this->request_uri );
        $rows = [];
        foreach ( $this->analyze_location_list as $_line ) {
            $_row  = sprintf( '<td><div class="flx-row flx-justify"><label class="copyable mr0">%1$s</label><a href="%2$s" rel="external" title="%2$s" class="link2count-up"></a></div></td>', $_line['location_id'], $location_root_uri . $_line['location_id'] );
            $_row .= sprintf( '<td><div class="flx-row flx-justify"><small class="copyable">%1$s</small><a href="%1$s" rel="external" title="%1$s" class="link2no-count"></a></div></td>', $_line['url'] );
            $_row .= sprintf( '<td class="hbar-container"><div class="hbar"><div class="bar" style="width:%1$s"></div></div><small class="hbar-label">%2$d(%1$s)</small></td>', (float) round( ($_line['total'] / $this->analyze_total_redirections * 100), 2 ) .'%', $_line['total'] );
            $_row .= sprintf( '<td><div class="flx-row flx-center"><button type="button" class="btn-referrers outline m0 pyh" data-target="%2$s"><small>(%1$d)</small></button></div></td>', count( $_line['referrers'] ), $_line['location_id'] );
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
            $lead_text = 'Currently, the registered short URL is not used.';
            $view_summary = '';
            $view_list = '<tr><td colspan="4">'. 'No analytical data are available.' .'</td></tr>';
        } else {
            $lead_text = 'The current usage status of redirected shorten URLs is as follows. This analysis result does not include registered shorten URLs that set without logging and those that have never been redirected.';
            $view_summary = self::create_summary_html();
            $view_list = self::create_list_html();
        }
        $internal_styles = <<<EOS
#analyze-summary, #logged-lication-list { overflow-x: auto; }
.link2count-up, .link2no-count { padding-left: 1em!important; }
.link2count-up::after, .link2no-count::after { bottom: calc(50% - 0.5em)!important; }
.hbar-container { position: relative; height: 1rem; width: auto; }
.hbar { background-color: #f0f0f0; height: 2rem; width: 100%; display: flex; }
.bar { box-sizing: border-box; background-color: #00a968; padding: 0; }
.hbar-label { position: absolute; top: 50%; left: 50%; margin: 0; padding: 0; color: #777; -webkit-transform: translate(-50%, -50%); transform: translate(-50%, -50%); }
/* Pie Chart */
.pie-chart { background: radial-gradient(circle closest-side, transparent 75%, white 0), conic-gradient(#f0f0f0 0,#f0f0f0 100%); position: relative; width: 100%; min-height: 280px; margin: 0; }
EOS;
        self::head( null, $internal_styles );
        $partial_main = <<<EOD
<div id="main">
    <h1 class="txt-center mb2"><span class="forkor-logo"></span>Forkor</h1>
    <div>
        <p>{$lead_text}</p>
        {$view_summary}
        <div id="logged-lication-list">
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
    </div>
    <div class="txt-center">
        <button type="button" id="btn-refresh" onclick="location.href='{$this->self_path}'">Refresh</button>
    </div>
</div>
EOD;
        echo $partial_main;
        $location_root_uri = str_replace( ANALYZE_PATH, '', $this->request_uri );
        
        $inline_scripts = <<<EOS
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script>
var init = function() {
    // Load google charts
    //google.charts.load('current', {'packages':['corechart']});
    //google.charts.setOnLoadCallback(drawChart);
    
    Array.prototype.forEach.call(document.querySelectorAll('.link2count-up'), function(elm) {
        elm.addEventListener('click', confirmCountUpLink, false);
    });
    Array.prototype.forEach.call(document.querySelectorAll('.link2no-count'), function(elm) {
        elm.addEventListener('click', confirmNoCountLink, false);
    });
    Array.prototype.forEach.call(document.querySelectorAll('.btn-referrers'), function(elm) {
        elm.addEventListener('click', infoReferrers, false);
    });
    
};
function confirmCountUpLink(evt) {
    evt.preventDefault();
    var content  = '<p class="txt-center">Transition to <code>'+ evt.target.title +'</code>.<br>'
        content += 'If this transition is run, the redirection count is incremented.<br>'
        content += 'Are you sure you want to transition?</p>';
    showDialog('Confirmation', content, {label:'Yes', callback:function(){location.href=evt.target.title;}}, 5);
}
function confirmNoCountLink(evt) {
    evt.preventDefault();
    var content  = '<p class="txt-center">Transition to <code>'+ evt.target.title +'</code>.<br>'
        content += 'If this transition is run, the redirection count is not incremented.<br>'
        content += 'Are you sure you want to transition?</p>';
    showDialog('Confirmation', content, {label:'Yes', callback:function(){location.href=evt.target.title;}}, 5);
}
function infoReferrers(evt) {
    evt.preventDefault();
    var lid       = evt.target.closest('button').dataset.target,
        lead_text = 'Referrers when the shorten URL <small><code>{$location_root_uri}'+ lid +'</code></small> is used.',
        content   = '',
        formData  = new FormData();
    
    formData.append('location_id', lid);
    
    fetch( '{$this->self_path}', {
        method: 'POST',
        body: formData,
    }).then(function(response) {
        if (response.ok) {
            //console.log( response.text() );
            return response.json();
        }
        throw new Error( 'Network response was invalid.' );
    }).then(function(resJson) {
        //content = createPieChart(resJson.result);
        content = '<div id="piechart"></div>';
        footer  = '<div class="txt-right"><small class="muted">Powered by Google Charts</small></div>';
        showDialog('Referrer Information', '<p>'+ lead_text +'</p>'+ content + footer, 'Close', 1);
        
        // Load google charts
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(function(){
            var chartData = [['Referrer','Count']],
                chartSize = document.getElementById('piechart').clientWidth,
                options = {width:chartSize, height:round(chartSize/2,2), chartArea:{left:0,top:0,width:'100%',height:'100%'}, is3D:true, legend:'none'};
            
            for( _k in resJson.result ) {
                chartData.push([_k, resJson.result[_k]]);
            }
            drawChart(chartData, options);
        });
    }).catch(function(error) {
        console.error('There has been a problem with fetch operation: ', error.message);
    });
}
function array_sum(arr) {
    var key,sum = 0;
    if (typeof arr !== 'object') {
        return null;
    }
    for (key in arr) {
        if (!isNaN(parseFloat(arr[key]))) {
            sum += parseFloat(arr[key]);
        }
    }
    return sum;
}
function round(number, precision) {
    var shift = function(number, precision, reverseShift) {
        if (reverseShift) {
            precision = -precision;
        }
        var numArray = ("" + number).split("e");
        return +(numArray[0] + "e" + (numArray[1] ? (+numArray[1] + precision) : precision));
    };
    return shift(Math.round(shift(number, precision, false)), precision, true);
}
function createPieChart(data) {
    var ranges = [],
        style  = '',
        total  = array_sum(data),
        colors = [ '#4e79a7', '#f28e2c', '#e15759', '#76b7b2', '#59a14f', '#edc949', '#4e79a7', '#f28e2c', '#e15759', '#76b7b2', '#59a14f', '#edc949' ],
        offset = 0,
        cnt    = 0;
    for( key in data ) {
        var percent = round((data[key] / total * 100), 2);
        offset += percent;
        if ( data.length - 1 === cnt || offset > 99 ) {
            offset = 100;
        } else {
            offset = round( offset, 2 );
        }
console.log([ data[key], percent, offset, key ]);
        ranges.push(colors[cnt] +' 0, '+ colors[cnt] +' '+ offset +'%');
        cnt++;
    }
console.log(ranges);
    style = 'background:radial-gradient(circle closest-side,transparent 75%,white 0),conic-gradient('+ranges.join(',')+');';
    return '<div class="pie-chart" style="'+style+'"></div>';
}
function drawChart(chartData, options) {
    // console.log(chartData, options);
    var data = google.visualization.arrayToDataTable(chartData),
        chart = new google.visualization.PieChart(document.getElementById('piechart'));
    chart.draw(data, options);
}
if ( document.readyState === 'complete' || ( document.readyState !== 'loading' && ! document.documentElement.doScroll ) ) {
    init();
} else
if ( document.addEventListener ) {
    document.addEventListener( 'DOMContentLoaded', init, false );
} else {
    window.onload = init;
}
</script>
EOS;
        self::footer( true, $inline_scripts );
    }

}
