<?php
/*
 AuthManager.php

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
require_once (__DIR__."/../logging.php");
require_once (__DIR__."/../tools.php");
require_once (__DIR__."/Config.php");
require_once (__DIR__."/CertManager.php");
require_once (__DIR__."/SymmetricCipher.php");
require_once (__DIR__."/../database/classes/DBObject.php");
require_once (__DIR__."/../database/classes/Sesiones.php");
require_once (__DIR__."/../database/classes/Eventos.php");

// permisos de acceso
define ("PERMS_ROOT",0);
define ("PERMS_ADMIN",1);
define ("PERMS_OPERATOR",2);
define ("PERMS_ASSISTANT",3);
define ("PERMS_GUEST",4);
define ("PERMS_NONE",5);
define ("PERMS_CHRONO",6); // special case to handle electronic chrono hardware. should be revisited

class AuthManager {
	
	protected $myLogger;
	protected $myConfig;
	protected $mySessionKey=null;
	protected $level=PERMS_NONE;
	protected $operador=0;
	protected $mySessionMgr;
	protected $levelStr;
	protected $file;

	public function getSessionManager() { return $this->mySessionMgr; }

    /**
     * AuthManager constructor.
     * @param {string} $file name for logger
     * @throws Exception on failed to identify session key
     */
	function __construct($file="AuthManager") {
	    $this->file=$file;
		$this->myConfig=Config::getInstance();
		$this->myLogger=new Logger($file,$this->myConfig->getEnv("debug_level"));
		$this->mySessionMgr=new Sesiones("AuthManager");
        $this->levelStr=array( _('Root'),_('Admin'),_('Operator'),_('Assistant'),_('Guest'),_('None') );

		/* try to retrieve session token */
		$hdrs=getAllHeaders();
		// $this->myLogger->trace("headers are: ".json_encode($hdrs));
		if (!array_key_exists("X-Ac-Sessionkey",$hdrs)) { // look for X-Ac-Sessionkey header
			$this->myLogger->info("No sessionKey found in request");
			// no key found: assume anonymous login
			$this->level=PERMS_GUEST;
			return;
		}
		/* if found evaluate for expiration and level */
		$sk=$hdrs['X-Ac-Sessionkey'];
		$obj=$this->getUserByKey($sk);
		$this->myLogger->info("Username:{$obj->Login} Perms:{$obj->Perms}");
		$this->level=$obj->Perms;
		$this->mySessionKey=$sk;
		$this->operador=$obj->ID;
	}

    protected static $instance;

    /**
	 * @param {string} $name entry name for php execution ( given name provides first entry point )
     * @return static
	 * @throws Exception
     */
    final public static function getInstance($name) {
        return isset(static::$instance)
            ? static::$instance
            : static::$instance = new static($name);
    }

	/**
	 * Localiza al usuario que tiene la SessionKey indicada
	 * @param {string} $sk SessionKey
	 * @return object throw exception
	 * @throws Exception on invalid session key
	 */
	function getUserByKey($sk) {
	    $obj=$this->mySessionMgr->__selectObject(
			"*",
			"sesiones,usuarios",
			"(usuarios.ID=sesiones.Operador) AND ( SessionKey='$sk')"
		);
		if (!$obj) throw new Exception ("Invalid session key: '$sk'");
		$userid=intval($obj->Operador);
		$this->myLogger->info("file:'{$this->file}' SessionKey:'$sk' belongs to userid:'$userid'");
		// notice that in master server session key is assigned to user id 1 (-- Sin asignar --)
	/*	
		// if token expired throw exception 
		// TODO: write
		// $lastModified=$obj->LastModified;
		// else retrieve permission level
		$obj=$this->mySessionMgr->__getObject("usuarios",$userid);
		if (!$obj) throw new Exception("Provided SessionKey:'$sk' gives invalid User ID: '$userid'");
	*/
		return $obj;
	}

	function getSessionKey() { return $this->mySessionKey;	}

    /**
     * find club data that matches license info
     */
	function searchClub() {
		$club = $this->myConfig->getEnv("club");
		$lclub=strtolower($club);
        $lclub=str_replace("agility","",$lclub);
        $lclub=str_replace("club","",$lclub);
		// remove extra chars to properly make club string likeness evaluation
        $lclub=preg_replace("/[^A-Za-z0-9 ]/", '', $lclub);
		$dbobj=new DBObject("Auth::searchClub");
		$res=$dbobj->__select("*","clubes","1");
		$better=array(0,array('ID'=>0,'Nombre'=>'') ); // percentage, data
		for ($idx=0; $idx<$res['total']; $idx++) {
			$club=$res['rows'][$idx];
			$dclub=strtolower($club['Nombre']);
            $dclub=str_replace("agility","",$dclub);
            $dclub=str_replace("club","",$dclub);
			$dclub=preg_replace("/[^A-Za-z0-9 ]/", '', $dclub);
			if ($dclub==='') continue; // skip blank. should not occur
			similar_text ( $lclub ,$dclub, $p );
			if (bccomp($p,$better[0])<=0) continue; // el nuevo "se parece menos", skip
			$better[0]=$p; $better[1]=$res['rows'][$idx]; // el nuevo "se parece mas", almacena
		}
		return $better[1];
	}

	function lastLogin($key) {
		$str="usuarios.Login='{$key}'";
		if (is_integer($key)) $str="usuarios.ID={$key}";
		return $this->mySessionMgr->__select(
			"sesiones.*",
			"sesiones,usuarios",
			"( sesiones.Operador=usuarios.ID ) AND ({$str})",
			"sesiones.LastModified DESC",
			"1"
		);
	}

    /**
     * Authenticate user from database
     * On Login success create session and if needed send login event
     * @param {string} $login user name
     * @param {string} $password user password
     * @param {integer} $sid requested session id to join to
     * @param {boolean} $nossesion true: emit session event
     * @throws Exception if something goes wrong
     * @return {array} errorMessage or result data
     */
    function login($login,$password,$sid=0,$nosession=false) {
        if (inMasterServer($this->myConfig,$this->myLogger))
            return $this->certLogin($sid, $nosession);
        else return $this->dbLogin($login, $password, $sid, $nosession);
    }

    /**
     * Authenticate user from certificates
     * On Login success create session and if needed send login event
     * @param {string} $login user name
     * @param {boolean} $nossesion true: emit session event
     * @throws Exception if something goes wrong
     * @return {array} errorMessage or result data
     */
    private function certLogin($sid,$nosession) {
		$this->myLogger->enter();
        $cm=new CertManager();
        $res=$cm->hasValidCert();
        if ($res !== "")
        	throw new Exception( _("A valid Digital Certificate is required") ."<br/>&nbsp;<br/> ErrorMsg: $res" );
        // ok, valid certificate, so check ACL
		$login=$cm->checkCertACL(); // try to retrieve login name from Cert Access Control List
        if ( $login === "")
        	throw new Exception(_("Your provided certificate is not in access control list"));
		$this->myLogger->leave();
	    return $this->handleLogin($login,$sid,$nosession);
    }

    /*
     * Authenticate user from database
     *@throws Exception
     */
    private function dbLogin($login,$password,$sid,$nosession) {
        /* access database to check user credentials */
        $this->myLogger->enter();
        $obj = $this->mySessionMgr->__selectObject("*", "usuarios", "(Login='$login')");
        if (!$obj) throw new Exception("dbLogin: Unknown user: '$login'");
        $pw = $obj->Password;
        if (strstr('--UNDEF--', $pw) !== FALSE)
            throw new Exception("User has no password declared. Please use another account to fix this");
        else if (strstr('--LOCK--', $pw) !== FALSE)
            throw new Exception("Account '$login' is LOCKED");
        else if (strstr('--NULL--', $pw) === FALSE) { // --NULL-- means no password required
            // unencode stored password
            $pass = base64_decode($pw);
            if (!password_verify($password, $pass)) // check password against stored one
                throw new Exception("Login: invalid password for account '$login'");
        }
        /* Arriving here means login success */
		$this->myLogger->leave();
        return $this->handleLogin($obj, $sid, $nosession);
    }

    /**
	 * Tasks to be performed when login is accepted
     * @param {string|obj} $obj login name (string)  or retrieved user data (object) from database
     * @param {integer } $sid SessionID to join to
     * @param {boolean } $nossession if true, do not create session event related info
     * @return array session data
     * @throws Exception
     */
    function handleLogin($user,$sid,$nosession) {
    	$obj=$user;
    	if (is_string($user)) {
            $obj = $this->mySessionMgr->__selectObject("*", "usuarios", "(Login='$user')");
            if (!$obj) throw new Exception("handleLogin: Unknown user: '$user'");
		}
		// get & store permission level
		$this->level=$obj->Perms;
		//create a random session key
		$sk=random_password(16);
		// compose data for a new session
		$data=array (
				// datos para el gestor de sesiones
				'Operador'	=>	$obj->ID,
				'SessionKey'=>  $sk,
				'Nombre' 	=> 	http_request("Nombre","s",""),
				'Prueba' 	=> 	http_request("Prueba","i",0),
				'Jornada'	=>	http_request("Jornada","i",0),
				'Manga'		=>	http_request("Manga","i",0),
				'Tanda'		=>	http_request("Tanda","i",0),
				'Perro'		=>	http_request("Perro","i",0),
				// informacion de respuesta a la peticion
				'UserID'	=>	$obj->ID,
				'Login' 	=> 	$obj->Login,
				'Password'	=>	'', // don't send back password :-)
				'Gecos'		=>	$obj->Gecos,
				'Phone'		=>	$obj->Phone,
				'Email'		=>	$obj->Email,
				'Perms'		=>	$obj->Perms,
				// required for event manager
				'Type'		=>  'init', /* ¿perhaps evtType should be 'login'¿ */
				'Source' 	=> 	http_request("Source","s","AuthManager"),
				'TimeStamp' => 	time() /* date('Y-m-d H:i:s') */

		);
		// if "nosession" is requested, just check password, do not create any session
		if ($nosession===true) {
			return $data;
        }
		// create/join to a session
		if ($sid<=0) { //  if session id is not defined, create a new session
			// remove all other console sessions from same user
			$this->mySessionMgr->__delete("sesiones","( Nombre='Console' ) AND ( Operador={$obj->ID} )");
			// insert new session
			$data['Nombre']="Console";
			$data['Comentario']=$obj->Login." - ".$obj->Gecos;
			$this->mySessionMgr->insert($data);
			// retrieve new session ID
			$data['SessionID']=$this->mySessionMgr->conn->insert_id;
		} else {
            // to join to a named session we need at least Assistant permission level
            $this->access(PERMS_ASSISTANT); // on fail throw exception
            $name=$data['Nombre'];
            unset($data['Nombre']); // to avoid override Session Name
            // TODO: check and alert on busy session ID
            // else join to declared session
            $data['SessionID'] = $sid;
            $this->mySessionMgr->update($sid, $data);
            $data['Nombre']=$name; // restore session name
        }
		// and fire 'init' event
		$evtMgr=new Eventos("AuthManager",($sid<=0)?1:$sid,$this);
		// genera informacion: usuario|consola/tablet|sesion|ipaddr
        $ipstr=str_replace(':',';',$_SERVER['REMOTE_ADDR']);
		$valuestr="{$obj->Login}:{$data['Nombre']}:{$data['SessionID']}:{$ipstr}";
		$event=array(
				// datos identificativos del evento
				"ID" => 0, 							// Event ID
				"Session" => ($sid<=0)?1:$sid, 		// Session (Ring) ID
				"TimeStamp" => $data['TimeStamp'],	// TimeStamp - event time
				"Type" => $data['Type'], 			// Event Type
				"Source" => $data['Source'],		// Event Source
				// datos asociados al contenido del evento
				"Pru" => $data['Prueba'],	// Prueba,
				"Jor" => $data['Jornada'],	// Jornada,
				"Mng" => $data['Manga'],	// Manga,
				"Tnd" => $data['Tanda'],	// Tanda,
				"Dog" => $data['Perro'],	// Perro,
				"Drs" => 0,					// Dorsal,
				"Hot" => 0,					// Celo,
				"Flt" => -1,				// Faltas,
				"Toc" => -1,				// Tocados,
				"Reh" => -1,				// Rehuses,
				"NPr" => -1,				// NoPresentado,
				"Eli" => -1,				// Eliminado,
				"Tim" => -1,				// Tiempo,
				// marca de tiempo en los eventos de crono
				"Value" => $valuestr		// Value
		);
		$evtMgr->putEvent($event);

		// That's all. Return generated result data
		// $this->myLogger->info(json_encode($data));
		$this->myLogger->leave();
		return $data;
	}
	
	function checkPassword($user,$pass) {
		return $this->login($user,$pass,0,true);	
	}

	function resetAdminPassword() {
        // allow only localhost access
        $white_list= array ("localhost","127.0.0.1","::1",$_SERVER['SERVER_ADDR'],"138.4.4.108");
		if (gethostname()==="agilitycontest-vm") array_push($white_list,$_SERVER['REMOTE_ADDR']);
        if (!in_array($_SERVER['REMOTE_ADDR'],$white_list)) {
            die("<p>Esta operacion debe ser realizada desde la consola del servidor</p>");
        }
		$p=base64_encode(password_hash("admin",PASSWORD_DEFAULT));
		$str="UPDATE usuarios SET Login='admin', Password='$p' ,Perms=1 WHERE (ID=3)"; //1:nobody 2:root 3:admin
		$this->mySessionMgr->query($str);
		return "";
	}

	/*
	 * closes current session
	 */
	function logout() {
	    if (!inMasterServer($this->myConfig,$this->myLogger)) {
            // remove console sessions for this
            $this->mySessionMgr->__delete("sesiones","( Nombre='Console' ) AND ( Operador={$this->operador} )");
        }
		// clear session key  on named sessions
		$str="UPDATE sesiones 
			SET SessionKey=NULL, Operador=1, Prueba=0, Jornada=0, Manga=0, Tanda=0 
			WHERE ( SessionKey='{$this->mySessionKey}' )";
		$this->mySessionMgr->query($str);
        return "";
	}
	
	/**
	 * change password for requested user ID
	 * @param {integer} $id user id to change password to
     * @param {string} $pass old password
     * @param {string} $sk session key
	 * @throws Exception on error
	 * @return string "" on success; else error message
	 */
	function setPassword($id,$pass,$sk) {
		$this->myLogger->enter();
		$u=$this->getUserByKey($sk);
		switch ($u->Perms) {
			case 5:
			case 4: throw new Exception("Guest accounts cannot change password");
				// no break needeed
			case 3:
                // no break
			case 2:	// comprobamos el user id
				if ($id!=$u->Operador) throw new Exception("User can only change their own password");
				// comprobamos que la contrasenya antigua es correcta
				$obj=$this->mySessionMgr->__selectObject("*","usuarios","(ID=$id)");
				if (!$obj) throw new Exception("SetPassword: Unknown userID: '$id'");
				$pw=$obj->Password;
				if (strstr('--LOCK--',$pw)!==FALSE)
					throw new Exception("Cuenta bloqueada. Solo puede desbloquearla un usuario administrador");
				if ( (strstr('--UNDEF--',$pw)!==FALSE) && (strstr('--NULL--',$pw)!==FALSE) ) {
					// unencode stored password
					$op=base64_decode($pw);
					if (!password_verify($pass,$op)) // check password against stored one
						throw new Exception("SetPassword: la contrase&ntilde;a anterior no es v&aacute;lida");
				}
				// no break
			case 1:
			case 0:
				// compare passwors
				$p1=http_request("NewPassword","s","");
				$p2=http_request("NewPassword2","s","");
				if ($p1!==$p2) throw new Exception("Las contrase&ntilde;as no coinciden");
				// and store it for requested user
				$p=base64_encode(password_hash($p1,PASSWORD_DEFAULT));
				$str="UPDATE usuarios SET Password='$p' WHERE (ID=$id)";
				$res=$this->mySessionMgr->query($str);
				if ($res===FALSE) return $this->mySessionMgr->conn->error;
                $this->myLogger->leave();
				return "";
			default: throw new Exception("Internal error: invalid permission level");
		}
	}
	
	/*
	 * return permissions for provided session token
	 */
	function getPerms() { return $this->level; }
	
	function setPerms($level) { $this->level=$level; }
	
	/* 
	 * check level on current session token against required level
	 */
	function access($requiredlevel) {
		if ($requiredlevel==PERMS_CHRONO) {
			// TODO: Chrono operation requires specical id handling
			return true;
		}
		if ($requiredlevel>=$this->level) return true;
        $cur="{$this->level} - {$this->levelStr[intval($this->level)]}";
        $req="{$requiredlevel} - {$this->levelStr[intval($requiredlevel)]}";
        $str=_("Insufficient credentials").": {$cur}<br/>". _("Required level is").": {$req}";
		throw new Exception($str);
	}
}

?>
