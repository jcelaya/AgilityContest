<?php
/*
PrintPodium.php

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
/**
 * genera un CSV con los datos para las etiquetas
 */

require_once(__DIR__."/../fpdf.php");
require_once(__DIR__."/../../tools.php");
require_once(__DIR__."/../../logging.php");
require_once(__DIR__.'/../../database/classes/DBObject.php');
require_once(__DIR__.'/../../database/classes/Clubes.php');
require_once(__DIR__.'/../../database/classes/Pruebas.php');
require_once(__DIR__.'/../../database/classes/Jueces.php');
require_once(__DIR__.'/../../database/classes/Jornadas.php');
require_once(__DIR__.'/../../database/classes/Mangas.php');
require_once(__DIR__.'/../../database/classes/Resultados.php');
require_once(__DIR__.'/../../database/classes/Clasificaciones.php');
require_once(__DIR__."/../print_common.php");

class PrintClasificacionGeneral extends PrintCommon {
	
	protected $manga1;
    protected $manga2;
    protected $manga3;
	protected $resultados;
	protected $hasGrades;

	 /** Constructor
     *@param {int} $prueba
     *@param {int} $jornada
	 *@param {array} $mangas lista de mangaid's
	 *@param {array} $results resultados asociados a la manga pedidas
	 *@throws Exception
	 */
	function __construct($prueba,$jornada,$mangas,$results) {
		parent::__construct('Landscape',"print_podium",$prueba,$jornada);
		$dbobj=new DBObject("print_podium");
        $this->manga1=($mangas[0]!=0)?$dbobj->__getObject("mangas",$mangas[0]):null;
        $this->manga2=($mangas[1]!=0)?$dbobj->__getObject("mangas",$mangas[1]):null;
        $this->manga3=($mangas[2]!=0)?$dbobj->__getObject("mangas",$mangas[2]):null;
		$this->resultados=$results;
		$this->hasGrades=Jornadas::hasGrades($this->jornada);

        // do not show fed icon in pre-agility, special, or ko
        if (in_array($this->manga1->Tipo,array(0,1,2,15,16,18,19,20,21,22,23,24,))) {
            $this->icon2=getIconPath($this->federation->get('Name'),"null.png");
        }
        // set file name
        $this->set_FileName("Clasificacion_General.pdf");
	}

	function Header() {
		$this->print_commonHeader(_("Final Scores"));
	}
	
	// Pie de página: tampoco cabe
	function Footer() {
		$this->print_commonFooter();
	}

    private function getRondaName($manga) {
        $tmanga=_(Mangas::getTipoManga($manga->Tipo,3,$this->federation));
        if ( $manga->Grado==="GI" ) {
            if ($manga->Observaciones!=="")	$tmanga .= " ({$manga->Observaciones})";
            else if (isMangaAgility($manga->Tipo)) $tmanga .= " (Agility)";
            else if (isMangaJumping($manga->Tipo)) $tmanga .= " (Jumping)";
        }
        return $tmanga;
    }

