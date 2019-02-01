<?php
/**
 * Created by PhpStorm.
 * User: jantonio
 * Date: 1/02/19
 * Time: 11:53
 */

class PrintEtiquetasCNEAC extends PrintCommon  {

    protected $data = array(
        // datos generales
        'Organizer' => array ( 0.15,    0.17, "Organizador"),
        'Country'   => array ( 0.55,    0.17, "País"),
        'Date'      => array ( 0.83,    0.17, 'Fecha'),
        'Name'      => array ( 0.07,    0.21, 'Nombre'),
        'LOE_RRC'   => array ( 0.40,    0.21, 'LOE_RRC'),
        'Breed'     => array ( 0.62,    0.21, 'Raza'),
        'Gender'    => array ( 0.91,    0.21, 'Género'),
        'Handler'   => array ( 0.11,    0.25, 'Guía'),
        'Club'      => array ( 0.46,    0.25, 'Club'),
        'Province'  => array ( 0.77,    0.25, 'Provincia'),
        'License'   => array ( 0.11,    0.29, 'Licencia'),
        'Dorsal'    => array ( 0.36,    0.29, 'Dorsal'),
        'Category'  => array ( 0.60,    0.29, 'Categoria'),
        'Grade'     => array ( 0.82,    0.29, 'Grado'),
        // datos de la primera manga
        'Juez11'            => array ( 0.10,    0.44, 'Juez1 Agility'),
        'Juez12'            => array ( 0.10,    0.47, 'Juez2 Agility'),
        'Participantes1'    => array ( 0.28,    0.45, 'Num'),
        'Longitud1'         => array ( 0.36,    0.45, 'Long'),
        'Obstaculos1'       => array ( 0.43,    0.45, 'Obst'),
        'TRS1'              => array ( 0.50,    0.45, 'TRS'),
        'TRM1'              => array ( 0.54,    0.45, 'TRM'),
        'T1'                => array ( 0.58,    0.45, 'Tiempo'),
        'V1'                => array ( 0.65,    0.45, 'Veloc'),
        'PTiempo1'          => array ( 0.71,    0.45, 'P.Tiem'),
        'PRecorrido1'       => array ( 0.78,    0.45, 'P.Rec'),
        'P1'                => array ( 0.84,    0.45, 'Penal'),
        'Puesto1'           => array ( 0.90,    0.45, 'Puesto'),
        'C1'                => array ( 0.95,    0.45, 'Calif'),
        // datos de la segunda manga
        'Juez21'            => array ( 0.10,    0.62, 'Juez1 Jumping'),
        'Juez22'            => array ( 0.10,    0.65, 'Juez2 Jumping'),
        'Participantes2'    => array ( 0.28,    0.63, 'Num'),
        'Longitud2'         => array ( 0.36,    0.63, 'Long'),
        'Obstaculos2'       => array ( 0.43,    0.63, 'Obst'),
        'TRS2'              => array ( 0.50,    0.63, 'TRS'),
        'TRM2'              => array ( 0.54,    0.63, 'TRM'),
        'T2'                => array ( 0.58,    0.63, 'Tiempo'),
        'V2'                => array ( 0.65,    0.63, 'Veloc'),
        'PTiempo2'          => array ( 0.71,    0.63, 'P.Tiem'),
        'PRecorrido2'       => array ( 0.78,    0.63, 'P.Rec'),
        'P2'                => array ( 0.84,    0.63, 'Penal'),
        'Puesto2'           => array ( 0.90,    0.63, 'Puesto'),
        'C2'                => array ( 0.95,    0.63, 'Calif')
    );

    /**
     * Constructor
     * @param {integer} $prueba Prueba ID
     * @param {integer} $jornada Jornada ID
     * @param {integer} $m Print mode. 0:Trs/Trm evaluation calc sheet 1:Trsdata template to enter data
     * @throws Exception
     */
    function __construct($prueba,$jornada) {
        date_default_timezone_set('Europe/Madrid');
        parent::__construct('Portrait',"print_cneac",$prueba,$jornada);
        if ( ($prueba<=0) || ($jornada<=0) ) {
            $this->errormsg="printTemplates: either prueba or jornada data are invalid";
            throw new Exception($this->errormsg);
        }
    }

    function Header() { /* empty */ }

    function Footer() {
        $this->print_commonFooter();
    }

    function getImage() {
        $img =imagecreatefrompng(__DIR__."/../cneac/cneac_result_form.png");

        // colores blanco y negro
        $black=imagecolorallocate($img,0,0,0);
        $white=imagecolorallocate($img, 255,255, 255);

        $font = __DIR__."/../../arial.ttf";
        foreach ( $this->data as $item) {
            // A4 page is 210*295
            // image size is 1007x715, so scale properly
            $x= intval ( 1003*$item[0]);
            $y= intval ( 715*$item[1]);
            imagettftext($img, 12, 0, $x, $y, $black, $font, $item[2]);
        }
        return $img;
    }

    function composeTable() {
        $this->myLogger->enter();
        $this->AddPage();
        $img=$this->getImage();

        $tmpfile=tempnam_sfx(__DIR__."/../../../../logs","cneac_","png");
        imagepng($img,$tmpfile);
        $this->SetX(10);
        $this->SetY(10);
        $this->Image($tmpfile,$this->getX(),$this->getY(),190);
        imagedestroy($img);
        @unlink($tmpfile);
        $this->myLogger->leave();
    }
}