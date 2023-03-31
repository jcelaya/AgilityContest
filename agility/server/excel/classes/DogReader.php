<?php

/**
 * Created by PhpStorm.
 * User: jantonio
 * Date: 2/04/16
 * Time: 16:20
DogReader.php

Copyright  2013-2018 by Juan Antonio Martinez ( juansgaviota at gmail dot com )

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation;
either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program;
if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */
require_once(__DIR__ . "/../../logging.php");
require_once(__DIR__ . "/../../tools.php");
require_once(__DIR__ . "/../../ProgressHandler.php");
require_once(__DIR__ . "/../../i18n/Country.php");
require_once(__DIR__ . "/../../auth/Config.php");
require_once(__DIR__ . "/../../auth/AuthManager.php");
require_once(__DIR__ . "/../../modules/Federations.php");
require_once(__DIR__ . "/../../database/classes/DBObject.php");
require_once __DIR__ . '/../Spout/Autoloader/autoload.php';
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;

define ('IMPORT_DIR', __DIR__."/../../../../logs/");
define ('TABLE_NAME',"importdata"); // name of temporary table to store excel file data into

/**
 * Class DogReader
 */
class DogReader {

    public $errormsg;
    public $myLogger;
    protected $name;
    protected $federation;
    protected $fedobj;
    protected $myOptions;
    protected $blindMode;
    protected $myAuthMgr;
    protected $tablename;
    protected $myDBObject;
    protected $isInternational;
    protected $validPageNames;

    protected $excelVars=array(); // variables declared in top of sheet ( fields: name-value )

    // NOTICE: limitation of the code: required fields in $fieldList must be greater than 3
    protected $fieldList=array (
        // name => index, required (1:true 0:false-to-evaluate -1:optional), default
        // dog related data
        // 'ID' =>      array (  -1,  0,  "i", "ID",        " `ID` int(4) UNIQUE NOT NULL, "), // automatically added
        'DogID' =>      array (  -2,  0,  "i", "DogID",     " `DogID` int(4) NOT NULL DEFAULT 0, "), // to be filled by importer
        'Name'   =>     array (  -3,  1,  "s", "Nombre",    " `Nombre` varchar(255) NOT NULL, "), // Dog name
        'LongName' =>   array (  -4,  -1, "s", "NombreLargo"," `NombreLargo` varchar(255) DEFAULT '', "), // dog pedigree long name
        'Gender' =>     array (  -5,  -1, "s", "Genero",    " `Genero` varchar(16) DEFAULT '', "), // M, F, Male/Female
        'Breed' =>      array (  -6,  -1, "s", "Raza",      " `Raza` varchar(255) DEFAULT '', "), // dog breed, optional
        'Chip' =>       array (  -17, -1, "s", "Chip",      " `Chip` varchar(255) DEFAULT '', "), // dog pedigree long name
        'License' =>    array (  -7,  -1, "s", "Licencia",  " `Licencia` varchar(255) DEFAULT '', "), // dog license. required for A2-A3;
        'KC id' =>      array (  -8,  -1, "s", "LOE_RRC",   " `LOE_RRC` varchar(255) DEFAULT '', "), // LOE_RRC kennel club dog id
        'Category' =>   array (  -9,   1, "s", "Categoria", " `Categoria` varchar(1) NOT NULL DEFAULT '-', "), // required
        'Grade' =>       array (  -10, 1, "s", "Grado",     " `Grado` varchar(16) DEFAULT '-', "), // required
         // handler related data
        'HandlerID' =>  array (  -11,  0, "i", "HandlerID", " `HandlerID` int(4) NOT NULL DEFAULT 0, "),  // to be evaluated by importer
        'Handler' =>    array (  -12,  1, "s", "NombreGuia"," `NombreGuia` varchar(255) NOT NULL, "), // Handler's name. Required
        'CatHandler' => array (  -13, -1, "s", "CatGuia",   " `CatGuia` varchar(1) NOT NULL DEFAULT 'A', "), // Handler's category. Optional
        // club related data
        // in international contests user can provide ISO country name either in "Club" or in "Country" field
        'ClubID' =>     array (  -14,  0, "i", "ClubID",    " `ClubID` int(4) NOT NULL DEFAULT 0, "),  // to be evaluated by importer
        'Club' =>       array (  -15,  1, "s", "NombreClub"," `NombreClub` varchar(255) NOT NULL DEFAULT '-- Sin asignar --',"),  // Club's Name. required
        'Country' =>    array (  -16, -1, "s", "Pais",      " `Pais` varchar(255) NOT NULL DEFAULT '-- Sin asignar --', ")  // Country. optional
    );

    public function __construct($name,$options) {
        $this->federation = intval($options['Federation']);
        $this->myOptions=$options;
        $this->name=$name;
        $this->myConfig=Config::getInstance();
        $this->myLogger= new Logger($this->name,$this->myConfig->getEnv("debug_level"));
        if (php_sapi_name()!="cli") {
            $this->myAuthMgr= AuthManager::getInstance($this->name);
            $this->myAuthMgr->access(PERMS_OPERATOR); // throw exception on fail
        }
        $this->tablename= TABLE_NAME;
        $this->myDBObject = new DBObject($name);
        // take care on international feds ajdusting "required" array field
        $this->fedobj=Federations::getFederation($this->federation);
        $this->isInternational=$this->fedobj->isInternational();
        if ($this->isInternational) {
            $this->fieldList['Club'][1]=-1;$this->fieldList['Country'][1]=1;
        }
        $this->validPageNames=array("Dogs","Inscriptions");
    }

    public function saveStatus($str,$reset=false){
        $ph=ProgressHandler::getHandler("import",$this->myOptions['Suffix']);
        $ph->putData($str,$reset);
    }

    public function saveExcelVars() {
        $fname=IMPORT_DIR."import_{$this->myOptions['Suffix']}.json";
        $f=fopen($fname,"w");
        if (!$f) {
            $this->myLogger->error("fopen() cannot create file: $fname");
            return;
        }
        fwrite($f,json_encode($this->excelVars));
        fclose($f);
    }

    public function loadExcelVars() {
        $fname=IMPORT_DIR."import_{$this->myOptions['Suffix']}.json";
        $str=file_get_contents($fname);
        $this->excelVars=json_decode($str,true);
        return $this->excelVars;
    }