    function print_datosMangas($data,$flag=true) { // on flag:false do not paint journey data
        $cat= $this->federation->getMangaMode($data['Mode'],0);
        // objeto para buscar jueces
        $jobj=new Jueces("print_Clasificaciones");

        $y=$this->GetY();
        if ($flag) { // on flag:true do not paint prueba/jornada/manga info, just TRS data
            $y=$this->GetY();
            // imprimimos informacion de la manga
            $this->setX(10);
            $this->SetFont($this->getFontName(),'B',11); // bold 9px
            $this->Cell(80,6,_('Journey').": {$this->jornada->Nombre}",0,0,'',false);
            $this->Ln(6);
            $this->Cell(80,6,_('Date').": {$this->jornada->Fecha}",0,0,'',false);
            $this->Ln(6);
            // $ronda=$this->getGradoString(intval($this->manga1->Tipo)); // todas las mangas comparten grado
            $ronda=_(Mangas::getTipoManga($this->manga1->Tipo,4,$this->federation)); // la misma que la manga 2
            $this->Cell(80,6,"$ronda",0,0,'',false);
            $this->SetY($y);
        }

        // ahora los datos de cada manga individual
        // manga 1:
        if ($this->manga1!=null) {
            // pintamos los datos de TRS
            $trs=$data['TRS'][0];
            $y=$this->GetY();
            $this->SetX(80);
            // ponemos los datos de TRS
            $this->SetFont($this->getFontName(),'B',10); // bold 9px
            $this->Cell(90,8,"","LTB",0,'L',false);// caja vacia
            $this->Cell(20,8,_('Dist').".: {$trs['dist']}m","LTB",0,'L',false);
            $this->Cell(20,8,_('Obst').".: {$trs['obst']}","LTB",0,'L',false);
            $this->Cell(25,8,_('SCT').": {$trs['trs']}s","LTB",0,'L',false);
            $this->Cell(25,8,_('MCT').": {$trs['trm']}s","LTB",0,'L',false);
            $this->Cell(25,8,_('Vel').".: {$trs['vel']}m/s","LTRB",0,'L',false);
            // ahora el nombre de la manga
            $nmanga=$this->getRondaName($this->manga1) .  " - " . $cat;
            $juez1=$jobj->selectByID($this->manga1->Juez1); $juez2=$jobj->selectByID($this->manga1->Juez2);
            $this->setXY(81,$y+1);
            $this->SetFont($this->getFontName(),'B',10); // bold 9px
            $this->Cell( 88,4,$nmanga,"",0,'L',false);
            // y el nombre de los jueces
            $this->setXY(81,$y+4);
            $this->SetFont($this->getFontName(),'I',8); // bold 9px
            $jueces = _('Judge') .": ". $juez1['Nombre'];
            $jueces .= ($juez2['Nombre']==="-- Sin asignar --")? "" : " - {$juez2['Nombre']}";
            $this->Cell( 88,4,$jueces,"",0,'R',false);
            $this->Ln(4);
        }
        // manga 2:
        if ($this->manga2!=null) {
            // pintamos los datos de TRS
            $trs=$data['TRS'][1];
            $y=$this->GetY();
            $this->SetX(80);
            $this->SetFont($this->getFontName(),'B',10); // bold 9px
            $this->Cell(90,8,"","LTB",0,'L',false);// caja vacia
            $this->Cell(20, 8, _('Dist') . ".: {$trs['dist']}m", "LTB", 0, 'L', false);
            $this->Cell(20, 8, _('Obst') . ".: {$trs['obst']}", "LTB", 0, 'L', false);
            $this->Cell(25, 8, _('SCT') . ": {$trs['trs']}s", "LTB", 0, 'L', false);
            $this->Cell(25, 8, _('MCT') . ": {$trs['trm']}s", "LTB", 0, 'L', false);
            $this->Cell(25, 8, _('Vel') . ".: {$trs['vel']}m/s", "LTBR", 0, 'L', false);
            // ahora el nombre de la manga y los jueces
            $nmanga=$this->getRondaName($this->manga2) . " - " . $cat;
            $juez1=$jobj->selectByID($this->manga2->Juez1); $juez2=$jobj->selectByID($this->manga2->Juez2);
            $this->setXY(81,$y+1);
            $this->SetFont($this->getFontName(),'B',10); // bold 9px
            $this->Cell( 88,4,$nmanga,"",0,'L',false);
            $this->setXY(81,$y+4);
            $this->SetFont($this->getFontName(),'I',8); // bold 9px
            $jueces = _('Judge') .": ". $juez1['Nombre'];
            $jueces .= ($juez2['Nombre']==="-- Sin asignar --")? "" : " - {$juez2['Nombre']}";
            $this->Cell( 88,4,$jueces,"",0,'R',false);
            $this->Ln(4);
        }
        // manga 3:
        if ($this->manga3!=null) {
            // pintamos los datos de TRS
            $trs=$data['TRS'][2];
            $y=$this->GetY();
            $this->SetX(80);
            $this->SetFont($this->getFontName(),'B',10); // bold 9px
            $this->Cell(90,8,"","LTB",0,'L',false); // caja vacia
            $this->Cell(20,8,_('Dist').".: {$trs['dist']}m","LTB",0,'L',false);
            $this->Cell(20,8,_('Obst').".: {$trs['obst']}","LTB",0,'L',false);
            $this->Cell(25,8,_('SCT').": {$trs['trs']}s","LTB",0,'L',false);
            $this->Cell(25,8,_('MCT').": {$trs['trm']}s","LTB",0,'L',false);
            $this->Cell(25,8,_('Vel').".: {$trs['vel']}m/s","LTBR",0,'L',false);
            // ahora el nombre de la manga
            $nmanga=$this->getRondaName($this->manga3) .  " - " . $cat;
            $juez1=$jobj->selectByID($this->manga3->Juez1); $juez2=$jobj->selectByID($this->manga3->Juez2);
            $this->setXY(81,$y+1);
            $this->SetFont($this->getFontName(),'B',10); // bold 9px
            $this->Cell( 88,4,$nmanga,"",0,'L',false);
            // ahora el nombre de  los jueces
            $this->setXY(81,$y+4);
            $this->SetFont($this->getFontName(),'I',8); // bold 9px
            $jueces = _('Judge') .": ". $juez1['Nombre'];
            $jueces .= ($juez2['Nombre']==="-- Sin asignar --")? "" : " - {$juez2['Nombre']}";
            $this->Cell( 88,4,$jueces,"",0,'R',false);
            $this->Ln(4);
        }
        // extra space between data and results
        $this->Ln(3);
    }

