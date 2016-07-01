<?php # db.php

require_once "uuid.php";
require_once "db-types.php";
require_once "diagnostic.php";

class DBException extends Exception
{}

/* 

Given a database as follows, filled with suitably interesting test data:

    CREATE TABLE t1
    (
        num    INTEGER,
        name   VARCHAR(255),
        notes  TEXT,
        PRIMARY KEY (a)
    )
    
    ;;
    
    CREATE PROCEDURE findsum(IN low INTEGER, IN hi INTEGER, OUT res INTEGER)
    BEGIN
        SELECT SUM(num) INTO res FROM t1 WHERE num >= low AND num <= hi;
    END
    
    ;;
    
    CREATE PROCEDURE list(IN low INTEGER, IN hi INTEGER)
    BEGIN
        SELECT num, name FROM t1 WHERE num >= low AND num <= hi ORDER BY name ASC;
        SELECT name, notes FROM t1 WHERE num >= low AND num <= hi ORDER BY name DESC;
    END
    
    
Assuming settings.ini:

    [mydatabase]
    server=localhost
    database=mydatabase
    user=root
    password=MrFluffy123
    
Usage:

    // inputs: $lo and $hi
    
    $d = new DB('settings.ini', 'mydatabase');

    list($sum) = $d->Call('findsum',
                          ['low'  => _INTEGER($lo),
                           'high' => _INTEGER($hi),
                           'sum'  => _INTEGER(NULL, _OUT)])
    print "The sum of rows $lo to $hi is $sum.<br/>\n";
    
    $d->Call('list',
             ['low'  => _INTEGER($lo),
              'high' => _INTEGER($hi)]);
    $a = $d->ReadSet(); // [[num => 11, name => 'Bart'], [num => 7, name => 'Lisa'], [num => 9, name => 'Marge']]
    $b = $d->ReadSet(); // [[name => 'Marge', notes => 'Hmmmm!'], [name => 'Lisa', 'notes' => NULL], [name => 'Bart', notes => 'Eat my shorts!']]
    
    $d->Query('SELECT MAX(num)+:increment FROM t1',
              ['increment' => _INTEGER(1)]);
    $new = $d->ReadValue(); // when there's one result set containing one row containing one value, this is a useful shortcut
    
    $d->Query('INSERT INTO t1 (num,name,notes) VALUES(:num,:name,:notes)',
              ['notes' => _STRING("D'oh!"),
               'name'  => _STRING('Homer'),
               'num'   => _INTEGER($new)]);
    if ($d->Succeeded()) echo "Database updated.<br/>\n";
    
    $d->Query('SELECT * FROM t1');
    $d->DumpHTMLTable();
*/
    
class DB
{                               
    private $mysqli;            // MySQLi object, or NULL if it hasn't been opened yet by a call to Query or Call.
    private $result;            // the token returned by mysqli->use_result().
    private $cached_all;        // true if $sets contains a complete cache dump of all (remaining) result sets
    private $cached_one;        // true if $sets[$setindex] contains a cache dump the current result set
    private $sets;              // array(array(row,...), ...)
    private $setindex;          // index into $sets of the current result set
    private $rowindex;          // index into $sets[$setindex] of the current row
    private $server;            // the base url of the server we're running on
    private $dbsettings;        // the database settings gleaned from the database .ini file
    private $inquery;           // query() was called, haven't got all results yet; need $result->close() before the next query
    private $tracing;           // true if we're tracing calls, triggered by Trace(bool)
    
    // Class constructor
    
    // Given the name of a database configuration inifile and a section name (see read_ini_file()), prepares the object for use

    public function __construct($inifile, $section)
    { 
        $this->dbsettings = $this->read_ini_file($inifile, $section);

        $this->mysqli = NULL;
        $this->result = NULL;
        $this->cached_all = FALSE;
        $this->cached_one = FALSE;
        $this->sets = NULL;
        $this->setindex = -1;
        $this->rowindex = -1;
        $this->inquery = FALSE;
        $this->tracing = FALSE;
    }

    // Trace(bool)
    
    // Set tracing mode on for debugging purposes.  Prints every query just before execution.
    
