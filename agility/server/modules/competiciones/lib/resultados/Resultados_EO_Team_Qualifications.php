<?php
/*
Resultados_EO_Team_Qualifications.php

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


class Resultados_EO_Team_Qualifications extends Resultados {
	/**
	 * Constructor
	 * @param {string} $file caller for this object
     * @param {object} $prueba Prueba ID
     * @param {object} $jornada Jornada ID
     * @param {object} $manga Manga ID
	 * @throws Exception when
	 * - cannot contact database
	 * - invalid manga ID
	 * - manga is closed
	 */
	function __construct($file,$prueba,$jornada,$manga) {
		parent::__construct($file,$prueba,$jornada,$manga);
	}

    /**
     * Gestion de resultados en Equipos3/Equipos4
     * Agrupa los resultados por equipos y genera una lista de equipos ordenados por resultados
     * @param {array} $results resultados de invocar getResultadosIndividual($mode)
     * @return {array} datos de equipos de la manga ordenados por resultados de equipo
     */
    function getResultadosEquipos($results) {
        $resultados=$results['rows'];
        // evaluamos mindogs/maxdogs
        $mindogs=Jornadas::getTeamDogs($this->getDatosJornada())[0]; // get mindogs
        $maxdogs=Jornadas::getTeamDogs($this->getDatosJornada())[1]; // get maxdogs

        // Datos de equipos de la jornada. obtenemos prueba y jornada del primer elemento del array
        $m=new Equipos("getResultadosEquipos",$this->IDPrueba,$this->IDJornada);
        $teams=$m->getTeamsByJornada();

        // reindexamos por ID y anyadimos campos extra:
        // Tiempo, penalizacion,Puntos, mejor punto del equipo y el array de resultados del equipo
        $equipos=array();
        foreach ($teams as &$equipo) {
            $equipo['Resultados']=array();
            $equipo['Tiempo']=0.0;
            $equipo['Penalizacion']=0.0;
            $equipo['Puntos']=0;
            $equipo['Extra']=0;
            $equipo['Best']=0;
            $equipos[$equipo['ID']]=$equipo;
        }
        // now fill team members array.
        // notice that $resultados is already sorted by results
        foreach($resultados as &$result) {
            $teamid=$result['Equipo'];
            $equipo=&$equipos[$teamid];
            array_push($equipo['Resultados'],$result);
            // suma el tiempo y penalizaciones de los tres/cuatro primeros
            $numdogs=count($equipo['Resultados']);
            if(($numdogs>$maxdogs) && ($result['Penalizacion']<200)) {
                $this->myLogger->info("Team {$equipo['Nombre']} excess Dogs:{$numdogs}. Disqualified");
                $equipo['Tiempo']=0.0;
                $equipo['Puntos']=0;
                $equipo['Extra']=0;
                $equipo['Penalizacion']=400*$mindogs; // todos no presentados, por listos
                continue;
            }
            if ($numdogs<=$mindogs) {
                // almacena los puntos de los tres mejores
                $equipo['Tiempo']+=floatval($result['Tiempo']);
                $equipo['Penalizacion']+=floatval($result['Penalizacion']);
                $equipo['Puntos']+=$result['Puntos'];
                // guardamos el mejor para el caso de empate a puntos y a cuarto puesto
                if (count($equipo['Resultados'])==1) $equipo['Best']=$result['Puntos'];
            } else {
                // en el EO se usa el cuarto perro en caso de empate, por lo que se almacena
                // si hay mas perros (p.e. en modo 3de5) no se toma el dato
                if ($equipo['Extra']==0) $equipo['Extra']=+$result['Puntos'];
            }
        }

        // rastrea los equipos con menos de $mindogs participantes y marca los que faltan
        // no presentados
        $teams=array();
        foreach($equipos as &$equipo) {
            $numdogs=count($equipo['Resultados']);
            if ($numdogs==0) continue; // team with no dogs: ignore
            if ($numdogs>$maxdogs) continue; // too many dogs: disqualify and ignore
            for ($n=$numdogs;$n<$mindogs;$n++) $equipo['Penalizacion']+=400.0;
            array_push($teams,$equipo); // add team to result to remove unused/empty teams
        }
        // re-ordenamos los datos en base a la puntuacion
        usort($teams, function($a, $b) {
            if ( $a['Puntos'] == $b['Puntos'] )	{
                if ($a['Extra']==$b['Extra']) {
                    return ($a['Best'] < $b['Best'])? 1:-1;
                }
                return ($a['Extra'] < $b['Extra'])? 1:-1;
            }
            return ( $a['Puntos'] < $b['Puntos'])?1:-1;
        });
        return $teams;
    }

}
?>