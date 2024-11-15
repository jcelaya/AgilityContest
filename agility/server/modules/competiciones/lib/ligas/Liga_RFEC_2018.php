<?php
/**
 * Created by PhpStorm.
 * User: jantonio
 * Date: 24/01/18
 * Time: 10:36

Liga_RFEC_2018.php

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
require_once (__DIR__."/../../../../database/classes/Ligas.php");
require_once (__DIR__."/../../../Federations.php");

class Liga_RFEC_2018 extends Ligas {

    /**
     * Ligas constructor.
     * @param $file object name used for debbugging
     * @throws Exception on invalid or not found jornada
     */
    function __construct($file) {
        parent::__construct($file);
        // valid competition types are Puntuables FMC Madrid 2018
        $this->validCompetitions=array(2);

    }

    /**
     * Retrieve short form ( global sums ) for all stored results
     * may be overriden for special handling
     * @param {integer} $fed federation id
     * @param {string} $grado
     * @return {array} result in easyui-datagrid response format
     */
    function getShortData($fed,$grado) {
        if ($this->federation==null) {
            $this->federation=Federations::getFederation($fed);
        }
        $cats=$this->federation->get('ListaCategorias');
        $jor="";
        $filter="";
        // filter only valid league modules
        if (count($this->validCompetitions)!==0) {
            $lista=implode(",",$this->validCompetitions);
            $jor="jornadas,";
            $filter=" ( jornadas.Tipo_Competicion IN ( {$lista} ) ) AND ligas.Jornada=jornadas.ID AND ";
        }

        // compose select field query
        $select="perroguiaclub.ID AS Perro, perroguiaclub.Nombre AS Nombre, perroguiaclub.Categoria AS Categoria, ".
                "perroguiaclub.Licencia, perroguiaclub.NombreGuia, perroguiaclub.NombreClub, ";
        if ($grado==="GI") {
                    $select .= "SUM(Puntos) AS Puntos, SUM(Estrellas) AS Ceros";
        }
        if ($grado==="GII") {
            $select .= "SUM(Puntos) AS Puntos";
        }
        // perform select
        $res=$this->__select( // for rsce
            $select,
            "{$jor} ligas, perroguiaclub",
            "{$filter} perroguiaclub.Federation={$fed} AND ligas.Perro=perroguiaclub.ID AND ligas.Grado='{$grado}'",
            "Categoria ASC, Puntos DESC",
            "",
            "Perro"
        );
        // rewrite categoria, as cannot pass "formatCategoria" formatter as function ( passed as string :-( )
        foreach ($res['rows'] as &$row) $row['Categoria']=$cats[$row['Categoria']];

        // add datagrid header common data
        $res['header']= array(
            array('field' => 'Perro',    'hidden'=>'true'),
            array('field' => 'Licencia',    'title'=>_('License'),  'width' => 28, 'align' => 'left'),
            array('field' => 'Categoria',   'title'=>_('Category'), 'width' => 12, 'align' => 'center'),
            array('field' => 'Nombre',      'title'=>_('Name'),     'width' => 20, 'align' => 'center'),
            array('field' => 'NombreGuia',  'title'=>_('Handler'),  'width' => 35, 'align' => 'right'),
            array('field' => 'NombreClub',  'title'=>_('Club'),     'width' => 30, 'align' => 'right')
        );
        if ($grado==="GI") { // extra headers for Promotion
            array_push($res['header'],array('field' => 'Puntos',  'title'=>_('Exc'),  'width' => 10, 'align' => 'center'));
            array_push($res['header'],array('field' => 'Ceros',  'title'=>_('Zeroes'),  'width' => 10, 'align' => 'center'));
        }
        if ($grado==="GII") { // extra headers for Competition
            array_push($res['header'],array('field' => 'Puntos',  'title'=>_('Points'),  'width' => 10,  'align' => 'center'));
        }
        return $res;
    }

    function getLongData($perro,$federation,$grado) {
        $res=parent::getLongData($perro,$federation,$grado);
        // rewrite fields array
        $res['header']= array(
            array('field' => 'Fecha',     'title'=>_('Date'),    'width' => 20, 'align' => 'right'),
            array('field' => 'Prueba',    'title'=>_('Contest'), 'width' => 35, 'align' => 'left'),
            array('field' => 'Jornada',   'title'=>_('Journey'), 'width' => 20, 'align' => 'right'),
            array('field' => 'NombreClub','title'=>_('Club'),    'width' => 30, 'align' => 'right')
        );
        if ($grado==="GI") {
            array_push($res['header'],array('field' => 'C1','title'=>_('Agility')." 1",'width' => 10, 'align' => 'center'));
            array_push($res['header'],array('field' => 'C2','title'=>_('Agility')." 2",'width' => 10, 'align' => 'center'));
            array_push($res['header'],array('field' => 'C3','title'=>_('Agility')." 3",'width' => 10, 'align' => 'center'));
        } else {
            array_push($res['header'],array('field' => 'C1','title'=>_('Agility'),'width' => 10, 'align' => 'center'));
            array_push($res['header'],array('field' => 'C2','title'=>_('Jumping'),'width' => 10, 'align' => 'center'));
            array_push($res['header'],array('field' => 'Calificacion','title'=>_('Final'),'width' => 10, 'align' => 'center'));

        }
        return $res;
    }
}