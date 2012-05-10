<?php

//http://www.faqs.org/rfcs/rfc1952.html

/** 
 * class to do backup, write to specified stream
 */

class Am_BackupProcessor {
    const COMPRESSION_LEVEL = 5;
    // you can add more with EventSkipBackup
    protected $skipTables = array(
        'access_log',
        'error_log',
        'invoice_log',
        'session',
        'access_cache',
    );
    
    public function isGzip() {
        return $this->isGzAvailable();
    }
    
    /**
     * Do backup
     *
     * @param resource $stream
     * @return resource 
     */
    public function run($stream) {
        
        if (in_array('cc', Am_Di::getInstance()->modules->getEnabled()))
           throw new Am_Exception_AccessDenied("Online backup is disabled if you have CC payment plugins enabled. Use offline backup instead");
        
        @ini_set('session.use_trans_sid', 0);
        if (@ini_get('session.use_trans_sid'))
            throw new Am_Exception_InternalError('Your hosting has PHP setting session.use_trans_sid ON. Please disable it because it brokes backup process');
        @ini_set('url_rewriter.tags', '');
        
        return $this->doBackup($stream, $this->getTables(), $this->isGzAvailable());
    }
    
    protected function isGzAvailable() {
        return function_exists('gzencode');
    }
    
    protected function getTables() {
        $prefix = Am_Di::getInstance()->db->getPrefix();
        $tables  = Am_Di::getInstance()->db->selectCol("SHOW TABLES LIKE ?", $prefix.'%');

        $event = Am_Di::getInstance()->hook->call(Am_Event::SKIP_BACKUP);
        $this->skipTables = array_merge($this->skipTables, $event->getReturn());
        
        foreach ($this->skipTables as & $table)
            $table = $prefix . $table;
        $tables = array_diff($tables, $this->skipTables);
        return $tables;
    }
    
    protected function doBackup($stream, $tables, $gzip=false) {
        $db = Am_Di::getInstance()->db;     
        
        $stream_filter = null;
        $hash = null;
        $len = 0;
        
        if ($gzip) {
            $hash = hash_init('crc32b');
            // gzip file header
            fwrite($stream, $this->getGzHeader());
            if (!$stream_filter = stream_filter_append($stream, "zlib.deflate", STREAM_FILTER_WRITE, self::COMPRESSION_LEVEL))
                throw new Am_Exception_InternalError("Could not attach gzencode filter to output stream");
        }

        $this->out($stream, "### aMember Pro ".AM_VERSION." database backup\n", $len, $hash);
        $this->out($stream, "### Created: " . date('Y-m-d H:i:s') . "\n", $len, $hash);

        foreach ($tables as $table){
            $this->out($stream, "\n\nDROP TABLE IF EXISTS $table;\n", $len, $hash);

            $r = $db->selectRow("SHOW CREATE TABLE $table");
            $this->out($stream, $r['Create Table'].";\n", $len, $hash);

            $q = $db->queryResultOnly("SELECT * FROM $table");
            while ($a = $db->fetchRow($q)){
                $fields = join(',', array_keys($a));
                $values = join(',', array_map(array($db, 'escape'), array_values($a)));
                $this->out($stream, "INSERT INTO $table ($fields) VALUES ($values);\n", $len, $hash);
            }
            $db->freeResult($q);
        }
        if ($stream_filter)
        {
            stream_filter_remove($stream_filter);
            fwrite($stream, $this->getGzFooter($len, $hash));
        }    
        
        return $stream;
    }
    
    protected function getGzHeader()
    {
        $out  = "\x1f\x8b"; //signature
        $out .= "\x08"; //method - deflate
        $out .= "\x00"; //flags
        $out .= pack('V', time());
        $out .= "\x00" . "\xff"; //extended flags and OS, we do not specify anything
        return $out;
    }

    protected function getGzFooter($len, $hash)
    {
        $crc = hash_final($hash);
        $crc = pack('V', hexdec($crc));
        return $crc . pack('V', fmod($len, pow(2,32)));
    }
    
    protected function out($stream, $s, & $len, & $hash)
    {
        if ($hash) hash_update($hash, $s);
        fwrite($stream, $s);
        $len += strlen($s);
    }
}