	function print_InfoJornada() {
		$this->setXY(10,42);
		$this->ac_SetFillColor($this->config->getEnv('pdf_hdrbg2')); // gris
		$this->ac_SetTextColor($this->config->getEnv('pdf_hdrfg2')); // negro
		$this->ac_SetDrawColor("0x000000"); // line color
		$this->SetFont($this->getFontName(),'B',11); // bold 11px
		$this->Cell(140,6,_("Journey").": {$this->jornada->Nombre}",0,0,'L',true);
		$this->Cell(135,6,_("Date").": {$this->jornada->Fecha}",0,0,'R',true);
		$this->ln(8); // TODO: write jornada / fecha / grado
	}
	
	function writeTableHeader($strcats) {
        $tm1=(!is_null($this->manga1))?_(Mangas::getTipoManga($this->manga1->Tipo,3,$this->federation)):"";
        $tm2=(!is_null($this->manga2))?_(Mangas::getTipoManga($this->manga2->Tipo,3,$this->federation)):"";
        $tm3=(!is_null($this->manga3))?_(Mangas::getTipoManga($this->manga3->Tipo,3,$this->federation)):"";
        $factor=($tm3==="")?1:0.75;
		$this->ac_header(1,12);
		
		$this->SetX(10); // first page has 3 extra header lines
		// REMINDER: $this->cell( width, height, data, borders, where, align, fill)

        // first row of table header
		$this->SetFont($this->getFontName(),'BI',12); // default font
		$this->Cell(115,5,$strcats,0,0,'L',true);
        $this->Cell(59*$factor,5,$tm1,0,0,'C',true);
        $this->Cell(59*$factor,5,$tm2,0,0,'C',true);
        if($tm3!=="") $this->Cell(59*$factor,6,$tm3,0,0,'C',true);
		$this->Cell(42*$factor,5,_('Score'),0,0,'C',true);
		$this->ln();
		$this->SetFont($this->getFontName(),'',8); // default font

		// datos del participante
		$this->Cell(10,5,_('Dorsal'),0,0,'C',true); 	// dorsal
        if ($this->useLongNames){
            $this->Cell(40,5,_('Name'),0,0,'L',true);	// nombre
        } else {
            $this->Cell(25,5,_('Name'),0,0,'L',true);	// nombre
            $this->Cell(15,5,_('Lic').'.',0,0,'C',true);	// licencia
        }
		if ($this->hasGrades){
			$this->Cell(10,5,_('Cat').'/'._('Grd'),0,0,'C',true);	// categoria/grado
		} else {
			$this->Cell(10,5,_('Cat'),0,0,'C',true);	// categoria/grado
		}
		$this->Cell(35,5,_('Handler'),0,0,'L',true);	// nombreGuia
		$this->Cell(20,5,$this->strClub,0,0,'L',true);	// nombreClub

		// manga 1
        if (!is_null($this->manga1)) {
            $this->Cell(7*$factor,5,_('F/T'),0,0,'C',true);	// 1- Faltas+Tocados
            $this->Cell(7*$factor,5,_('Ref'),0,0,'C',true);	// 1- Rehuses
            $this->Cell(12*$factor,5,_('Time'),0,0,'C',true);	// 1- Tiempo
            $this->Cell(9*$factor,5,_('Vel'),0,0,'C',true);	// 1- Velocidad
            $this->Cell(12*$factor,5,_('Penal'),0,0,'C',true);	// 1- Penalizacion
            $this->Cell(12*$factor,5,'Calif',0,0,'C',true);	// 1- calificacion
        } else {
            $this->Cell(59*$factor,5,'',0,0,'C',true);	// espacio en blanco
        }

		// manga 2
		if (!is_null($this->manga2)) {
			$this->Cell(7*$factor,5,_('F/T'),0,0,'C',true);	// 2- Faltas+Tocados
			$this->Cell(7*$factor,5,_('Ref'),0,0,'C',true);	// 2- Rehuses
			$this->Cell(12*$factor,5,_('Time'),0,0,'C',true);	// 2- Tiempo
			$this->Cell(9*$factor,5,_('Vel'),0,0,'C',true);	// 2- Velocidad
			$this->Cell(12*$factor,5,_('Penal'),0,0,'C',true);	// 2- Penalizacion
			$this->Cell(12*$factor,5,_('Calif'),0,0,'C',true);	// 2- calificacion
		} else {
			$this->Cell(59*$factor,5,'',0,0,'C',true);	// espacio en blanco
		}

        // manga 3
        if (!is_null($this->manga3)) {
            $this->Cell(7*$factor,5,_('F/T'),0,0,'C',true);	// 2- Faltas+Tocados
            $this->Cell(7*$factor,5,_('Ref'),0,0,'C',true);	// 2- Rehuses
            $this->Cell(12*$factor,5,_('Time'),0,0,'C',true);	// 2- Tiempo
            $this->Cell(9*$factor,5,_('Vel'),0,0,'C',true);	// 2- Velocidad
            $this->Cell(12*$factor,5,_('Penal'),0,0,'C',true);	// 2- Penalizacion
            $this->Cell(12*$factor,5,_('Calif'),0,0,'C',true);	// 2- calificacion
        }
        // do not print empty data if no round 3

		// global
		$this->Cell(12*$factor,5,_('Time'),0,0,'C',true);	// Tiempo total
		$this->Cell(12*$factor,5,_('Penaliz'),0,0,'C',true);	// Penalizacion
		$this->Cell(10*$factor,5,_('Calific'),0,0,'C',true);	// Calificacion
		$this->Cell(8*$factor,5,_('Position'),0,0,'C',true);	// Puesto
		$this->Ln();	
		// restore colors
		$this->ac_SetFillColor($this->config->getEnv('pdf_rowcolor2')); // azul merle
		$this->SetTextColor(0,0,0); // negro
		$this->ac_SetDrawColor($this->config->getEnv('pdf_linecolor')); // line color
	}

