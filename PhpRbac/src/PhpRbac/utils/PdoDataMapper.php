<?php
namespace phprbac\utils;

/**
 * Queries our Postgres instance for data
 * Starting class for future data layer interface.
 **/
class PdoDataMapper
{
    protected $cfg;
    protected $tblName; // sub-classes set this for convenience

    protected $dbh;

    /**
     * Create a PDO connection to the database to handle common DB tasks.
     *
     * Must pass in your desired configuration via the $cfg array.
     *
     * $cfg is a map with the following keys:
     *   - dbType   - DSN prefix for PDO; e.g. 'mysql', 'pgsql', or 'sqlite'
     *
     *   - host     - DB host to connect to
     *   - port     - optional, port to connect to if not the default port
     *   OR
     *   - socket   - unix socket used to connect to database
     *   OR
     *   - filePath - absolute path to sqlite DB file
     *
     *   - dbName   - name of database to connect to, optional for sqlite
     *   - pfx      - prefix for all table names, default is 'rbac_'
     *   - user     - username to connect with, optional for sqlite
     *   - pass     - the password to connect with, optional for sqlite
     *
     *   - appName  - optional, Postgres only
     *   - persist  - whether to use persistent DB connection; default is false
     *
     * @param array   Configuration with all details used to connect.
     **/
    public function __construct($cfg)
    {
        $defaultCfg = array(
            'socket' => false,
            'filePath' => false,
            'port' => false,
            'dbName' => false,
            'persist' => false,
            'pfx' => 'rbac_',
            'user' => null,
            'pass' => null,
            'appName' => null,
        );

        $this->cfg = array_merge($defaultCfg, $cfg);
    }

    /**
     * Connects to the database and the given collection name if not connected.
     *
     * If already connected, does nothing.
     *
     * Caches the last collection name connected to.
     * Side effects: sets $this->conn, ->db, and ->coll.
     **/
    private function _ensureConnected()
    {
        if (is_object($this->dbh))
            return;

        $dsn = $this->_constructDSN($this->cfg);
        $cfg = $this->cfg;

        $opts = array(
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_PERSISTENT => $cfg['persist'],
        );

        try {
            $this->dbh = new \PDO($dsn, $cfg['user'], $cfg['pass'], $opts);
        }
        catch (\Exception $e) {
            error_log($e->getMessage());
            die("Database connection error; see error_log or syslog.\n");
        }
    }

    /**
     * Get the reference to our database handle.
     **/
    public function getDBH()
    {
        $this->_ensureConnected();
        return $this->dbh;
    }

    /**
     * Prepare a SELECT statement and execute it with bound parameters.
     *
     * @param string  SQL select statement to execute, with '?' placeholders
     * @param array   List of parameters to bind to the '?', in order
     * @return array  All rows found, or an empty array if none found
     **/
    protected function _fetchAll($qry, $qryParams = array())
    {
        $this->_ensureConnected();
        $stmt = $this->dbh->prepare($qry);

        if ($stmt === false) {
            error_log($this->dbh->errorInfo()[2]);
            // debug only:
            die($this->dbh->errorInfo()[2]);

            return array();
        }

        if ($stmt->execute($qryParams)) {
            $data = $stmt->fetchAll();
            $stmt->closeCursor();

            return $data;
        }
        else {
            error_log($this->dbh->errorInfo()[2]);
            die('Error executing query');
        }
    }

    /**
     * Prepare and execute a query; return only the first row of the result.
    **/
    protected function _fetchRow($qry, $qryParams = null)
    {
        $res = $this->_fetchAll($qry, $qryParams);

        if (!empty($res))
            return $res[0];
        else
            return null;
    }

