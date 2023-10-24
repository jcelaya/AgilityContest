<?php
require_once(__DIR__ . "/Puntuable_RFEC_2018.php");

/*
Puntuable_LigaAragon_2024.php

Copyright  2013-2018 by Juan Antonio Martinez ( juansgaviota at gmail dot com )
Copyright  2022-2023 by Javier Celaya ( jcelaya at gmail dot com )

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation;
either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program;
if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

require_once(__DIR__."/lib/ligas/Liga_RFEC_2018.php");

class Puntuable_LigaAragon_2024 extends Puntuable_RFEC_2018 {

    function __construct() {
        parent::__construct("Puntuable Liga Aragonesa 2024");
        $this->federationID = 1;
        $this->federationDefault = 1;
        $this->competitionID = 8;
        $this->moduleVersion = "1.0.0";
        $this->moduleRevision = "20231024_0000";
        $this->federationLogoAllowed = true;
    }

    function getModuleInfo($contact = null)  {
        return parent::getModuleInfo("yvonneagility@fecaza.com");
    }

    /**
     * Evalua la calificacion parcial del perro
     *
     * Note that we cannot call parent::evalPartialCalification() because parent class is Liga_RFEC
     * and it has its own point assignment code. So invoke directly Competitions:: to get generic code
     *
     * @param {object} $m datos de la manga
     * @param {array} $perro datos de puntuacion del perro. Passed by reference
     * @param {array} $puestocat puesto en funcion de la categoria
     */
    public function evalPartialCalification($m, &$perro, $puestocat) {
        $cat = $perro['Categoria'];

        if (in_array($m->Tipo,array(8, 9, 13, 14))) { // equipos
            parent::evalPartialCalification($m, $perro, $puestocat);
            return;
        }
        if ($perro['Grado'] !== "GII") { // solo se puntua en grado II
            Competitions::evalPartialCalification($m, $perro, $puestocat);
            return;
        }
        if (!$this->isInLeague($perro)) { // do not get league points if competitor does not belong to current zone
            $this->poffset[$cat]++; // properly handle puestocat offset
            Competitions::evalPartialCalification($m, $perro, $puestocat);
            return;
        }

        $ptsmanga = array(12, 10, 8, 7, 6, 5, 4, 3, 2, 1); // puntos por manga y puesto
        // puntos a los 10 primeros de la zona liguera por manga/categoria
        // que no estan eliminados o NC
        $pt1 = 0;
        $puesto = $puestocat[$cat] - $this->poffset[$cat];
        $penal = floatval($perro['Penalizacion']);
        if ($puesto <= 10 && $penal < 16) {
            $pt1 = $ptsmanga[$puesto - 1];
            if ($penal == 0.0) $pt1 += 2; // 2 puntos por cero
        }

        if ($penal >= 400)  {
            $perro['Penalizacion'] = 400.0;
            $perro['Calificacion'] = "-";
            $perro['CShort'] = "-";
        }
        else if ($penal >= 200)  {
            $perro['Penalizacion'] = 200.0;
            $perro['Calificacion'] = _("Not Present");
            $perro['CShort'] = _("N.P.");
        }
        else if ($penal >= 100) {
            $perro['Penalizacion'] = 100.0;
            $perro['Calificacion'] = _("Eliminated");
            $perro['CShort'] = _("Elim");
        }
        else if ($penal >= 16) {
            $perro['Calificacion'] = _("Not Clasified");
            $perro['CShort'] = _("N.C.");
        }
        else if ($penal >= 6) {
            $perro['Calificacion'] = _("Very good")." ".$pt1;
            $perro['CShort'] = _("V.G.")." ".$pt1;
        }
        else {
            $perro['Calificacion'] = _("Excellent")." ".$pt1;
            $perro['CShort'] = _("Exc")." ".$pt1;
        }
        // datos para la exportacion de parciales en excel
        $perro['Puntos'] = $pt1;
        $perro['Estrellas'] = 0;
        $perro['Extras'] = 0;
    }

    /**
     * Evalua la calificacion final del perro
     * @param {array} $mangas informacion {object} de las diversas mangas
     * @param {array} $resultados informacion {array} de los resultados de cada manga
     * @param {array} $perro datos de puntuacion del perro. Passed by reference
     * @param {array} $puestocat puesto en funcion de la categoria
     */
    public function evalFinalCalification($mangas, $resultados, &$perro, $puestocat) {
        $cat = $perro['Categoria'];

        // si no grado II utiliza los sistemas de calificacion de la RFEC
        if ($perro['Grado'] !== "GII") {
            parent::evalFinalCalification($mangas, $resultados, $perro, $puestocat);
            return;
        }
        // los "extranjeros" no puntuan
        if (!$this->isInLeague($perro)) {
            $this->pfoffset[$cat]++; // properly handle puestocat offset
            return;
        }

        if ( ($resultados[0] == null) || ($resultados[1] == null)) {
            $perro['Calificacion'] = " ";
        } else { // se coge la peor calificacion
            $perro['Calificacion'] = $perro['P1'] < $perro['P2'] ? $perro['C2'] : $perro['C1'];
        }
        $perro['Puntos'] = 0;
        $perro['Estrellas'] = 0;
        $perro['Extras'] = 0;

        $ptsglobal = array(12, 10, 8, 7, 6, 5, 4, 3, 2, 1); //puestos por general si tiene excelente o muy bueno

        // manga 1
        $pt1 = 0;
        if ($resultados[0] !== null) { // extraemos los puntos de la primera manga
            $x = trim(substr($perro['C1'], -2));
            $pt1 = is_numeric($x) ? $x : 0;
        }
        // manga 2
        $pt2 = 0;
        if ($resultados[1] !== null) { // extraemos los puntos de la segunda manga
            $x = trim(substr($perro['C2'], -2));
            $pt2 = is_numeric($x) ? $x : 0;
        }
        // conjunta
        // Temporada 2024 no puntuan en conjunta si tienen alguna manga con mas de 16.00 (NC)
        if ( ($perro['P1'] >= 16.0) || ($perro['P2'] >= 16.0) ) {
            $perro['Calificacion'] = "$pt1 - $pt2 - 0";
            $perro['Puntos'] = $pt1 + $pt2;
            return;
        }
        // evaluamos puesto real una vez eliminados los "extranjeros"
        $puesto = $puestocat[$cat] - $this->pfoffset[$cat];
        // si esta entre los 10 primeros cogemos los puntos
        $pfin = $puesto < 11 ? $ptsglobal[$puesto - 1] : 0;
        // y asignamos la calificacion final
        $perro['Calificacion'] = "$pt1 - $pt2 - $pfin";
        $perro['Puntos'] = $pt1 + $pt2 + $pfin;
    }

    /**
     * Retrieve handler for manage Ligas functions.
     * Default is use standard Ligas, but may be overriden ( eg wao. Rounds )
     * @param {string} $file
     * @return {Ligas} instance of requested Ligas object
     * @throws Exception on invalid prueba/jornada/manga
     */
    protected function getLigasObject($file) {
        return new Liga_RFEC_2018($file);
    }
}