    function writeEmptyCell($idx,$row) {
        // REMINDER: $this->cell( width, height, data, borders, where, align, fill)
        $y=$this->getY();
        $this->SetX(10 ); // first page has 3 extra header lines
        $this->ac_row($idx,9);
        // fomateamos datos
        $fired=0; // count to remove "puesto" when eliminated in all rounds
        $factor=1;
        $v1="";$t1="";$p1="";
        $v2="";$t2="";$p2="";
        $v3="";$t3="";$p3="";
        $puesto= ($row['Penalizacion']>=$fired)? "-":"{$row['Puesto']}º";
        $penal=number_format2($row['Penalizacion'],$this->timeResolution);
        $tiempo=number_format2($row['Tiempo'],$this->timeResolution);
        if (!is_null($this->manga3)) $factor=0.75;
        $this->Cell(10,6,"",0,0,'R',true); 	// dorsal
        $this->SetFont($this->getFontName(),'',7); //

        if ($this->useLongNames) {
            $this->Cell(40,6,"",0,0,'L',true);	// nombrelargo
            $this->SetFont($this->getFontName(),'',9); // default font
        } else {
            $this->Cell(25,6,"",0,0,'L',true);	// nombre
            $this->SetFont($this->getFontName(),'',9); // default font
            $this->Cell(15,6,"",0,0,'C',true);	// licencia
        }
        $this->Cell(10,6,"",0,0,'C',true);	// categoria/grado
        $this->Cell(35,6,"",0,0,'R',true);	// nombreGuia
        $this->Cell(20,6,"",0,0,'R',true);	// nombreClub
        $this->Cell(59*$factor,6,'',0,0,'C',true);	// manga 1
        $this->Cell(59*$factor,6,'',0,0,'C',true);	// manga 2
        if (!is_null($this->manga3)) $this->Cell(59*$factor,6,'',0,0,'C',true);	// manga 3

        // global
        $this->Cell(24*$factor,6,"",0,0,'C',true);	// Tiempo / Penalización
        $this->SetFont($this->getFontName(),'B',6); // default font
        $this->Cell(10*$factor,6,"-",0,0,'C',true);	// Calificacion
        $this->SetFont($this->getFontName(),'B',10); // default font
        $this->Cell(8*$factor,6,$puesto,0,0,'R',true);	// Puesto

        // lineas verticales
        $this->ac_SetDrawColor($this->config->getEnv('pdf_linecolor'));
        $this->Line(10    ,$y,10,    $y+6);
        $this->Line(10+115,$y,10+115,$y+6);
        $this->Line(10+115+59*$factor,$y,10+115+59*$factor,$y+6);
        $this->Line(10+115+59*2*$factor,$y,10+115+59*2*$factor,$y+6);
        if(!is_null($this->manga3)) {
            $this->Line(10+115+59*3*$factor,$y,10+115+59*3*$factor,$y+6);
            $this->Line(10+115+(59*3+42)*$factor,$y,10+115+(59*3+42)*$factor,$y+6);
        } else {
            $this->Line(10+115+(59*2+42)*$factor,$y,10+115+(59*2+42)*$factor,$y+6);
        }
        $this->Ln(6);
    }