    public function retrieveExcelFile() {
        // phase 1 retrieve data from browser
        $this->myLogger->enter();
        $this->saveStatus("Loading file...",true); // initialize status file
        // extraemos los datos de registro
        $data=http_request("Data","s",null);
        if (!$data) return array("errorMsg" => "{$this->name}::download(): No data to import has been received");
        if (!preg_match('/data:([^;]*);base64,(.*)/', $data, $matches)) {
            return array("operation"=>"upload","errorMsg" => "{$this->name}::download() Invalid received data format");
        }
        // mimetype for excel file is be stored at $matches[1]: and should be checked
        // $type=$matches[1]; // 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', or whatever. Not really used
        $contents= base64_decode( $matches[2] ); // decodes received data
        // phase 2 store it into temporary file
        $tmpfile=tempnam_sfx(__DIR__ . "/../../../../logs","import","xlsx");
        $file=fopen($tmpfile,"wb");
        fwrite($file,$contents);
        fclose($file);
        $this->myLogger->leave();
        return array("operation"=>"upload","success"=>true,"filename"=>$tmpfile);
    }

    protected function validateHeader($header) {
        $this->myLogger->enter();
        $this->saveStatus("Validating header...");
        // search required fields in header and store index when found

        foreach ($this->fieldList as $field =>&$data) {
            $toSearch=$field;
            if (strpos($field,"Jornada")!==FALSE ) $toSearch=$data[3]; // already converted and stripped
            // iterate header to find each stored field index
            for($index=0; $index<count($header); $index++) {
                $name=$header[$index];
                $name=preg_replace('/\s+/', '', $name);
                $name=$this->myDBObject->conn->real_escape_string($name);
                // search header item in available values from fieldlist
                // Try to take care on special chars and i18n issues
                if ( ($name==$toSearch) || ($name==_($toSearch)) || ($name==_utf($toSearch)) || ($name==$data[3]) ) {
                    $this->myLogger->trace("Found key $name at index $index");
                    $data[0]=$index;
                }
            }
        }

        // fill fieldList default values with declared one in excelVars
        foreach ($this->fieldList as $key =>&$val) {
            $newval="";
            // try to match field with anty stored excel variables to perform global substitution
            if (array_key_exists($key,$this->excelVars)) $newval=$this->excelVars[$key];
            if (array_key_exists(_utf($key),$this->excelVars)) $newval=$this->excelVars[_utf($key)];
            if (array_key_exists($val[3],$this->excelVars)) $newval=$this->excelVars[$val[3]];
            if (array_key_exists(_utf($val[3]),$this->excelVars)) $newval=$this->excelVars[_utf($val[3])];
            // if replacement default value found, handle it
            $newval=$this->myDBObject->conn->real_escape_string(trim($newval));
            if ($newval!=="") {
                $this->myLogger->trace("Using user defined default value '{$newval}' in field '{$val[4]}'");
                $val[4]=preg_replace("/DEFAULT '.*'/","DEFAULT '{$newval}'",$val[4]);
                $val[1]=-1; // mark field as not required
            }
        }

        $this->myLogger->trace("field list 3: \n".json_encode($this->fieldList));
        // now check for required but not declared fields
        foreach ($this->fieldList as $key =>&$val) {
            if ( ($val[0]<0) && ($val[1]>0) ){
                if (!array_key_exists($key,$this->excelVars))
                    throw new Exception ("{$this->name}::required field '$key' => ".json_encode($val)." not found in Excel header");
            }
            $this->myLogger->trace("Key: {$key} Value: ".json_encode($val));
        }
        $this->myLogger->trace("field list 4: \n".json_encode($this->fieldList));
        $this->myLogger->leave();
        return 0;
    }

    protected function createTemporaryTable() {
        $this->myLogger->enter();
        $this->saveStatus("Creating temporary table...");
        // To create database we need root DB access
        $rconn=DBConnection::getRootConnection();
        if ($rconn->connect_error)
            throw new Exception("Cannot perform import process: database::dbConnect()");
        // $str="DELETE FROM TABLE {$this->tablename} IF EXISTS";
        $str="CREATE TABLE {$this->tablename} ( `ID` int(4) NOT NULL AUTO_INCREMENT,";
        // include every fields, either are declared in excel file or not
        foreach ($this->fieldList as $key => $val)  $str .=$val[4];
        $str .=" PRIMARY KEY (`ID`)"; // to get an unique id in database
        $str .=") ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";
        $this->myLogger->trace($str);
        $res=$rconn->query($str);
        if (!$res) {
            $error=$rconn->error;
            $str="{$this->name}::createTemporaryTable(): Error creating temporary table: '$error'";
            $this->myLogger->error($str);
            throw new Exception($str);
        }
        $this->myLogger->leave();
        return 0;
    }

    protected function import_storeExcelRowIntoDB($index,$row) {
        $this->myLogger->enter();
        // compose insert sequence
        $str1= "INSERT INTO {$this->tablename} (";
        $str2= "ID ) VALUES (";
        // for each row evaluate field name and get content from provided row
        // notice that
        foreach ($this->fieldList as $key => $val) {
            if ( ($val[0]<0) || ($val[1]==0)) continue; // field not provided or to be evaluated by importer
            $str1 .= "{$val[3]}, "; // add field name
            $item=(array_key_exists($val[0],$row))? $row[$val[0]]:""; // trick to avoid empty fields at the end of row
            if ($key==='Grade') $item=parseGrade($item);
            if ($key==='Category') $item=parseCategory($item,$this->federation);
            if ($key==='Gender') $item=parseGender($item);
            if ($key==='CatHandler') $item=parseHandlerCat($item);
            if ($this->isInternational) {
                // in international contests when no club store country as club value
                // retrieve index for country field
                $idx=$this->fieldList['Country'][0];
                if ( ($key==='Club') && ($item=="") ) $item=$row[$idx];;
            }
            if ($item instanceof DateTime) $item=$item->format('U'); // stupid Spout bug
            if (is_object($item)) {
                $this->myLogger->error("Unexpected objet found at index: {$index}");
                $this->myLogger->error("Row: ".json_encode($row));
            }
            switch ($val[2]) {
                case "s": // string
                    $a=$this->myDBObject->conn->real_escape_string(trim($item));
                    $str2 .= " '{$a}', ";
                    break;
                case "i":
                    // take care on boolean-as-integer case
                    if (is_numeric($item)) $a=intval($item);
                    else $a=(toBoolean($item))?1:0;
                    $str2 .= " {$a}, "; // integer
                    break;
                case "b":
                    $a=(toBoolean($item))?1:0;
                    $str2 .= " {$a}, "; // boolean as 1/0
                    break;
                case "f":
                    $a=floatval($item);
                    $str2 .= " {$a}, "; // float
                    break;
                default:
                    // escape to avoid sql injection issues
                    $a=$this->myDBObject->conn->real_escape_string(trim($item));
                    $str2 .= " {$a}, ";
            }
        }
        $str ="$str1 $str2 {$index} );"; // compose insert string
        $res=$this->myDBObject->query($str);
        if (!$res) {
            $error=$this->myDBObject->conn->error;
            throw new Exception("{$this->name}::populateTable(perros): Error inserting row $index ".json_encode($row)."\n$error");
        }
        $this->myLogger->leave();
        return 0;
    }

