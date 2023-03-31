<?php

/*
Admin.php

Copyright  2013-2021 by Juan Antonio Martinez ( juansgaviota at gmail dot com )

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation;
either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program;
if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

// Github redirects links, and make curl fail.. so use real ones
// define ('cd w','https://github.com/jonsito/AgilityContest/raw/master/ChangeLog');
define ('UPDATE_INFO','https://raw.githubusercontent.com/jonsito/AgilityContest/master/ChangeLog');
define('UPLOAD_DIR',__DIR__."/../../../../logs/uploads");

require_once(__DIR__."/../../tools.php");
require_once(__DIR__."/../../logging.php");
require_once(__DIR__."/../../ProgressHandler.php");
require_once(__DIR__."/../../auth/Config.php");
require_once(__DIR__."/../../auth/AuthManager.php");
require_once(__DIR__ . "/../../auth/SymmetricCipher.php");
require_once(__DIR__."/DBObject.php");
require_once(__DIR__."/../../printer/RawPrinter.php");

class Admin extends DBObject {
	protected $restore_dir;
    protected $myAuth;
	private $dbname;
	private $dbhost;
	private $dbuser;
	private $dbpass;

	// used to store backup version info to handle remote updates
	protected $bckVersion="0.0.0"; // version
	protected $bckRevision="0000000_0000"; //revision
	protected $bckLicense="00000000";
	protected $bckDate="20180215_0944";
	protected $progressHandler=null;
    protected $compress = true;

    /**
     * Admin constructor.
     * @param {string} $file name of module to be registered in logs
     * @param {object} $am Auth Manager to be used
     * @param {string} $suffix suffix to be added to progress information file
     * @throws Exception
     */
	function __construct($file,$am) {
        parent::__construct($file);
        $this->restore_dir=__DIR__."/../../../../logs/";
		// connect database
        $this->myAuth=$am;
        $this->bckVersion=$this->myConfig->getEnv('version_name'); // extracted from sql file. defaults to current
        $this->bckRevision=$this->myConfig->getEnv('version_date'); // extracted from sql file. defaults to current
		$this->dbname=$this->myConfig->getEnv('database_name');
		$this->dbhost=$this->myConfig->getEnv('database_host');
		$this->dbuser=base64_decode($this->myConfig->getEnv('database_user'));
		$this->dbpass=base64_decode($this->myConfig->getEnv('database_pass'));
        $this->compress = intval($this->myConfig->getEnv('backup_compress')) === 1;
	}

	function setProgressHandler($mode,$suffix) {
	    $this->progressHandler=ProgressHandler::getHandler( ($mode===0)?"restore":"upgrade",$suffix);
    }

    /**
     * Parse mysql dump and pretty-print output
     * FROM: https://gist.github.com/lavoiesl/9a08e399fc9832d12794
     * @param {resource} $stream where to write to default backup ( file or php://output )
     * @param {string} $line parsed line
     * @throws Exception on mysqldump syntax error
     */
	private function process_line($stream,$line) {
		$length = strlen($line);
		$pos = strpos($line, ' VALUES ') + 8;
        @fwrite($stream, substr($line, 0, $pos));
		$parenthesis = false;
		$quote = false;
		$escape = false;
		for ($i = $pos; $i < $length; $i++) {
			switch($line[$i]) {
				case '(':
					if (!$quote) {
						if ($parenthesis) {
							throw new Exception('double open parenthesis');
						} else {
							@fwrite($stream, PHP_EOL);
							$parenthesis = true;
						}
					}
					$escape = false;
					break;
				case ')':
					if (!$quote) {
						if ($parenthesis) {
							$parenthesis = false;
						} else {
							throw new Exception('closing parenthesis without open');
						}
					}
					$escape = false;
					break;
				case '\\':
					$escape = !$escape;
					break;
				case "'":
					if ($escape) {
						$escape = false;
					} else {
						$quote = !$quote;
					}
					break;
				default:
					$escape = false;
					break;
			}
            @fwrite($stream, $line[$i]);
		}
	}

	public function dumpLog() {
        $fname="trace-".date("Ymd_Hi").".log";
        header('Set-Cookie: fileDownload=true; path=/');
        header('Cache-Control: max-age=60, must-revalidate');
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$fname.'"');
        $f=fopen(ini_get('error_log'),"r");
        if(!$f) throw new Exception("Error opening log file");
        while(!feof($f)) { $line = fgets($f); echo $line; }
        fclose($f);
        return "";
	}

	public function resetLog() {
        $f = @fopen(ini_get('error_log'), "r+");
        if ($f !== false) {
            ftruncate($f, 0);
            fputs($f,"Log registry started at ".date("Y-m-d H:i:s")."\n");
            fclose($f);
        }
        return "";
	}

    private function do_backup($compress) {
        $dbname=$this->dbname;
        $dbhost=$this->dbhost;
        $dbuser=$this->dbuser;
        $dbpass=$this->dbpass;
        set_time_limit(0); // some windozes are too slow dumping databases
		$cmd="mysqldump"; // unix
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$path=str_replace("\\apache\\bin\\httpd.exe","",PHP_BINARY);
			$cmd="start /B ".$path."\\mysql\\bin\\mysqldump.exe";
		}
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'DAR') { // Darwin (MacOSX)
			$cmd='/Applications/XAMPP/xamppfiles/bin/mysqldump';
		}

		// phase 1: dump structure
		$cmd1 = "$cmd --opt --no-data --single-transaction --routines --triggers -h $dbhost -u$dbuser -p$dbpass $dbname";
		$this->myLogger->info("Ejecutando comando: '$cmd1'");
		$input = popen($cmd1, 'r');
		if ($input===FALSE) { $this->myLogger->error("adminFunctions::backup():popen() failed"); return null;}
        $memfile=@fopen('php://memory','r+');
        if ($memfile===FALSE) { $this->myLogger->error("adminFunctions::backup():fopen(memory) failed"); return null;}
		while(!feof($input)) {
			$line = fgets($input);
			if (substr($line, 0, 6) === 'INSERT') {
				$this->process_line($memfile,$line);
			} else {
                @fwrite($memfile, $line);
			}
		}
		pclose($input);

		// phase 2: dump data. Exclude ImportData and (if configured to) Eventos table contents
        $noexport="--ignore-table={$this->dbname}.importdata";
		if (intval($this->myConfig->getEnv("full_backup"))==0) $noexport .= " --ignore-table={$this->dbname}.eventos";
        $cmd2 = "$cmd --opt --no-create-info --single-transaction --routines --triggers $noexport -h $dbhost -u$dbuser -p$dbpass $dbname";
        $this->myLogger->info("Ejecutando comando: '$cmd2'");
        $input = popen($cmd2, 'r');
        if ($input===FALSE) { $this->myLogger->error("adminFunctions::popen() failed"); return null;}
        while(!feof($input)) {
            $line = fgets($input);
            if (substr($line, 0, 6) === 'INSERT') {
                $this->process_line($memfile,$line);
            } else {
                @fwrite($memfile, $line);
            }
        }
        pclose($input);

		// insert AgilityContest Info at begining of backup file
        $bckdate=date("Ymd_Hi");
        $ver=$this->myConfig->getEnv("version_name");
        $rev=$this->myConfig->getEnv("version_date");
        $data = "-- AgilityContest Version: {$ver} Revision: {$rev}\n-- AgilityContest Backup Date: {$bckdate}\n";
        rewind($memfile);
        $data .= stream_get_contents($memfile);

        return $compress ? gzencode($data, 9) : $data;
    }

	/**
	 * Automatic backup to log file
	 * and -if defined- to user specified file
	 * @param {integer} mode -1: no copy; 0: numerated backup copy to ${HOME}; 1: try to copy backup to specified file
     * @param {integer} directory to store user copy, or empty to use configuration value
	*/
    public function autobackup($mode=1,$directory="") {
        $this->myLogger->enter();
        $dnb=$this->restore_dir."/do_not_backup";
        if (file_exists($dnb)) { // if first_install mark exists do not autobackup at login
            @unlink($dnb);
            return array('do_not_backup'=>true);
        }

        $data = $this->do_backup($this->compress);
        if ($data == null) {
            return "";
        }

        // prepare auto backup file
        // rename ( if any ) previous backup
        // notice name uses '_' (underscore) whilst named backup uses '-' (minus sign)
        // this is done to preserve autobackups on temporary directory clear
        $zext = $this->compress ? '.gz' : '';
        $oldname="{$this->restore_dir}/{$this->dbname}_backup_old.sql$zext";
        $fname="{$this->restore_dir}/{$this->dbname}_backup.sql$zext";
        @rename($fname,$oldname); // @ to ignore errors in case of
        $outfile=@fopen($fname,"w");
        if (!$outfile){
            $this->myLogger->error("Cannot fopen backup file {$fname}");
            return "adminFunctions::AutoBackup('fopen') failed";
        }
        @flock($outfile,LOCK_EX);
        @fwrite($outfile, $data);
        fclose($outfile);

        // prepare user-requested backup file (if any)
        $tname="";
        $dt=date("Ymd_Hi");
        // on mode=0 copy to $HOME as numerated (date+hour) backup
        if ($mode==0) $tname="{$this->restore_dir}/{$this->dbname}-{$dt}.sql$zext";
        // on mode=1 copy backup to file specified in configuration
        if ($mode>0) {
            $dirname=($directory=="")?$this->myConfig->getEnv("backup_dir"):$directory;
            if ($dirname=="") {
                $this->myLogger->trace( "adminFunctions::AutoBAckup) empty user directory");
                return ""; // not an error, just skip generating user defined backup
            }
            if (!is_dir($dirname)) return "adminFunctions::AutoBAckup) invalid user directory {$dirname}";
            if (!is_writeable($dirname)) return "adminFunctions::AutoBAckup) cannot write into user directory {$dirname}";
            $tname="{$dirname}/{$this->dbname}-userbackup.sql$zext";
        }
        // notice that apache/php may restrict access permissions
        // and copy will silently fail
        if ($tname!=="") {
            $this->myLogger->trace("Copying backup to destination user file {$tname}");
            @copy($fname,$tname);
            if (!file_exists($tname)) return "adminFunctions::AutoBAckup) couldn't create user backup {$tname}";
        }
		// and finally return ok
        $this->myLogger->leave();
        return ""; // return empty string to let json response work
        // notice that exit procedure releases flock on agility_backup.sql
    }

    public function backup() {
        // open stdout as file handler
        $outfile=fopen("php://output","w");
        if ($outfile===FALSE) { $this->myLogger->error("adminFunctions::backup():fopen(stdout) failed"); return null;}

        $data = $this->do_backup($this->compress);
        if ($data == null) {
            fclose($outfile);
            return null;
        }
        $zext = $this->compress ? '.gz' : '';
        $mime = $this->compress ? 'application/gzip' : 'text/plain';

        // prepare html response header
        $bckdate=date("Ymd_Hi");
		$fname="{$this->dbname}-{$bckdate}.sql$zext";
		header('Set-Cookie: fileDownload=true; path=/');
		header('Cache-Control: max-age=60, must-revalidate');
		header("Content-Type: $mime; charset=utf-8");
		header('Content-Disposition: attachment; filename="'.$fname.'"');
        @fwrite($outfile, $data);
        fclose($outfile);
		return "ok";
	}	

	private function handleSession($str) {
        if ($this->progressHandler===null) {
            $this->myLogger->error("call to handleSession() without progressHandler()");
            return;
        }
        $this->progressHandler->putData($str,false);
	}

	private function retrieveDBFile() {
		$this->myLogger->enter();
		$this->handleSession("Download");
		// extraemos los datos de registro
		$data=http_request("Data","s",null);
		if (!$data) return array("errorMsg" => "restoreDB(): No restoration data received");
		// data may contain
        // case 1: string "remoteDownload" -> retrieve database from master server
		if ($data==="remoteDownload") {
            $rev=$this->myConfig->getEnv("version_date");
            $lic=0;
            $srvr=$this->myConfig->getEnv("master_server");
            $url="https://{$srvr}/agility/ajax/serverRequest.php?Operation=retrieveBackup&Revision={$rev}&Serial={$lic}";
		    $res=retrieveFileFromURL($url);
		    if ($res===FALSE) return array("errorMsg" => "downloadDatabase(): cannot download file from server");
        }
        // case 2: base64 encoded string with data to be downloaded
        // server configuration restrict POST size to about 6Mb, so this procedure may fail with big uploads
		else if (preg_match('/data:([^;]*);base64,(.*)/', $data, $matches)) {
            // $type=$matches[1]; // 'application/octet-stream', or whatever. Not really used
            $res= base64_decode( $matches[2] ); // decodes received data
		}
		// case 3: filename uploaded by mean of File_Loader.php library
		else {
            // check if file exists; read into memory and unlink
            $pinfo=pathinfo(str_replace( "\\", '/', $data ));
		    $filename=UPLOAD_DIR."/{$pinfo['basename']}";
		    $this->myLogger->trace("filename is {$filename}");
		    if (!file_exists($filename)) return array("errorMsg" => "restoreDatabase(): Invalid data requested: '{$filename}'");
		    $res=file_get_contents($filename);
		    @unlink($filename);
        }
        $this->myLogger->leave();
		return $res;
	}

	private function dropAllTables($conn) {
        $conn->query('SET foreign_key_checks = 0');
        if ($result = $conn->query("SHOW TABLES")) {
            while($row = $result->fetch_array(MYSQLI_NUM)) {
				$this->handleSession("Drop table ".$row[0]);
                $conn->query('DROP TABLE IF EXISTS '.$row[0]);
            }
        }
        $conn->query('SET foreign_key_checks = 1');
    }

    /**
     * Extract backup contents and store into database
     * @param $conn  Database connection
     * @param $data backup conents
     * @return string "" on success, else errormsg
     * @throws Exception unrecoverable error
     */
    private function readIntoDB($conn,$data) {
        $keystr="";
        // retrieve file information from header
        $newline=strpos($data,"\n");
        // first line is copyright and license info
        $line=substr($data,0,$newline);
        $num=sscanf($line,
            "-- AgilityContest Version: %s Revision: %s License: %s",
            $this->bckVersion, $this->bckRevision, $this->bckLicense);
        if ($num===3) {
            $data=substr($data,$newline+1); // advance to newline
            $newline=strpos($data,"\n");
            // second line is backup file creation date
            $line=substr($data,0,$newline);
            $num=sscanf("$line","-- AgilityContest Backup Date: %s Hash: %s",$this->bckDate,$keystr);
            if ($num==1) $keystr="";
        } else {
            $this->bckLicense="00000000"; //older db backups lacks on third field
            $this->bckDate=$this->bckRevision; // older db backups lacks on backup creation date
            $keystr="";
        }
        // now comes backup data.
        $data=substr($data,$newline+1); // advance to newline
        // if encryption key found in header, decrypt file
        if ($keystr!=="") {
            // encryption key
            $key= base64_encode(substr("{$this->bckLicense}{$this->bckRevision}{$this->bckDate}",-32));
            // check key hash
            if ($keystr!== hash("md5",$key,false)) {
                $this->handleSession("Done.");
                throw new Exception("Restore failed: Key hash does not match");
            }
            $data=SymmetricCipher::decrypt($data,$key);
        }
        // Read entire file into an array
        $lines = explode("\n",$data);
        // prepare restore process
		$numlines=count($lines);
        // Temporary variable, used to store current query
        $templine = '';
        $trigger=false;
		$timeout=ini_get('max_execution_time');
        // Loop through each line
        foreach ($lines as $idx => $str) {
            // avoid php to be killed on very slow systems
            set_time_limit($timeout);
            $line=trim($str);
            // Skip it if it's a comment
            if (substr($line, 0, 2) === '--' || trim($line) === '') continue;
            // properly handle "DELIMITER ;;" command
            if ($line==="DELIMITER ;;") { $trigger=true; continue; }
            else if ($line==="DELIMITER ;") { $trigger=false; }
            else $templine .= $line;    // Add this line to the current segment
            if ($trigger) continue;
            // log every create/insert
            if (strpos($line,"CREATE")===0) { $this->myLogger->trace("$line "); }
            if (strpos($line,"INSERT")===0) { $this->myLogger->trace("$line "); }
            // If it has a semicolon at the end, it's the end of the query
            if (substr(trim($line), -1, 1) == ';') {
				$this->handleSession("".intval((100*$idx)/$numlines). "% Completed" );
                // Perform the query
                if (! $conn->query($templine) ){
					$this->myLogger->error('Error performing query \'<strong>' . $templine . '\': ' . $conn->error . '<br />');
				}
                // Reset temp variable to empty
                $templine = '';
            }
        }
		$this->handleSession("Done.");
        $this->myLogger->info("database restore success");
        return "";
    }

    /**
     * Restore database main process.
     * Download file, check, install db and update version history
     * @return string "" emtpy on success, else error
     * @throws Exception
     */
	public function restore() {
        // we need root database access to re-create tables
        $rconn=DBConnection::getRootConnection();
        if ($rconn->connect_error) {
            $this->handleSession("Done.");
            throw new Exception("Cannot perform upgrade process: database::dbConnect()");
        }
		// phase 1: retrieve file from http request
        $data=$this->retrieveDBFile();
        if (is_array($data)){
            $this->handleSession("Done.");
            throw new Exception($data['errorMsg']);
        }
        // Try to uncompress it
        $err = error_reporting();
        error_reporting($err & ~E_WARNING);
        $uncompressed = gzdecode($data);
        error_reporting($err);
        if ($uncompressed !== false) $data = $uncompressed;
        // phase 2: verify received file
		if (strpos(substr($data,0,25),"-- AgilityContest")===FALSE) {
            $this->handleSession("Done.");
            throw new Exception("Provided file is not an AgilityContest database file");
        }
        // phase 3: delete all tables and structures from database
        $this->dropAllTables($rconn);
        // phase 4: parse sql file and populate tables into database
        $this->readIntoDB($rconn,$data);

        // phase 5 update VersionHistory: set current sw version entry with restored backup creation date
        $bckd=toLongDateString($this->bckDate); // retrieve database backup date
        // trick to update only newest version record
        // https://stackoverflow.com/questions/12242466/update-row-with-max-value-of-field
        $str= "UPDATE versionhistory SET Updated='{$bckd}' ORDER BY Version DESC LIMIT 1";

        // errata on 3.7.3: this didn't update properly database on sw version change, so use above
        //$str="INSERT INTO versionhistory (Version,Updated) VALUES ('{$this->bckRevision}','{$bckd}') ON DUPLICATE KEY UPDATE Updated='{$bckd}'";

        $this->myLogger->trace($str);
        $rconn->query($str);
        // finally close db connection and return
        DBConnection::closeConnection($rconn);
        // mark system to do not auto-backup on next login,
        @touch($this->restore_dir."/do_not_backup");
        // after restore too many diffs may exist against master server database
        // and need to leave restored database "as is"
        // so mark system to do not sync from server after restore
        if (intval($this->myConfig->getEnv('search_updatedb'))>0) { // if zero leave untouched
            $this->myConfig->setEnv('search_updatedb',-1); // if 1, reset to ask (-1)
            $this->myConfig->saveConfig();
        }
		return "";
	}

    public function clearContests($fed=-1) {
        $fed=intval($fed); // for yes the flies
        $f=($fed===-1)?"":" AND (RSCE={$fed})";
        // this will recursively delete teams, journeys, rounds, results, and so
        return $this->__delete("pruebas","(ID>1) {$f}");
    }

	public function clearDatabase($fed=-1) {
        $fed=intval($fed); // for yes the flies...
        $f=($fed===-1)?"":" AND (Federation={$fed})";
		// drop pruebas
        $this->clearContests($fed);
        // do not delete users nor sessions

        // delete dogs and handlers on selected federation
        $this->__delete("perros","(ID>1) {$f}");
        $this->__delete("guias","(ID>1) {$f}");

        // judges and clubes need to evaluate federation mask;
        // do not delete clubes for international contests, !cause they are just countries!
        if ($fed===-1) { // delete all data regardless federation
            $this->__delete("clubes","(ID>1) AND (Federations < 512)"); // do not delete countries!!
            $this->__delete("jueces","(ID>1)");
            $this->__delete("eventos");
        } else {
            $fmask=1<<$fed; // get federation mask
            // remove every judges related only to provided federation
            $this->__delete("jueces","(ID>1) AND (Federations={$fmask})");
            // delete all clubes registered _only_ on requested federation
            if ($f<512) $this->__delete("clubes","(ID>1) AND (Federations={$fmask})");
            // pending: also remove related logos (when no shared)
        }
		return "";
	}

	public function clearTemporaryDirectory() {
		// borramos ficheros relacionados con actualizaciones
		$this->myLogger->trace("Clearing update related tmp files");
		array_map('unlink',glob("{$this->restore_dir}AgilityContest*.zip"));
        array_map('unlink',glob("{$this->restore_dir}update.log"));

		// ficheros excel importados
        $this->myLogger->trace("Clearing excel import related tmp files");
        array_map('unlink',glob("{$this->restore_dir}import*.xlsx"));
        array_map('unlink',glob("{$this->restore_dir}import*.log"));
        array_map('unlink',glob("{$this->restore_dir}import*.json"));

        // actualizaciones de la base de datos
        $this->myLogger->trace("Clearing database sync related tmp files");
        if (!is_dir("{$this->restore_dir}updateRequests")) @mkdir("{$this->restore_dir}updateRequests");
        array_map('unlink',glob("{$this->restore_dir}updateRequests/dbsync*.log"));
        array_map('unlink',glob("{$this->restore_dir}updateRequests/*.json"));

        // restore operations log
        $this->myLogger->trace("Clearing progress info related tmp files");
        array_map('unlink',glob("{$this->restore_dir}restor*.log"));
        array_map('unlink',glob("{$this->restore_dir}equipos*.log"));

        // AgilityContest updates
        $this->myLogger->trace("Clearing AgilityContest updates related tmp files");
        array_map('unlink',glob("{$this->restore_dir}upgrade*.log"));
        array_map('unlink',glob("{$this->restore_dir}AgilityContest*.zip"));

        // remove results mail directories
        $this->myLogger->trace("Clearing mail results related tmp files");
        array_map('unlink',glob("{$this->restore_dir}results_*/*.*"));
        array_map('rmdir',glob("{$this->restore_dir}results_*"));

        // remove inscriptions mail directories
        $this->myLogger->trace("Clearing mail inscriptions related tmp files");
        array_map('unlink',glob("{$this->restore_dir}mail_*/*.*"));
        array_map('rmdir',glob("{$this->restore_dir}mail_*"));

        // finally clear also named backup files, but preserve autobackups
        array_map('unlink',glob("{$this->restore_dir}agility-*.sql"));
        return "";
	}

    /**
     * @param bool $fireException fire exception on failure to retrieve data
     * @return array version and date info
     * @throws Exception on fail
     */
	public function checkForUpgrades($fireException=true) {
        $info=retrieveFileFromURL(UPDATE_INFO);
        /* pre 3.8.0 uses system.ini
        if ( ($info===null) || ($info===FALSE) || (!is_string($info)) ) {
            if ($fireException)  throw new Exception("checkForUpgrade(): cannot retrieve version info from github system.ini");
            $info="version_name = \"0.0.0\"\nversion_date = \"19700101_0000\"\n"; // escape quotes to get newlines into string
        }

        $info = str_replace("\r\n", "\n", $info);
        $info = str_replace(" ", "", $info);
        $data = explode("\n",$info);
        foreach ($data as $line) {
            if (strpos($line,"version_name=")===0) $version_name = trim(substr($line,13),'"');
            if (strpos($line,"version_date=")===0) $version_date = trim(substr($line,13),'"');
        }
        */
        /* post 3.7.3 uses ChangeLog to retrieve version and date info */
        if (!is_string($info) ) {
            if ($fireException) {
                throw new Exception("checkForUpgrade(): cannot retrieve version info from github ChangeLog");
            }
            $info="Version 0.0.0 19700101_0000\n";
        }
        $firstline=trim(strtok($info,"\n"));
        $this->myLogger->trace("ChangeLog: {$firstline}");
        $data=explode(" ", $firstline);
        $version_name=$data[1];
        $version_date=$data[2];

        $res=array(
            'version_name' => $version_name,
            'version_date' => $version_date
        );
		// mark filesystem to allow upgrade
		$f=fopen($this->restore_dir."/do_upgrade","w");
		fwrite($f,$this->myAuth->getSessionKey());
		fclose($f);
		return $res;
	}

	public function downloadUpgrades($version) {
	    $this->myLogger->enter($version);
	    // versions greater than 4.4.1 download updates from github releases
        // instead of "master" tree, to avoid download working copy
        // so remember: must add new tag in github when new release is published
        // https://github.com/jonsito/AgilityContest/archive/refs/tags/4.5.0-20210707_1745.zip
        $source="https://github.com/jonsito/AgilityContest/archive/refs/tags/${version}.zip";
        //$source='https://codeload.github.com/jonsito/AgilityContest/zip/master';
        $dest=__DIR__."/../../../../logs/AgilityContest-{$version}.zip";
		// file_get_contents() and copy() suffers from allow_url_fopen and max_mem problem, so just use curl
		// to download about 300Mb
		$res="";
		@unlink($dest); // use @ to prevent warns to console
        set_time_limit(0);
        $fp = fopen ($dest, 'w+');  //This is the file where we save the information
		if(!$fp) {
        	$errors= error_get_last();
        	$res="Create upgrade file error:{$errors['type']} {$errors['message']}";
            $this->handleSession("Done.");
        	return $res;
    	}
        $ch = curl_init(str_replace(" ","%20",$source)); //Here is the file we are downloading, replace spaces with %20
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // not really needed but...
        curl_setopt($ch, CURLOPT_TIMEOUT, 420); // 7 minutes should be enougth for wellknownforslowness github
        curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . "/../../../../config/cacert.pem");
        curl_setopt($ch, CURLOPT_FILE, $fp); // write curl response to file
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // to allow redirect
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // try to fix some slowness issues in windozes
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, array($this,'downloadProgress'));
        curl_setopt($ch, CURLOPT_NOPROGRESS, false); // needed to make progress function work
        curl_setopt($ch, CURLOPT_BUFFERSIZE, (1024*1024*4)); // set buffer to 4Mb
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 5); // wait 5 secs to attemp connect
        if ( curl_exec($ch) === false ) { // get curl response
            $res="Upgrade download error: ".curl_error($ch);
            $this->handleSession("Done.");
            return $res;
		}
        curl_close($ch);
        fclose($fp);
        $this->handleSession("Verifying download...");
        // now verify downloaded file
        $zip = new ZipArchive();
        $chk = $zip->open($dest, ZipArchive::CHECKCONS);
        if ($chk !== TRUE) {
            switch($chk) {
                case ZipArchive::ER_NOZIP: $res='Downloaded file is not a zip archive'; break;
                case ZipArchive::ER_INCONS: $res='Upgrade zipfile consistency check failed'; break;
                case ZipArchive::ER_CRC : $res='Upgrade zipfile checksum failed'; break;
                default: $res='Upgrade zipfile check error ' . $chk; break;
            }
        }
        $zip->close();
        $this->handleSession($res);
        $this->handleSession("Done.");
        $this->myLogger->leave();
        return $res;
	}

	// notice that this function is called as callback from curl
	// so cannot use any resource of current class because no scope set
	// also github does not provide file size to curl, so cannot evaluate percentage
    function downloadProgress($resource,$download_size, $downloaded, $upload_size, $uploaded)  {
		$dl=intval($downloaded/(1024*1024));
		$msg="$dl Mbytes";
		$this->handleSession($msg);
    }
}

?>