	function writeCell($idx,$row) {
		// REMINDER: $this->cell( width, height, data, borders, where, align, fill)
		$y=$this->getY();
		$this->SetX(10 ); // first page has 3 extra header lines
		$this->ac_row($idx,9);
		
		// fomateamos datos
        $fired=0; // count to remove "puesto" when eliminated in all rounds
        $factor=1;
        if (!is_null($this->manga1)) {
            $v1= ($row['P1']>=200)?"-":number_format2($row['V1'],2);
            $t1= ($row['P1']>=200)?"-":number_format2($row['T1'],$this->timeResolution);
            $p1=number_format2($row['P1'],$this->timeResolution);
            $fired+=100;
        } else { $v1="";$t1="";$p1=""; }
        if (!is_null($this->manga2)) {
            $v2= ($row['P2']>=200)?"-":number_format2($row['V2'],2);
            $t2= ($row['P2']>=200)?"-":number_format2($row['T2'],$this->timeResolution);
            $p2=number_format2($row['P2'],$this->timeResolution);
            $fired+=100;
        } else { $v2="";$t2="";$p2=""; }
        if (!is_null($this->manga3)) {
            $factor=0.75;
            $v3= ($row['P3']>=200)?"-":number_format2($row['V3'],2);
            $t3= ($row['P3']>=200)?"-":number_format2($row['T3'],$this->timeResolution);
            $p3=number_format2($row['P3'],$this->timeResolution);
            $fired+=100;
        } else { $v3="";$t3="";$p3=""; }
        $puesto= ($row['Penalizacion']>=$fired)? "-":"{$row['Puesto']}º";
        $penal=number_format2($row['Penalizacion'],$this->timeResolution);
        $tiempo=number_format2($row['Tiempo'],$this->timeResolution);
		
		// REMINDER: $this->cell( width, height, data, borders, where, align, fill)
		// datos del participante
		$this->Cell(10,6,$row['Dorsal'],0,0,'R',true); 	// dorsal
		$this->SetFont($this->getFontName(),'',8); //
        if ($this->useLongNames) {
            $nombre=$row['Nombre']." - ".$row['NombreLargo'];
            $this->Cell(40,6,$nombre,0,0,'L',true);	// nombre (20,y
        } else {
            $this->Cell(25,6,$row['Nombre'],0,0,'L',true);	// nombre (20,y
            $this->Cell(15,6,$row['Licencia'],0,0,'C',true);	// licencia
        }
        $this->SetFont($this->getFontName(),'',9); // default font
        $cat=$this->federation->getCategoryShort($row['Categoria']);
		if ($this->hasGrades) {
            $grad=$this->federation->getGradeShort($row['Grado']);
			$this->Cell(10,6,"{$cat} {$grad}",0,0,'C',true);	// categoria/grado
		} else {
			$this->Cell(10,6,"{$cat}",0,0,'C',true);	// solo categoria (Individual-Open/Teams/KO)
		}
		$this->SetFont($this->getFontName(),'',8); //
		$this->Cell(35,6,$this->getHandlerName($row),0,0,'L',true);	// nombreGuia
		$this->Cell(20,6,$row['NombreClub'],0,0,'L',true);	// nombreClub
        $this->SetFont($this->getFontName(),'',9); // default font

        // manga 1
        if(!is_null($this->manga1)) {
            // manga 1
            $this->Cell(7*$factor,6,$row['F1'],0,0,'C',true);	// 1- Faltas+Tocados
            $this->Cell(7*$factor,6,$row['R1'],0,0,'C',true);	// 1- Rehuses
            $this->Cell(12*$factor,6,$t1,0,0,'C',true);	// 1- Tiempo
            $this->Cell(9*$factor,6,$v1,0,0,'C',true);	// 1- Velocidad
            $this->Cell(12*$factor,6,$p1,0,0,'C',true);	// 1- Penalizacion
            $this->Cell(12*$factor,6,$row['C1'],0,0,'C',true);	// 1- calificacion
        } else {
            $this->Cell(59*$factor,6,'',0,0,'C',true);	// espacio en blanco
        }

		// manga 2
		if (!is_null($this->manga2)) {
			$this->Cell(7*$factor,6,$row['F2'],0,0,'C',true);	// 2- Faltas+Tocados
			$this->Cell(7*$factor,6,$row['R2'],0,0,'C',true);	// 2- Rehuses
			$this->Cell(12*$factor,6,$t2,0,0,'C',true);	// 2- Tiempo
			$this->Cell(9*$factor,6,$v2,0,0,'C',true);	// 2- Velocidad
			$this->Cell(12*$factor,6,$p2,0,0,'C',true);	// 2- Penalizacion
			$this->Cell(12*$factor,6,$row['C2'],0,0,'C',true);	// 2- calificacion
		} else {
			$this->Cell(59*$factor,6,'',0,0,'C',true);	// espacio en blanco
		}

        // manga 3
        if (!is_null($this->manga3)) {
            $this->Cell(7*$factor,6,$row['F3'],0,0,'C',true);	// 2- Faltas+Tocados
            $this->Cell(7*$factor,6,$row['R3'],0,0,'C',true);	// 2- Rehuses
            $this->Cell(12*$factor,6,$t3,0,0,'C',true);	// 2- Tiempo
            $this->Cell(9*$factor,6,$v3,0,0,'C',true);	// 2- Velocidad
            $this->Cell(12*$factor,6,$p3,0,0,'C',true);	// 2- Penalizacion
            $this->Cell(12*$factor,6,$row['C3'],0,0,'C',true);	// 2- calificacion
        }

		// global
		$this->Cell(12*$factor,6,$tiempo,0,0,'C',true);	// Tiempo
		$this->Cell(12*$factor,6,$penal,0,0,'C',true);	// Penalizacion
        $this->SetFont($this->getFontName(),'B',6); // default font
		$this->Cell(10*$factor,6,$row['Calificacion'],0,0,'C',true);	// Calificacion
        $this->SetFont($this->getFontName(),'B',10); // default font
		$this->Cell(8*$factor,6,$puesto,0,0,'R',true);	// Puesto
		// lineas rojas
		$this->ac_SetDrawColor($this->config->getEnv('pdf_linecolor'));
		$this->Line(10    ,$y,10,    $y+6);
		$this->Line(10+115,$y,10+115,$y+6);
		$this->Line(10+115+59*$factor,$y,10+115+59*$factor,$y+6);
		$this->Line(10+115+59*2*$factor,$y,10+115+59*2*$factor,$y+6);
		if(!is_null($this->manga3)) {
            $this->Line(10+115+59*3*$factor,$y,10+115+59*3*$factor,$y+6);
            $this->Line(10+115+(59*3+42)*$factor,$y,10+115+(59*3+42)*$factor,$y+6);
        } else {
            $this->Line(10+115+(59*2+42)*$factor,$y,10+115+(59*2+42)*$factor,$y+6);
        }
		$this->Ln(6);
	}