    protected function dropTable() {
        // To create database we need root DB access
        $rconn=DBConnection::getRootConnection();
        if ($rconn->connect_error)
            throw new Exception("Cannot perform import process: database::dbConnect()");
        $str="DROP Table IF EXISTS {$this->tablename};";
        $res=$rconn->query($str);
        if (!$res) {
            $error=$rconn->error;
            $str="{$this->name}::dropTable(): Error deleting temporary table: '$error'";
            $this->myLogger->error($str);
            throw new Exception($str);
        }
        return 0;
    }

    // stupid spout that has no sheet /row count function
    protected function sheetCount($reader) {
        $count=0;
        foreach ($reader->getSheetIterator() as $sheet) $count++;
        return $count;
    }

    // seems that xlsx reader parse also empty rows, so this is a dirty hack to take care on
    protected function isEmptyRow($index,$row) {
        // $nitems=count($row);
        // $data=json_encode($row);
        // $this->myLogger->debug("Line:$index count:$nitems data:$data");
        foreach($row as $cell) if ($cell!="") return false;
        return true;
    }

    public function validateFile( $filename,$droptable=true) {
        $this->myLogger->enter();
        // Latests versions can also extract info from dog catalog and inscriptions pdf files,
        // so need an intermediate file name to handle both formats
        $newname=$filename;
        $this->saveStatus("Validating received file...");
        // @unlink(IMPORT_DIR."import_{$this->myOptions['Suffix']}.log");
        // open temporary file
        $ok=false;
        try {
            $reader = ReaderFactory::create(Type::XLSX);
            $reader->open($newname);
            $ok=true;
        } catch (Exception $e) {
            $this->myLogger->notice("Cannot open file {$newname} as .xlsx Excel. Trying pdf");
        }
        if ($ok===false) {
            $newname=str_replace(".xlsx",".pdf",$filename);
            try {
                @rename($filename,$newname);
                $reader = ReaderFactory::create(Type::PDF);
                $reader->open($newname);
            } catch (Exception $e) {
                $this->myLogger->error("Cannot open file {$newname} as .pdf PortableDocumentFormat. Aborting import");
                $this->saveStatus("Read Excel Done.");
                return 0;
            }
        }
        // if there are only one sheet assume it is what we are looking for
        $found=false;
        $sheet=null;
        if ($this->sheetCount($reader)>1) {
            // else look for a sheet named _("Dogs") or _("Inscriptions")
            foreach ($reader->getSheetIterator() as $sheet) {
                $name = $sheet->getName();
                $this->myLogger->trace("analizyng sheet name: $name");
                foreach($this->validPageNames as $pname) {
                    $this->myLogger->trace("analizyng sheet name: '$name' searching for '$pname'");
                    if ( ($name!=$pname) && ($name!=_($pname)) ) continue;
                    $found=true; break;
                }
                if ($found) break;
            }
        } else {
            // getCurrentSheet() is not available for reader. so dirty trick
            // $sheet=$reader->getCurrentSheet();
            foreach ($reader->getSheetIterator() as $sheet) {
                $found=true;
                break; // just break at one and only sheet
            }
        }

        // arriving here means requested page ("dogs","inscriptions", etc ) not found
        if (!$found) throw new Exception ("No valid sheet name found in excel file");

        // OK: now parse sheet
        $index=0; // parsed line. Used as ID key in temporary table
        $hasHeader=false; // parse status 0:var 2:data
        $timeout=ini_get('max_execution_time');
        foreach ($sheet->getRowIterator() as $row) {
            // count the number of non-empty cells in this row
            for($nitems=0,$n=0;$n<count($row);$n++) if ($row[$n]!=="") $nitems++;
            // handle data according number of items
            // notice cannot use switch inside foreach, as break breaks loop
            if($nitems===0) continue; // empty row: skip
            if($nitems===1) continue; // just label: skip
            if($nitems===2) { // single value variable
                $this->excelVars[$row[0]]=$row[1];
                $this->myLogger->trace("Adding Excel Variable: {$row[0]} => {$row[1]}");
                continue;
            }
            if($nitems===3) { // double value variable
                $this->excelVars[$row[0]]=array($row[1],$row[2]);
                $this->myLogger->trace("Adding Excel Variable: {$row[0]} => [ {$row[1]} , {$row[2]} ]");
                continue;
            }
            if($nitems===4) { // triple value variable
                $this->excelVars[$row[0]]=array($row[1],$row[2],$row[3]);
                $this->myLogger->trace("Adding Excel Variable: {$row[0]} => [ {$row[1]} , {$row[2]} , {$row[3]} ]");
                continue;
            }
            if (!$hasHeader) { // first full-filled row contains header
                // validate header and create table
                $this->myLogger->trace("parsing header: ".json_encode($row));
                // check that every required field is present
                $this->validateHeader($row); // throw exception on fail
                // create temporary table in database to store and analyze Excel data
                if ($droptable===true) $this->dropTable();
                $this->createTemporaryTable(); // throw exception when an import is already running
                $hasHeader=true; // on next iteration start store into temporary table
            } else {
                // dump excel data into temporary database table
                set_time_limit($timeout); // avoid php to be killed on very slow systems
                $this->saveStatus("Parsing excel row #$index");
                $this->import_storeExcelRowIntoDB($index,$row);
            }
            // arriving here means header parsed and/or data processed. So mark status and repeat loop
            $index++; // start on ID 1 to store into temporary table
        }
        $this->saveStatus("Read Excel Done.");
        // fine. we can start parsing data in DB database table
        $reader->close();
        @unlink($newname); // remove temporary file if no named file provided
        // save variables imported from excel and exit
        $this->saveExcelVars();
        $this->myLogger->leave();
        return 0;
    }

