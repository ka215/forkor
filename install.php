<?php
/*
 * Installer for the Forkor
 *
 * @package forkor
 */
function load_constants() {
    $is_loaded = false;
    if ( file_exists( __DIR__ . '/index.php' ) && $_tmp_index = @file_get_contents( __DIR__ . '/index.php' ) ) {
        $_lines = explode( "\n", $_tmp_index );
        foreach ( $_lines as $_line ) {
            if ( strpos( $_line, 'define(' ) !== false ) {
                eval( $_line );
            }
        }
        if ( defined( 'APP_NAME' ) && defined( 'VERSION' ) && defined( 'APP_ROOT' ) && defined( 'FORKOR_HOST_HASH' ) ) {
            $is_loaded = true;
        }
    }
    if ( ! $is_loaded ) {
        die( 'The Forkor installer could not ready. There is a problem with the file structure of the application. Please get the package again from the repository.' );
    }
}

load_constants();

require_once __DIR__ . '/config.prototype.php';

function update_aconf( $aconf_path, $aconf_str ) {
    return @file_put_contents( $aconf_path, $aconf_str, LOCK_EX );
}

function create_htaccess() {
    $install_root = rtrim( str_replace( $_SERVER['DOCUMENT_ROOT'], '', __DIR__ ), '/' ) . '/';
    $aconf_path = __DIR__ . '/.htaccess';
    $base_aconf = <<<EOC
ServerSignature Off
DirectoryIndex index.php index.html
EOC;
    $forkor_aconf = <<<EOC
## Settings for Forkor: START ##
<IfModule mod_rewrite.c>
  <IfModule mod_negotiation.c>
    Options -MultiViews -Indexes
  </IfModule>

  RewriteEngine On
  RewriteBase {$install_root}
  RewriteRule ^index\.php$ - [L]

  RewriteCond %{REQUEST_FILENAME} -d
  RewriteRule (app|assets|vendor)$ - [R=404,L]

  RewriteCond %{REQUEST_FILENAME} -f
  RewriteRule ^(app|vendor)/.*?\.php$ - [R=404,L]

  RewriteCond %{REQUEST_FILENAME} -f
  RewriteRule ^(install|config\.prototype|functions)\.php$ - [R=404,L]

  RewriteCond %{REQUEST_FILENAME} -f
  RewriteRule \.(db|log|csv|ini|dat|tpl|yml|md|txt|json|gitignore)$ - [R=404,L]

  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule ^([a-zA-Z0-9]+)(/?.*)$ index.php?lid=$1 [L]
</IfModule>

ErrorDocument 404 {$install_root}404.php
## Settings for Forkor: END ##
EOC;

    if ( @file_exists( $aconf_path ) ) {
        $_aconf = @file_get_contents( $aconf_path );
        if ( $_aconf !== false ) {
            if ( preg_match( '/^(.*)?## Settings for Forkor\: START ##.*?## Settings for Forkor\: END ##(.*)?$/s', $_aconf, $matches ) !== false && ! empty( $matches ) ) {
                if ( isset( $matches[1] ) && ! empty( $matches[1] ) ) {
                    $forkor_aconf = $matches[1] . $forkor_aconf;
                }
                if ( isset( $matches[2] ) && ! empty( $matches[2] ) ) {
                    $forkor_aconf = $forkor_aconf . $matches[2];
                }
            } else {
                $forkor_aconf = $_aconf . $forkor_aconf;
            }
            $result = update_aconf( $aconf_path, $forkor_aconf );
        } else {
            // Can not read .htaccess
            $result = false;
        }
    } else {
        $forkor_aconf = $base_aconf ."\n\n". $forkor_aconf;
        $result = update_aconf( $aconf_path, $forkor_aconf );
    }
    return [ $result, $forkor_aconf ];
}

function create_config( $post_vars ) {
    $_config = @file_get_contents( __DIR__ . '/config.prototype.php' );

    foreach ( $post_vars as $_key => $_val ) {
        switch ( $_key ) {
            case 'register_allowed_ips':
            case 'analyze_allowed_ips':
                $pattern = "/'#%". strtolower( $_key ) ."%#'/";
                $_val = empty( $_val ) ? '' : '"'. implode( '", "', $_val ) .'"';
                $_config = preg_replace( $pattern, html_entity_decode( $_val, ENT_QUOTES ), $_config, 1 );
                break;
            case 'show_index':
                $pattern = "/'#%". strtolower( $_key ) ."%#'/";
                $_config = preg_replace( $pattern, $_val ? 'true' : 'false', $_config, 1 );
                break;
            default:
                $pattern = '/#%'. strtolower( $_key ) .'%#/';
                $_config = preg_replace( $pattern, $_val, $_config, 1 );
                break;
        }
    }
    if ( @file_exists( __DIR__ . '/config.php' ) ) {
        return [ true, 'already_exists' ];
    }
    if ( @file_put_contents( __DIR__ . '/config.php', $_config, LOCK_EX ) ) {
        return [ true, 'successfully' ];
    } else {
        return [ false, 'not_create_config' ];
    }
}