	function composeTable() {
		$this->myLogger->enter();
        $len=(($this->manga3)!==null)?115+(59*3+42)*0.75:115+59*2+42; // lenght of closing line

		// en las siguientes hojas pintamos los resultados
        $this->AddPage();
        $this->print_InfoJornada();
		foreach($this->resultados as $data) {
		    if (count($data['Data'])==0) continue; // la altura no tiene resultados
		    // si el comienzo de la categoria esta cerca del final de la pagina
            // salta a la pagina siguiente
            if ($this->GetY()>165) {
		        $this->AddPage();
		        $this->print_InfoJornada(); // nombre de la jornada y fecha
            }
			$rowcount=0;
            $this->print_datosMangas($data); // datos de la manga ( juez, trs, etc )
			foreach($data['Data'] as $row) {
			    if ($this->GetY()>185) {
                    $this->AddPage();
                    $this->print_InfoJornada(); // nombre de la jornada y fecha
                    $rowcount=0;
                }
				if($rowcount==0) {
                    $strcats=Mangas::getMangaMode($data['Mode'],0,$this->federation);
				    $this->writeTableHeader($strcats);
                } // cabecera de la tabla de resultados
				$this->ac_SetDrawColor($this->config->getEnv('pdf_linecolor')); // line color
				$this->writeCell( $rowcount,$row);
				$rowcount++;
			}
			// pintamos linea de cierre final
			$this->setX(10);
			$this->ac_SetDrawColor($this->config->getEnv('pdf_linecolor')); // line color
			$this->cell($len,0,'','T'); // celda sin altura y con raya
			$this->Ln(5); // 3 mmts to next box
		}
		$this->myLogger->leave();
	}