    /**
     * routine to mix db data and excel data in blind mode
     * @param $dbdata
     * @param $filedata
     * @return string
     */
    private function import_mixData($dbdata,$filedata,$ucase=true) {
        $uppercase=intval($this->myOptions['WordUpperCase']);
        $dbpriority=intval($this->myOptions['DBPriority']);
        $ignorewhitespaces=intval($this->myOptions['IgnoreWhiteSpaces']);
        // dbdata is already escaped, so do only on $filedata
        // $filedata=$this->myDBObject->conn->real_escape_string($filedata);
        // handle word uppercase
        if($ucase && ($uppercase!=0) ) {
            $dbdata=toUpperCaseWords($dbdata);
            $filedata=toUpperCaseWords($filedata);
        }
        // take care on precedence and empty fields
        if($dbpriority!=0) {
            // database has priority
            if ($ignorewhitespaces!=0) return $dbdata;
            if ($dbdata=="") return $filedata; // no dbdata, try to use excel
            if ($dbdata=="-") return $filedata; // unknown dbdata, try to use excel
            return $dbdata;
        } else {
            // excel file has priority
            if ($ignorewhitespaces!=0) return $filedata;
            if ($filedata=="") return $dbdata; // no file data, try to use dbdata
            if ($filedata=="-") return $dbdata; // unknown file data, try to use dbdata
            return $filedata;
        }
    }

    protected function findAndSetClub($item) {
        $this->myLogger->enter();
        $a=$this->myDBObject->conn->real_escape_string($item['NombreClub']);
        $old=$a;
        // in international contest, Club field should contain country name
        // but excel file can also provide "Country" field. So check for both
        if ($this->isInternational) {
            // country field takes precedence if exists
            if (array_key_exists('Country',$item)) $a=$item['Pais'];
            if (array_key_exists(_utf('Country'),$item)) $a=$item['Pais'];
            // if field comes in 3Char ISO convention replace with country name
            if (array_key_exists($a,Country::$countryList) ) $a=Country::$countryList[$a];
            $this->saveStatus("Analyzing country '$a'");
        } else {
            $this->saveStatus("Analyzing club '$a'");
        }
        // our database stores countries as clubs for international contest, so we can now make normal query for club search
        // remember that "Blind" mode looks for exact match
        if ($this->myOptions['Blind']==0) $search=$this->myDBObject->__select("*","clubes","( Nombre LIKE '%$a%') OR (NombreLargo LIKE '%$a%')","","");
        else                     $search=$this->myDBObject->__select("*","clubes","( Nombre = '$a') OR (NombreLargo = '$a')","","");
        if ( !is_array($search) ) return "findAndSetClub(): Invalid search term: '$a'"; // invalid search. mark error

        // to create clubs additional info is needed, so cannot auto-create in blind mode
        if ($search['total']==0) return false; // no search found ask user to select or create
        if ($search['total']>1) return $search; // more than 1 compatible item found. Ask user to choose
        if ($search['rows'][0]['Federations'] & (1<<($this->federation)) == 0 ) return $search; // federation missmatch. ask user to fix

        // arriving here means match found. So replace all instances with found data and return to continue import
        $t=TABLE_NAME;
        $i=$search['rows'][0]['ID']; // Club ID
        $nombre=$this->myDBObject->conn->real_escape_string($search['rows'][0]['Nombre']);
        // fix Club name according importing rules
        $nombre=$this->import_mixData($nombre,$a);
        $str="UPDATE $t SET ClubID=$i, NombreClub='$nombre' WHERE (NombreClub = '$old')";
        $res=$this->myDBObject->query($str);
        if (!$res) return "findAndSetClub(): update club '$a' error:".$this->myDBObject->conn->error; // invalid search. mark error
        $this->myLogger->leave();
        return true; // tell parent item found. proceed with next
    }

    protected function findAndSetHandler($item) {
        $this->myLogger->enter();
        $t=TABLE_NAME;
        $c=$item['ClubID'];
        $cat=$item['CatGuia'];
        // notice that arriving here means all clubs has been parsed and analyzed
        $a=$this->myDBObject->conn->real_escape_string($item['NombreGuia']);
        $this->saveStatus("Analyzing handler '$a'");
        $f=$this->federation;
        if ($this->myOptions['Blind']==0)
                $search=$this->myDBObject->__select("*","guias","( Nombre LIKE '%$a%' ) AND ( Federation = $f ) ","","");
        else    $search=$this->myDBObject->__select("*","guias","( Nombre = '$a' ) AND ( Federation = $f ) ","","");
        if ( !is_array($search) ) return "findAndSetHandler(): Invalid search term: '$a'"; // invalid search. mark error
        // parse found entries looking for match

        // POSSIBLE BUG: if in a club exists two handlers named, ie: "Pedro" and "Pedro Perez",
        // interactive import will match and process first entry found in DB ( as of "LIKE '%$a%' search )
        // so make sure database is consistent :-)
        for ($index=0;$index<$search['total'];$index++) {
            // find right entry. if not found ask user
            if ($search['rows'][$index]['Club']!=$item['ClubID']) continue;

            // arriving here means handler and club matches. so process

            // Replace all instances in tmptable with found data and return to continue import
            $id=$search['rows'][$index]['ID']; // id del guia en la base de datos
            $nombre= $this->myDBObject->conn->real_escape_string($search['rows'][$index]['Nombre']); // nombre del guia (DB)
            $dbcat=$search['rows'][$index]['Categoria'];
            // handle name according excelname rules
            $exname=$this->myDBObject->conn->real_escape_string($item['NombreGuia']);
            $nombre=($this->myOptions['UseExcelNames']==0)?$nombre:$exname;
            if ($this->myOptions['WordUpperCase']!=0) $nombre=toUpperCaseWords($nombre);
            $cat=$this->import_mixData($dbcat,$cat);
            // fix handler's name in temporary table according importing rules
            $str="UPDATE $t SET HandlerID=$id, NombreGuia='$nombre', CatGuia='{$cat}' WHERE (NombreGuia = '$a')  AND (ClubID=$c)"; // exact match
            $res=$this->myDBObject->query($str);
            if (!$res) return "findAndSetHandler(): update guia '$a' error:".$this->myDBObject->conn->error; // invalid update; mark error
            return true; // tell parent item found. proceed with next
        }
        // in interactive mode, when no entries, or no matches or cannot decide ask user
        if ($this->myOptions['Blind']==0) return ($search['total']==0)? false /* no entries or no match */ : $search /* cannot decide */;

        // arriving here means blind mode and item not found or not exact match
        // so blindly create new item with data from excel
        $nombre=$a;
        if ($this->myOptions['WordUpperCase']!=0) $nombre=toUpperCaseWords($a);
        $str="INSERT INTO guias (Nombre,Categoria,Club,Federation) VALUES ( '{$nombre}','{$cat}',{$c},{$f})";
        $res=$this->myDBObject->query($str);
        if (!$res) return "findAndSetHandler(): blindInsertGuia '$a' error:".$this->myDBObject->conn->error;
        $id=$this->myDBObject->conn->insert_id; // retrieve insertID and update temporary table
        $this->myDBObject->setServerID("guias",$id); // on server add ServerID
        $str="UPDATE {$t} SET HandlerID={$id}, NombreGuia='{$nombre}', CatGuia='{$cat}' WHERE (NombreGuia = '{$a}') AND (ClubID={$c})";
        $res=$this->myDBObject->query($str);
        if (!$res) return "findAndSetHandler(): update guia '{$a}' error:".$this->myDBObject->conn->error; // invalid update; mark error
        $this->myLogger->leave();
        return true; // tell parent item found (created). proceed with next
    }

