<?php
namespace Forkor;

trait test
{
    /*
     * Test of methods
     */
    public function test( $type ) {
        $res = [];
        switch ( $type ) {
case 'test_seeds':
    $seed = 100;
    $data = 0;
    self::delete_data( 'locations', 'all' );
    for ( $i = 0; $i < $seed; $i++ ) {
        $seed_url = 'https://'. strtolower( self::make_hash( mt_rand(), 6 ) ) .'.co';
        if ( self::add_location( self::make_hash( $seed_url, mt_rand( 3, 8 ) ), $seed_url, ( mt_rand( 0, $seed ) & 1 ) ) ) {
            $data++;
        }
    }
    self::set_buffer( 'Inserted Seeds: ' . $data );
    break;
case 'check_seeds':
    $res = self::fetch_data( 'locations', [ 'location_id', 'url', 'logged' ] );
    self::set_buffer( $res );
    break;
case 'tables_exists':
    self::{$type}();
    self::set_buffer( $this->errors );// ok
    break;
case 'get_table_columns':
    $res[] = self::{$type}( '' );// ok
    $res[] = self::{$type}( 'locations' );// ok
    $res[] = self::{$type}( 'location_logs' );// ok
    self::set_buffer( $res );
    break;
case 'data_exists':
    $res[] = self::{$type}( 'location' );// ok
    $res[] = self::{$type}( 'locations' );// ok
    $res[] = self::{$type}( 'location_logs' );// ok
    $res[] = self::{$type}( 'locations', [ 'id', '=', 1 ] );// ok
    $res[] = self::{$type}( 'locations', [ 'id', '=', 'a' ] );// ok
    $res[] = self::{$type}( 'locations', [ 'id', '>', 5 ] );// ok
    $res[] = self::{$type}( 'locations', [ 'location_id', 'like', 'tes%' ] );// ok
    $res[] = self::{$type}( 'locations', [ 'location_id', '=', 'testing' ] );// ok
    $res[] = self::{$type}( 'locations', [ 'logged', '=', false ] );// ok
    self::set_buffer( [ $type => $res, 'errors' => $this->errors ], 'export' );
    break;
case 'fetch_data':
    //$res[] = self::{$type}( 'location' );// ok
    //$res[] = self::{$type}( 'locations' );// ok
    //$res[] = self::{$type}( 'locations', 'count' );// ok
    //$res[] = self::{$type}( 'location_logs', 'count' );// ok
    //$res[] = self::{$type}( 'locations', [ 'url', 'logged' ] );// ok
    //$res[] = self::{$type}( 'locations', [ 'id', 'url', 'logged' ], [ 'id', '=', 3 ] );// ok
    //$res[] = self::{$type}( 'locations', [ 'id', 'location_id', 'url' ], [ 'id', '>', 4 ] );// ok
    //$res[] = self::{$type}( 'locations', [ 'url' ], [ [ 'location_id', 'like', 'tes%' ], [ 'logged', '=', true ] ] );// ok
    //$res[] = self::{$type}( 'locations', [ 'id', 'url' ], [ [ 'location_id', 'like', 'tes%' ], [ 'logged', '=', true ] ], null, [ 'id' => 'desc' ] );// ok
    //$res[] = self::{$type}( 'locations', [ 'id', 'url' ], [ [ 'location_id', 'like', 'tes%' ], [ 'logged', '=', true ] ], null, [ 'id' => 'desc' ], 1 );// ok
    //$res[] = self::{$type}( 'locations', [ 'id', 'url' ], [ [ 'location_id', 'like', 'tes%' ], [ 'logged', '=', true ] ], null, null, [ 3, 1 ] );// ok
    $res[] = self::{$type}( 'locations', 'count', [ 'logged', '=', true ] );
    $res[] = self::{$type}( 'locations', [ 'id', 'location_id', 'url' ], [ 'logged', '=', true ], null, [ 'id' => 'desc' ], [ 20 ] );
    self::set_buffer( [ $type => $res, 'errors' => $this->errors ], 'export' );
    break;
case 'get_redirect_url':
    $res[] = self::{$type}();// ok
    $res[] = self::{$type}( 'test' );// ok
    $res[] = self::{$type}( 'test1' );// ok
    self::set_buffer( [ $type => $res, 'errors' => $this->errors ], 'export' );
    break;
case 'get_locations':
    $res[] = self::{$type}();// ok
    $res[] = self::{$type}( [ 'id' => 0 ] );// ok: NULL
    $res[] = self::{$type}( [ 'id' => 1 ] );// ok
    $res[] = self::{$type}( [ 'location_id' => 'google' ] );// ok
    $res[] = self::{$type}( [ 'url' => 'google.com' ], [ 'url' => 'like' ] );// ok
    $res[] = self::{$type}( [ 'modified_at' => '2020-06-10 16:00:00' ], [ 'modified_at' => '<' ] );// ok
    $res[] = self::{$type}( [ 'logged' => false ] );// ok
    self::set_buffer( [ $type => $res, 'errors' => $this->errors ], 'export' );
    break;
case 'upsert_data':
    try {
        //$res[] = self::{$type}();// ok; Too few arguments to function
        //$res[] = self::{$type}( 'location' );// ok; Too few arguments to function
        //$res[] = self::{$type}( 'location', [] );// ok
        //$res[] = self::{$type}( 'locations', [] );// ok
        //$res[] = self::{$type}( 'location_logs', [] );// ok
        //$res[] = self::{$type}( 'locations', [ 'location_id' => 'sample' ] );// ok
        //$res[] = self::{$type}( 'locations', [ 'location_id' => 'sample-1' ] );// ok
        //$res[] = self::{$type}( 'locations', [ 'id' => 6, 'location_id' => 'sample-2' ] );// ok; Duplicate entry '6' for key 'PRIMARY'
        //$res[] = self::{$type}( 'locations', [ 'id' => 6, 'location_id' => 'sample-2' ], true );// ok
        //$res[] = self::{$type}( 'locations', [ 'id' => 6, 'location_id' => 'sample-2' ], true, [ 'logged' => false, 'modified_at' => 'current_timestamp' ] );// ok
        //$res[] = self::{$type}( 'locations', [ 'id' => 6, 'location_id' => 'sample-2' ], true, [ 'id' => 6 ] );// ok
        $res[] = self::{$type}( 'locations', [ 'id' => 1, 'location_id' => 'test' ] );// ok; Duplicate entry '6' for key 'PRIMARY'
    } catch ( \PDOException $e ) {
        if ( self::has_error( 'failure_upserting' ) ) {
            self::add_error( 'failure_upserting', $e->getMessage() );
        } else {
            self::add_error( 'db_error', $e->getMessage() );
        }
    }
    self::set_buffer( [ $type => $res, 'errors' => $this->errors ], 'export' );
    break;
case 'delete_data':
    //$res[] = self::{$type}( 'location', null );// ok
    $res[] = self::{$type}( 'locations', null );// ok
    //$res[] = self::{$type}( 'locations', 'all' );//
    //$res[] = self::{$type}( 'locations', [ 'location_id', '=', 'sample-1' ] );// ok
    //$res[] = self::{$type}( 'locations', [ 'location_id', 'like', 'tes%' ] );// ok
    self::set_buffer( [ $type => $res, 'errors' => $this->errors ], 'export' );
    break;
case 'add_location':
    //$res[] = self::{$type}();// ok
    //$res[] = self::{$type}( 'lid-only' );// ok
    //$res[] = self::{$type}( 'lid-only', 'redirect_to' );// ok
    $res[] = self::{$type}( 'test', 'https://ka2.org/' );// ok
    $res[] = self::{$type}( 'google', 'https://google.com/' );// ok
    self::set_buffer( [ $type => $res, 'errors' => $this->errors ], 'export' );
    break;
case 'remove_location':
    $res[] = self::{$type}( 'lid-only' );// ok
    $res[] = self::{$type}( 'google' );// ok

    self::set_buffer( [ $type => $res, 'errors' => $this->errors ], 'export' );
    break;
case 'find_location':
    $res[] = self::{$type}();//
    self::set_buffer( [ $type => $res, 'errors' => $this->errors ], 'export' );
    break;
case 'add_log':
    $res[] = self::{$type}();//
    self::set_buffer( [ $type => $res, 'errors' => $this->errors ], 'export' );
    break;
case 'remove_log':
    $res[] = self::{$type}();//
    self::set_buffer( [ $type => $res, 'errors' => $this->errors ], 'export' );
    break;
case 'aggregate_logs':
    $res[] = self::{$type}();//
    self::set_buffer( [ $type => $res, 'errors' => $this->errors ], 'export' );
    break;
case 'is_usable_location_id':
    //$res[] = self::{$type}();// ok
    //$res[] = self::{$type}( 'test' );// ok
    $res[] = self::{$type}( 'test', false );// ok
    self::set_buffer( [ $type => $res, 'errors' => $this->errors ], 'export' );
    break;
case 'make_hash':
    for ( $i = 0; $i < 20; $i++ ) {
        $res[] = self::{$type}( 'maenok', $i );
    }
    self::set_buffer( $res );
    break;
case 'sanitize_path':
    $res[] = self::{$type}( null );// ok: NULL
    $res[] = self::{$type}( 123 );// ok: 123
    $res[] = self::{$type}( 3.14 );// ok: 3.14
    $res[] = self::{$type}( self::make_hash( 'test' ) );// ok: as like 'VQy9' etc.
    $res[] = self::{$type}( 'My Name Is Hoge.' );// ok: 'My-Name-Is-Hoge'
    $res[] = self::{$type}( '日本語が含まれているパターン' );// ok: ''
    $res[] = self::{$type}( 'https://example.com/?t=1234&l[]=jQ,Ag2' );// ok: https-example-com-t-1234-l-jQ-Ag2
    $res[] = self::{$type}( rawurlencode( '日本語' ) );// ok: 'E6-97-A5-E6-9C-AC-E8-AA-9E'
    $res[] = self::{$type}( true );// ok: true
    $res[] = self::{$type}( false );// ok: false
    $res[] = self::{$type}( [] );// ok: array()
    self::set_buffer( $res );
    break;
case 'is_datetime':
    $res[] = self::{$type}();// ok: false
    $res[] = self::{$type}( '2017-01-06' );// ok: true
    $res[] = self::{$type}( '2017-13-06' );// ok: false
    $res[] = self::{$type}( '2017-02-06T04:20:33' );// ok: true
    $res[] = self::{$type}( '2020-6-10 0:30:00' );// ok: true
    $res[] = self::{$type}( '2017/02/06' );// ok: true
    $res[] = self::{$type}( '3.6. 2017' );// ok: true
    $res[] = self::{$type}( null );// ok: false
    $res[] = self::{$type}( true );// ok: false
    $res[] = self::{$type}( false );// ok: false
    $res[] = self::{$type}( '' );// ok: false
    $res[] = self::{$type}( 45 );// ok: false
    $res[] = self::{$type}( 'Wed, 25 Sep 2013 15:28:57 -0700' );// ok: true
    $res[] = self::{$type}( '2000-07-01T00:00:00+00:00' );// ok: true
    $res[] = date_format( date_create( 'Wed, 25 Sep 2013 15:28:57 -0700' ), 'Y-m-d H:i:s' );
    self::set_buffer( $res );
    break;
case 'remove_error':
    $res[] = self::{$type}();//
    self::set_buffer( [ $type => $res, 'errors' => $this->errors ], 'export' );
    break;
default:
    $res[] = self::{$type}();
    self::set_buffer( [ $type => $res, 'errors' => $this->errors ] );
    break;
        }
    }

}
