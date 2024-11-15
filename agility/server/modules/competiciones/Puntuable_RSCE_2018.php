<?php

/**
 *
 * Sistema de calificacion para las pruebas puntuables C.E. RSCE Temporada 2017
 * En grado 1 se obtiene punto por cada excelente a cero
 *
 * En grado 2 y 3 Se obtienen puntos por cada excelente a cero con velocidad superior a:
 *
 * GII: Agility 3.6m/s Jumping 3.8m/s
 * GIII: Agility 4.1m/s Jumping 4.5m/s
 * Además los perros que hagan el recorrido a cero con una velocidad superior a 5.1(agility) / 5.5(Jumping)
 * obtendran un punto extra
 *
 * Para la clasificacion para el C.E. Se exigen seis excelentes a cero en cada manga,
 * en los que al menos 3 de ellos tienen que tener puntos
 */
require_once(__DIR__."/lib/ligas/Liga_RSCE_2018.php");

class Puntuable_RSCE_2018 extends Competitions {

   protected $puntos;

    function __construct($name="Punt. temporada 2018 (CE 2019)") {
        parent::__construct($name);
        $this->federationID=0;
        $this->federationDefault=1;
        $this->competitionID=10;
        $this->moduleVersion="1.0.3";
        $this->moduleRevision="20191108_1525";
        $this->federationLogoAllowed=true;
        $this->puntos=array(
            // en la temporada 2018 desaparecen los puntos dobles
            // se anyade un campo extra para los puntos de ascenso a grado 3
            /* grado      puntos  AgL     AgM    AgS    JpL     JpM     JpS    pts  stars  extras(g3) */
            array("GII",    "Pv",  4.0,    3.8,   3.8,   4.2,    4.0,    4.0,   0,  1,      0 ),
            array("GII",    "Pa",  4.7,    4.5,   4.5,   4.9,    4.7,    4.7,   0,  1,      1 ), // same as g3
            array("GIII",   "Pv",  4.7,    4.5,   4.5,   4.9,    4.7,    4.7,   0,  1,      0 )
        );
    }

    function getRoundHeights($mangaid) {
        return 3; // old RSCE Seasons had 3 heights
    }

    /**
     * Provide default TRS/TRM/Recorrido values for a given competitiona at
     * Round creation time
     * @param {integer} $tipo Round tipe as declared as Mangas::TipoManga
     * @return {array} trs array or null if no changes
     */
    public function presetTRSData($tipo) {
        // when not grade 2 or 3,use parent default
        if (!in_array($tipo,array(5,6,10,11))) return parent::presetTRSData($tipo);
        $factor=(in_array($tipo,array(5,10)))?25:15; // Grado 2:25%; grado 3: 15%
        $manga=array();
        $manga['Recorrido']=3; // 0:separados 1:mixto(2 grupos) 2:conjunto 3:mixto(tres grupos)
        $manga['TRS_X_Tipo']=1;$manga['TRS_X_Factor']=$factor;  $manga['TRS_X_Unit']='%';
        $manga['TRM_X_Tipo']=1;$manga['TRM_X_Factor']=50;       $manga['TRM_X_Unit']='%';
        $manga['TRS_L_Tipo']=1;$manga['TRS_L_Factor']=$factor;  $manga['TRS_L_Unit']='%'; // best dog + 25 %
        $manga['TRM_L_Tipo']=1;$manga['TRM_L_Factor']=50;       $manga['TRM_L_Unit']='%'; // trs + 50 %
        $manga['TRS_M_Tipo']=1;$manga['TRS_M_Factor']=$factor;  $manga['TRS_M_Unit']='%';
        $manga['TRM_M_Tipo']=1;$manga['TRM_M_Factor']=50;       $manga['TRM_M_Unit']='%';
        $manga['TRS_S_Tipo']=1;$manga['TRS_S_Factor']=$factor;  $manga['TRS_S_Unit']='%';
        $manga['TRM_S_Tipo']=1;$manga['TRM_S_Factor']=50;       $manga['TRM_S_Unit']='%';
        $manga['TRS_T_Tipo']=1;$manga['TRS_T_Factor']=$factor;  $manga['TRS_T_Unit']='%'; // not used but required
        $manga['TRM_T_Tipo']=1;$manga['TRM_T_Factor']=50;       $manga['TRM_T_Unit']='%';
        return $manga;
    }

    /**
     * Re-evaluate and fix -if required- results data used to evaluate TRS for
     * provided $prueba/$jornada/$manga
     * @param {object} $manga Round data and trs parameters. Passed by reference
     * @param {array} $data Original results provided for evaluation
     * @param {integer} $mode which categories have to be selected
     * @param {boolean} $roundUp on true round UP SCT and MCT to nearest second. Passed by reference
     * @return {array} final data to be used to evaluate trs/trm
     */
    public function checkAndFixTRSData(&$manga,$data,$mode,&$roundUp) {
        // remember that prueba,jornada and manga are objects, so passed by reference
        $this->prueba->Selectiva = 0; // not really required, just to be sure
        // en grado 3 el trs lo marca el perro mas rapido + 15% sin redondeo
        $roundUp=true;
        // if (($manga->Tipo==6) || ($manga->Tipo==11)) $roundUp=false;
        return $data;
    }