    protected function findAndSetDog($item) {
        $this->myLogger->enter();
        $t=TABLE_NAME;
        $h=$item['HandlerID'];
        // notice that arriving here means all clubs and handlers has been parsed and analyzed
        // TODO: search and handle also dog's long (pedigree) name
        $a=$this->myDBObject->conn->real_escape_string($item['Nombre']);
        $aa="";
        if(isset($item['NombreLargo'])) {
            $aa=$this->myDBObject->conn->real_escape_string($item['NombreLargo']);
        }
        $this->saveStatus("Analyzing dog '$a'");
        $f=$this->federation;
        if ($this->myOptions['Blind']==0)
             $search=$this->myDBObject->__select("*","perros","(( Nombre LIKE '%$a%') OR ( NombreLargo LIKE '%$a%')) AND ( Federation = $f ) ","","");
        else $search=$this->myDBObject->__select("*","perros","(( Nombre = '$a') OR ( NombreLargo = '$a')) AND ( Federation = $f ) ","","");
        if ( !is_array($search) ) return "findAndSetDog(): Invalid search term: '$a'"; // invalid search. mark error

        // parse found entries looking for match
        for ($index=0;$index<$search['total'];$index++) {
            // find right entry. if not found iterate on next entry from query
            if ($search['rows'][$index]['Guia']!=$item['HandlerID']) continue;

            // arriving here means match found. So replace all instances with found data and return to continue import
            $i=$search['rows'][$index]['ID']; // id del perro
            $nombre=$this->myDBObject->conn->real_escape_string($search['rows'][$index]['Nombre']); // nombre del perro
            $nlargo=$this->myDBObject->conn->real_escape_string($search['rows'][$index]['NombreLargo']); // nombre largo
            $raza=$this->myDBObject->conn->real_escape_string($search['rows'][$index]['Raza']); // raza
            $chip=$this->myDBObject->conn->real_escape_string($search['rows'][$index]['Chip']); // microchip
            $lic=$this->myDBObject->conn->real_escape_string($search['rows'][$index]['Licencia']); // licencia
            $loe=$this->myDBObject->conn->real_escape_string($search['rows'][$index]['LOE_RRC']); // LOE /RRC
            $cat=$this->myDBObject->conn->real_escape_string($search['rows'][$index]['Categoria']); // licencia
            $grad=$this->myDBObject->conn->real_escape_string($search['rows'][$index]['Grado']); // licencia
            $sex=$this->myDBObject->conn->real_escape_string($search['rows'][$index]['Genero']); // sexo

            // rework data according translate rules

            // handle name according excelname rules

            $exname=$this->myDBObject->conn->real_escape_string($item['Nombre']);
            $nombre=($this->myOptions['UseExcelNames']==0)?$nombre:$exname;
            if ($this->myOptions['WordUpperCase']!=0) $nombre=toUpperCaseWords($nombre);
            // remaining  data are handler according import rules
            $nlargo=$this->import_mixData($nlargo,$aa); // $aa already set at start of method
            $raza=$this->import_mixData($raza,isset($item['Raza'])?$item['Raza']:"");
            $chip=$this->import_mixData($chip,isset($item['Chip'])?$item['Chip']:"");
            $lic=$this->import_mixData($lic,isset($item['Licencia'])?$item['Licencia']:"",false);
            $lic=normalize_license($lic);
            $loe=$this->import_mixData($loe,isset($item['LOE_RRC'])?$item['LOE_RRC']:"",false);
            $cat=$this->import_mixData($cat,$item['Categoria'],false);
            $grad=$this->import_mixData($grad,$item['Grado'],false);
            $sex=$this->import_mixData($sex,$item['Genero'],false);

            // and finally update temporary table with evaluated data
            $str="UPDATE $t SET DogID=$i,Nombre='$nombre',NombreLargo='$nlargo',Genero='$sex',Raza='$raza',".
                "Chip='$chip',Licencia='$lic',LOE_RRC='$loe', Categoria='$cat', Grado='$grad'".
                "WHERE (Nombre = '$a')  AND (HandlerID=$h)";
            $res=$this->myDBObject->query($str);
            if (!$res) return "findAndSetDog(): update dog '$a' error:".$this->myDBObject->conn->error; // invalid search. mark error

            // tell parent item found. proceed with next$search['rows'][$index]['Nombre']
            return true;
        }

        // in interactive mode, when no entries, or no matches or cannot decide ask user
        if ($this->myOptions['Blind']==0) return ($search['total']==0)? false /* no entries or no match */ : $search /* cannot decide */;

        // in blind mode, create dog and update temporary table
        $c=$item['Categoria'];
        $g=$item['Grado'];
        $s=$item['Genero'];
        $chip=isset($item['Chip'])?$this->myDBObject->conn->real_escape_string($item['Chip']):"";
        $lic=isset($item['Licencia'])?$this->myDBObject->conn->real_escape_string($item['Licencia']):"";
        $lic=normalize_license($lic);
        $loe=isset($item['LOE_RRC'])?$this->myDBObject->conn->real_escape_string($item['LOE_RRC']):"";
        $raza=isset($item['Raza'])?$this->myDBObject->conn->real_escape_string($item['Raza']):"";
        $nlargo=isset($item['NombreLargo'])?$this->myDBObject->conn->real_escape_string($item['NombreLargo']):"";
        $nombre=$a;
        // check precedence on DB or Excel
        if ($this->myOptions['WordUpperCase']!=0) { // formato mayuscula inicial
            $nombre= toUpperCaseWords($nombre);
            $raza= toUpperCaseWords($raza);
            $nlargo= toUpperCaseWords($nlargo);
        }
        $str="INSERT INTO perros (Nombre,NombreLargo,LOE_RRC,Guia,Categoria,Grado,Raza,Chip,Licencia,Genero,Federation)".
            " VALUES ( '$nombre','$nlargo','$loe',$h,'$c','$g','$raza','$chip','$lic','$s',$f)";
        $res=$this->myDBObject->query($str);
        if (!$res) return "findAndSetDog(): blindInsertDog '$a' error:".$this->myDBObject->conn->error;
        // retrieve insertID and update temporary table.
        $id=$this->myDBObject->conn->insert_id;
        if ($lic!=="") $this->myDBObject->setServerID("perros",$id); // on master server also set ServerID
        // Notice that some items may alread be set, so a bit redundant ( just only really need insert id )
        $str="UPDATE $t SET DogID=$id, Nombre='$nombre',LOE_RRC='$loe',Raza='$raza',Chip='$chip',Licencia='$lic',NombreLargo='$nlargo' ".
            "WHERE (Nombre = '$a') AND (HandlerID=$h)";
        $res=$this->myDBObject->query($str);
        if (!$res) return "findAndSetDog(): update guia '$a' error:".$this->myDBObject->conn->error; // invalid update; mark error
        $this->myLogger->leave();
        return true; // tell parent item found (created). proceed with next
    }

