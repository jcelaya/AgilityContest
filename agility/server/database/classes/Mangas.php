<?php
/*
Mangas.php

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

require_once("DBObject.php");

class Mangas extends DBObject {
	protected $jornadaObj;
	protected $pruebaObj;
	protected $defaultTeamObj;
	
	/* copia de la estructura de la base de datos, para ahorrar consultas */
	public static $tipo_manga= array(
		0 =>	array( 0, 'Nombre Manga largo',	'Grado corto',	'Nombre manga',	'Grado largo',  'IsAgility', 'Name'),
		1 =>	array( 1, 'Pre-Agility Round 1', 		'P.A.',	'PreAgility 1',	'Pre-Agility',  1,          'Pa1'),
		2 => 	array( 2, 'Pre-Agility Round 2', 		'P.A.',	'PreAgility 2',	'Pre-Agility',  2,          'Pa2'),
		3 =>	array( 3, 'Agility Grade I Round 1',	'GI',	'Agility-1 GI',	'Grade I',      1,          'Ag1'),
		4 => 	array( 4, 'Agility Grade I Round 2',	'GI',	'Agility-2 GI',	'Grade I',      2,          'Jp'),
		5 =>	array( 5, 'Agility Grade II', 			'GII',	'Agility GII',	'Grade II',     1,          'Ag'),
		6 =>	array( 6, 'Agility Grade III', 			'GIII',	'Agility GIII',	'Grade III',    1,          'Ag'),
		7 =>	array( 7, 'Agility', 	        		'-',	'Agility',		'Individual',   1,          'Ag'), // Open
		8 =>	array( 8, 'Agility Teams',			    '-',	'Ag. Teams',	'Teams',        1,          'AgT'), // team best
		9 =>	array( 9, 'Agility Teams'				,'-',	'Ag. Teams.',	'Teams',        1,          'AgT'), // team combined
		10 =>	array( 10,'Jumping Grade II',			'GII',	'Jumping GII',	'Grade II',     2,          'Jp'),
		11 =>	array( 11,'Jumping Grade III',			'GIII',	'Jumping GIII',	'Grade III',    2,          'Jp'),
		12 =>	array( 12,'Jumping',    				'-',	'Jumping',		'Individual',   2,          'Jp'), // Open
		13 =>	array( 13,'Jumping Teams'				,'-',   'Jmp Teams',	'Teams',        2,          'JpT'), // team best
		14 =>	array( 14,'Jumping Teams'				,'-',  	'Jmp Teams',	'Teams',        2,          'JpT'), // team combined
		15 =>	array( 15,'K.O. First round',			'-',	'K.O. Round 1',	'K.O. R1',      1,          'KO1'),
		16 =>	array( 16,'Special Round', 			    '-',	'Special Round','Individual',   1,          'Sp.'), // special round, no grades
		17 => 	array( 17,'Agility Grade I Round 3',	'GI',	'Agility-3 GI',	'Grade I',      3,          'Ag2'), // on RFEC special G1 3rd round
        // mangas extra para K.O.
        18 =>	array( 18,'K.O. Second round',			'-',	'K.O. Round 2',	'K.O. R2',      2,          'KO2'),
        19 =>	array( 19,'K.O. Third round',			'-',	'K.O. Round 3',	'K.O. R3',      3,          'KO3'),
        20 =>	array( 20,'K.O. Fourth round',			'-',	'K.O. Round 4',	'K.O. R4',      4,          'KO4'),
        21 =>	array( 21,'K.O. Fifth round',			'-',	'K.O. Round 5',	'K.O. R5',      5,          'KO5'),
        22 =>	array( 22,'K.O. Sixth round',			'-',	'K.O. Round 6',	'K.O. R6',      6,          'KO6'),
        23 =>	array( 23,'K.O. Seventh round',			'-',	'K.O. Round 7',	'K.O. R7',      7,          'KO7'),
        24 =>	array( 24,'K.O. Eight round',			'-',	'K.O. Round 8',	'K.O. R8',      8,          'KO8'),
        // mandas extras para wao
        25 =>	array( 25,'Agility A',			        '-',	'Agility A',	'Ag. A',        1,          'AgA'),
        26 =>	array( 26,'Agility B',			        '-',	'Agility B',	'Ag. B',        3,          'AgB'),
        27 =>	array( 27,'Jumping A',			        '-',	'Jumping A',	'Jp. A',        2,          'JpA'),
        28 =>	array( 28,'Jumping B',			        '-',	'Jumping B',	'Jp. B',        4,          'JpB'),
        29 =>	array( 29,'Snooker',			        '-',	'Snooker',	    'Snkr',         5,          'Snk'),
        30 =>	array( 30,'Gambler',			        '-',	'Gambler',	    'Gmblr',        6,          'Gmb'),
        31 =>	array( 31,'SpeedStakes',			    '-',	'SpeedStakes',	'SpdStk',       7,          'SpStk'), // single round
        // junior ( really should be a separate journey with every cats and grades, but people doesn't follow rules... )
        32 =>	array( 32,'Agility Junior',	            'Jr',	'Junior Ag',	    'Ag. Jr.',   1,          'AgJ'),
        33 => 	array( 33,'Jumping Junior',	            'Jr',	'Junior Jp',	    'Jp. Jr.',   2,          'JpJ'),
        34 =>	array( 34,'Agility Senior',	            'Sr',	'Senior Ag',	    'Ag. Sr.',   1,          'AgS'),
        35 => 	array( 35,'Jumping Senior',	            'Sr',	'Senior Jp',	    'Ag. Sr',    2,          'JpS'),
        36 =>	array( 36,'Agility Children',	        'Ch',	'Children Ag',	    'Ch. Ag',    1,          'AgI'),
        37 => 	array( 37,'Jumping Children',	        'Ch',	'Children Jp',	    'Ch. Jp',    2,          'JpI'),
        38 =>	array( 38,'Ag. ParaAgility',	        'Par',	'P.Agility Ag',	    'Par. Ag',   1,          'AgP'),
        39 => 	array( 30,'Jp. ParaAgility',	        'Par',	'P.Agility Jp',	    'Par. Jp',   2,          'JpP'),
	);

	public static function getTipoManga($tipo,$idx,$fed=null) {
	    if ($idx==6) return Mangas::$tipo_manga[$tipo][6]; // direct access to round abbreviature (dirty, but works)
        if (!$fed) return Mangas::$tipo_manga[$tipo][$idx];
        return $fed->getTipoManga($tipo,$idx);
    }
    public static function isAgility($tipo,$fed=null) {
	    return (Mangas::getTipoManga($tipo,5,$fed)==1)?true:false;
    }

	/* tabla para obtener facilmente la manga complementaria a una manga dada */
	public static $manga_hermana= array(
		array(0),	    /* 0,'','' No manga declared of type 0 */
        array(2),       /* 1, 'Pre-Agility Manga 1', 'P.A.' */ // notice that in 1 manga mode there is no sister
        array(1),       /* 2, 'Pre-Agility Manga 2', 'P.A.' */
        array(4,17),    /* 3, 'Agility Grado I Manga 1', 'GI' */
        array(3,17),    /* 4, 'Agility Grado I Manga 2', 'GI' */
        array(10),	    /* 5, 'Agility Grado II', 'GII' */
        array(11),	    /* 6, 'Agility Grado III', 'GIII' */
        array(12),	    /* 7, 'Agility Abierta', '-' */
        array(13),	    /* 8, 'Agility Equipos (3 mejores)', '-' */
        array(14),	    /* 9, 'Agility Equipos (Conjunta)', '-' */
        array(5),	    /* 10,'Jumping Grado II', 'GII' */
        array(6),	    /* 11,'Jumping Grado III', 'GIII' */
        array(7),	    /* 12,'Jumping Abierta', '-' */
        array(8),	    /* 13,'Jumping Equipos (3 mejores)', '-' */
        array(9),	    /* 14,'Jumping Equipos (Conjunta)', '-' */
        array(0),	    /* 15,'Ronda K.O.', '-' */
        array(0),	    /* 16,'Manga Especial', '-' */
        array(3,4),	    /* 17,'Agility Grado I Manga 3', 'GI' */
        /* mangas extra para K.O. */
        array(0),       /* 18 ,'K.O. Second round',	*/
        array(0),       /* 19 ,'K.O. Third round',*/
        array(0),       /* 20 ,'K.O. Fourth round',*/
        array(0),       /* 21 ,'K.O. Fifth round',*/
        array(0),       /* 22 ,'K.O. Sixth round',*/
        array(0),       /* 23 ,'K.O. Seventh round',*/
        array(0),       /* 24 ,'K.O. Eight round',*/
        /* mandas extras para wao */
        array(27,26,28,31),/* 25 ,'Agility A',	*/
        array(28,27,27,31),/* 26 ,'Agility B',	*/
        array(25,26,28,31),/* 27 ,'Jumping A',	*/
        array(26,25,27,31),/* 28 ,'Jumping B',	*/
        array(30),      /* 29 ,'Snooker',	*/
        array(29),      /* 30 ,'Gambler',	*/
        array(25,26,27,28),/* 31 ,'SpeedStakes',*/
        array(33),      /* 32, 'Junior Agility', 'Jr' */
        array(32),      /* 33, 'Junior Jumping', 'Jr' */
        array(35),      /* 34, 'Senior Agility', 'Sr' */
        array(34),       /* 35, 'Senior Jumping', 'Sr' */
        array(37),       /* 36, 'infantil Agility', 'Ch' */
        array(36),       /* 37, 'infantil Jumping', 'Ch' */
        array(39),       /* 38, 'ParaAgility Agility', 'Par' */
        array(38),       /* 39, 'ParaAgility Jumping', 'Par' */
	);
	
	public static $manga_modes= array (
		0 => array('Large','L'),
		1 => array('Medium','M'),
		2 => array('Small','S'),
		3 => array('Medium + Small','MS'),
		4 => array('Conjunta L+M+S','LMS'),
		5 => array('Tiny','T'),
		6 => array('Large + Medium','LM'),
		7 => array('Small + Tiny','ST'),
		8 => array('Conjunta L+M+S+T','LMST'),
        9 => array('Extra','X'),
        10 => array('Extra + Large','XL'),
        11 => array('Medium + Small + Tiny','MST'),
        12 => array('Conjunta X+L+M+S+T','XLMST')
	);

    public static function getMangaMode($mode,$idx,$fed=null) {
        if (!$fed) return Mangas::$manga_modes[$mode][$idx];
        return $fed->getMangaMode($mode,$idx);
    }

	/**
	 * Constructor
	 * @param {string} $file caller for this object
	 * @param {integer | object} $jornada jornada ID
	 * @throws Exception if cannot contact database or invalid jornada ID
	 */
	function __construct($file,$jornada) {
		parent::__construct($file);
		if (is_object($jornada)) {
		    $this->jornadaObj=$jornada;
        } else {
            if ($jornada<=0) {
                $this->errormsg="Manga::Construct invalid jornada ID";
                throw new Exception($this->errormsg);
            }
            $this->jornadaObj=$this->__selectObject("*","jornadas","(ID=$jornada)" );
        }
		$this->pruebaObj=$this->__selectObject("*","pruebas","(ID={$this->jornadaObj->Prueba})");
        $this->defaultTeamObj=$this->__selectObject("*","equipos","(Jornada={$this->jornadaObj->ID}) AND (DefaultTeam=1)");
	}
	
	/**
	 * inserta una manga en la jornada
	 * @param {integer} $tipo ID del tipo manga (tabla 'Mangas::Tipo_Manga')
	 * @param {string} $grado valor asociado al grado de la manga de la ID dada
	 * @return {string} empty on success, else error 
	 */
	function insert($tipo,$grado) {
		$this->myLogger->enter();
		$j=$this->jornadaObj->ID;
		// truco para cambiar al vuelo las mangas por equipos entre modalidades x-mejores y x-conjunta
        if ($tipo==8) $this->query("UPDATE mangas set Tipo=8 WHERE (Jornada=$j) AND (Tipo=9)");
        if ($tipo==9) $this->query("UPDATE mangas set Tipo=9 WHERE (Jornada=$j) AND (Tipo=8)");
        if ($tipo==13) $this->query("UPDATE mangas set Tipo=13 WHERE (Jornada=$j) AND (Tipo=14)");
        if ($tipo==14) $this->query("UPDATE mangas set Tipo=14 WHERE (Jornada=$j) AND (Tipo=13)");

		// si la manga existe no hacer nada; si no existe crear manga
        $sem=enterCriticalRegion(1);
        $res=$this->__select("*","mangas","( Jornada=$j ) AND  ( Tipo=$tipo ) AND ( Grado='$grado' )");
		if(!$res) return $this->error("Cannot get info on Mangas for Jornada:$j");
		if ($res['total']>0){
			$this->myLogger->info("Jornada:$j Manga:$tipo already exists");
			$mangaid=$res['rows'][0]['ID'];  // should exist only one. so take id from it
            leaveCriticalRegion($sem);
		} else {
            // buscamos el equipo por defecto de la jornada y lo insertamos
            $team=$this->defaultTeamObj->ID;
			$observaciones = http_request("Observaciones","s","");
			$str="INSERT INTO mangas ( Jornada,Tipo,Grado,Observaciones,Orden_Salida,Orden_Equipos ) VALUES ( $j,$tipo,'$grado','$observaciones','BEGIN,END','BEGIN,$team,END' )";
			$rs=$this->query($str);
            leaveCriticalRegion($sem);
			if (!$rs) return $this->error($this->conn->error);
			$mangaid=$this->conn->insert_id;
		}

        /* invocamos el modulo de competicion correspondiente para ver si hay que prefijar los datos de trs y trm */
        $comp=Competitions::getCompetition($this->pruebaObj,$this->jornadaObj);
        $cdata=$comp->presetTRSData($tipo); // retrieve course data info
        if (! $cdata) { // no change on trs info
            $this->myLogger->leave();
            return "";
        }
        $str="UPDATE mangas
				SET Recorrido={$cdata['Recorrido']},
				TRS_X_Tipo={$cdata['TRS_X_Tipo']},     TRS_L_Tipo={$cdata['TRS_L_Tipo']},     TRS_M_Tipo={$cdata['TRS_M_Tipo']},     TRS_S_Tipo={$cdata['TRS_S_Tipo']},     TRS_T_Tipo={$cdata['TRS_T_Tipo']},
				TRS_X_Factor={$cdata['TRS_X_Factor']}, TRS_L_Factor={$cdata['TRS_L_Factor']}, TRS_M_Factor={$cdata['TRS_M_Factor']}, TRS_S_Factor={$cdata['TRS_S_Factor']}, TRS_T_Factor={$cdata['TRS_T_Factor']},
				TRS_X_Unit='{$cdata['TRS_X_Unit']}',   TRS_L_Unit='{$cdata['TRS_L_Unit']}',   TRS_M_Unit='{$cdata['TRS_M_Unit']}',   TRS_S_Unit='{$cdata['TRS_S_Unit']}',   TRS_T_Unit='{$cdata['TRS_T_Unit']}',
				TRM_X_Tipo={$cdata['TRM_X_Tipo']},     TRM_L_Tipo={$cdata['TRM_L_Tipo']},     TRM_M_Tipo={$cdata['TRM_M_Tipo']},     TRM_S_Tipo={$cdata['TRM_S_Tipo']},     TRM_T_Tipo={$cdata['TRM_T_Tipo']},
				TRM_X_Factor={$cdata['TRM_X_Factor']}, TRM_L_Factor={$cdata['TRM_L_Factor']}, TRM_M_Factor={$cdata['TRM_M_Factor']}, TRM_S_Factor={$cdata['TRM_S_Factor']}, TRM_T_Factor={$cdata['TRM_T_Factor']},
				TRM_X_Unit='{$cdata['TRM_X_Unit']}',   TRM_L_Unit='{$cdata['TRM_L_Unit']}',   TRM_M_Unit='{$cdata['TRM_M_Unit']}',   TRM_S_Unit='{$cdata['TRM_S_Unit']}',   TRM_T_Unit='{$cdata['TRM_T_Unit']}'
				WHERE ( ID=$mangaid )";
        $rs=$this->query($str);
        if (!$rs) return $this->error($this->conn->error);

        // thats all folks
        $this->myLogger->leave();
        return "";
	}
	
	function update($mangaid) {
		$this->myLogger->enter();
		if ($mangaid <=0) return $this->error("Invalid Manga ID");
        assertClosedJourney($this->jornadaObj); // throw exception on closed journeys
		// retrieve http request variables
		/*
		 * ID		(PRIMARY KEY)
		* Jornada	(no debe ser modificada)
		* Tipo 	(no debe ser modificada)
		* Recorrido
		* Dist_L Obst_L Dist_M Obst_M Dist_S Obst_S
		* TRS_L_Tipo TRS_L_Factor TRS_L_Unit TRM_L_Tipo TRM_L_Factor TRM_L_Unit
		* TRS_M_Tipo TRS_M_Factor TRS_M_Unit TRM_M_Tipo TRM_M_Factor TRM_M_Unit
		* TRS_S_Tipo TRS_S_Factor TRS_S_Unit TRM_S_Tipo TRM_S_Factor TRM_S_Unit
		* Juez1 Juez2
		* Observaciones
		* Orden_Salida (se modifica en otro sitio)
		*/
		$id			= $mangaid;
		$recorrido	= http_request("Recorrido","i",0);
		$tipo	= http_request("Tipo","i",0);
		// distancias
        $dist_x = http_request("Dist_X","i",0);
        $dist_l = http_request("Dist_L","i",0);
		$dist_m = http_request("Dist_M","i",0);
		$dist_s = http_request("Dist_S","i",0);
		$dist_t = http_request("Dist_T","i",0);
		// obstaculos
        $obst_x = http_request("Obst_X","i",0);
        $obst_l = http_request("Obst_L","i",0);
		$obst_m = http_request("Obst_M","i",0);
		$obst_s = http_request("Obst_S","i",0);
		$obst_t = http_request("Obst_T","i",0);
		// tipo TRS
        $trs_x_tipo = http_request("TRS_X_Tipo","i",0);
        $trs_l_tipo = http_request("TRS_L_Tipo","i",0);
		$trs_m_tipo = http_request("TRS_M_Tipo","i",0);
		$trs_s_tipo = http_request("TRS_S_Tipo","i",0);
		$trs_t_tipo = http_request("TRS_T_Tipo","i",0);
		// tipo TRM
        $trm_x_tipo = http_request("TRM_X_Tipo","i",1);
        $trm_l_tipo = http_request("TRM_L_Tipo","i",1);
		$trm_m_tipo = http_request("TRM_M_Tipo","i",1);
		$trm_s_tipo = http_request("TRM_S_Tipo","i",1);
		$trm_t_tipo = http_request("TRM_T_Tipo","i",1);
		// factor TRS
        $trs_x_factor = http_request("TRS_X_Factor","f",0.0);
        $trs_l_factor = http_request("TRS_L_Factor","f",0.0);
		$trs_m_factor = http_request("TRS_M_Factor","f",0.0);
		$trs_s_factor = http_request("TRS_S_Factor","f",0.0);
		$trs_t_factor = http_request("TRS_T_Factor","f",0.0);
		// factor TRM
        $trm_x_factor = http_request("TRM_X_Factor","f",50.0);
        $trm_l_factor = http_request("TRM_L_Factor","f",50.0);
		$trm_m_factor = http_request("TRM_M_Factor","f",50.0);
		$trm_s_factor = http_request("TRM_S_Factor","f",50.0);
		$trm_t_factor = http_request("TRM_T_Factor","f",50.0);
		// Unidad TRS
        $trs_x_unit = http_request("TRS_X_Unit","s","s",false);
        $trs_l_unit = http_request("TRS_L_Unit","s","s",false);
		$trs_m_unit = http_request("TRS_M_Unit","s","s",false);
		$trs_s_unit = http_request("TRS_S_Unit","s","s",false);
		$trs_t_unit = http_request("TRS_T_Unit","s","s",false);
		// Unidad TRM
        $trm_x_unit = http_request("TRM_X_Unit","s","%",false);
        $trm_l_unit = http_request("TRM_L_Unit","s","%",false);
		$trm_m_unit = http_request("TRM_M_Unit","s","%",false);
		$trm_s_unit = http_request("TRM_S_Unit","s","%",false);
		$trm_t_unit = http_request("TRM_T_Unit","s","%",false);
		// Jueces y observaciones
		$juez1 = http_request("Juez1","i",1);
		$juez2 = http_request("Juez2","i",1);
		$observaciones = http_request("Observaciones","s",null,false);

		// preparamos la query SQL
		$sql= "UPDATE mangas SET
 			Recorrido=? ,
			Dist_X=? , Obst_X=? , 
			Dist_L=? , Obst_L=? , 
            Dist_M=? , Obst_M=? , 
            Dist_S=? , Obst_S=? , 
            Dist_T=? , Obst_T=? ,
			TRS_X_Tipo=? , TRS_X_Factor=? , TRS_X_Unit=? , TRM_X_Tipo=? , TRM_X_Factor=? , TRM_X_Unit=? ,
			TRS_L_Tipo=? , TRS_L_Factor=? , TRS_L_Unit=? , TRM_L_Tipo=? , TRM_L_Factor=? , TRM_L_Unit=? ,
			TRS_M_Tipo=? , TRS_M_Factor=? , TRS_M_Unit=? , TRM_M_Tipo=? , TRM_M_Factor=? , TRM_M_Unit=? ,
			TRS_S_Tipo=? , TRS_S_Factor=? , TRS_S_Unit=? , TRM_S_Tipo=? , TRM_S_Factor=? , TRM_S_Unit=? ,
			TRS_T_Tipo=? , TRS_T_Factor=? , TRS_T_Unit=? , TRM_T_Tipo=? , TRM_T_Factor=? , TRM_T_Unit=? ,
			Juez1=? , Juez2=? ,
			Observaciones=?
			WHERE (ID=$id)";

		$stmt=$this->conn->prepare($sql);
		if (!$stmt) return $this->error($this->conn->error); 
		$res=$stmt->bind_param(
			'iiiiiiiiiiiidsidsidsidsidsidsidsidsidsidsiis',
			$recorrido,
            $dist_x,	$obst_x,	$dist_l,	$obst_l,	$dist_m,	$obst_m,	$dist_s,	$obst_s, 	$dist_t,	$obst_t,// distancias y obstaculos
            $trs_x_tipo,	$trs_x_factor,	$trs_x_unit,	$trm_x_tipo,	$trm_x_factor,	$trm_x_unit,// TRS y TRM XLarge
            $trs_l_tipo,	$trs_l_factor,	$trs_l_unit,	$trm_l_tipo,	$trm_l_factor,	$trm_l_unit,// TRS y TRM Large
			$trs_m_tipo,	$trs_m_factor,	$trs_m_unit,	$trm_m_tipo,	$trm_m_factor,	$trm_m_unit,// TRS Y TRM Medium
			$trs_s_tipo,	$trs_s_factor,	$trs_s_unit,	$trm_s_tipo,	$trm_s_factor,	$trm_s_unit,// TRS y TRM Small
			$trs_t_tipo,	$trs_t_factor,	$trs_t_unit,	$trm_t_tipo,	$trm_t_factor,	$trm_t_unit,// TRS y TRM Small
			$juez1, 		$juez2, 		$observaciones
		);
		if (!$res) return $this->error($stmt->error);

		// ejecutamos el query
		// invocamos la orden SQL y devolvemos el resultado
		$res=$stmt->execute();
		if (!$res) return $this->error($stmt->error);
		$stmt->close();
		
		// actualizamos el campo "Recorrido" de las Mangas gemelas
		$tipos=implode(',' , Mangas::$manga_hermana[$tipo]);
		$sql="UPDATE mangas SET Recorrido=$recorrido WHERE ( Jornada={$this->jornadaObj->ID} ) AND (Tipo IN ({$tipos}) )";
		$res=$this->query($sql);
		if (!$res) return $this->error($this->conn->error); 
		$this->myLogger->leave();
		return "";
	}
	
	function shareJuez() {
	    assertClosedJourney($this->jornadaObj); // throw exception on closed journeys
		$juez1 = http_request("Juez1","i",1);
		$juez2 = http_request("Juez2","i",1);
		$sql="UPDATE mangas SET Juez1=$juez1, Juez2=$juez2 WHERE ( Jornada={$this->jornadaObj->ID} )";
		$res=$this->query($sql);
		if (!$res) return $this->error($this->conn->error); 
		$this->myLogger->leave();
        return "";
	}	
	
	/**
	 * Delete a Manga from jornada $this->jornadaObj-ID when tipo is $tipo
	 * @param {integer} tipo ID a sociado a tipo manga
	 * @return "" on success; null on error
	 */
	function delete($tipo) {
		$this->myLogger->enter();
		if ( !array_key_exists($tipo,Mangas::$tipo_manga)) {
		    return $this->error("Invalid value for 'Tipo': {$tipo}");
        }
		// si la manga existe, borrarla; si no existe, no hacer nada
		$rs=$this->__delete("mangas","( Jornada = {$this->jornadaObj->ID} ) AND  ( Tipo = $tipo )");
		if (!$rs) return $this->error($this->conn->error); 
		$this->myLogger->leave();
		return "";
	}
	
	function deleteByID($id) {
		$this->myLogger->enter();
		if ( ($id<=0) ) return $this->error("Invalid Manga ID: $id"); 
		// si la manga existe, borrarla; si no existe, no hacer nada
		$rs=$this->__delete("mangas","( Jornada = {$this->jornadaObj->ID} ) AND  ( ID = $id )");
		if (!$rs) return $this->error($this->conn->error); 
		$this->myLogger->leave();
		return "";
	}
	
	/**
	 * recupera los datos de una manga determinada
	 * @param {int} $id id de la manga
	 * @return {array} null on error, data on success
	 */
	function selectByID($id) {
		$this->myLogger->enter();
		if ($id<=0) return $this->error("Invalid Manga ID");
        $fed=Federations::getFederation( intval($this->pruebaObj->RSCE) );
		// second query to retrieve $rows starting at $offset
		$result=$this->__getObject("mangas",$id);
		$result->Manga=$id;
		$result->Jornada=$this->jornadaObj->ID;
		$result->Nombre=_(Mangas::getTipoManga($result->Tipo,1,$fed));
		$result->Operation="update";
		// JAMC Agosto 2020: if Observaciones==="" add Agility/Jumping information
        if ($result->Observaciones=="") {
            if (isMangaAgility($result->Tipo)) $result->Observaciones="Agility";
            if (isMangaJumping($result->Tipo)) $result->Observaciones="Jumping";
        }
		$this->myLogger->leave();
		return $result;
	}

    /**
     * retrieve every related information about provided mangas
     * @param {integer|object} $id mangaID or mangaObj ( as __getObject )
     * @return object { prueba, jornada, manga, tanda, federation, competition } or null on error
     * @throws Exception when invalid parameter provided
     */
	static function getMangaInfo($id) {
	    // do_log("getMangaInfo id:".json_encode($id));
	    $mid=null;
	    if (is_object(($id))) $mid=$id->ID;
	    if (is_numeric($id)) $mid=intval($id);
	    if (is_null($mid)) throw new Exception ("Mangas::getMangaInfo() invalid object or identifier");
	    $myDbObject= new DBObject("getMangaInfo");
	    $result = new stdClass();
	    $result->Manga=$myDbObject->__getObject('mangas',$mid);
	    if (!is_object($result->Manga)) {
	        $myDbObject->myLogger->error("Mangas::getMangaInfo() cannot locate MangaID: {$mid}");
	        return null;
        }
        $result->Jornada=$myDbObject->__getObject('jornadas',$result->Manga->Jornada);
        if (!is_object($result->Jornada)) {
            $myDbObject->myLogger->error("Mangas::getMangaInfo() cannot locate JornadaID: {$result->Manga->Jornada}");
            return null;
        }
        $result->Prueba=$myDbObject->__getObject('pruebas',$result->Jornada->Prueba);
        if (!is_object($result->Jornada)) {
            $myDbObject->myLogger->error("Mangas::getMangaInfo() cannot locate PruebaID: {$result->Jornada->Prueba}");
            return null;
        }
        $result->Federation=Federations::getFederation($result->Prueba->RSCE);
        $result->Competition=Competitions::getCompetition($result->Prueba,$result->Jornada);
        $result->Tandas=Tandas::getTandasByTipoManga($result->Manga->Tipo,$result->Competition->getRoundHeights($mid));
        return $result;
    }

	/**
	 * Enumera las mangas de una jornada
	 * @return null on error, result on success
	 */
	function selectByJornada() {
		$this->myLogger->enter();
        $fed=Federations::getFederation( intval($this->pruebaObj->RSCE) );
		$result=$this->__select(
			/* SELECT */"ID,Tipo,Recorrido,Grado",
			/* FROM */ "mangas",
			/* WHERE */ "(Jornada = {$this->jornadaObj->ID} )",
			/* ORDER */ "ID ASC", // se asume que las mangas se crean por orden agility/jumping
			/* LIMIT */ ""
		);
		foreach ( $result['rows'] as &$item) {
			// merge information on Mangas::Tipo_Manga without using database Tipo_Manga table (to allow i18n)
			$item['Descripcion']=_(Mangas::getTipoManga($item['Tipo'],1,$fed));
		}
		$this->myLogger->leave();
		return $result;
	}

	/**
	 * Obtiene la manga "hermana" de la que tiene el ID dado
	 * @param {integer} $id ID de la manga
	 * @return array[0:mangaid,1:mangahermanaid]
	 */
	function getHermanas($id) {
		$this->myLogger->enter();
		if ($id<=0) return $this->error("Invalid Manga ID");
		// second query to retrieve $rows starting at $offset
		$result=$this->__getObject("mangas",$id);
		if (!is_object($result)) return $this->error("Cannot locate Manga with ID=$id");
		$tipos=implode(',',Mangas::$manga_hermana[$result->Tipo]);
		if ($tipos=="0") {
			$this->myLogger->info("La manga:$id de tipo:{$result->Tipo} no tiene hermanas asociadas");
			return array($result,null); 
		}
		// Obtenemos __Todas__ las mangas de esta jornada que tienen el tipo buscado ninguna, una o hasta 8(k.O.)
        // debemos conservar el orden indicado en manga_hermana
        $order="FIND_IN_SET(Tipo,'{$tipos}')";
		$result2=$this->__select(
		    /*select*/"*",
            /*from*/ "mangas",
            /*where*/"( Jornada={$this->jornadaObj->ID} ) AND ( Tipo IN ({$tipos}) )",
            /*order*/ $order,
            /*limit*/"");
		if (!is_array($result2)) {
			// inconsistency error muy serio 
			return $this->error("No encuentro mangas hermanas de manga:$id de tipo:{$result->Tipo}");
		}
		$hermanas=array();
		array_push($hermanas,$result); // manga original as index 0
		foreach ($result2['rows'] as $index => $item) {
			// iterate on every sisters found, converting it to Objects
			array_push($hermanas,json_decode(json_encode($item), FALSE));
		}
		$this->myLogger->leave();
		return $hermanas;
	}

    /**
     * Dada una manga, obtiene la lista de mangas compatibles de todas las jornadas marcadas como
     * subordinadas de la jornada actual
     * @param {integer} $manga manga de la jornada principal
     * @return {array} lista de mangas compatibles con $manga de las jornadas subordinadas a la actual
     */
	function getSubordinates($manga) {
        $res=array();
        // fase 0: buscamos datos de la manga solicitada
        $mng=$this->__getArray("mangas",$manga);
        if ( !is_array($mng) || ($mng['Jornada']!=$this->jornadaObj->ID) ) {
            $this->myLogger->error("Invalid manga ID: $manga");
            return array("total"=>0, "rows"=>$res);
        }
        // fase 1: obtenemos la lista de mangas de las jornadas subordinadas
        $mngs=$this->__select(
            "mangas.*",
            "mangas,jornadas",
            "mangas.Jornada=jornadas.ID and jornadas.SlaveOf={$this->jornadaObj->ID}",
            "",
            "");
        // fase 2: de la lista anterior cogemos las mangas compatibles
        // notese que no basta con comparar el tipo: la jornada padre puede ser una normal,
        // y la subordinada una de tipo open, con lo que hay que ver si ambas mangas son de tipo
        // agility o jumping
        foreach ($mngs['rows'] as $m) {
            // comparamos tipos viendo si ambas son "agility" o "jumping"
            if(Mangas::$tipo_manga[$m['Tipo']][5] != Mangas::$tipo_manga[$mng['Tipo']][5]) continue;
            $res[]=$m;
        }
        // retornamos resultado de la comparacion
        return array('total'=>count($res),"rows"=>$res);
    }

	/**
	 * creacion / borrado de mangas asociadas a una jornada
	 * @param {integer} $id ID de jornada
	 * @param {integer} $grado1 la jornada tiene(1/2/3) o no (0) mangas de grado 1
	 * @param {integer} $grado2 la jornada tiene (1) o no (0) mangas de grado 2
	 * @param {integer} $grado3 la jornada tiene (1) o no (0) mangas de grado 3
     * @param {integer} $junior la jornada tiene (1) o no (0) mangas de categoria junior
     * @param {integer} $senior la jornada tiene (1) o no (0) mangas de categoria senior
     * @param {integer} $children la jornada tiene (1) o no (0) mangas de categoria infantil
     * @param {integer} $paraagility la jornada tiene (1) o no (0) mangas de categoria para-agility
     * @param {integer} $open la jornada tiene (1) o no (0) una prueba abierta
	 * @param {integer} $equipos3 mindogs en manga por equipos; 0: no team ( since 4.2.x )
	 * @param {integer} $equipos4 maxdogs en manga por equipos: 0: no team ( since 4.2.x )
	 * @param {integer} $preagility la jornada tiene (1/2) o no (0) mangas de preagility
     * @param {integer} $ko la jornada contiene (1) o no (0) una prueba k0
     * @param {integer} $games la jornada contiene (1) o no (0) una sesion games/wao
	 * @param {integer} $especial la jornada tiene (1) o no (0) mangas especial a una vuelta
	 * @param {integer} $observaciones nombre con el que se denominara la manga especial
	 * // TODO: handle ko, exhibicion and otras
	 */
	function prepareMangas($id,$grado1,$grado2,$grado3,$junior,$senior,$children,$paraagility,$open,$equipos3,$equipos4,$preagility,$ko,$games,$especial,$observaciones) {
		$this->myLogger->enter();

		/*  0,'','' */

		/* 1, 'Pre Agility (una manga)', 'P.A.' */
		/* 2, 'Pre Agility (dos mangas)', 'P.A.' */
        if ($preagility==2) { // pre-agility 2 mangas
            $this->insert(1,'P.A.'); $this->insert(2,'P.A.');
        } else if($preagility==1) { // pre-agility 1 manga
            $this->insert(1,'P.A.'); $this->delete(2);
        } else { // no hay pre-agility
            $this->delete(1); $this->delete(2);
        }

        /* 32,'Junior Agility', 'Jr' */
        /* 33,'Junior Jumping', 'Jr' */
        if ($junior!=0) { $this->insert(32,'Jr'); $this->insert(33,'Jr'); }
        else { $this->delete(32); $this->delete(33); }

        /* 34,'Senior Agility', 'Sr' */
        /* 35,'Senior Jumping', 'Sr' */
        if ($senior!=0) { $this->insert(34,'Sr'); $this->insert(35,'Sr'); }
        else { $this->delete(34); $this->delete(35); }

        /* 36,'Infantil Agility', 'Ch' */
        /* 37,'Infantil Jumping', 'Ch' */
        if ($children!=0) { $this->insert(36,'Ch'); $this->insert(37,'Ch'); }
        else { $this->delete(36); $this->delete(37); }

        /* 38,'ParaAgility Agility', 'Par' */
        /* 39,'ParaAgility Jumping', 'Par' */
        if ($paraagility!=0) { $this->insert(38,'Par'); $this->insert(39,'Par'); }
        else { $this->delete(38); $this->delete(39); }

		/* 3, 'Agility Grado I Manga 1', 'GI' */
        /* 4, 'Agility Grado I Manga 2', 'GI' */
        /* 17, 'Agility Grado I Manga 3', 'GI' */
        if ($grado1==1)  { $this->insert(3,'GI'); $this->insert(4,'GI'); $this->delete(17);}
        else if ($grado1==2)  { $this->insert(3,'GI'); $this->delete(4); $this->delete(17);}
        else if ($grado1==3)  { $this->insert(3,'GI'); $this->insert(4,'GI'); $this->insert(17,'GI');}
        else  { $this->delete(3); $this->delete(4); $this->delete(17);}

		/* 5, 'Agility Grado II', 'GII' */
		/* 10,'Jumping Grado II', 'GII' */
		if ($grado2!=0) { $this->insert(5,'GII'); $this->insert(10,'GII'); }
		else { $this->delete(5); $this->delete(10); }

		/* 6, 'Agility Grado III', 'GIII' */
		/* 11,'Jumping Grado III', 'GIII' */
		if ($grado3!=0) { $this->insert(6,'GIII'); $this->insert(11,'GIII'); }
		else { $this->delete(6);	$this->delete(11); }

		/* 7, 'Agility Abierta', '-' */
		/* 12,'Jumping Abierta', '-' */
		if ($open!=0) { $this->insert(7,'-'); $this->insert(12,'-'); }
		else { $this->delete(7);	$this->delete(12); }

		/* 8, 'Agility Equipos (3 mejores)', '-' */
		/* 13,'Jumping Equipos (3 mejores)', '-' */
        /* 9, 'Agility Equipos (Conjunta)', '-' */
        /* 14,'Jumping Equipos (Conjunta)', '-' */
        /* PENDING: revise if we should delete round when change from best to all team mode */
        $minmax=Jornadas::getTeamDogs($id);
        if ($minmax[0]>1) {
            if ($minmax[0]!=$minmax[1]) { $this->insert(8,'-');	$this->insert(13,'-'); }
            if ($minmax[0]==$minmax[1]) { $this->insert(9,'-');	$this->insert(14,'-'); }
        } else { $this->delete(8); $this->delete(9); $this->delete(13);	$this->delete(14);}

		/* 16,'Ronda de Exhibición', '-' */
		if ($especial!=0) { $this->insert(16,'-');}
		else { $this->delete(16); }

		/* 15,'Ronda K.O.', '-' */
        /* 18 ,'K.O. Second round',	*/
        /* 19 ,'K.O. Third round',*/
        /* 20 ,'K.O. Fourth round',*/
        /* 21 ,'K.O. Fifth round',*/
        /* 22 ,'K.O. Sixth round',*/
        /* 23 ,'K.O. Seventh round',*/
        /* 24 ,'K.O. Eight round',*/
		// PENDING: las mangas KO hay que crearlas dinamicamente en funcion del numero de participantes
		if ($ko!=0) {
            $this->insert(15,'-');	$this->insert(18,'-');
            $this->insert(19,'-');	$this->insert(20,'-');
            $this->insert(21,'-');	$this->insert(22,'-');
            $this->insert(23,'-');	$this->insert(24,'-');
        } else {
            $this->delete(15);	$this->delete(18); $this->delete(19);	$this->delete(20);
            $this->delete(21);	$this->delete(22); $this->delete(23);	$this->delete(24);
        }

        /* mangas para games / wao */
        /* 25 ,'Agility A',	*/
        /* 26 ,'Agility B',	*/
        /* 27 ,'Jumping A',	*/
        /* 28 ,'Jumping B',	*/
        /* 29 ,'Snooker',	*/
        /* 30 ,'Gambler',	*/
        /* 31 ,'SpeedStakes',*/
        if ($games) {
            $this->insert(25,'-');	$this->insert(26,'-');
            $this->insert(27,'-');	$this->insert(28,'-');
            $this->insert(29,'-');	$this->insert(30,'-');
            $this->insert(31,'-');
        } else {
            $this->delete(25);	$this->delete(26); $this->delete(27);	$this->delete(28);
            $this->delete(29);	$this->delete(30); $this->delete(31);
        }
		$this->myLogger->leave();
	}
}

?>