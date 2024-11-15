<?php
/**
 * Created by PhpStorm.
 * User: jantonio
 * Date: 30/11/15
 * Time: 13:42
 */
require_once __DIR__ . '/../Spout/Autoloader/autoload.php';
require_once __DIR__ . '/../../auth/Config.php';
require_once __DIR__ . '/../../auth/AuthManager.php';
require_once __DIR__ . '/../../modules/Federations.php';

use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\StyleBuilder;

class XLSX_Writer {
    protected $myConfig;
    protected $myLogger;
    protected $myWriter;
    protected $myFile;
    protected $prueba; // array
    protected $jornada; // array
    protected $federation; // object

    protected $titleStyle;
    protected $rowHeaderStyle;

    function __construct($file) {
        $this->myFile=$file;
        date_default_timezone_set('Europe/Madrid');
        $this->myConfig=Config::getInstance();
        $this->myLogger= new Logger($file,$this->myConfig->getEnv("debug_level"));
        $this->myWriter=WriterFactory::create(Type::XLSX);
        $this->titleStyle=(new StyleBuilder())
           ->setFontBold()
           ->setFontItalic()
           ->setFontSize(15)
           ->setFontColor(substr($this->myConfig->getEnv('pdf_hdrfg1'),1))
           ->build();
        $this->rowHeaderStyle=(new StyleBuilder())
            ->setFontBold()
            ->setFontItalic()
            ->setFontSize(12)
            ->setFontColor(substr($this->myConfig->getEnv('pdf_hdrfg2'),1))
            ->build();
    }

    function open($file=null) {
        if (!$file) $this->myWriter->openToBrowser($this->myFile);
        else $this->myWriter->openToFile($file);
    }

    function createInfoPage($title,$federation=-1) {
        $infopage=$this->myWriter->getCurrentSheet();
        $name=_utf("Information");
        $infopage->setName($this->normalizeSheetName($name));

        // titulo
        $this->myWriter->addRowsWithStyle([[$title],[""]], $this->titleStyle);

        // en caso de estar definido, informacion de Prueba, jornada, y en su caso federacion
        if ($this->prueba !== null )
            $this->myWriter->addRowWithStyle([ _utf("Contest").":",$this->prueba['Nombre']], $this->rowHeaderStyle);
        if ($this->jornada !== null )
            $this->myWriter->addRowWithStyle([ _utf("Journey").":",$this->jornada['Nombre']], $this->rowHeaderStyle);
        if ($federation>=0) {
            $fed=Federations::getFederation(intval($federation));
            if ($fed===null) {
                $this->myLogger->trace("Invalid federation ID:$federation");
            } else {
                $this->myWriter->addRowWithStyle([ _utf("Federation").":",$fed->get('Name')], $this->rowHeaderStyle);
                $this->federation=$fed;
            }
        }

        // informacion de la aplicacion
        $this->myWriter->addRows(
            [
                [ "" ],
                [ _utf("Program info") ],
                [ "Application: ",  $this->myConfig->getEnv("program_name") ],
                [ "Version:",      $this->myConfig->getEnv("version_name") ],
                [ "Revision:",     $this->myConfig->getEnv("version_date") ]
            ]
        );
    }

    /**
     * Anyade una pagina con informacion de la prueba y de las jornadas
     * @param $prueba
     * @param $jornadas
     */
    function createPruebaInfoPage($prueba,$jornadas) {
        // Create page
        $ppage=$this->myWriter->addNewSheetAndMakeItCurrent();
        $name=$this->normalizeSheetName($prueba['Nombre']);
        $ppage->setName($name);

        // componemos informacion de la prueba
        $this->myWriter->addRow(array(_utf('Contest'),$prueba['Nombre'])); // prueba
        $clbObj= new Clubes("common_writer");
        $club=$clbObj->selectByID($prueba['Club']);
        $this->myWriter->addRow(array(_utf('Club'),$club['Nombre'])); // club
        $this->myWriter->addRow(array(_utf('Federation'),$this->federation->get('Name'))); // federacion
        $this->myWriter->addRow(array(_utf('Selective'),$prueba['Selectiva'])); // selectiva
        $this->myWriter->addRow(array(_utf('Comments'),$prueba['Observaciones'])); // comentarios

        // anyadimos ahora informacion de las jornadas
        $this->myWriter->addRow(array(""));
        $jrdHdr=array("",_utf('Name'),_utf('Date'),_utf('Hour'),_utf('Closed'),"" /*_utf('Special round')*/);
        $this->myWriter->addRowWithStyle($jrdHdr, $this->rowHeaderStyle);
        foreach ($jornadas as $jornada) {
            if ($jornada['Nombre']==='-- Sin asignar --') continue; // skip empty journeys
            $row=array();
            $row[]=_utf('Journey').": ".$jornada['Numero'];
            $row[]=$jornada['Nombre'];
            $row[]=$jornada['Fecha'];
            $row[]=$jornada['Hora'];
            $row[]=$jornada['Cerrada'];
            if ( ($jornada['Observaciones']!==null) && ($jornada['Observaciones']!=="(sin especificar)")){
                $row[]=$jornada['Observaciones']; // add name for special rounds
            }
            $this->myWriter->addRow($row);
        }

    }

    function close() {
        $this->myWriter->close();
    }

    function normalizeSheetName($name) {
        // convert to ASCII-7
        $name=toASCII($name);
        // remove forbidden characters
        $name = preg_replace('/[^A-Za-z0-9\. -]/', '', $name);
        // limit to 31 chars
        $name=substr($name,0,31);
        return $name;
    }
}