    /**
     * @return {array} data to be evaluated
     */
    public function parse() {
        $this->myLogger->enter();
        $res=$this->myDBObject->__select(
            /* SELECT */ "*",
            /* FROM   */ TABLE_NAME,
            /* WHERE  */ "( ClubID = 0) || ( HandlerID = 0 ) || ( DogID = 0 )",
            /* ORDER BY */ "DogID ASC, HandlerID ASC, ClubID ASC",
            /* LIMIT */  ""
        );
        foreach ($res['rows'] as $item ) {
            $found=null;
            // if club==0 try to locate club ID. on fail ask user
            if ($item['ClubID']==0) $found=$this->findAndSetClub($item);
            // if handler== 0 try to locate handler ID. on fail or missmatch ask user
            else if ($item['HandlerID']==0) $found=$this->findAndSetHandler($item);
            // if dog == 0 try to locate dog ID. On fail or misssmatch ask user
            else $found=$this->findAndSetDog($item);
            if (is_string($found)) throw new Exception("import parseDog: $found");
            if (is_bool($found)) {
                if ($found===true) // item found and match: notify and return
                    return array('operation'=> 'parse', 'success'=> 'ok', 'search' => $item, 'found' => array());
                else // item not found: create a default item
                    return array('operation'=> 'parse', 'success'=> 'fail', 'search' => $item, 'found' => array());
            }
            // nultiple matching items found: ask
            return array('operation'=> 'parse', 'success'=> 'fail', 'search' => $item, 'found' => $found['rows']);
        }
        // arriving here means no more items to analyze. So tell user to proccedd with import
        $this->myLogger->leave();
        return array('operation'=> 'parse', 'success'=> 'done');
    }

    public function createEntry($options) {
        $this->myLogger->enter();
        // update existing entry from database
        $t=TABLE_NAME;
        $f=$this->federation;
        // locate entry in database
        $obj=$this->myDBObject->__selectObject("*",TABLE_NAME,"ID={$options['ExcelID']}");
        if (!is_object($obj)) {
            // Temporary table id not found. notify error and return
            return "CreateEntry(): Temporary table RowID:{$options['ExcelID']} not found  error:".$this->myDBObject->conn->error;
        }
        // add a new entry into database
        if ($options['Object']=="Club") {
            // this is an error: clubs cannot be created on the fly, as need extra parameters
            return "CreateEntry(): cannot automagically create new club {$obj->Nombre}";
        } else if ($options['Object']=="Guia") {
            $nombre=$this->myDBObject->conn->real_escape_string($obj->NombreGuia);
            $c=$obj->ClubID;
            $cat=$obj->CatGuia;
            if ($this->myOptions['WordUpperCase']!=0) $nombre=toUpperCaseWords($nombre);
            $str="INSERT INTO guias (Nombre,Categoria,Club,Federation) VALUES ( '{$nombre}','{$cat}',{$c},{$f})";
            $res=$this->myDBObject->query($str);
            if (!$res) return "CreateEntry(): Insert Guia '{$obj->NombreGuia}' error:".$this->myDBObject->conn->error;
            $id=$this->myDBObject->conn->insert_id; // retrieve insertID and update temporary table
            $this->myDBObject->setServerID("guias",$id); // on master server set ServerID
            $str="UPDATE $t SET HandlerID=$id, NombreGuia='{$nombre}', CatGuia='{$cat}' WHERE (NombreGuia = '{$nombre}') AND (ClubID={$c})";
            $res=$this->myDBObject->query($str);
            if (!$res) return "CreateEnrty(): Temporary table update Guia '{$obj->NombreGuia}' error:".$this->myDBObject->conn->error; // invalid update; mark error
        } else if ($options['Object']=="Perro") {
            $c=$obj->Categoria;
            $g=$obj->Grado;
            $s=$obj->Genero;
            $loe=$this->myDBObject->conn->real_escape_string($obj->LOE_RRC);
            $raza=$this->myDBObject->conn->real_escape_string($obj->Raza);
            $lic=$this->myDBObject->conn->real_escape_string($obj->Licencia);
            $lic=normalize_license($lic);
            $chip=$this->myDBObject->conn->real_escape_string($obj->Chip);
            $nlargo=$this->myDBObject->conn->real_escape_string($obj->NombreLargo);
            $nombre=$this->myDBObject->conn->real_escape_string($obj->Nombre);
            $h=$obj->HandlerID;
            // check precedence on DB or Excel
            if ($this->myOptions['WordUpperCase']!=0) { // formato mayuscula inicial
                $nombre= toUpperCaseWords($nombre);
                $raza= toUpperCaseWords($raza);
                $nlargo= toUpperCaseWords($nlargo);
                $lic= strtoupper($lic);
            }
            $str="INSERT INTO perros (Nombre,NombreLargo,LOE_RRC,Guia,Categoria,Grado,Raza,Licencia,Chip,Genero,Federation)".
                " VALUES ( '$nombre','$nlargo','$loe',$h,'$c','$g','$raza','$lic','$chip','$s',$f)";
            $res=$this->myDBObject->query($str);
            if (!$res) return "CreateEntry(): InsertDog '$nombre' error:".$this->myDBObject->conn->error;
            $id=$this->myDBObject->conn->insert_id; // retrieve insertID
            if ($lic!=="") $this->myDBObject->setServerID("perros",$id); // on master server set ServerID
            // and update temporary table with processed data and add insertid.
            // Leave as is unchanged fields
            $str="UPDATE $t SET DogID=$id, Nombre='$nombre',Licencia='$lic',Raza='$raza',NombreLargo='$nlargo' ".
                "WHERE (Nombre = '$nombre') AND (HandlerID=$h)";
            $res=$this->myDBObject->query($str);
            if (!$res) return "Create(): temp table update dog '$nombre' error:".$this->myDBObject->conn->error; // invalid update; mark error
        } else {
            // invalid object: notice error and return
            return "CreateEntry(): Invalid Object to search for update in temporary table: {$options['Object']}";
        }
        // tell client to continue parse
        $this->myLogger->leave();
        return array('operation'=> 'create', 'success'=> 'done');
    }    
    
