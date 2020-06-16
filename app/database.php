<?php
namespace Forkor;

trait database
{

    /*
     * Connect to the database and check the status
     * @access protected
     */
    protected function connect_db() {
        $dsn = sprintf( '%s:dbname=%s;host=%s;charset=%s', DB_DRIVER, DB_NAME, DB_HOST, DB_CHARSET );
        /*
         * Filter handler: "pdo_options"
         * @since v1.0
         */
        $options = self::call_filter( 'pdo_options', [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ], DB_DRIVER );

        try {
            $this->dbh = new \PDO( $dsn, DB_USER, DB_PASS, $options );
        } catch ( \PDOException $e ) {
            self::add_error( 'disconnect_db', 'Failed to connect to DB: ' . $e->getMessage() );
            self::die( 'disconnect_db' );
        }

        // Initial check the database
        if ( $this->is_ready_db !== true ) {
            self::tables_exists();
            $this->is_ready_db = true;
            $this->dbeq = 'mysql' === DB_DRIVER ? '`' : '';
            $this->binary_attr = 'mysql' === DB_DRIVER ? ' BINARY' : '';
        }
    }

    /*
     * Check if the tables for Forkor exists in the database
     * @access protected
     */
    protected function tables_exists() {
        try {
            $stmt = $this->dbh->query( 'SHOW TABLES' );
            $tables = $stmt->fetchAll( \PDO::FETCH_COLUMN );
            if ( ! in_array( strtolower( APP_NAME ) .'_locations', $tables, true ) || ! in_array( strtolower( APP_NAME ) .'_location_logs', $tables, true ) ) {
                self::add_error( 'no_ready_db', 'The required table(s) does not exist in DB.' );
                self::die( 'no_ready_db' );
            }
        } catch ( \PDOException $e ) {
            self::add_error( 'db_error', $e->getMessage() );
            self::die( 'db_error' );
            throw $e;
        }
    }

    /*
     * Retrieve all columns on the specified table
     * @access protected
     * @param string $table_name (required) A table name without prefix
     * @return array $table_columns
     */
    protected function get_table_columns( $table_name ) {
        if ( ! empty( $table_name ) && in_array( $table_name, [ 'locations', 'location_logs' ], true ) ) {
            if ( 'locations' === $table_name ) {
                $table_columns = [ 'id', 'location_id', 'url', 'logged', 'modified_at' ];
            } else {
                $table_columns = [ 'id', 'location_id', 'referrer', 'created_at' ];
            }
        } else {
            $table_columns = [];
        }
        return $table_columns;
    }

    /*
     * Check if record exists match specified conditions
     * @access protected
     * @param string $table_name (required) A table name to fetch; 'locations' or 'location_logs'
     * @param array $conditions (optional) A one-dimensional array when only one condition; If more than one, specify as a two-dimensional array as like [ [ column, operator, value ],... ]
     * @param string $operator (optional) Defaults to 'and', you can also specify which 'or'
     * @return boolean
     */
    protected function data_exists( $table_name, $conditions = [], $operator = 'and' ) {
        $table_columns = self::get_table_columns( $table_name );
        if ( ! empty( $table_columns ) ) {
            $eq_table_name = $this->dbeq. strtolower( APP_NAME ) .'_'. $table_name .$this->dbeq;
            $base_sql = 'SELECT 1 FROM '. $eq_table_name .' WHERE EXISTS (SELECT `id` FROM '. $eq_table_name .' %s)';
        } else {
            return false;
        }
        $where_clauses = [];
        $prepare_vars  = [];
        if ( empty( $conditions ) || ! is_array( reset( $conditions ) ) ) {
            $conditions = [ $conditions ];
        }
        foreach ( $conditions as $_where ) {
            if ( count( $_where ) == 3 && in_array( $_where[0], $table_columns, true ) ) {
                switch ( $_where[0] ) {
                    case 'id':
                        $where_clauses[] = $this->dbeq.$_where[0].$this->dbeq.' '.$_where[1].' :'.$_where[0];
                        $prepare_vars[':'.$_where[0]] = (int) $_where[2];
                        break;
                    case 'logged':
                        $where_clauses[] = $this->dbeq.$_where[0].$this->dbeq.' '.$_where[1].' :'.$_where[0];
                        $prepare_vars[':'.$_where[0]] = (bool) $_where[2];
                        break;
                    case 'modified_at':
                    case 'created_at':
                        $where_clauses[] = $this->dbeq.$_where[0].$this->dbeq.' '.$_where[1].' :'.$_where[0];
                        if ( self::is_datetime( $_where[2] ) ) {
                            $prepare_vars[':'.$_where[0]] = self::datetime_val( $_where[2] );
                        } else {
                            $prepare_vars[':'.$_where[0]] = (string) $_where[2];
                        }
                        break;
                    case 'location_id':
                        $where_clauses[] = $this->dbeq.$_where[0].$this->dbeq.' '.$_where[1].$this->binary_attr.' :'.$_where[0];
                        $prepare_vars[':'.$_where[0]] = (string) $_where[2];
                        break;
                    default:
                        $where_clauses[] = $this->dbeq.$_where[0].$this->dbeq.' '.$_where[1].' :'.$_where[0];
                        $prepare_vars[':'.$_where[0]] = (string) $_where[2];
                        break;
                }
            }
        }
        unset( $_where );
        if ( ! empty( $where_clauses ) ) {
            $where_clause = 'WHERE '. implode( ' '. strtoupper( $operator ) .' ', $where_clauses );
        } else {
            $where_clause = '';
        }
        /*
         * Filter handler: "sql_data_exists"
         * @since v1.0
         */
        $sql = self::call_filter( 'sql_data_exists', sprintf( $base_sql, $where_clause ), $prepare_vars );
        try {
            $stmt = $this->dbh->prepare( $sql );
            $stmt->execute( $prepare_vars );
            $col = $stmt->fetchColumn();
            return ! empty( $col );
        } catch ( \PDOException $e ) {
            self::add_error( 'db_error', $e->getMessage() );
            throw $e;
            return false;
        }
    }

