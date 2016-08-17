<?php
/*
Entrenamientos.php

Copyright  2013-2016 by Juan Antonio Martinez ( juansgaviota at gmail dot com )

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

class Entrenamientos extends DBObject {

    protected $pruebaID;
    protected $prueba;
    protected $myConfig;

	function __construct($name,$prueba) {
		parent::__construct($name);
        if ($prueba<=0) throw new Exception('$name: Invalid pruebaID:$prueba');
        $this->prueba=$this->__getObject("Pruebas",$prueba);
        if (!$this->prueba) throw new Exception('$name: Prueba with ID:$prueba not found in database');
        $this->pruebaID=$prueba;
        $this->myConfig=Config::getInstance();
	}

    /**
     * remove all trainning entries for provided contest id
     * @return {string} "" if ok; null on error
     */
    function clear() {
        $this->myLogger->enter();
        $str="DELETE FROM Entrenamientos WHERE (Prueba={$this->pruebaID}) ";
        $res= $this->query($str);
        if (!$res) return $this->error("Cannot remove training session entries for contest id: {$this->pruebaID}");
        $this->myLogger->leave();
        return "";
    }

    /**
     * Fill trainning sesion with default data from database and configuration
     * @return {string} "" if ok; null on error
     */
    function populate() {
        $this->myLogger->enter();
        // cogemos todos los perros inscritos en una prueba y los agrupamos por clubes y categoria
        $res=$this->__select(
            /* SELECT */    "COUNT(PerroGuiaClub.ID) AS Numero, Categoria, Club,NombreClub",
            /* FROM */      "Inscripciones,PerroGuiaClub",
            /* WHERE */     "(Inscripciones.Prueba={$this->pruebaID}) AND (Inscripciones.Perro=PerroGuiaClub.ID)",
            /* ORDER */     "Club ASC, Categoria ASC",
            /* LIMIT */     "",
            /* GROUP BY */  "Club,Categoria"
        );
        if (!$res) return $this->error($this->conn->error);
        // analizamos datos, añadiento tiempos
        $clubes=array();
        $orden=1;
        foreach ($res['rows'] as $item) {
            $idclub=intval($item['Club']);
            // if entry not created, time to do
            if (!array_key_exists($idclub,$clubes)) {
                $nuevoclub= array(
                    'Prueba'    => $this->pruebaID,
                    'Orden'     => $orden++,
                    'Club'      => $idclub,
                    'NombreClub'=> $item['NombreClub'],
                    'Fecha'     => date('Y-m-d'),
                    'Firma'     => '',
                    'Veterinario'=>'',
                    'Entrada'   => '',
                    'Salida'    => '',
                    'Total'     => 0,
                    'L'         => 0,
                    'M'         => 0,
                    'S'         => 0,
                    'T'         => 0,
                    '-'         => 0, // to avoid warnings on nonexistent
                    'Observaciones' => "",
                    'Estado'    => -1 // -1:pending 0:running 1:done
                );
                $clubes[$idclub]=$nuevoclub;
            }
            // vamos rellenando datos
            $clubes[$idclub]['Total']+=intval($item['Numero']);
            $clubes[$idclub][$item['Categoria']]+=intval($item['Numero']);
        }
        // ok. ahora toca asignar los tiempos
        $nextTime=time(); // enter to ring comes one hour after veterinary
        $mode=intval($this->myConfig->getEnv("training_type"));
        $dtime=intval($this->myConfig->getEnv("training_time"));
        $gtime=intval($this->myConfig->getEnv("training_grace"));
        foreach($clubes as &$club) {
            $duration=($mode==0)? $club['Total']*$dtime : max($club['L'],$club['M'],$club['S'],$club['T'])*$dtime;
            $club['Firma']=date('Y-m-d H:i',$nextTime);
            $club['Veterinario']=date('Y-m-d H:i',$nextTime+120); // 2 minutes later
            $club['Entrada']=date('Y-m-d H:i:s',$nextTime+3600); // 1 hour later
            $club['Salida']=date('Y-m-d H:i:s',$nextTime+3600+$duration);
            $nextTime+=$duration+$gtime;
            $this->myLogger->trace("Club: {$club['NombreClub']} Entrada: {$club['Entrada']} Salida: {$club['Salida']} Duracion:$duration");
        }
        // ok. next comes clear and populate Training database table
        $this->clear();
        // to speedup, use prepared statements
        // componemos un prepared statement (para evitar sql injection)
        $sql ="INSERT INTO Entrenamientos (Prueba,Orden,Club,Fecha,Firma,Veterinario,Entrada,Salida,L,M,S,T,Observaciones,Estado)
			   VALUES({$this->pruebaID},?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt=$this->conn->prepare($sql);
        if (!$stmt) return $this->error($this->conn->error);
        $res=$stmt->bind_param('iisssssiiiis',$idx,$clb,$fecha,$firma,$vet,$ent,$sal,$l,$m,$s,$t,$obs,$st);
        if (!$res) return $this->error($this->conn->error);
        foreach($clubes as $elem) {
            $idx=$elem['Orden'];
            $clb=$elem['Club'];
            $fecha=$elem['Fecha'];
            $firma=$elem['Firma'];
            $vet=$elem['Veterinario'];
            $ent=$elem['Entrada'];
            $sal=$elem['Salida'];
            $l=$elem['L'];
            $m=$elem['M'];
            $s=$elem['S'];
            $t=$elem['T'];
            $obs=$elem['Observaciones'];
            $st=$elem['Estado'];
            // invocamos la orden SQL y devolvemos el resultado
            $res=$stmt->execute();
            if (!$res) return $this->error($stmt->error);
        }
        $stmt->close();

        $this->myLogger->leave();
        return "";
    }

	/**
	 * Insert a new user into database. Used in Excel import functions
     * as no way to insert non-inscribed countries/clubs from console
	 * @return {string} "" if ok; null on error
	 */
	function insert() {
		$this->myLogger->enter();
		$this->myLogger->leave();
		return ""; 
	}

	/**
	 * Update trainning entry data
	 * @param {integer} $id entry ID primary key
	 * @return {string} "" on success; null on error
	 */
	function update($id) {
		$this->myLogger->enter();
		$this->myLogger->leave();
		return "";
	}
	
	/* Delete has been removed, as no use in training sessions */
	
	/**
	 * Select user with provided ID
	 * @param {string} $user name primary key
	 * @return "" on success ; otherwise null
	 */
	function selectByID($id) {
		$this->myLogger->enter();
		if ($id<=0) return $this->error("Invalid Entry ID"); // Trainning entry ID must be positive greater than 0

		// make query
		$obj=$this->__getObject("Entrenamientos",$id);
		if (!is_object($obj))	return $this->error("No Training session found with provided ID=$id");
		$data= json_decode(json_encode($obj), true); // convert object to array
		$data['Operation']='update'; // dirty trick to ensure that form operation is fixed
		$this->myLogger->leave();
		return $data;
	} 
	
	function select() {
		$this->myLogger->enter();
		//needed to properly handle multisort requests from datagrid
		$sort=getOrderString(
				http_request("sort","s",""),
				http_request("order","s",""),
				"Orden ASC"
		);
		// search string
		$search =  isset($_GET['where']) ? strval($_GET['where']) : '';
		// evaluate offset and row count for query
		$page=http_request("page","i",1);
		$rows=http_request("rows","i",50);
		$limit="";
		if ($page!=0 && $rows!=0 ) {
			$offset=($page-1)*$rows;
			$limit="".$offset.",".$rows;
		}
		$where = "(Prueba={$this->pruebaID}) AND (Entrenamientos.Club = Clubes.ID) ";
		if ($search!=='') $where= $where . " AND ( (Clubes.Nombre LIKE '%$search%') OR ( Clubes.Pais LIKE '%$search%' ) ) ";
		$result=$this->__select(
				/* SELECT */ "Entrenamientos.*, Clubes.Nombre as NombreClub, Clubes.Logo as LogoClub",
				/* FROM */ "Entrenamientos,Clubes",
				/* WHERE */ $where,
				/* ORDER BY */ $sort,
				/* LIMIT */ $limit
		);
		$this->myLogger->leave();
		return $result;
	}

	function enumerate() { // like select but with fixed order
		$this->myLogger->enter();
		// evaluate search criteria for query
        $q=http_request("q","s",null);
		$where="(Prueba={$this->pruebaID}) AND (Entrenamientos.Club = Clubes.ID) ";
        if ($q!=="") $where=$where . " AND ( (Clubes.Nombre LIKE '%$q%') OR ( Clubes.Pais LIKE '%$q%' ) ) ";
		$result=$this->__select(
				/* SELECT */ "Entrenamientos.*, Clubes.Nombre as NombreClub, Clubes.Logo as LogoClub",
				/* FROM */ "Entrenamientos,Clubes",
				/* WHERE */ $where,
				/* ORDER BY */ "Orden ASC",
				/* LIMIT */ ""
		);
		$this->myLogger->leave();
		return $result;
	}

	private function getEmptyData($orden){
	    $nextTime=time();
        $gtime=intval($this->myConfig->getEnv("training_grace"));
        return array(
            'Prueba'    => $this->pruebaID,
            'Orden'     => $orden,
            'Club'      => 0,
            'NombreClub'=> '',
            'Fecha'     => date('Y-m-d',$nextTime),
            'Firma'     =>date('Y-m-d H:i',$nextTime),
            'Veterinario'=>date('Y-m-d H:i',$nextTime+120), // 2 minutes later
            'Entrada'   =>date('Y-m-d H:i:s',$nextTime+3600), // 1 hour later
            'Salida'    =>date('Y-m-d H:i:s',$nextTime+3600+$gtime),
            'Total'     => 0,
            'L'         => 0,
            'M'         => 0,
            'S'         => 0,
            'T'         => 0,
            '-'         => 0, // to avoid warnings on nonexistent
            'Observaciones' => '',
            'Estado'    => -1 // -1:pending 0:running 1:done
        );
    }

    /**
     * Retrieve next 10 elements starting at provided ID
     * As Orden may not be consecutive, need to parse all entries, and reevaluate index
     * when at end of list, fill with empty data
     * @param $id
     * @param int $count
     */
	function window($id,$size=10) {
        $enum=$this->enumerate()['rows'];
        $orden=0;
        $result=array();
        for ($idx=0;$idx<count($enum);$idx++) {
            $orden++;
            if( $enum[$idx]['ID']!=$id) continue;
            $enum['Orden']=$orden; // override internal orden as may be non-consecutive
            array_push($result,$enum[$idx]);
            $size--;
            if ($size==0) break;
        }
        // fill empty data until complete requested size
        for(;$size>0;$size--) {
            $orden++;
            array_push($result,$this->getEmptyData($orden));
        }
        // reverse array
        $res=array('total'=>count($result),'rows'=>array_reverse($result));
    }

    /**
     * insert $from before(where==false) or after(where=true) $to
     * This dnd routine uses a Orden shift'ng: increase every remaining row order,
     * and assign moved row orden to created hole
     * @param {integer} $from id to move
     * @param {integer} $to id to insert arounn
     * @param {boolean} $where false:insert before  / true:insert after
     */
	function dragAndDrop() {
	    $this->myLogger->enter();
        $from=http_request("From","i",-1);
        $to=http_request("To","i",-1);
        $where=http_request("Where","i",0);
        if (($from<0)|| ($to<0)) return $this->error("{$this->file}::DragAndDrop()Invalid parameters From:$from or To:$to received");

        // get from/to trainning session ID
        $f=$this->__selectObject("*","Entrenamientos","(Prueba={$this->pruebaID}) AND (ID=$from)");
        $t=$this->__selectObject("*","Entrenamientos","(Prueba={$this->pruebaID}) AND (ID=$to)");
        if(!$f || !$t) {
            $this->myLogger->error("Error: no ID for Trainning sesion '$from' and/or '$to' on prueba:{$this->pruebaID}");
            return $this->errormsg;
        }
        $torder=$t->Orden;
        $neworder=($where)?$torder+1/*after*/:$torder/*before*/;
        $comp=($where)?">"/*after*/:">="/*before*/;
        $str="UPDATE Entrenamientos SET Orden=Orden+1 WHERE ( Prueba = {$this->pruebaID} ) AND ( Orden $comp $torder )";
        $rs=$this->query($str);
        if (!$rs) return $this->error($this->conn->error);
        $str="UPDATE Entrenamientos SET Orden=$neworder WHERE ( Prueba = {$this->pruebaID} ) AND ( ID = $from )";
        $rs=$this->query($str);
        if (!$rs) return $this->error($this->conn->error);
        return "";
    }
}
?>