    public function updateEntry($options) {
        $this->myLogger->enter();
        // update existing entry from database
        $t=TABLE_NAME;
        // locate entry in database
        $obj=$this->myDBObject->__selectObject("*",TABLE_NAME,"ID={$options['ExcelID']}");
        if (!is_object($obj)) {
            // Temporary table id not found. notify error and return
            return "UpdateEntry(): Temporary table RowID:{$options['ExcelID']} not found  error:".$this->myDBObject->conn->error;
        }
        // delete every entry with matching name
        if ($options['Object']=="Club") {
            // obtenemos nombre del club tal y como figura en la base de datos
            $dbobj=$this->myDBObject->__selectObject("*","clubes","ID={$options['DatabaseID']}");
            // actualizamos nombre del club y club ID en todas las entradas de la tabla temporal
            // en que aparezca. para ello tenemos que hacer un update por nombre
            $dbname=$this->myDBObject->conn->real_escape_string($dbobj->Nombre);
            $name=$this->myDBObject->conn->real_escape_string($obj->NombreClub);
            $str="UPDATE $t SET ClubID={$dbobj->ID}, NombreClub='$dbname' WHERE (NombreClub = '$name')";
            $res=$this->myDBObject->query($str);
            if (!$res) return "UpdateEntry(): update club '$name' error:".$this->myDBObject->conn->error;
        }
        else if ($options['Object']=="Guia") {
            // obtenemos nombre del guia tal y como figura en la base de datos
            $dbobj=$this->myDBObject->__selectObject("*","guias","ID={$options['DatabaseID']}");
            // ajustamos el id y el nombre del guia en la tabla excel
            $dbname=$this->myDBObject->conn->real_escape_string($dbobj->Nombre);
            $name=$this->myDBObject->conn->real_escape_string($obj->NombreGuia);

            // handle name according excelname rules
            $n=($this->myOptions['UseExcelNames']==0)?$dbname:$name;
            if ($this->myOptions['WordUpperCase']!=0) $n=toUpperCaseWords($n);

            // update handle category according database and/or excel
            $dbcat=$dbobj->Categoria;
            $cat=$this->import_mixData($dbcat,isset($obj->CatGuia)?$obj->CatGuia:"A"); // adult if not defined

            // actualizamos nombre en la tabla temporal
            $str="UPDATE $t SET HandlerID={$dbobj->ID}, NombreGuia='$n', CatGuia='${cat}' WHERE (NombreGuia = '$name')";
            $res=$this->myDBObject->query($str);
            if (!$res) return "UpdateEntry(): update handler '$name' Set Name/ID error:".$this->myDBObject->conn->error;
            // ajustamos nombre del guia y el club en la base de datos
            $str="UPDATE guias SET Nombre='{$n}', Categoria='{$cat}', Club={$obj->ClubID} WHERE (ID={$dbobj->ID})";
            $res=$this->myDBObject->query($str);
            if (!$res) return "UpdateEntry(): update handler '$name' Set Club error:".$this->myDBObject->conn->error;
        }
        else if ($options['Object']=="Perro") {
            // obtenemos nombre del perro tal y como figura en la base de datos
            $dbobj=$this->myDBObject->__selectObject("*","perros","ID={$options['DatabaseID']}");

            // escapamos todos los textos para evitar problemas con las operaciones de la base de datos
            $tnombre=$this->myDBObject->conn->real_escape_string($obj->Nombre); // nombre del perro en tabla temporal
            $nombre=$this->myDBObject->conn->real_escape_string($dbobj->Nombre); // nombre del perro
            $tnlargo=$this->myDBObject->conn->real_escape_string($obj->NombreLargo); // nombre largo en tabla
            $nlargo=$this->myDBObject->conn->real_escape_string($dbobj->NombreLargo); // nombre largo en db
            $raza=$this->myDBObject->conn->real_escape_string($dbobj->Raza); // raza - db
            $traza=$this->myDBObject->conn->real_escape_string($obj->Raza); // raza - tabla
            $lic=$this->myDBObject->conn->real_escape_string($dbobj->Licencia); // licencia
            $chip=$this->myDBObject->conn->real_escape_string($dbobj->Chip); // licencia
            $loe=$this->myDBObject->conn->real_escape_string($dbobj->LOE_RRC); // LOE /RRC
            $cat=$this->myDBObject->conn->real_escape_string($dbobj->Categoria); // categoria
            $grad=$this->myDBObject->conn->real_escape_string($dbobj->Grado); // grado
            $sex=$this->myDBObject->conn->real_escape_string($dbobj->Genero); // sexo

            // evaluamos todos los parametros en funcion de los modos de imporatacion

            // handle name according excelname rules
            $nombre=($this->myOptions['UseExcelNames']==0)?$nombre:$tnombre;
            if ($this->myOptions['WordUpperCase']!=0) $nombre=toUpperCaseWords($nombre);
            // remaining  data are handler according import rules
            $nlargo=$this->import_mixData($nlargo,isset($obj->NombreLargo)?$tnlargo:"");
            $raza=$this->import_mixData($raza,isset($obj->Raza)?$traza:"");
            $lic=$this->import_mixData($lic,isset($obj->Licencia)?$obj->Licencia:"",false);
            $lic=normalize_license($lic);
            $chip=$this->import_mixData($chip,isset($obj->Chip)?$obj->Chip:"",false);
            $loe=$this->import_mixData($loe,isset($obj->LOE_RRC)?$obj->LOE_RRC:"",false);
            $cat=$this->import_mixData($cat,$obj->Categoria,false);
            $grad=$this->import_mixData($grad,$obj->Grado,false);
            $sex=$this->import_mixData($sex,$obj->Genero,false);

            // update temporary table with evaluated data
            $str="UPDATE $t SET DogID={$dbobj->ID},Nombre='$nombre',NombreLargo='$nlargo',Genero='$sex',Raza='$raza',".
                "Licencia='$lic',Chip='$chip',LOE_RRC='$loe', Categoria='$cat', Grado='$grad' ".
                "WHERE (Nombre = '{$tnombre}')  AND (HandlerID={$obj->HandlerID})";
            $res=$this->myDBObject->query($str);
            if (!$res) return "UpdateEntry(): update dog '{$obj->Nombre}' Set Dog Data error:".$this->myDBObject->conn->error;
            // notice that no need to update data in database, this is done in "import" phase
        }
        else {
            // invalid object: notice error and return
            return "UpdateEntry(): Invalid Object to search for update in temporary table: {$options['Object']}";
        }// tell client to continue parse
        $this->myLogger->leave();
        return array('operation'=> 'update', 'success'=> 'done');
    }