    /*
     * Fetch records from the specific table
     * @access protected
     * @param string $table_name (required) A table name to fetch; 'locations' or 'location_logs'
     * @param mixed $columns (optional) A column name or array of columns to fetch. If not specified, all columns are fetched; Or can count total records too by specifying the "count".
     * @param array $conditions (optional) A one-dimensional array when only one condition; If more than one, specify as a two-dimensional array as like [ [ column, operator, value ],... ]
     * @param string $operator (optional) Defaults to 'and', you can also specify which 'or'
     * @param array<assoc> $order_by (optional) This make query for clause of order by; c.f. [ 'id' => 'asc' ]
     * @param mixed $limit (optional) This make query for clause of limit and offset, also set only the limit if an integer; c.f. [ limit, offset ] as like [ 20, 10 ]
     * @return mixed Basically return an array of all records fetched. however, return the assoc array if one record only fetched; return an integer as number of total records if specified "count" with second argument
     */
    protected function fetch_data( $table_name, $columns = [], $conditions = [], $operator = 'and', $order_by = [], $limit = null ) {
        $table_columns = self::get_table_columns( $table_name );
        if ( ! empty( $table_columns ) ) {
            $base_sql = 'SELECT %s FROM '.$this->dbeq. strtolower( APP_NAME ) .'_'. $table_name .$this->dbeq.' %s';
        } else {
            return [];
        }
        if ( ! empty( $columns ) ) {
            if ( is_array( $columns ) ) {
                $select_columns = [];
                foreach ( $columns as $_col ) {
                    if ( in_array( $_col, $table_columns, true ) ) {
                        $select_columns[] = $this->dbeq . $_col . $this->dbeq;
                    }
                }
                unset( $_col );
                $select_clause = implode( ',', $select_columns );
            } else
            if ( is_string( $columns ) && in_array( $columns, $table_columns, true ) ) {
                $select_clause = $this->dbeq . $columns . $this->dbeq;
            } else
            if ( is_string( $columns ) && 'count' === strtolower( $columns ) ) {
                $select_clause = 'COUNT('.$this->dbeq.'id'.$this->dbeq.')';
            } else {
                return [];
            }
        } else {
            $select_clause = '*';
        }
        if ( empty( $operator ) ) {
            $operator = 'and';
        }
        $narrow_downs = [];
        $prepare_vars = [];
        if ( empty( $conditions ) || ! is_array( reset( $conditions ) ) ) {
            $conditions = [ $conditions ];
        }
        foreach ( $conditions as $_where ) {
            if ( count( $_where ) == 3 && in_array( $_where[0], $table_columns, true ) ) {
                switch ( $_where[0] ) {
                    case 'id':
                        $narrow_downs['where'][] = $this->dbeq.$_where[0].$this->dbeq.' '.$_where[1].' :'.$_where[0];
                        $prepare_vars[':'.$_where[0]] = (int) $_where[2];
                        break;
                    case 'logged':
                        $narrow_downs['where'][] = $this->dbeq.$_where[0].$this->dbeq.' '.$_where[1].' :'.$_where[0];
                        $prepare_vars[':'.$_where[0]] = (bool) $_where[2];
                        break;
                    case 'modified_at':
                    case 'created_at':
                        $narrow_downs['where'][] = $this->dbeq.$_where[0].$this->dbeq.' '.$_where[1].' :'.$_where[0];
                        if ( self::is_datetime( $_where[2] ) ) {
                            $prepare_vars[':'.$_where[0]] = self::datetime_val( $_where[2] );
                        } else {
                            $prepare_vars[':'.$_where[0]] = (string) $_where[2];
                        }
                        break;
                    case 'location_id':
                        $narrow_downs['where'][] = $this->dbeq.$_where[0].$this->dbeq.' '.$_where[1].$this->binary_attr.' :'.$_where[0];
                        $prepare_vars[':'.$_where[0]] = (string) $_where[2];
                        break;
                    default:
                        $narrow_downs['where'][] = $this->dbeq.$_where[0].$this->dbeq.' '.$_where[1].' :'.$_where[0];
                        $prepare_vars[':'.$_where[0]] = (string) $_where[2];
                        break;
                }
            }
        }
        unset( $_where );
        if ( ! empty( $order_by ) ) {
            foreach ( $order_by as $_col => $_val ) {
                if ( in_array( $_col, $table_columns, true ) ) {
                    $narrow_downs['order_by'][$_col] = 'desc' === strtolower( $_val ) ? 'desc' : 'asc';
                }
            }
            unset( $_col, $_val );
        }
        if ( ! empty( $limit ) ) {
            if ( is_array( $limit ) ) {
                if ( isset( $limit[0] ) && is_int( $limit[0] ) ) {
                    $narrow_downs['limit'] = $limit[0];
                }
                if ( isset( $limit[1] ) && is_int( $limit[1] ) ) {
                    $narrow_downs['offset'] = $limit[1];
                }
            } else
            if ( is_int( $limit ) || (int) $limit > 0 ) {
                $narrow_downs['limit'] = (int) $limit;
            }
        }
        $narrow_down_clauses = [];
        if ( ! empty( $narrow_downs ) ) {
            if ( array_key_exists( 'where', $narrow_downs ) ) {
                $narrow_down_clauses[] = 'WHERE '. implode( ' '. strtoupper( $operator ) .' ', $narrow_downs['where'] );
            }
            if ( array_key_exists( 'order_by', $narrow_downs ) ) {
                array_walk( $narrow_downs['order_by'], function( $_val, $_col ) use ( &$order_by_clauses ) {
                    $order_by_clauses[] = sprintf( $this->dbeq. $_col .$this->dbeq.' '. strtoupper( $_val ) );
                } );
                $narrow_down_clauses[] = 'ORDER BY '. implode( ', ', $order_by_clauses );
                unset( $_val, $_col );
            }
            if ( array_key_exists( 'limit', $narrow_downs ) ) {
                $narrow_down_clauses[] = 'LIMIT '. (int) $narrow_downs['limit'];
                if ( array_key_exists( 'offset', $narrow_downs ) ) {
                    $narrow_down_clauses[] = 'OFFSET '. (int) $narrow_downs['offset'];
                }
            }
        }
        /*
         * Filter handler: "sql_fetch_locations"
         * @since v1.0
         */
        $sql = self::call_filter( 'sql_fetch_locations', sprintf( $base_sql, $select_clause, implode( ' ', $narrow_down_clauses ) ), $prepare_vars );
        try {
            $stmt = $this->dbh->prepare( $sql );
            $stmt->execute( $prepare_vars );
            if ( is_string( $columns ) && 'count' === strtolower( $columns ) ) {
                return (int) $stmt->fetchColumn();
            } else {
                $rows = $stmt->fetchAll( \PDO::FETCH_ASSOC );
                return count( $rows ) == 1 ? $rows[0] : $rows;
            }
        } catch ( \PDOException $e ) {
            self::add_error( 'db_error', $e->getMessage() );
            throw $e;
            return 0;
        }
    }

