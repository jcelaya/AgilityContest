<?php

/**
 * Created by PhpStorm.
 * User: jantonio
 * Date: 16/11/16
 * Time: 10:58
 */
class Selectiva_awc_RSCE extends Puntuable_RSCE_2017 {

    private $poffset=array('L'=>0,'M'=>0,'S'=>0,'T'=>0); // to skip not-league competitors (partial scores)
    private $pfoffset=array('L'=>0,'M'=>0,'S'=>0,'T'=>0); // to skip not-league competitors (final scores)

    function __construct() {
        parent::__construct("Prueba selectiva AWC 2017");
        $this->federationID=0;
        $this->competitionID=1;
    }

    /**
     * Re-evaluate and fix -if required- results data used to evaluate TRS for
     * provided $prueba/$jornada/$manga
     * @param {object} $manga Round data and trs parameters
     * @param {array} $data Original results provided for evaluation
     * @return {array} final data to be used to evaluate trs/trm
     */
    public function checkAndFixTRSData($manga,$data) {
        // remember that prueba,jornada and manga are objects, so passed by reference
        $this->prueba->Selectiva=1;
        // en pruebas selectivas RSCE de la temporada 2017
        // el trs para grado 3 es el del mejor perro por categoria y sin redondeo
        if ($manga->Grado==="GIII") {
            $manga->TRS_L_Tipo=1;$manga->TRS_L_Factor=0;$manga->TRS_L_Unit='s';
            $manga->TRM_L_Tipo=1;$manga->TRM_L_Factor=50;$manga->TRM_L_Unit='%';
            $manga->TRS_M_Tipo=1;$manga->TRS_M_Factor=0;$manga->TRS_M_Unit='s';
            $manga->TRM_M_Tipo=1;$manga->TRM_M_Factor=50;$manga->TRM_M_Unit='%';
            $manga->TRS_S_Tipo=1;$manga->TRS_S_Factor=0;$manga->TRS_S_Unit='s';
            $manga->TRM_S_Tipo=1;$manga->TRM_S_Factor=50;$manga->TRM_S_Unit='%';
        }
        return $data;
    }

    /**
     * Evaluate if a dog has a mixBreed License
     * @param $lic
     */
    function validLicense($lic){
        $lic=strval($lic);
        // remove dots, spaces and dashes
        $lic=str_replace(" ","",$lic);
        $lic=str_replace("-","",$lic);
        $lic=str_replace(".","",$lic);
        $lic=strtoupper($lic);
        if (strlen($lic)<4) {
            if (is_numeric($lic)) return true; // licenses from 0 to 999
            return false;
        }
        if (strlen($lic)>4) return false; // rsce licenses has up to 4 characters
        if (substr($lic,0,1)=='0') return true; // 0000 to 9999
        if (substr($lic,0,1)=='A') return true; // A000 to A999
        if (substr($lic,0,1)=='B') return true; // B000 to B999
        if (substr($lic,0,1)=='C') return true; // C000 to C999
        return false;
    }

    /**
     * Evalua la calificacion parcial del perro
     * @param {object} $m datos de la manga
     * @param {array} $perro datos de puntuacion del perro. Passed by reference
     * @param {array} $puestocat puesto en funcion de la categoria
     */
    public function evalPartialCalification($m,&$perro,$puestocat) {
        if ($perro['Grado']!=="GIII") {
            parent::evalPartialCalification($m,$perro,$puestocat);
            return;
        }
        // arriving here means grado III
        if ($this->prueba->Selectiva==0) { // need to be marked as selectiva to properly evaluate TRS in GIII
            parent::evalPartialCalification($m,$perro,$puestocat);
            return;
        }
        // si no tiene excelente no puntua
        if ( ($perro['Penalizacion']>=6.0)) {
            parent::evalPartialCalification($m,$perro,$puestocat);
            return;
        }
        // comprobamos si el perro es mestizo
        if (! $this->validLicense($perro['Licencia']) ) { // perro mestizo o extranjero no puntua
            $this->poffset[$perro['Categoria']]++; // mark to skip point assignation
            parent::evalPartialCalification($m,$perro,$puestocat);
            return;
        }
        $pts=array("25","20","16","12","8","6","4","3","2","1"); // puntuacion manga de agility
        if (intval($m->Tipo)==11) $pts=array("20","16","12","8","6","5","4","3","2","1"); // puntuacion manga de jumping
        // solo puntuan los 10 primeros
        $puesto=$puestocat[$perro['Categoria']]-$this->pfoffset[$perro['Categoria']];
        if ( ($puesto>10) || ($puesto<=0) ) {
            parent::evalPartialCalification($m,$perro,$puestocat);
            return;
        }
        // si llegamos aqui tenemos los 10 primeros perros una prueba selectiva en grado 3 con un perro no mestizo que ha sacado excelente :-)
        $pt1=$pts[$puesto-1];
        if ($perro['Penalizacion']>0)	{
            $perro['Calificacion'] = _("Excellent")." $pt1";
            $perro['CShort'] = _("Exc")." $pt1";
        }
        if ($perro['Penalizacion']==0)	{
            $perro['Calificacion'] = _("Excellent")." (p) $pt1";
            $perro['CShort'] = _("ExP")." $pt1";
        }
    }