    public function ignoreEntry($options) {
        $this->myLogger->enter();
        $t=TABLE_NAME;
        // locate entry in temporary database
        $obj=$this->myDBObject->__selectObject("*",TABLE_NAME,"ID={$options['ExcelID']}");
        $perro=$this->myDBObject->conn->real_escape_string($obj->Nombre); // nombre del perro
        $guia=$this->myDBObject->conn->real_escape_string($obj->NombreGuia); // nombre del guia
        $club=$this->myDBObject->conn->real_escape_string($obj->NombreClub); // nombre del club
        if (!is_object($obj)) {
            // Temporary table id not found. notify error and return
            return "IgnoreEntry(): Temporary table RowID:{$options['ExcelID']} not found  error:".$this->myDBObject->conn->error;
        }
        if ($options['Object']=="Club") {
            $res=$this->myDBObject->__delete($t,"NombreClub = '{$club}'");
            if (!$res) return "IgnoreEntry(): Ignore Club '{$obj->NombreClub}' error:".$this->myDBObject->conn->error;
        }
        else if ($options['Object']=="Guia") {
            $res=$this->myDBObject->__delete($t,"NombreGuia = '{$guia}'");
            if (!$res) return "IgnoreEntry(): Ignore Handler '{$obj->NombreGuia}' error:".$this->myDBObject->conn->error;
        }
        else if ($options['Object']=="Perro") {
            $res=$this->myDBObject->__delete($t,"Nombre = '{$perro}'");
            if (!$res) return "IgnoreEntry(): Ignore Dog '{$obj->Nombre}' error:".$this->myDBObject->conn->error;
        }
        else {
            // invalid object: notice error and return
            return "IgnoreEntry(): Invalid Object to search in temporary table: {$options['Object']}";
        }
        // tell client to continue parse
        $this->myLogger->leave();
        return array('operation'=> 'ignore', 'success'=> 'done');
    }

    /**
     * When parse, analyze and mix is done, time to update database with final results
     * @return array|string
     */
    public function beginImport() {
        $this->myLogger->enter();
        $t=TABLE_NAME;
        $this->saveStatus("Analysis done. Updating database with final results");

        if ($this->myOptions['Blind']==0) { // do not update clubs in blind mode
            // import clubes data
            $this->saveStatus("Importing resulting clubs data");
            $str="UPDATE clubes INNER JOIN $t ON ( $t.ClubID = clubes.ID ) ".
                "SET clubes.Nombre = $t.NombreClub ";
            $res=$this->myDBObject->query($str);
            if (!$res) return "beginImport(clubes): update error:".$this->myDBObject->conn->error;
        }
        $this->myDBObject->fixServerID("clubes");

        // import handler data
        $this->saveStatus("Importing resulting handlers data");
        $str="UPDATE guias INNER JOIN $t ON ($t.HandlerID = guias.ID) ".
            "SET guias.Nombre = $t.NombreGuia ".
            ", guias.Club = $t.ClubID ";
        $res=$this->myDBObject->query($str);
        if (!$res) return "beginImport(handlers): update error:".$this->myDBObject->conn->error;
        $this->myDBObject->fixServerID("guias");

        // import dog data
        $this->saveStatus("Importing resulting dogs data");
        // $str="UPDATE perros INNER JOIN $t ON ($t.DogID = Perros.ID) AND ($t.HandlerID = Perros.Guia) ".
        $str="UPDATE perros INNER JOIN $t ON ($t.DogID = perros.ID) ".
            "SET perros.Nombre = $t.Nombre ".
            ", perros.NombreLargo = $t.NombreLargo ".
            ", perros.Raza = $t.Raza ".
            ", perros.Chip = $t.Chip ".
            ", perros.Genero = $t.Genero ".
            ", perros.LOE_RRC = $t.LOE_RRC ".
            ", perros.Licencia = $t.Licencia ".
            ", perros.Categoria = $t.Categoria ".
            ", perros.Grado = $t.Grado ".
            ", perros.Guia = $t.HandlerID ";
        $res=$this->myDBObject->query($str);
        if (!$res) return "{$this->name} beginImport(): update error:".$this->myDBObject->conn->error;
        // finalmente, si estamos en el master server se ajusta el server id
        // en aquellos perros que tienen licencia pero serverID=0
        $this->myDBObject->fixServerID("perros");

        $this->myLogger->leave();
        return array( 'operation'=>'import','success'=>'close');
    }

    public function endImport() {
        $this->saveStatus("Done.");
        return array( 'operation'=>'close','success'=>'ok');
    }

    public function cancelImport() {
        $this->saveStatus("Done.");
        return array( 'operation'=>'abort','success'=>'ok');
    }
}