    public function Trace($bool)
    {
        $this->tracing = !!$bool;
    }
    
    // Query(sql, [name => _type(value), ...], bool)
    
    // Calls one or more queries.  Results are put into internal variables in order to allow for a non-cached row-by-row
    // read with the NextRow and MoreSets functions.

    // $sql               a single SQL query or several separated by semi-colons, or an array of them without semi-colons
    // $args              an array of arguments and values, to be substituted into the queries (args in the form ":name")
    // $force_cache       can be passed as TRUE to allow the use of SeekSet and RowAt, at some cost in memory for large results

    public function Query($sql, $args = array(), $force_cache = FALSE)
    {
        $this->check_open();
        $this->discard_old_results();

        // convert SQL to standard form and interpolate the arguments, since mysqli lacks support for parameters in this case
        if (is_array($sql)) $sql = join(';',$sql);
        $this->check_arguments($sql,$args);
        $sql = $this->interpolate_arguments($sql,$args);
        // DEBUG_LOG('Query (interpolated)',$sql);
        
        if ($this->tracing) DEBUG_LOG('Query',$sql);
        
        // options: $force_cache is false: prepare the first set and exit
        //          $force_cache is true: read all the sets and all the rows in each set

        $first = $this->mysqli->multi_query($sql);
        $this->check_error();

        $this->sets = array();
        if ($force_cache) {
            do { // each result set...
                $result = $this->mysqli->use_result();
                $this->check_error();
                $one = array();
                if ($result) { // each row
                    while (($row = $result->fetch_assoc())) $one[] = $row;
                    $result->close();
                }
                if ($one) $this->sets[] = $one;
            } while($this->mysqli->more_results() && $this->mysqli->next_result());

            $this->cached_all = TRUE;
            $this->cached_one = TRUE;
            $this->inquery = FALSE;
            // result: $sets[] is an array of sets, each set being an array of rows
            // You can use MoreSets and NextRow to get your data, or ask for each set using ReadSet()
        } else {
            $this->cached_all = FALSE;
            $this->cached_one = FALSE;
            $this->inquery = TRUE;
            // result: $sets[] is empty, so you use the MoreSets and NextRow functions to get your data
        }
        $this->result = NULL;
        $this->setindex = -1;
        $this->rowindex = -1;
    }

    // Calls a stored procedure.  If there are any _INOUT or _OUT args, this returns their final values in an array
    // and for technical reasons relating to the criminal incompetence of the PHP5 maintainers, pre-reads all the procedure's
    // result sets for access via MoreSets using $this->sets; otherwise, returns NULL and sets up MoreSets for use with the
    // row-by-row non-cached read, which is more memory efficient.

    // $functor        is the name of the stored procedure, without `quotes`.
    // $args           is an array of arguments and their values, generated using helper functions like _STRING() and _INTEGER()
    // $force_cache    can be passed as TRUE to make it read the resultsets into $this->sets, even without any OUT or INOUT args

    public function Call($functor, $args, $force_cache = FALSE)
    {
        $this->check_open();

        $sql = array();
        $argnames = array();
        $out = array();

        // based on the supplied args, set up some initial and final SQL statements
        foreach ($args as $argname => $arg) {
            $argnames[] = sprintf('@%s',$argname);
            if ($arg->GetDirection() & _IN) $sql[] = sprintf('SET @%s = %s',$argname, $arg->Quote($this->mysqli));
            if ($arg->GetDirection() & _OUT) $out[] = sprintf('@%s AS `%s`',$argname,$argname);
        }

        $sql[] = sprintf("CALL `%s`(%s)", $functor, join(', ',$argnames));

        // so that we know which result set is the last one (with the OUT and INOUT values), we create a unique semaphore here
        $semaphore = GenerateUUID();
        if ($out) $sql[] = sprintf("SELECT '%s' AS `%s`, %s", $semaphore, $semaphore, join(', ',$out));

        $this->Query($sql, array(), $out || $force_cache); // run the queries, with a full read of all result sets if necessary
    
        if ($out) { // if there were any INOUT or OUT args, the last set returned should be their values
            $result = array_pop($this->sets);
            $result = $result[0]; // the last result (the OUT vars) is an array inside an array, for some reason
            if (!$result || !array_key_exists($semaphore,$result)) {
                //DEBUG_LOG("No results for $functor",array('result' => $result, 'first' => $first, 'semaphore' => $semaphore));
                throw new DBException("No results returned from call to {$functor}.");
            }
            unset($result[$semaphore]);
            return array_values($result);
        } else {
            return NULL;
        }
    }