function create_tables( $post_vars ) {
    $dsn = sprintf( '%s:dbname=%s;host=%s;charset=%s', $post_vars['dsn_prefix'], $post_vars['db_name'], $post_vars['db_host'], $post_vars['db_charset'] );
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $dbh = new \PDO( $dsn, $post_vars['db_user'], $post_vars['db_pass'], $options );
    } catch ( PDOException $e ) {
        return [ false, 'disconnect_db' ];
    }

    $table_prefix = 'forkor_';
    $sqls = [
        'mysql' => [
            'locations' => <<<EOQ
CREATE TABLE IF NOT EXISTS {$table_prefix}locations (
  `id` int(11) UNSIGNED AUTO_INCREMENT,
  `location_id` varchar(16) NOT NULL,
  `url` varchar(255) NOT NULL,
  `logged` bit(1) NOT NULL DEFAULT b'1',
  `modified_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX lid_index(`location_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET={$post_vars['db_charset']}
EOQ,
            'location_logs' => <<<EOQ
CREATE TABLE IF NOT EXISTS {$table_prefix}location_logs (
  `id` bigint(20) UNSIGNED AUTO_INCREMENT,
  `location_id` varchar(16) NOT NULL,
  `referrer` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET={$post_vars['db_charset']}
EOQ,
        ],
    ];

    $uncreated_tables = [];
    foreach ( $sqls[$post_vars['dsn_prefix']] as $_table => $_sql ) {
        $result = $dbh->query( $_sql );
        if ( ! $result ) {
            $uncreated_tables[] = $_table;
        }
    }

    if ( ! empty( $uncreated_tables ) ) {
        foreach ( $uncreated_tables as $_i => $_table ) {
            $uncreated_tables[$_i] = $sqls[$post_vars['dsn_prefix']][$_table];
        }
        return [ false, $uncreated_tables ];
    } else {
        return [ true, 'successfully' ];
    }
}

$httppost = ( 'post' === strtolower( $_SERVER['REQUEST_METHOD'] ) );

if ( $httppost ) {
    $post_vars = [];
    if ( empty( $_POST ) ) {
        die( 'Invalid Submission!' );
    }
    foreach ( $_POST as $_key => $_val ) {
        switch ( $_key ) {
            case 'register_allowed_ips':
            case 'analyze_allowed_ips':
                $_array = explode( ',', filter_var( $_val, FILTER_SANITIZE_STRING ) );
                foreach ( $_array as $_i => $_v ) {
                    $_v = trim( $_v );
                    if ( empty( $_v ) ) {
                        unset( $_array[$_i] );
                    } else {
                        $_array[$_i] = $_v;
                    }
                }
                $post_vars[$_key] = $_array;
                break;
            case 'show_index':
                $post_vars[$_key] = filter_var( $_val, FILTER_VALIDATE_BOOLEAN );
                break;
            default:
                $post_vars[$_key] = filter_var( $_val, FILTER_SANITIZE_STRING );
                break;
        }
    }
    if ( ! isset( $post_vars['show_index'] ) ) {
        $post_vars['show_index'] = false;
    }

    // Create Tables On DataBase
    $_res = create_tables( $post_vars );
    $created_tables = $_res[0];
    $error_message = $_res[1];

    // Create config.php
    $_res = create_config( $post_vars );
    $created_config = $_res[0];


    // Create .htaccess
    $_res = create_htaccess();
    $created_htaccess = $_res[0];
    $forkor_aconf = $_res[1];

    $install_completed = ( $created_tables && $created_config && $created_htaccess );
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta charset="UTF-8">
    <title>Forkor ─ Shortener URL Generator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Forkor generates the shorten URL from entered something URL">
    <link rel="stylesheet" href="./assets/sloth.min.css">
    <link rel="shortcut icon" href="./assets/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="./assets/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="144x144" href="./assets/apple-touch-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="180x180" href="./assets/apple-touch-icon-180x180.png">
    <style><?php
    $internal_css = <<<EOS
/* Internal CSS */
[data-standby="shown"] { visibility: hidden; opacity: 0; transition: opacity 0.3s linear; }
body > *:not(.dialog-backdrop) { margin: 0 auto; width: 100%; max-width: 960px; }
.forkor-logo { position: relative; display: inline-block; width: 1em; height: 1em; margin-right: 0.5rem; line-height: 1.5; }
.forkor-logo::after { position: absolute; content: ''; left: 50%; top: 50%; width: 100%; height: 100%; background-image: url(./assets/forkor.svg); background-size: contain; background-position: center center; background-repeat: no-repeat; transform: translate(-50%, -50%); }
EOS;
echo preg_replace( [ '@\s*([{}|:;,])\s+|\s*(\!)|/\*.+?\*\/|\R@is', '@;(})@' ], '$1$2', $internal_css );
?></style>
</head>
<body class="sloth" data-standby="shown">
  <div class="my1 ma">
<?php if ( $httppost ) : ?>
    <h2 class="line-both"><span class="forkor-logo"></span>Forkor Installation Results</h2>
<?php   if ( $created_tables ) : ?>
    <h3 class="txt-sec">Forkor created the tables on database successfully.</h2>
    <p class="lh-2">
        The table for Forkor has been created correctly. If you have already created it, this section will be skipped.<br>
    </p>
<?php   else : ?>
    <h3 class="txt-tert">Could not create tables to database.</h2>
    <p class="lh-2">
<?php     if ( 'disconnect_db' === $error_message ) : ?>
        The connection information to the database may be incorrect, or the database user may have insufficient privileges.<br>
<?php     else : ?>
        You need to execute the following SQL in the database used by Forkor to create the tables.<br>
    </p>
<?php       foreach ( $error_message as $_sql ) : ?>
    <pre><code><?= $_sql ?></code></pre>
<?php       endforeach; ?>
<?php     endif; ?>
<?php   endif; ?>
    <hr class="double">
<?php   if ( $created_config ) : ?>
    <h3 class="txt-sec">Forkor created a "config.php" successfully.</h2>
    <p class="lh-2">
        The config.php has been created correctly. If you have already created it, this section will be skipped.
    </p>
<?php   else : ?>
    <h3 class="txt-tert">Could not create a "config.php".</h2>
    <p class="lh-2">
        You need to create a modified <code>config.php</code> based on <code>config.prototype.php</code> in the same directory as this installation file.<br>
    </p>
<?php   endif; ?>
    <hr class="double">
<?php   if ( $created_htaccess ) : ?>
    <h3 class="txt-sec">Forkor created a ".htaccess" successfully.</h2>
    <p class="lh-2">
        It is recommended to change the <code>.htaccess</code> file permissions to <code>400</code> or <code>404</code> for security.<br>
    </p>
<?php   else : ?>
    <h3 class="txt-tert">Could not create a ".htaccess".</h2>
    <p class="lh-2">
        You need to put a <code>.htaccess</code> with the following settings in the same directory as this installation file.<br>
        If you use a web server other than Apache, make the same settings as the following settings.<br>
    </p>
    <pre><code><?php
echo htmlspecialchars( $forkor_aconf ); ?></code></pre>
    <p class="lh-2">For security reasons, we recommend changing the permissions of the created <code>.htaccess</code> file to <code>400</code> or <code>404</code>.</p>
<?php   endif; ?>
    <hr class="double">
<?php   if ( $install_completed ) : ?>
    <h3 class="txt-sec">Forkor install completed!</h3>
    <p class="lh-2">
        <strong>Let's get start Forkor!</strong><br>
    </p>
    <div style="text-align:center"><a href="./<?= $post_vars['register_path']; ?>">Register Shortener URL</a></div>
<?php   else : ?>
    <p class="lh-2">
        Would you like to try the installation again?<br>
    </p>
    <div class="txt-center"><a href="./install.php?<?= strtotime( 'now' ); ?>">Retry Installation</a></div>
<?php   endif; ?>
<?php else : ?>
    <h2 class="line-both"><span class="forkor-logo"></span>Forkor Installer</h2>
    <div class="my1">
    <p>Forkor works under <b>PHP</b>, <b>MySQL</b> and <b>Apache</b>. If your environment meets the requirements, please install.</p>
    <hr class="double">
    <form method="post" id="forkor-installer" class="sloth-validation" autocomplete="off">
        <p class="mb1">Forkor uses a database. Currently only MySQL is supported. Enter the information for the database that creates the table for Forkor.</p>
        <input type="hidden" name="dsn_prefix" value="mysql">
        <div class="inline mb1">
            <label for="dsn-prefix" class="m0 required" data-switch-class="sm:w-1-3,md:w-1-5,lg:w-1-6" />DB Driver</label>
            <label for="dsn-prefix" class="m0" _data-switch-class="sm:w-2-3,md:w-2-5,lg:w-1-3">
                <select id="dsn-prefix" name="dsn_prefix" class="m0" readonly>
                    <option value="mysql">MySQL</option>
                </select>
            </label>
        </div>
        <div class="inline mb1">
            <label for="db-name" class="m0 required" data-switch-class="sm:w-1-3,md:w-1-5,lg:w-1-6">DB Name</label>
            <input type="text" id="db-name" name="db_name" placeholder="Enter Database Name" class="m0" data-switch-class="sm:w-2-3,md:w-2-5,lg:w-1-3" required>
        </div>
        <div class="inline mb1">
            <label for="db-user" class="m0 required" data-switch-class="sm:w-1-3,md:w-1-5,lg:w-1-6">DB User</label>
            <input type="text" id="db-user" name="db_user" placeholder="Enter Database User" class="m0" data-switch-class="sm:w-2-3,md:w-2-5,lg:w-1-3" required>
        </div>
        <div class="inline mb1">
            <label for="db-pass" class="m0 required" data-switch-class="sm:w-1-3,md:w-1-5,lg:w-1-6">DB Password</label>
            <input type="text" id="db-pass" name="db_pass" placeholder="Enter Database Password" class="m0" data-switch-class="sm:w-2-3,md:w-2-5,lg:w-1-3" required>
        </div>
        <div class="inline mb1">
            <label for="db-host" class="m0 required" data-switch-class="sm:w-1-3,md:w-1-5,lg:w-1-6">DB Host</label>
            <input type="text" id="db-host" name="db_host" placeholder="Enter Database Host" value="localhost" class="m0" data-switch-class="sm:w-2-3,md:w-2-5,lg:w-1-3" required>
        </div>
        <div class="inline mb1">
            <label for="db-charset" class="m0 required" data-switch-class="sm:w-1-3,md:w-1-5,lg:w-1-6">DB Charset</label>
            <input type="text" id="db-charset" name="db_charset" placeholder="Enter Database Charset" value="utf8mb4" class="m0" data-switch-class="sm:w-2-3,md:w-2-5,lg:w-1-3" required>
        </div>
        <hr class="double">
        <p class="mb1">
            Set the path name for registering the shortened URL and the path name for analyzing the usage history. It is possible to limit the IP address to connect to each path.<br>
            <small class="note mb2">By the way, the remote IP address of the environment you are currently connected to is <code><?= $_SERVER['REMOTE_ADDR'] ?></code>.</small>
        </p>
        <div class="inline mb1">
            <label for="register-path" class="m0 required" data-switch-class="sm:w-1-3,md:w-1-5,lg:w-1-6">Register Path</label>
            <span class="mrh"><?= dirname( $_SERVER['REQUEST_URI'] ) . '/' ?></span>
            <input type="text" id="register-path" name="register_path" placeholder="Enter Register Path" value="make" required>
        </div>
        <div class="inline mb1">
            <label for="register-allowed-ips" class="m0" data-switch-class="sm:w-1-3,md:w-1-5,lg:w-1-6">Register Allowed IPs</label>
            <textarea id="register-allowed-ips" name="register_allowed_ips" placeholder="Enter IP addresses to allow with separated comma; all allowed if empty" class="m0" data-switch-class="sm:w-2-3,md:w-3-5,lg:w-2-3" rows="3"></textarea>
        </div>
        <div class="inline mb1">
            <label for="analyze-path" class="m0 required" data-switch-class="sm:w-1-3,md:w-1-5,lg:w-1-6">Analyze Path</label>
            <span class="mrh"><?= dirname( $_SERVER['REQUEST_URI'] ) . '/' ?></span>
            <input type="text" id="analyze-path" name="analyze_path" placeholder="Enter Analyze Path" value="analyze" required>
        </div>
        <div class="inline mb1">
            <label for="analyze-allowed-ips" class="m0" data-switch-class="sm:w-1-3,md:w-1-5,lg:w-1-6">Analyze Allowed IPs</label>
            <textarea id="analyze-allowed-ips" name="analyze_allowed_ips" placeholder="Enter IP addresses to allow with separated comma; all allowed if empty" class="m0" data-switch-class="sm:w-2-3,md:w-3-5,lg:w-2-3" rows="3"></textarea>
        </div>
        <hr class="double">
        <div class="inline mb1">
            <label class="checkbox"><span class="mlh">Display Forkor index page when location path of shortened URL is not specified</span>
                <input type="checkbox" id="show-index" name="show_index" value="1" checked>
                <span class="indicator"></span>
            </label>
        </div>
        <hr class="double">
        <p class="mb1">Forkor installation involves creating tables in the database, creating configuration files for applications, and creating/updating ".htaccess". When you are ready, click the "Install" button.</p>
        <div class="inline mb1">
            <button type="submit" id="btn-install" class="w-full">Install</button>
        </div>
    </form>
    </div>
<?php endif; ?>
    <footer class="flx-row flx-center py2">
        <span class="fnt-sm txt-darkgray">Version <?= VERSION ?> / Crafted with <span class="txt-darkgray">&copy;</span> 2020 MAGIC METHODS by <a href="https://ka2.org/">ka2</a></span>
    </footer>
  </div>
  <script async src="./assets/sloth.extension.min.js"></script>
</body>
</html>