    /*
     * Upsert records into the specific table
     * @access protected
     * @param string $table_name (required) A table name to fetch; 'locations' or 'location_logs'
     * @param array<assoc> $data (required) An array of column name/value pairs
     * @param boolean $is_upsert (optional) Defaults to false and only new insertion
     * @param array<assoc> $update_data (optional) An array of column name/value pairs to update when upsert is enabled
     * @return boolean
     */
    protected function upsert_data( $table_name, $data, $is_upsert = false, $update_data = [] ) {
        $table_columns = self::get_table_columns( $table_name );
        if ( ! empty( $table_columns ) ) {
            $base_sql = 'INSERT INTO '.$this->dbeq. strtolower( APP_NAME ) .'_'. $table_name .$this->dbeq.' (%s) VALUES (%s)';
            if ( $is_upsert ) {
                // c.f. `UPDATE table_name SET col=val,... WHERE ...`
                $base_sql .= ' ON DUPLICATE KEY UPDATE %s';
            }
        } else {
            self::add_error( 'invalid_table_upserting', 'The table corresponding to upsert is not specified.' );
            return false;
        }
        if ( ! empty( $data ) && is_array( $data ) ) {
            $insert_columns = [];
            $insert_values  = [];
            $prepare_vars   = [];
            foreach ( $data as $_col => $_val ) {
                if ( in_array( $_col, $table_columns, true ) ) {
                    $insert_columns[] = $this->dbeq. $_col .$this->dbeq;
                    $insert_values[]  = ':' . $_col;
                    switch ( $_col ) {
                        case 'id':
                            // In case of upsert, it is necessary to specify this column which is the primary key
                            $prepare_vars[':'.$_col] = (int) $_val;
                            break;
                        case 'logged':
                            $prepare_vars[':'.$_col] = (bool) $_val;
                            break;
                        case 'created_at':
                        case 'modified_at':
                            if ( self::is_datetime( $_val ) ) {
                                $prepare_vars[':'.$_col] = self::datetime_val( $_val );
                            } else
                            if ( 'CURRENT_TIMESTAMP' === strtoupper( $_val ) ) {
                                $prepare_vars[':'.$_col] = self::datetime_val();
                            } else {
                                $prepare_vars[':'.$_col] = (string) $_val;
                            }
                            break;
                        default:
                            $prepare_vars[':'.$_col] = (string) $_val;
                            break;
                    }
                }
            }
            unset( $_col, $_val );
        } else {
            self::add_error( 'empty_insert_data', 'The data required for insertion is lacking or invalid.' );
            return false;
        }
        if ( $is_upsert ) {
            if ( array_key_exists( 'id', $data ) && ! empty( $update_data ) ) {
                $update_clauses = [];
                foreach ( $update_data as $_col => $_val ) {
                    if ( in_array( $_col, $table_columns, true ) ) {
                        $update_clauses[] = $this->dbeq. $_col .$this->dbeq.' = :ups_'. $_col;
                        switch ( $_col ) {
                            case 'id':
                                $prepare_vars[':ups_'.$_col] = (int) $_val;
                                break;
                            case 'logged':
                                $prepare_vars[':ups_'.$_col] = (bool) $_val;
                                break;
                            case 'created_at':
                            case 'modified_at':
                                if ( self::is_datetime( $_val ) ) {
                                    $prepare_vars[':ups_'.$_col] = self::datetime_val( $_val );
                                } else
                                if ( 'CURRENT_TIMESTAMP' === strtoupper( $_val ) ) {
                                    $prepare_vars[':ups_'.$_col] = self::datetime_val();
                                } else {
                                    $prepare_vars[':ups_'.$_col] = (string) $_val;
                                }
                                break;
                            default:
                                $prepare_vars[':ups_'.$_col] = (string) $_val;
                                break;
                        }
                    }
                }
                unset( $_col, $_val );
            } else {
                self::add_error( 'lack_upsert_data', 'The data required for upserting is lacking or invalid.' );
                return false;
            }
        }
        // Create sql statement
        if ( ! $is_upsert ) {
            $sql = sprintf( $base_sql, implode( ', ', $insert_columns ), implode( ', ', $insert_values ) );
        } else {
            $sql = sprintf( $base_sql, implode( ', ', $insert_columns ), implode( ', ', $insert_values ), implode( ', ', $update_clauses ) );
        }
        /*
         * Filter handler: "sql_upsert_data"
         * @since v1.0
         */
        $sql = self::call_filter( 'sql_upsert_data', $sql, $prepare_vars, $is_upsert );
        $this->dbh->beginTransaction();
        try {
            $stmt = $this->dbh->prepare( $sql );
            $stmt->execute( $prepare_vars );
            if ( 'locations' === $table_name ) {
                $this->last_location_id = $this->dbh->lastInsertId( 'id' );
            }
            $result = true;
            $this->dbh->commit();
        } catch ( \PDOException $e ) {
            self::add_error( 'failure_upserting' );
            $this->dbh->rollBack();
            $result = false;
            throw $e;
        }
        return $result;
    }