    // MoreSets()
    
    // Returns TRUE if there's another result set waiting to be read.  After this, NextRow will once again work to retrieve the
    // values.

    public function MoreSets()
    {
        if ($this->cached_all) {
            $this->setindex++;
            $this->rowindex = -1;
            if ($this->setindex >= count($this->sets)) {
                $this->cached_all = FALSE;
                $this->cached_one = FALSE;
                $this->setindex = -1;
                return FALSE;
            } else {
                return TRUE;
            }
        } else {
            do {
                $this->result = $this->mysqli->use_result();
            } while (!$this->result && $this->mysqli->next_result());
            $this->check_error();
            return !!$this->result;
        }
    }

    // NextRow()
    
    // Returns the next row of results in the current set.  If there are none, returns NULL.  Once you run out of rows in
    // the current set, call MoreSets to see if there are any more sets of rows to read.

    public function NextRow()
    {
        if ($this->cached_all) {
            if (++$this->rowindex >= count($this->sets[$this->setindex])) {
                $this->rowindex = -1;
                return NULL;
            }
            return $this->sets[$this->setindex][$this->rowindex];
        } else {
            if (!$this->result && !$this->MoreSets()) return NULL;
            $row = $this->result->fetch_assoc();
            if (!$row) {
                $this->result->close();
                $this->result = NULL;
            }
            return $row;
        }
    }

    // ReadSet()
    
    // Read an entire result set into an array, keyed by column names.  Returns NULL if there are no more result sets.
    
    public function ReadSet()
    {
        if (!$this->MoreSets()) return NULL;
        
        $result = array();
        while (($row = $this->NextRow()) !== NULL) $result[] = $row;
        return $result;
    }
        
    // ReadSingleRow()
    
    // Read a single row, discarding any following rows in the set.
    
    public function ReadSingleRow()
    {
        if (!$this->MoreSets()) return NULL;
        
        $result = [];
        if (($row = $this->NextRow()) !== NULL) $result = $row;

        return $result;
    }
        
    // ReadValue()
    
    // Read a single column from a single row, regardless of field name.
    
    public function ReadValue()
    {
        $result = $this->ReadSingleRow();

        if ($result) {
            $result = array_values($result)[0];
        }
        return $result;
    }
        
    // DumpHTMLTable()
    
    // Utility function: dump all remaining result sets as HTML tables.