    /**
     * Evalua la calificacion parcial del perro
     * @param {object} $m datos de la manga
     * @param {array} $perro datos de puntuacion del perro. Passed by reference
     * @param {array} $puestocat puesto en funcion de la categoria
     */
    public function evalPartialCalification($m,&$perro,$puestocat) {

        // comprueba que las mangas sean puntuables
        if (! in_array($m->Tipo, array(3 /*GI-1*/, 4/*GI*/, 5/*A2*/,6/*A3*/,10/*J2*/,11/*J3*/,17/*GI-3*/))) {
            parent::evalPartialCalification($m,$perro,$puestocat);
            return;
        }

        // si estamos en preagility, grado 1 o no tiene cero puntos de penalizacion, utiliza la puntuacion estandard
        if ($perro['Grado']==="P.A.") {
            parent::evalPartialCalification($m,$perro,$puestocat);
            return;
        }
        if ($perro['Grado']==="GI") {
            parent::evalPartialCalification($m,$perro,$puestocat);
            $perro['Estrellas']=0;
            $perro['Extras']=0;
            if($perro['Penalizacion']==0) $perro['Puntos']=1;
            return;
        }
        if ($perro['Penalizacion']>0) {
            parent::evalPartialCalification($m,$perro,$puestocat);
            return;
        }
        $perro['Calificacion'] = _("Excellent")." P.";
        $perro['CShort'] = "Ex P.";
        $perro['Puntos'] = 1;
        $perro['Estrellas'] = 0;
        $perro['Extras'] = 0;
        foreach ( $this->puntos as $item) {
            if ($perro['Grado']!==$item[0]) continue;
            // comprobamos si estamos en agility o en jumping (1:agility,2:jumping,3:third round and so )
            $offset=( (Mangas::$tipo_manga[$m->Tipo][5]) == 1)?0/*agility*/:3/*jumping*/;
            $base=2;
            switch($perro['Categoria']) {
                case "X": case "L": $base=2; break;
                case "M": $base=3; break;
                case "S":case "T": $base=4; break;
            }
            // si la velocidad es igual o superior se apunta tanto. notese que el array está ordenado por grad/velocidad
            if ($perro['Velocidad']>=$item[$base+$offset]) {
                $perro['Calificacion'] = _("Excellent")." ".$item[1];
                $perro['CShort'] = "Ex ".$item[1];
                $perro['Puntos'] = $item[8];
                $perro['Estrellas'] = $item[9];
                $perro['Extras'] = $item[10];
            }
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
        // si las mangas no son puntuables utiliza los criterios de la clase padre
        $flag=false;
        $tipo=$mangas[0]->Tipo;
        if ($tipo==3) $flag=true; // agility G1 primera manga
        if ($tipo==4) $flag=true; // agility G1 segunda manga
        if ($tipo==5) $flag=true; // agility G2
        if ($tipo==6) $flag=true; // agility G3
        if ($tipo==10) $flag=true;// jumping G3
        if ($tipo==11) $flag=true;// jumping G3
        if (!$flag) {
            parent::evalFinalCalification($mangas,$resultados,$perro,$puestocat);
            return;
        }

        $grad=$perro['Grado']; // cogemos la categoria
        if ($grad==="P.A.") {
            parent::evalFinalCalification($mangas,$resultados,$perro,$puestocat);
            return;
        }
        if ($grad==="GI") { // en grado uno se puntua por cada manga
            $pts=0;
            $perro['Calificacion'] = "";
            if ($perro['P1']==0.0) { // comprobamos si realmente hay datos del recorrido ( "pending" )
                $perro['Calificacion']= "- No data -";
                if ($perro['T1']!=0.0) $pts++;
            }
            if (array_key_exists('P2',$perro)) { // en selectivas solo hay una manga de G1
                if ($perro['P2']==0.0) { // comprobamos si realmente hay datos del recorrido ( "pending" )
                    $perro['Calificacion']= "- No data -";
                    if ($perro['T2']!=0.0) $pts++;
                }
            }
            if (array_key_exists('P3',$perro)) { // desde la temporada 2020 hay posibilidad de una tercera manga
                if ($perro['P3']==0.0) { // comprobamos si realmente hay datos del recorrido ( "pending" )
                    $perro['Calificacion']= "- No data -";
                    if ($perro['T3']!=0.0) $pts++;
                }
            }
            if ($pts>0) $perro['Calificacion'] = "{$pts} Punto".(($pts>1)?"s":"");
            return;
        }
        // llegando aquí tenemos grado 2 o 3 ( siempre con dos mangas )
        // componemos string de calificacion final
        $p1=" ";
        if ($perro['P1']<6.0) $p1="-";
        if ($perro['P1']==0) $p1=mb_substr($perro['C1'],-2,2);
        $p2=" ";
        if ($perro['P2']<6.0) $p2="-";
        if ($perro['P2']==0) $p2=mb_substr($perro['C2'],-2,2);
        $perro['Calificacion']="$p1 / $p2";
    }

    /**
     * Retrieve handler for manage Ligas functions.
     * Default is use standard Ligas, but may be overriden ( eg wao. Rounds )
     * @param {string} $file
     * @return {Ligas} instance of requested Ligas object
     * @throws Exception on invalid prueba/jornada/manga
     */
    protected function getLigasObject($file) {
        return new Liga_RSCE_2018($file);
    }

}