    /*
     * Delete records on the specific table
     * @access protected
     * @param string $table_name (required) A table name to fetch; "locations" or "location_logs"
     * @param mixed $conditions (required) By the array for narrowing down conditions as like "[ [ column, operator, value ],... ]" or the "all" of string
     * @param string $operator (optional) Defaults to "and", you can also specify which "or"
     * @return boolean
     */
    protected function delete_data( $table_name, $conditions, $operator = 'and' ) {
        $table_columns = self::get_table_columns( $table_name );
        if ( ! empty( $table_columns ) ) {
            $base_sql = 'DELETE FROM '.$this->dbeq. strtolower( APP_NAME ) .'_'. $table_name .$this->dbeq.' %s';
        } else {
            self::add_error( 'invalid_table_deleting', 'The table corresponding to delete is not specified.' );
            return false;
        }
        if ( empty( $conditions ) ) {
            self::add_error( 'no_deleting_condition', 'There is no condition for deleting data' );
            return false;
        } else
        if ( is_array( $conditions ) ) {
            if ( ! is_array( reset( $conditions ) ) ) {
                $conditions = [ $conditions ];
            }
            $where_clauses = [];
            $prepare_vars  = [];
            foreach ( $conditions as $_where ) {
                if ( count( $_where ) == 3 && in_array( $_where[0], $table_columns, true ) ) {
                    switch ( $_where[0] ) {
                        case 'id':
                            $where_clauses[] = $this->dbeq.$_where[0].$this->dbeq.' '.$_where[1].' :'.$_where[0];
                            $prepare_vars[':'.$_where[0]] = (int) $_where[2];
                            break;
                        case 'logged':
                            $where_clauses[] = $this->dbeq.$_where[0].$this->dbeq.' '.$_where[1].' :'.$_where[0];
                            $prepare_vars[':'.$_where[0]] = (bool) $_where[2];
                            break;
                        case 'modified_at':
                        case 'created_at':
                            $where_clauses[] = $this->dbeq.$_where[0].$this->dbeq.' '.$_where[1].' :'.$_where[0];
                            if ( self::is_datetime( $_where[2] ) ) {
                                $prepare_vars[':'.$_where[0]] = self::datetime_val( $_where[2] );
                            } else {
                                $prepare_vars[':'.$_where[0]] = (string) $_where[2];
                            }
                            break;
                        case 'location_id':
                            $where_clauses[] = $this->dbeq.$_where[0].$this->dbeq.' '.$_where[1].$this->binary_attr.' :'.$_where[0];
                            $prepare_vars[':'.$_where[0]] = (string) $_where[2];
                            break;
                        default:
                            $where_clauses[] = $this->dbeq.$_where[0].$this->dbeq.' '.$_where[1].' :'.$_where[0];
                            $prepare_vars[':'.$_where[0]] = (string) $_where[2];
                            break;
                    }
                }
            }
            unset( $_where );
            if ( ! empty( $where_clauses ) ) {
                $where_clause = 'WHERE '. implode( ' '. strtoupper( $operator ) .' ', $where_clauses );
            } else {
                self::add_error( 'empty_valid_condition', 'Cannot be deleted because there are no valid conditions.' );
                return false;
            }
            $sql = sprintf( $base_sql, $where_clause );
            $use_transaction = true;
        } else
        if ( is_string( $conditions ) && 'all' === strtolower( $conditions ) ) {
            $sql = 'TRUNCATE TABLE '.$this->dbeq. strtolower( APP_NAME ) .'_'. $table_name .$this->dbeq;
            $prepare_vars = [];
            /*
             * Filter handler: "ddl_rollbackable_dsn_prefix"
             * @since v1.0
             */
            $ddl_rollbackable_dbms = self::call_filter( 'ddl_rollbackable_dsn_prefix', [ 'psql', 'sqlsrv' ], DB_DRIVER );
            $use_transaction = in_array( strtolower( DB_DRIVER ), $ddl_rollbackable_dbms, true );
        } else {
            self::add_error( 'invalid_deleting_condition', 'There is the invalid deleting condition.' );
            return false;
        }
        /*
         * Filter handler: "sql_delete_data"
         * @since v1.0
         */
        $sql = self::call_filter( 'sql_delete_data', $sql, $prepare_vars );
        if ( $use_transaction ) {
            $this->dbh->beginTransaction();
        }
        try {
            $stmt = $this->dbh->prepare( $sql );
            $stmt->execute( $prepare_vars );
            if ( ! is_string( $conditions ) || 'all' !== strtolower( $conditions ) ) {
                if ( $stmt->rowCount() == 0 ) {
                    self::add_error( 'no_deleted_rows', 'No rows have been deleted.' );
                    $result = false;
                } else {
                    $result = true;
                }
            } else {
                $result = true;
            }
            if ( $use_transaction ) {
                $this->dbh->commit();
            }
        } catch ( \PDOException $e ) {
            self::add_error( 'failure_deleting' );
            if ( $use_transaction ) {
                $this->dbh->rollBack();
            }
            $result = false;
            throw $e;
        }
        return $result;
    }