    /**
     * Evalua la calificacion final del perro
     * @param {array} $mangas informacion {object} de las diversas mangas
     * @param {array} $resultados informacion {array} de los resultados de cada manga
     * @param {array} $perro datos de puntuacion del perro. Passed by reference
     * @param {array} $puestocat puesto en funcion de la categoria
     */
    public function evalFinalCalification($mangas,$resultados,&$perro,$puestocat){
        $grad=$perro['Grado']; // cogemos la categoria
        if ($grad==="GI") { // en grado uno puntua como prueba normal
            parent::evalFinalCalification($mangas,$resultados,$perro,$puestocat);
            return;
        }
        if ($grad==="GII") { // grado dos puntua como prueba normal
            parent::evalFinalCalification($mangas,$resultados,$perro,$puestocat);
            return;
        }
        if ($grad!=="GIII") { // ignore other extrange grades
            do_log("Invalid grade '$grad' found");
            return;
        }
        // arriving here means grado III
        if ($this->prueba->Selectiva==0){ // need to be marked as selectiva to properly evaluate TRS in GIII
            parent::evalFinalCalification($mangas,$resultados,$perro,$puestocat);
            return;
        }
        // arriving here means prueba selectiva and Grado III
        if ( ! $this->validLicense($perro['Licencia']) ) {  // comprobamos si el perro es mestizo o extranjero
            $this->pfoffset[$perro['Categoria']]++; // mark to skip point assignation
            parent::evalFinalCalification($mangas,$resultados,$perro,$puestocat);
            return;
        }

        // en la temporada 2017 el trs para individual y equipos es el mismo
        // la calificacion conjunta no puntua por individual, solo por equipos
        // lo que se pondrá como calificacion es X / Y
        // donde X es la suma de las calificaciones individuales
        //       Y es la clasificacion por equipos
        // solo puntuan por conjunta los 10 primeros perros no mestizos/extranjeros que tengan doble excelente

        $ptsglobal = array("20", "16", "12", "8", "7", "6", "4", "3", "2", "1"); //puestos por general (si excelentes en ambas mangas)

        // manga 1
        $pt1 = "0";
        if ($resultados[0] != null) { // extraemos los puntos de la primera manga
            $x=trim(substr($perro['C1'],-2));
            $pt1=(is_numeric($x))?$x:"0";
        }
        // manga 2
        $pt2="0";
        if ($resultados[1]!=null) { // extraemos los puntos de la segunda manga
            $x=trim(substr($perro['C2'],-2));
            $pt2=(is_numeric($x))?$x:"0";
        }
        // conjunta
        $pfin="0";
        $pi=intval($pt1)+intval($pt2);
        if ( ($resultados[0]==null) || ($resultados[1]==null)) { // si falta alguna manga no puntua en conjunta
            $perro['Calificacion']= "$pi / -";
            return;
        }
        // si no tiene doble excelente no puntua en conjunta
        if ( ($perro['P1']>=6.0) || ($perro['P2']>=6.0) ) {
            $perro['Calificacion']= "$pi / -";
            return;
        }
        // evaluamos puesto real una vez eliminados los "extranjeros"
        $puesto=$puestocat[$perro['Categoria']]-$this->pfoffset[$perro['Categoria']];
        // si esta entre los 10 primeros cogemos los puntos
        if ($puesto<11) $pfin=$ptsglobal[$puesto-1];
        // y asignamos la calificacion final
        $perro['Calificacion']="$pi / $pfin";

        return; // should be overriden
    }
}