	protected function mergeResults($indexes) {
        $this->myLogger->enter();
        // fase 1 agrupa los arrays
        $result=array();
        foreach($indexes as $index) {
            $size=count($this->resultados[$index]['Data']);
            $this->myLogger->trace("Adding index:{$index} count:{$size}");
            $result = array_merge($result,$this->resultados[$index]['Data']);
        }
        // fase 2 ordena el resultado
        usort($result, function($a, $b) {
            if ( $a['Penalizacion'] == $b['Penalizacion'] ) return ($a['Tiempo'] > $b['Tiempo'])? 1:-1;
            return ( $a['Penalizacion'] > $b['Penalizacion'])?1:-1;
        });
        $puesto=0;
        $last_t=0;$last_p=0;
        // fase 3: ajusta puestos
        for ($n=0;$n<count($result);$n++) {
            if ( ($result[$n]['Tiempo']!=$last_t) || ($result[$n]['Penalizacion']!=$last_p) ){
                // time or penalization differs: next puesto
                $result[$n]['Puesto']=$n+1;
                $puesto=$n+1;
                $last_t=$result[$n]['Tiempo'];
                $last_p=$result[$n]['Penalizacion'];
            } else { // same data: preserve puesto
                $result[$n]['Puesto']=$puesto;
            }
        }
        $this->myLogger->leave();
        return $result;
    }