    // Public Methods

    /*
     * Retrieve redirect URL matching location ID
     * @access public
     * @param string $location_id (optional) If omitted, find by location_id property of class
     * @return mixed string of URL if it can fetch redirect destination, false if cannot
     */
    public function get_redirect_url( $location_id = null ) {
        if ( empty( $this->dbh ) ) {
            self::connect_db();
        }
        if ( empty( $location_id ) ) {
            $location_id = $this->location_id;
        }

        $location_data = self::fetch_data( 'locations', [ 'url', 'logged' ], [ 'location_id', '=', $location_id ], null, null, 1 );

        if ( ! empty( $location_data ) ) {
            /*
             * Filter handler: "logging_before_redirect"
             * - Modify whether do logging the current location before redirection
             * @since v1.0
             */
            $this->current_logging = self::call_filter( 'logging_before_redirect', (bool) $location_data['logged'], $location_id, $location_data );

            return $location_data['url'];
        } else {
            $this->current_logging = false;
            self::add_error( 'no_location_path', 'The specified location path does not exists.' );
            return false;
        }
    }

    /*
     * Retrieve the location data with matching conditions
     * @access public
     * @param array<assoc> $conditions (required) Defaults to empty, then it's applied properties below as the default conditions
     * - @property int     id:          null
     * - @property string  location_id: null
     * - @property string  url:         null
     * - @property boolean logged:      null
     * - @property string  modified_at: null
     * - @property string  order_by:    "id"
     * - @property string  order:       "asc"
     * - @property int     limit:       null
     * - @property int     offset:      null
     * @param array<assoc> $match_options (optional) Defines operators to be matched for some condition properties (id, location_id, url, logged, modified_at).
     * @return mixed Returns an array of data lists if matched, or null if no data to match.
     */
    public function get_locations( $conditions = [], $match_options = [] ) {
        // Set default conditions
        $default_conds = [
            'id'          => null,
            'location_id' => null,
            'url'         => null,
            'logged'      => null,
            'modified_at' => null,
            'order_by'    => 'id',
            'order'       => 'asc',
            'limit'       => null,
            'offset'      => null,
        ];
        $conditions = array_merge( $default_conds, $conditions );
        // Set default matching options
        $default_opts = [
            'id'          => '=',
            'location_id' => '=',
            'url'         => '=',
            'logged'      => '=',
            'modified_at' => '=',
        ];
        $operators = array_merge( $default_opts, $match_options );
        // Make the finding args
        $fetch_conds = [];
        $fetch_order_by = [];
        $fetch_limit = [];
        foreach ( $conditions as $_key => $_val ) {
            switch ( $_key ) {
                case 'id':
                    if ( ! is_null( $_val ) && is_int( $_val ) ) {
                        $_ope = 'like' === strtolower( $operators[$_key] ) ? '=' : $operators[$_key];
                        $fetch_conds[] = [ $_key, $_ope, (int) $_val ];
                    }
                    break;
                case 'location_id':
                case 'url':
                    if ( ! empty( $_val ) && is_string( $_val ) ) {
                        $_val = 'like' === strtolower( $operators[$_key] ) ? '%'. $_val .'%' : $_val;
                        $fetch_conds[] = [ $_key, $operators[$_key], $_val ];
                    }
                    break;
                case 'logged':
                    if ( ! is_null( $_val ) && is_bool( $_val ) ) {
                        $_ope = 'like' === strtolower( $operators[$_key] ) ? '=' : $operators[$_key];
                        $fetch_conds[] = [ $_key, $_ope, (bool) $_val ];
                    }
                    break;
                case 'modified_at':
                    if ( ! empty( $_val ) && self::is_datetime( $_val ) ) {
                        $_ope = 'like' === strtolower( $operators[$_key] ) ? '=' : $operators[$_key];
                        $fetch_conds[] = [ $_key, $_ope, self::datetime_val( $_val ) ];
                    }
                    break;
                case 'order_by':
                    if ( ! empty( $_val ) && in_array( $_val, self::get_table_columns( 'locations' ) ) ) {
                        $fetch_order_by[$_val] = 'desc' === strtolower( $conditions['order'] ) ? 'desc' : 'asc';
                    }
                    break;
                case 'limit':
                    if ( ! empty( $_val ) && is_int( $_val ) && $_val > 0 ) {
                        $fetch_limit[] = $_val;
                        if ( ! empty( $conditions['offset'] ) && is_int( $conditions['offset'] ) && $conditions['offset'] >= 0 ) {
                            $fetch_limit[] = $conditions['offset'];
                        }
                    }
                    break;
            }
        }
        // Execute fetching
        $results = self::fetch_data( 'locations', [], $fetch_conds, 'and', $fetch_order_by, ( empty( $fetch_limit ) ? null : $fetch_limit ) );
        return empty( $results ) ? null : $results;
    }