    /**
     * Prepare and execute a query; results are returned as an associative array
     * where the first column of query is the key to the second column.
     *
     * By default, the *first* column in the returned result set becomes the
     * array key. If $numCols is set to 1, then the key points only to one other
     * atomic value. For any other value of $numCols, the key points to a map
     * of all remaining values.
     *
     * @param string   The query to execute
     * @param array    List of parameters to bind.
     * @param integer|null If 1, the returned results map only to one value.
     *
     * @return array   A map of name / value pairs. Value may be one column or
     * another map itself.
     */
    protected function _fetchAssoc($qry, $qryParams = null, $numCols = null) {
        $this->_ensureConnected();

        $stmt = $this->dbh->prepare($qry);
        $stmt->execute($qryParams);

        if ($stmt->execute($qryParams)) {
            $data = array();
            while ($row = $stmt->fetch()) {
                $key = array_shift($row);

                if ($numCols === 1)
                    $data[$key] = array_shift($row);
                else
                    $data[$key] = $row;
            }

            $timeEnd = microtime(true);

            $stmt->closeCursor();
            return $data;
        }
        else {
            error_log($this->dbh->errorInfo()[2]);
            die('Error executing query');
        }
    }

    /**
     * Prepare and execute a query; results are returned as an associative array
     * where the first column of query is the key to the second column.
     *
     * Only works on 2-column result sets, I think.
     */
    protected function _fetchCol($qry, $qryParams = null) {
        $this->_ensureConnected();

        $stmt = $this->dbh->prepare($qry);
        $stmt->execute($qryParams);

        $data = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $stmt->closeCursor();

        return $data;
    }

    /**
     * Fetch the first value of the first row of a query.
     **/
    protected function _fetchOne($qry, $qryParams = array())
    {
        $res = $this->_fetchCol($qry, $qryParams);

        if (!empty($res))
             return $res[0];
        else
             return null;
    }

    /**
     * Execute an arbitrary query; returns the ID of the inserted row,
     * if available.
     *
     * Determines if this is an update or an insert based on the presence of
     * the key 'id' in $qryParams.
     *
     * @return array
     *   Array containing the 'success', 'reason' and 'output' of the query.
     *   'output' contains the PK (if atomic) of the data inserted or updated.
     *   If data was deleted it contains the number of rows deleted.
    **/
    protected function _execQuery($qry, $qryParams = null, $pk = null) {
         $res = array('success' => false,
                      'reason'  => 'did not execute the SQL statement',
                      'output'  => null);

        $this->_ensureConnected();

        $stmt = $this->dbh->prepare($qry);

        if (is_object($stmt) AND $stmt->execute($qryParams)) {
                $stmtType = strtoupper(substr($qry, 0, 3));

                if ($stmtType == 'UPD') {
                    // an update
                     $output = $pk;
                }
                else if ($stmtType == 'INS') {
                    $result = $stmt->fetch(\PDO::FETCH_ASSOC);

                    if (array_key_exists('id', $result))
                        $output = $result['id'];
                    else
                        $output = null;
                }
                else {
                     // was probably a DELETE
                     $output = $stmt->rowCount();
                }

                $stmt->closeCursor();

                $res['success'] = true;
                $res['reason']  = 'Query executed okay';
                $res['output']  = $output;
         }
         else {
                $errObj = is_object($stmt) ? $stmt : $this->dbh;
                $errorInfo = $errObj->errorInfo();

                error_log($errorInfo[2] . "\t" . $qry);
                $res['reason']  = $errorInfo[2]; // driver-specific error message
         }

         return $res;
    }

    /**
     * Assemble the DSN to connect with.
     *
     * Unfortunately, DSN strings are database specific, so we have to vary
     * logic based on dbType here.
     *
     **/
    protected function _constructDSN($cfg)
    {
        $dsn = array($cfg['dbType'] . ':');

        if ($cfg['socket']) {
            $dsn[] = 'unix_socket=' . $cfg['socket'];
        }
        elseif ($cfg['filePath']) {
            $dsn[] = $cfg['filePath'];
        }
        else {
            $dsn[] = 'host=' . $cfg['host'];

            if ($cfg['port'])
                $dsn[] = 'port=' . $cfg['port'];
        }

        if ($cfg['dbName'])
            $dsn[] = 'dbname=' . $cfg['dbName'];

        if ($cfg['appName'])
            $dsn[] = 'application_name=' . $cfg['appName'];

        // DB specific ways of composing the DSN
        if ($cfg['dbType'] == 'pgsql') {
            $dsnStr = implode(' ', $dsn);
        }
        elseif ($cfg['dbType'] == 'sqlite') {
            $dsnStr = implode('', $dsn);
        }
        else {
            // use mysql as the general case
            $dsnStr = implode(';', $dsn);
        }

        return $dsnStr;
    }

 }