    /**
     * imprime agrupando categorias segun el argumento solicitado
     * Se asume que la manga está configurada como recorridos separados
     * con lo que tenemos 5 alturas con las siguientes posibilidades de agrupacion (indicadas en mergecats:
     * [0],[1],[2],[3],[4] X-L-M-S-T
     * [0,1],[2],[3,4]     XL-M-ST
     * [0,1],[2,3,4]       XL-MST
     * [0,1,2,3,4]         XLMST
     * @param {array} $mergecats array with cats to be merged
     * @throws Exception
     */
    function composeMergedTable($mergecats) {
        $this->myLogger->enter();
        $len=(($this->manga3)!==null)?115+(59*3+42)*0.75:115+59*2+42; // lenght of closing line

		// en las siguientes hojas pintamos los resultados
        $this->AddPage();
        $this->print_InfoJornada();
        foreach ($mergecats as $indexes) {
            // mezclamos los resultados
            $result=$this->mergeResults($indexes);
            if (count($result)==0) continue; // no data, skip to next group
		    // si el comienzo de la categoria esta cerca del final de la pagina
            // salta a la pagina siguiente
            if ($this->GetY()>(185 - 24*count($indexes))) {
		        $this->AddPage();
		        $this->print_InfoJornada(); // nombre de la jornada y fecha
            }
            $rowcount=0; // just an index to display background on cells
            $names=array();
            // imprimimos los datos de trs de cada una de las alturas a mezclar
            for ($n=0;$n<count($indexes);$n++) {
                $this->print_datosMangas($this->resultados[$indexes[$n]],($n==0)?true:false);
                $names[]=Mangas::getMangaMode($this->resultados[$indexes[$n]]['Mode'],0,$this->federation);
            }
            foreach($result as $row) {
			    if ($this->GetY()>185) {
                    $this->AddPage();
                    $this->print_InfoJornada(); // nombre de la jornada y fecha
                    $rowcount=0;
                }
				if($rowcount==0) {
                    // componemos el nombre de la manga en base a los indices
                    // imprimimos el resultado mezclado iterando sobre ellos
				    $this->writeTableHeader(implode(" + ",$names));
                } // cabecera de la tabla de resultados
				$this->ac_SetDrawColor($this->config->getEnv('pdf_linecolor')); // line color
                $this->writeCell($rowcount++,$row);
            }
			// pintamos linea de cierre final
			$this->setX(10);
			$this->ac_SetDrawColor($this->config->getEnv('pdf_linecolor')); // line color
			$this->cell($len,0,'','T'); // celda sin altura y con raya
			$this->Ln(5); // 3 mmts to next box
        }
        $this->myLogger->leave();
    }
}
?>