    /*
     * Insert new location to locations table
     * @access public
     * @param string $location_id (required)
     * @param string $url (required)
     * @param boolean $logged (optional) Defaults to true
     * @return boolean
     */
    public function add_location( $location_id = null, $url = null, $logged = true ) {
        if ( ! isset( $location_id ) || empty( $location_id ) || ! isset( $url ) || empty( $url ) ) {
            self::add_error( 'empty_location_data', 'There is not enough location data to add.' );
            return false;
        }
        if ( ! self::is_usable_location_id( $location_id ) ) {
            return false;
        }
        $location_data = [
            'location_id' => $location_id,
            'url'         => $url,
            'logged'      => (bool) $logged,
        ];
        /*
         * Filter handler: "add_location_data"
         * @since v1.0
         */
        $location_data = self::call_filter( 'add_location_data', $location_data );
        if ( ! empty( $location_data ) ) {
            if ( empty( $this->dbh ) ) {
                self::connect_db();
            }
            try {
                return self::upsert_data( 'locations', $location_data, false );
            } catch ( \PDOException $e ) {
                if ( self::has_error( 'failure_upserting' ) ) {
                    self::add_error( 'failure_upserting', $e->getMessage() );
                } else {
                    self::add_error( 'db_error', $e->getMessage() );
                }
            }
        } else {
            self::add_error( 'empty_location_data', 'There is not enough location data to add.' );
            return false;
        }
    }

