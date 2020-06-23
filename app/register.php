<?php
namespace Forkor;

trait register
{

    /*
     * Preparation process before rendering the register page
     * @access public
     */
    public function register() {
        if ( $this->httppost ) {
            foreach ( $this->post_vars as $_key => $_val ) {
                $_opts = [];
                $_is_delete = false;
                switch ( $_key ) {
                    case 'redirect_url':
                        $_opts['flags'] = [
                            FILTER_FLAG_SCHEME_REQUIRED,
                            FILTER_FLAG_HOST_REQUIRED,
                        ];
                        $_val = filter_var( $_val, FILTER_VALIDATE_URL, $_opts );
                        break;
                    case 'generate_type':
                        $_val = in_array( $_val, [ 'auto', 'self' ], true ) ? $_val : 'auto';
                        break;
                    case 'min_path_length':
                        $_opts['options'] = [
                            'default'   => 4,
                            'min_range' => 1,
                            'max_range' => 15,
                        ];
                        $_val = filter_var( $_val, FILTER_VALIDATE_INT, $_opts );
                        break;
                    case 'max_path_length':
                        $_opts['options'] = [
                            'default'   => 16,
                            'min_range' => 2,
                            'max_range' => 16,
                        ];
                        $_val = filter_var( $_val, FILTER_VALIDATE_INT, $_opts );
                        break;
                    case 'path_candidate':
                    case 'register_shorten_uri':
                        if ( preg_match( '|^[a-zA-Z0-9_-]{1,16}$|', $_val ) !== 1 ) {
                            $_val = null;
                        }
                        break;
                    case 'is_logged':
                    case 'committed':
                        $_val = filter_var( $_val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
                        break;
                    default:
                        $_is_delete = true;
                        break;
                }
                if ( $_is_delete ) {
                    unset( $this->post_vars[$_key] );
                } else {
                    $this->post_vars[$_key] = $_val;
                }
            }
            if ( ! array_key_exists( 'is_logged', $this->post_vars ) ) {
                $this->post_vars['is_logged'] = false;
            }
            unset( $_is_delete, $_opts, $_val, $_key );
            
            self::remove_error();
            if ( empty( $this->dbh ) ) {
                self::connect_db();
            }
            if ( $this->post_vars['committed'] ) {
                if ( self::add_location( $this->post_vars['register_shorten_uri'], $this->post_vars['redirect_url'], $this->post_vars['is_logged'] ) ) {
                    // Completion of registration
                    $this->json_response = [
                        'result' => $this->post_vars['register_shorten_uri'],
                        'notice'  => sprintf( 'The location path %s has been registered as a new shortened URL.', '<code>'. $this->post_vars['register_shorten_uri'] .'</code>' ),
                    ];
                } else {
                    $this->json_response = [
                        'result' => '',
                        'error'  => self::has_error() ? self::get_error_messages() : 'Invalid request.',
                    ];
                }
                self::die();
            } else {
                $new_location_id = self::pregenerate_location_id();
                $check_error_code = 'failure_to_generate_id';
                $this->json_response = [
                    'result' => $new_location_id,
                    'error'  => self::has_error( $check_error_code ) ? self::get_error_messages( $check_error_code ) : '',
                ];
                self::die();
            }
        }
        self::send_header( true );
        self::view_register();
    }

    /*
     * 
     * @access protected
     * @return string
     */
    protected function pregenerate_location_id() {
        if ( 'auto' === $this->post_vars['generate_type'] ) {
            $_min = $this->post_vars['min_path_length'];
            $_max = $this->post_vars['max_path_length'];
            for ( $_len = $_min; $_len <= max( $_min, $_max ); $_len++ ) {
                for ( $_i = 0; $_i < pow( 62, $_len ); $_i++ ) {
                    $pre_location_id = self::make_hash( $this->post_vars['redirect_url'], $_len );
                    if ( self::is_usable_location_id( $pre_location_id, true ) ) {
                        return $pre_location_id;
                        //break 2;
                    }
                }
            }
            self::add_error( 'failure_to_generate_id', 'Auto generation has failed. Please change the range of the characters length and try again.' );
            return null;
        } else {
            $pre_location_id = $this->post_vars['path_candidate'];
            if ( self::is_usable_location_id( $pre_location_id, false ) ) {
                return $pre_location_id;
            } else {
                self::add_error( 'failure_to_generate_id', self::get_error_messages(), true );
                return null;
            }
        }
    }

    /*
     * Render register page
     * @access public
     */
    public function view_register() {
        $location_root_uri = str_replace( REGISTER_PATH, '', $this->request_uri );
        $default_vars = [
            'redirect_url'    => isset( $this->post_vars['redirect_url'] ) ? $this->post_vars['redirect_url'] : '',
            'generate_type'   => isset( $this->post_vars['generate_type'] ) ? $this->post_vars['generate_type'] : 'auto',
            'min_path_length' => isset( $this->post_vars['min_path_length'] ) ? $this->post_vars['min_path_length'] : 4,
            'max_path_length' => isset( $this->post_vars['max_path_length'] ) ? $this->post_vars['max_path_length'] : 16,
            'path_candidate'  => isset( $this->post_vars['path_candidate'] ) ? $this->post_vars['path_candidate'] : '',
            'is_logged'       => isset( $this->post_vars['is_logged'] ) ? $this->post_vars['is_logged'] : true,
            //'register_shorten_uri' => isset( post_vars['register_shorten_uri'] ) ? post_vars['register_shorten_uri'] : '',
            'register_shorten_uri' => '',
            //'committed'       => isset( post_vars['committed'] ) ? (int) post_vars['committed'] : 0,
            'committed'       => '0',
        ];
        $checked_auto   = 'auto' === $default_vars['generate_type'] ? ' checked="checked"' : '';
        $checked_self   = 'self' === $default_vars['generate_type'] ? ' checked="checked"' : '';
        $required_self  = 'self' === $default_vars['generate_type'] ? 'required' : '';
        $checked_logged = $default_vars['is_logged'] ? ' checked="checked"' : '';
        $internal_styles = <<<EOS
#path-candidate { margin-right: 0; width: 16em; }
.after-margin:not(.required)::after { content: ''; margin-right: 11.55px; }
EOS;
        self::head( null, $internal_styles );
        $partial_main = <<<EOD
<div id="main">
    <h1 class="txt-center"><span class="forkor-logo"></span>Forkor Register</h1>
    <form id="register-form" method="post" action="{$this->self_path}" class="sloth-validation">
    <div class="flx-row flx-wrap item-start">
        <div class="w-full mb2">
            <h2 class="line-right txt-darkgray">Create Shorten URI</h2>
            <p>
                First, enter the URL with the full address starting with "http(s)://..." that you want to redirect via the shortened URI.<br>
                Then, select the method for generating the shorten URI. There are two generation methods: <strong>Auto Generation</strong> by specifying the minimum and maximum character string lengths, and <strong>Self Generation</strong> that allows you to specify a free string.<br>
                Only the single-byte alphanumeric and "_" and "-" symbols can be used for self-generation. In addition, uppercase and lowercase letters of the alphabet are distinguished, and up to 16 is maximum characters.<br>
                Furthermore, when entering a self-generated URI path, it is checked whether it can be registered in real time. You cannot register a path that has already been registered or a word reserved by the application, so if the check is NG, change the path accordingly.<br>
                Finally, set whether to log when the generated shorten URI is used. Shortener URIs with logging enabled will be able to see statistics on the analytics page.<br>
            </p>
        </div>
        <div id="field-redirect-url" class="w-full mb1">
            <label for="redirect-url" class="required">The URL you want to redirect with the shorten URI:</label>
            <input type="text" id="redirect-url" name="redirect_url" placeholder="Full address beginning with http(s)://..." data-dispname="Redirect URL" value="{$default_vars['redirect_url']}" data-do-check="1" required>
        </div>
        <div id="field-auto-generation" class="flx-row flx-wrap item-center w-full mb1">
            <div class="w-1-5">
                <label id="label-auto-generate" class="radio" data-follow-color="inherit">Auto Generation
                    <input type="radio" id="switch-generate-type-1" name="generate_type" value="auto" data-do-check="1"{$checked_auto}>
                    <span class="indicator"></span>
                </label>
            </div>
            <div class="w-4-5 flx-row item-center">
                <div class="inline">
                    <label for="min-path-length">Minimum Length</label>
                    <input type="number" id="min-path-length" name="min_path_length" value="{$default_vars['min_path_length']}" min="1" max="15" data-do-check="1">
                </div>
                <div class="inline">
                    <label for="max-path-length">Maximum Length</label>
                    <input type="number" id="max-path-length" name="max_path_length" value="{$default_vars['max_path_length']}" min="2" max="16" data-do-check="1">
                </div>
            </div>
        </div>
        <div id="field-self-generation" class="flx-row flx-wrap item-center w-full mb1">
            <div class="w-1-5">
                <label id="label-self-generate" class="radio" data-follow-color="inherit">Self Generation
                    <input type="radio" id="switch-generate-type-2" name="generate_type" value="self" data-do-check="1"{$checked_self}>
                    <span class="indicator"></span>
                </label>
            </div>
            <div class="w-4-5 flx-row item-center">
                <div class="inline">
                    <label for="path-candidate" class="{$required_self} after-margin">Enter path of shorten URI:</label>
                    <input type="text" id="path-candidate" name="path_candidate" placeholder="Enter path of shorten URI" data-dispname="Path of Shorten URI" pattern="^[a-zA-Z0-9_-]{1,16}$" value="{$default_vars['path_candidate']}" data-do-check="1">
                </div>
            </div>
        </div>
        <div id="field-logging" class="w-full">
            <label class="tgl flat" data-follow-color="inherit">Logged when the shorten URI is used.
                <input type="checkbox" name="is_logged" value="1"{$checked_logged}>
                <span class="tgl-btn"></span>
            </label>
        </div>
        <div id="field-register-shorten-uri" class="w-full">
            <hr class="dotted">
            <label for="register-shorten-uri">The shorten URI to be registered:</label>
            <input type="text" id="register-shorten-uri" placeholder="{$location_root_uri}" readonly disabled>
            <input type="hidden" id="new-register-shorten-uri" name="register_shorten_uri" value="">
            <hr class="dotted">
        </div>
        <div id="field-commit-button" class="w-full">
            <button type="button" id="btn-commit" class="">Commit & Register</button>
            <input type="hidden" id="committed" name="committed" value="0">
        </div>
    </div>
    </form>

</div>
EOD;
        echo $partial_main;
        $inline_scripts = <<<EOS
var init = function() {
    // Check whether is registrable the path of the shortened URI
    Array.prototype.forEach.call(document.querySelectorAll('[data-do-check]'), function(elm) {
        switch(elm.id) {
            case 'redirect-url':
                //elm.addEventListener('paste', checkNewLocationPath, false);
                elm.addEventListener('input', checkNewLocationPath, false);
                //elm.addEventListener('blur', checkNewLocationPath, false);
                break;
            case 'switch-generate-type-1':
            case 'switch-generate-type-2':
                //toggleGenerationType(document.querySelector('input[name=generate_type]:checked').value);
                elm.addEventListener('change', checkNewLocationPath, false);
                break;
            case 'min-path-length':
            case 'max-path-length':
                elm.addEventListener('input', checkNewLocationPath, false);
                break;
            case 'path-candidate':
                //elm.addEventListener('keypress', checkNewLocationPath, false);
                elm.addEventListener('input', checkNewLocationPath, false);
                break;
        }
    });
    
    // Handle to toggle generate type
    Array.prototype.forEach.call(document.querySelectorAll('input[name=generate_type]'), function(elm) {
        elm.addEventListener('change', function(evt) {
            var currentType = evt.target.value;
            toggleGenerationType( currentType );
        }, false);
    });
    toggleGenerationType(document.querySelector('input[name=generate_type]:checked').value);
    
    // Handler of "Commit & Register" button
    document.getElementById('btn-commit').addEventListener('click', function(evt) {
        var newPath = document.getElementById('new-register-shorten-uri'),
            commitF = document.getElementById('committed');
        
        if ( '' === newPath.value ) {
            return false;
        } else {
            commitF.value = 1;
            //document.getElementById('register-form').submit();
            var formData = new FormData(document.getElementById('register-form'));
            fetch( '', {
                method: 'POST',
                body: formData,
            }).then(function(response) {
                if (response.ok) {
                    //console.log( response.text() );
                    return response.json();
                }
                throw new Error( 'Network response was invalid.' );
                showDialog('Registration Failed', '<span class="txt-tert">Network response was invalid.</span>', 'Close', 1);
            }).then(function(resJson) {
                //console.log( resJson, resJson.error );
                if ( Object.prototype.hasOwnProperty.call(resJson, 'notice') ) {
                    showDialog('Registration Successful', '<span class="txt-sec">'+ resJson.notice +'</span>', {label:'Close', callback:function(){ location.replace('{$this->self_path}'); }}, 1);
                } else {
                    showDialog('Registration Failed', '<span class="txt-tert">'+ resJson.error +'</span>', 'Close', 1);
                }
            }).catch(function(error) {
                console.error('There has been a problem with fetch operation: ', error.message);
                showDialog('Registration Failed', '<span class="txt-tert">'+ error.message +'</span>', 'Close', 1);
            });
        }
    }, false);
    
};

function checkNewLocationPath(evt) {
    // initialize
    var preview = document.getElementById('register-shorten-uri');
        newPath = document.getElementById('new-register-shorten-uri'),
        btnComt = document.getElementById('btn-commit');
        //commitF = document.getElementById('committed');
    preview.classList.remove('txt-sec','txt-quat','fw500');
    preview.value = '';
    newPath.value = '';
    btnComt.classList.remove('clr-prim');
    
    if ( document.getElementById('redirect-url').value === '' ||
       ( 'switch-generate-type-2' === evt.target.id && document.getElementById('path-candidate').value === '' ) ) {
        return;
    }
    var formData = new FormData(document.getElementById('register-form'));
    //console.log('Do check: '+ evt.target.id, 'Event Type: '+ evt.type, formData);
    fetch( '', {
        method: 'POST',
        body: formData,
    }).then(function(response) {
        if (response.ok) {
            //console.log( response.text() );
            return response.json();
        }
        throw new Error( 'Network response was invalid.' );
    }).then(function(resJson) {
        if ( resJson.error !== '' ) {
            preview.classList.add('txt-quat','fw500');
            preview.value = 'Error: ' + resJson.error;
            newPath.value = '';
            btnComt.classList.remove('clr-prim');
        } else {
            preview.classList.add('txt-sec','fw500');
            preview.value = preview.placeholder + resJson.result;
            newPath.value = resJson.result;
            btnComt.classList.add('clr-prim');
        }
    }).catch(function(error) {
        console.error('There has been a problem with fetch operation: ', error.message);
    });
}

function toggleGenerationType(currentType) {
    var minLen = document.getElementById('min-path-length'),
        maxLen = document.getElementById('max-path-length'),
        pathCd = document.getElementById('path-candidate'),
        minLbl = document.querySelector('[for=min-path-length]'),
        maxLbl = document.querySelector('[for=max-path-length]'),
        pathLb = document.querySelector('[for=path-candidate]');
    
    if ( 'auto' === currentType ) {
        minLbl.classList.remove('muted');
        //minLen.removeAttribute('disabled');
        minLen.removeAttribute('readonly');
        maxLbl.classList.remove('muted');
        //maxLen.removeAttribute('disabled');
        maxLen.removeAttribute('readonly');
        pathLb.classList.remove('required');
        pathLb.classList.add('muted');
        //pathCd.setAttribute('disabled', true);
        pathCd.setAttribute('readonly', true);
        pathCd.classList.remove('value-ok', 'value-ng');
        pathCd.removeAttribute('required');
        pathCd.value = '';
    } else {
        minLbl.classList.add('muted');
        //minLen.setAttribute('disabled', true);
        minLen.setAttribute('readonly', true);
        maxLbl.classList.add('muted');
        //maxLen.setAttribute('disabled', true);
        maxLen.setAttribute('readonly', true);
        pathLb.classList.add('required');
        pathLb.classList.remove('muted');
        //pathCd.removeAttribute('disabled');
        pathCd.removeAttribute('readonly');
        pathCd.setAttribute('required', true);
    }
}
if ( document.readyState === 'complete' || ( document.readyState !== 'loading' && ! document.documentElement.doScroll ) ) {
    init();
} else
if ( document.addEventListener ) {
    document.addEventListener( 'DOMContentLoaded', init, false );
} else {
    window.onload = init;
}
EOS;
        self::footer( true, $inline_scripts );
    }
}