    public function DumpHTMLTable()
    {
        print '
<style>
    table.dump { margin: 1em 0; border-collapse: collapse; border: solid 1px black; } 
    table.dump td, table.dump th { border: solid 1px black; } 
    table.dump th { background: #cce; }
</style>';

        $any = FALSE;
        while ($this->MoreSets()) {
            $any = TRUE;
            print '<table class="dump">';
            $head = FALSE;
            while (($row = $this->NextRow())) {
                if (!$head) {
                    $head = TRUE;
                    print '<thead><tr>';
                    foreach ($row as $column => $value) printf('<th>%s</th>',$column);
                    print '</tr></thead><tbody>';
                }
                print '<tr>';
                foreach ($row as $column => $value) printf('<td>%s</td>', htmlentities($value));
                print '</tr>';
            }

            if (!$head) {
                print '<thead><tr><th>Empty result set</th></tr></thead>';
            } else {
                print '</tbody>';
            }

            print "</table>\n";
        }
        if (!$any) print '<table class="dump"><thead><tr><th>No result sets</th></thead></table>';
    }


    // ReportStatus()
    
    // Check for an error and die if there was one.

    public function ReportStatus()
    {
        try {
            $this->check_error();
        }
        catch (DBException $e) {
            die(sprintf("ERROR: %s", $e));
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    // Convert a mysqli error to an exception.

    private function check_error()
    {
        $error = $this->mysqli->error;
        if ($error) {
            var_dump($error);
            var_dump($error);
            throw new DBException(sprintf('Error %d: %s', $this->mysqli->errno, $error));
        }
    }

    // Get the connection details from an ini file.  Looks up the specified section and finds values for server, database, user
    // and password.

    private function read_ini_file($inifile, $section)
    {
        $db = parse_ini_file($inifile, TRUE);
        return $db[$section];
    }

    // Using the values found by read_ini_file, check if the database is already open and open it if not.

    private function check_open()
    {
        if (!$this->mysqli) {
            $this->mysqli = new mysqli($this->dbsettings['server'],
                                       $this->dbsettings['user'],
                                       $this->dbsettings['password'],
                                       $this->dbsettings['database']);
            $this->mysqli->set_charset("utf8");
            }
        $this->check_error();
    }

    // Check that the arguments in the query (eg 'SELECT * FROM t1 WHERE id = :person') match the supplied args array
    
    private function check_arguments($sql,$args)
    {
        // look for any text matching ":argname" in the SQL query
        preg_match_all('/(:[A-Za-z][A-Za-z0-9_]*)/',$sql,$colon);

        // if there were none, but args were supplied, complain.
        if (!$colon[0] && $args) {
            printf("<h1>Argument Redundancy</h1><pre>%s</pre>",$sql);
            die(sprintf("Query has no arguments, but some were supplied: %s.", join(', ', array_keys($args))));
        }

        // otherwise, check to see that the names are all accounted for on both sides
        if ($colon[0]) {
            $missing = array();

            // go through each arg found in the query and see if it exists in the args array
            foreach ($colon[1] as $arg) {
                $argname = substr($arg,1);
                if (array_key_exists($argname,$args)) {
                    unset($args[$argname]);
                } else {
                    $missing[] = $argname;
                }
            }

            $extra = array_keys($args);

            if ($missing || $extra) {
                printf("<h1>Named Argument Mismatch</h1><pre>%s</pre>",$sql);
                die(sprintf("Argument(s) required by query but not provided: %s<br>Argument(s) provided but not required: %s", 
                            join(', ',$missing) ?: 'none', 
                            join(', ',$extra) ?: 'none'));
            }
        }
    }
  
    // Because mysqli can leave PHP in a weird state after a query unless you explicitly read all the results, this function
    // flushes out the pipes and sets everything right again.
    
    private function discard_old_results()
    {
        if ($this->inquery) {
            // exhaust all remaining rows in this result set
            do {
                $r = $this->result ?: $this->mysqli->use_result();
            } while (!$r && $this->mysqli->more_results() && @$this->mysqli->next_result());
            // move to the next result set
            while ($r) {
                $r->close();
                if (!@$this->mysqli->next_result()) break;
                $r = $this->mysqli->use_result();
                $this->check_error();
            }
            $this->inquery = FALSE;
        }
    }
    
    // Mysqli doesn't work well with MySQL query parameters when you're fooling around with stored procedures, so to make it
    // simple we interpolate the argument values directly.  With proper quoting, handled automatically by the DBType class,
    // this shouldn't cause any problems with SQL injection.  Just watch out for the use of colons in the actual query -- this
    // function can't tell the difference between 'WHERE id = :person' and 'WHERE id = ":person"', and will interpolate the
    // current value of $args['person'] in either case.
    
    private function interpolate_arguments($sql, $args)
    {
        if (!$args) return $sql;
        
        $search = array();
        $replace = array();
        foreach ($args as $name => $arg) {
            $search[] = sprintf('/:%s/',$name);
            $replace[] = $arg->Quote($this->mysqli);
        }
        $new = preg_replace($search,$replace,$sql);
        //DEBUG_LOG('Interpolate',array_merge(['old' => $sql, 'new' => $new],$args));
        return $new;
    }
    
    
    public function TestQuotedString($str)
    {
        $this->check_open();
        $s = _STRING($str);
        return $s->Quote($this->mysqli);
    }
}