    /*
     * Remove the data from locations table
     * @access public
     * @param string $location_id (required)
     * @return boolean
     */
    public function remove_location( $location_id = null ) {
        if ( ! isset( $location_id ) || empty( $location_id ) ) {
            self::add_error( 'empty_location_path', 'There is no location path to remove.' );
            return false;
        }
        if ( empty( $this->dbh ) ) {
            self::connect_db();
        }
        try {
            return self::delete_data( 'locations', [ 'location_id', '=', $location_id ] );
        } catch ( \PDOException $e ) {
            if ( self::has_error( 'failure_deleting' ) ) {
                self::add_error( 'failure_deleting', $e->getMessage() );
            } else {
                self::add_error( 'db_error', $e->getMessage() );
            }
            return false;
        }
    }

    /*
     * Check whether location id is usable (registrable)
     * @access public
     * @param string $location_id (required)
     * @param boolean $prevent_error (optional) Defaults to true, then check result is not added as an error.
     * @return boolean
     */
    public function is_usable_location_id( $location_id = null, $prevent_error = true ) {
        if ( ! isset( $location_id ) || empty( $location_id ) ) {
            if ( ! $prevent_error ) {
                self::add_error( 'empty_location_id', 'There is no location path to check.' );
            }
            return false;
        }
        $unusable_ids = array_merge( [], $this->reserved_words, [ REGISTER_PATH, ANALYZE_PATH ] );
        /*
         * Filter handler: "unusable_location_ids"
         * @since v1.0
         */
        $unusable_ids = self::call_filter( 'unusable_location_ids', $unusable_ids );
        if ( in_array( $location_id, $unusable_ids, true ) ) {
            if ( ! $prevent_error ) {
                self::add_error( 'unusable_words', 'That word as the location path is prevented to use.' );
            }
            return false;
        }
        // Whether the location id is already registered
        if ( self::data_exists( 'locations', [ [ 'location_id', '=', $location_id ] ] ) ) {
            if ( ! $prevent_error ) {
                self::add_error( 'already_same_id', 'Same location path already registered.' );
            }
            return false;
        }
        return true;
    }

    /*
     * Insert new log to location_logs table
     * @access public
     * @param string $location_id (required) 
     * @param string $referrer (optional) Defaults to null
     * @return boolean
     */
    public function add_log( $location_id = '', $referrer = null ) {
        if ( empty( $location_id ) ) {
            return false;
        }
        if ( ! self::data_exists( 'locations', [ [ 'location_id', '=', $location_id ], [ 'logged', '=', true ] ] ) ) {
            self::add_error( 'invalid_logging', 'There is invalid logging to locations that is not allowed.' );
            return false;
        }
        $location_log_data = [
            'location_id' => $location_id,
            'referrer'    => empty( $referrer ) ? '' : $referrer,
        ];
        /*
         * Filter handler: "add_location_log"
         * @since v1.0
         */
        $location_log_data = self::call_filter( 'add_location_log', $location_log_data );
        if ( ! empty( $location_log_data ) ) {
            if ( empty( $this->dbh ) ) {
                self::connect_db();
            }
            try {
                return self::upsert_data( 'location_logs', $location_log_data, false );
            } catch ( \PDOException $e ) {
                if ( self::has_error( 'failure_upserting' ) ) {
                    self::add_error( 'failure_upserting', $e->getMessage() );
                } else {
                    self::add_error( 'db_error', $e->getMessage() );
                }
            }
        } else {
            self::add_error( 'empty_location_log', 'There is not enough location log to add.' );
            return false;
        }
    }

    /*
     * Retrieve the list of location IDs that have been logged
     * @access public
     * @param array<assoc> $sort (optional) Defaults to null therefore order by latest logging
     * @param boolean $valid_ids_only (optional) Defaults to true; targeted only for currently valid location IDs if true, retrieve all location IDs logged if false.
     * @param boolean $with_count_number (optional) Defaults to false
     * @return array Returns array listed only location ids if $with_count_number is false (at default); returns assoc array that has with pair of location id and count logged if true.
     */
    public function get_logged_ids( $sort = null, $valid_ids_only = true, $with_count_number = false ) {
        $logged_ids = [];
        if ( empty( $sort ) ) {
            $sort = [ 'id' => 'desc' ];
        }
        $_res = self::fetch_data( 'location_logs', 'location_id', [], null, $sort );
        foreach ( $_res as $_val ) {
            $_lid = $_val['location_id'];
            if ( array_key_exists( $_lid, $logged_ids ) ) {
                $logged_ids[$_lid]++;
            } else {
                $logged_ids[$_lid] = 1;
            }
        }
        unset( $_lid, $_val, $_res );
        if ( $valid_ids_only ) {
            $_res = self::fetch_data( 'locations', 'location_id', [ 'logged', '=', true ] );
            $current_valid_ids = array_map( function( $_val ) { return $_val['location_id']; }, $_res );
            foreach ( $logged_ids as $_lid => $_cnt ) {
                if ( ! in_array( $_lid, $current_valid_ids, true ) ) {
                    unset( $logged_ids[$_lid] );
                }
            }
            unset( $_cnt, $_lid, $_res );
        }
        if ( $with_count_number ) {
            return $logged_ids;
        } else {
            return array_keys( $logged_ids );
        }
    }

    /*
     * Retrieve the specified location logs
     * @access public
     * @param string $location_id (optional) Defaults to null, in which case it will get all the logs. However, that is a performance deprecation.
     * @return mixed If location id matches, return that list of logs. If not, it returns null.
     */
    public function get_logs( $location_id = null ) {
        $condition = [];
        if ( ! empty( $location_id ) ) {
            $condition = [ 'location_id', '=', $location_id ];
        }
        $logs = self::fetch_data( 'location_logs', null, $condition );
        if ( empty( $logs ) ) {
            return null;
        } else {
            return $logs;
        }
    }

    /*
     * Aggregate the logs of a specific location ID for easy analysis
     * @access public
     * @param string $location_id (required)
     * @return mixed Returns assoc array if location id matches, if not, it returns false.
     */
    public function aggregate_logs( $location_id = null ) {
        if ( empty( $location_id ) ) {
            return false;
        }
        $raw_logs = self::fetch_data( 'location_logs', [ 'referrer', 'created_at' ], [ 'location_id', '=', $location_id ] );
        if ( ! empty( $raw_logs ) ) {
            $log_details = [
                'location_id' => $location_id,
                'url'         => self::get_redirect_url( $location_id ),
                'total'       => count( $raw_logs ),
                'referrers'   => [],
                'timestamps'  => [],
                'accesses'    => [],
            ];
            foreach ( $raw_logs as $_onelog ) {
                $_referrer = empty( $_onelog['referrer'] ) ? 'none' : trim( $_onelog['referrer'] );
                if ( array_key_exists( $_referrer, $log_details['referrers'] ) ) {
                    $log_details['referrers'][$_referrer]++;
                } else {
                    $log_details['referrers'][$_referrer] = 1;
                }
                if ( isset( $_onelog['created_at'] ) && self::is_datetime( $_onelog['created_at'] ) ) {
                    $_epoctime = strtotime( $_onelog['created_at'] );
                    $log_details['timestamps'][] = $_epoctime;
                    $log_details['accesses'][$_referrer][] = $_epoctime;
                }
            }
            return $log_details;
        } else {
            return false;
        }
    }


}
