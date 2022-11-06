<?php
/**
 * Federations.php
 *
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

require_once(__DIR__ . "/../database/classes/Tandas.php");
require_once(__DIR__ . "/../database/classes/Mangas.php");

/* for poedit */
$dummy= _('Common course');
$dummy= _('Separate courses');
$dummy= _('LongName');

class Federations {

    static $LICENSE_REQUIRED_NONE=0;
    static $LICENSE_REQUIRED_SHORT=1;
    static $LICENSE_REQUIRED_WIDE=2;

    protected $config=null;

    function __construct() {
        $this->config = array(
            'ID' => 0,
            'Name' => '',
            'LongName' => '',
            // use basename http absolute path for icons, as need to be used in client side
            'OrganizerLogo' => '',  // contest organizer logo
            'Logo' => '',       // local federation logo
            'ParentLogo' => '',   // global federation logo
            'WebURL' => '',
            'ParentWebURL' => '',
            'Email' => 'jonsito@www.agilitycontest.es',
            'Heights' => 3,
            'Grades' => 3,
            'Games' => 0,
            'International' => 0,
            'LicenseType' => Federations::$LICENSE_REQUIRED_NONE, // indicates license type required on this federation
            'RoundsG1' => 2, // on rfec may be 3
            'ReverseXLMST' => false, // default order is XLMST
            'Recorridos' => array(_('Common course'), _('Standard / Midi + Mini'), _('Separate courses')),
            'ListaGradosShort' => array(
                '-' => 'Sin especificar',
                // 'Jr' => 'Jr.',
                // 'Sr' => 'Sr.',
                //'Ch' => 'Ch.',
                //'Par' => 'Para.',
                'GI' => 'GI',
                'GII' => 'GII',
                'GIII' => 'GIII',
                'P.A.' => 'P.A.',
                'P.B.' => 'P.B'
            ),
            'ListaGrados' => array(
                '-' => 'Sin especificar',
                //'Jr' => 'Junior',
                //'Sr' => 'Senior',
                //'Ch' => 'Children',
                //'Par' => 'ParaAgility',
                'GI' => 'Grade I',
                'GII' => 'Grade II',
                'GIII' => 'Grade III',
                'P.A.' => 'Pre-Agility',
                'P.B.' => 'Test dog'
            ),
            'ListaCategoriasShort' => array(
                '-' => '-',
                'X' => 'X-Large',
                'L' => 'Large',
                'M' => 'Medium',
                'S' => 'Small',
                'T' => 'Tiny'
            ),
            'ListaCategorias' => array(
                '-' => 'Sin especificar',
                'X' => 'Extra Large - 60',
                'L' => 'Large - Standard - 50',
                'M' => 'Medium - Midi - 40',
                'S' => 'Small - Mini - 30',
                'T' => 'Tiny - Toy - 20'
            ),
            'ListaCatGuias' => array(
                '-' => 'Not specified',
                'I' => 'Children',
                'J' => 'Junior',
                'A' => 'Adult',
                'S' => 'Senior',
                'R' => 'Retired',
                'P' => 'Para-Agility',
            ),
            // la información sobre las posibles mangas se obtiene de la siguiente manera:
            // - se obtiene el numero de alturas N de la manga deseada
            // - se busca el array "InfoManga"+N
            // - si no se encuentra, se busca el array "InfoManga"
            'InfoManga3' => array( // 3 alturas
                array('L' => 'Large', 'M' => 'Medium', 'S' => 'Small', 'T' => '', 'X' => ''), // separate courses
                array('L' => 'Large', 'M' => 'Medium+Small', 'S' => '', 'T' => '', 'X' => ''), // 2 group courses
                array('L' => 'Common course', 'M' => '', 'S' => '', 'T' => '', 'X' => ''), // common
                array('L' => '', 'M' => '', 'S' => '', 'T' => '', 'X' => '') // 3 group courses ( 5 heights )
            ),
            'InfoManga4' => array( // 4 alturas
                array('L' => 'Large', 'M' => 'Medium', 'S' => 'Small', 'T' => 'Tiny', 'X' => ''), // separate courses
                array('L' => 'Large+Medium', 'M' => '', 'S' => 'Small+toy', 'T' => '', 'X' => ''), // 2 group courses
                array('L' => 'Common course', 'M' => '', 'S' => '', 'T' => '', 'X' => ''), // common
                array('L' => '', 'M' => '', 'S' => '', 'T' => '', 'X' => '') //3 group courses ( 5 heights )
            ),
            'InfoManga5' => array( // 5 alturas
                array('L' => 'Large', 'M' => 'Medium', 'S' => 'Small', 'T' => 'Tiny', 'X' => 'XLarge'), // separate courses
                array('L' => '', 'M' => 'Medium+Small+Tiny', 'S' => '', 'T' => '', 'X' => 'XLarge+Large'), // 2 group courses
                array('L' => '', 'M' => '', 'S' => '', 'T' => '', 'X' => 'Common course'), // common
                array('L' => '', 'M' => 'Medium', 'S' => 'Small+Tiny', 'T' => '', 'X' => 'XLarge+Large') //3 group courses ( 5 heights )
            ),
            'Modes' => array(
                array(/* separado */ 0, 1, 2, -1, -1),
                array(/* 2 grupos */ 0, 3, 3, -1, -1),
                array(/* conjunto */ 4, 4, 4, -1, -1),
                array(/* 3 grupos */-1,-1,-1, -1, -1),
            ),
            'ModeStrings' => array( // text to be shown on each category
                array(/* separado */
                    "Large", "Medium", "Small", "Invalid","Invalid"),
                array(/* 2 grupos */
                    "Large", "Medium+Small", "Medium+Small", "Invalid","Invalid"),
                array(/* conjunto */
                    "Common course", "Common course", "Common course", "Invalid","Invalid"),
                array(/* 3 grupos */
                    "Invalid", "Medium", "Medium+Small", "Invalid","XL+Large"),
            ),
            'IndexedModes' => array(
                /* 0*/ "Large",
                /* 1*/ "Medium",
                /* 2*/ "Small",
                /* 3*/ "Medium+Small",
                /* 4*/ "Common L/M/S",
                /* 5*/ "Tiny",
                /* 6*/ "Large+Medium",
                /* 7*/ "Small+Tiny",
                /* 8*/ "Common L/M/S/T",
                /* 9*/ "Extra Large",
                /* 10*/ "Large + XL",
                /* 11*/ "Medium+Small+Tiny",
                /* 12*/ "Common X/L/M/S/T"
           ),
           'IndexedModeStrings' => array(
               "-" => "",
               "L" => "Large",
               "M" => "Medium",
               "S" => "Small",
               "T" => "Tiny",
               "LM" => "Large/Medium",
               "ST" => "Small/Tiny",
               "MS" => "Medium/Small",
               "LMS" => 'Common LMS',
               "-LMS" => 'Common LMS',
               "LMST", 'Common LMST',
               "-LMST", 'Common LMST',
               "X" => "Extra Large",
               "XL" => "X-Large/Large",
               "MST" => "Med/Small/Tiny",
               "XLMST" => "Common XLMST",
               "-XLMST" => ''
           ),
           'NombreTandas' => array(
               0 => '-- Sin especificar --',
               1 => 'Pre-Agility 1',
               2 => 'Pre-Agility 2',
               3 => 'Agility-1 GI Large',
               4 => 'Agility-1 GI Medium',
               5 => 'Agility-1 GI Small',
               6 => 'Agility-2 GI Large',
               7 => 'Agility-2 GI Medium',
               8 => 'Agility-2 GI Small',
               9 => 'Agility GII Large',
               10 => 'Agility GII Medium',
               11 => 'Agility GII Small',
               12 => 'Agility GIII Large',
               13 => 'Agility GIII Medium',
               14 => 'Agility GIII Small',
               15 => 'Agility Large', //  Individual-Open
               16 => 'Agility Medium',    //  Individual-Open
               17 => 'Agility Small', //  Individual-Open
               18 => 'Agility team Large', // team best
               19 => 'Agility team Medium',// team best
               20 => 'Agility team Small',     // team best
               // en jornadas por equipos conjunta tres alturas se mezclan categorias M y S
               21 => 'Ag. Teams Large',// team combined
               22 => 'Ag. Teams Med/Small', // team combined
               23 => 'Jumping GII Large',
               24 => 'Jumping GII Medium',
               25 => 'Jumping GII Small',
               26 => 'Jumping GIII Large',
               27 => 'Jumping GIII Medium',
               28 => 'Jumping GIII Small',
               29 => 'Jumping Large',//  Individual-Open
               30 => 'Jumping Medium',    //  Individual-Open
               31 => 'Jumping Small', //  Individual-Open
               32 => 'Jumping team Large',    // team best
               33 => 'Jumping team Medium',// team best
               34 => 'Jumping team Small',    // team best
               // en jornadas por equipos conjunta 3 alturas se mezclan categorias M y S
               35 => 'Jp. Teams Large',// team combined
               36 => 'Jp. Teams Med/Small', // team combined
               // en las rondas KO, los perros compiten todos contra todos
               37 => 'K.O. Round 1',
               38 => 'Special Round Large',
               39 => 'Special Round Medium',
               40 => 'Special Round Small',

               // "Tiny" support for Pruebas de cuatro alturas
               41 => 'Agility-1 GI Tiny',
               42 => 'Agility-2 GI Tiny',
               43 => 'Agility GII Tiny',
               44 => 'Agility GIII Tiny',    // no existe
               45 => 'Agility Tiny', //  Individual-Open
               46 => 'Agility team Tiny',// team best
               // en equipos4  cuatro alturas  agrupamos por LM y ST
               47 => 'Ag. teams Large/Medium', // team combined
               48 => 'Ag. teams Small/Tiny', // team combined

               49 => 'Jumping GII Tiny',
               50 => 'Jumping GIII Tiny', // no existe
               51 => 'Jumping Tiny', //  Individual-Open
               52 => 'Jumping team Tiny',     // team best
               53 => 'Jp. teams Large/Medium',  // team combined
               54 => 'Jp. teams Small/Tiny',// team combined
               55 => 'Special round Tiny',
               56 => 'Agility-3 GI Large',     // extra rounds for GI RFEC
               57 => 'Agility-3 GI Medium',
               58 => 'Agility-3 GI Small',
               59 => 'Agility-3 GI Tiny',
               // resto de las rondas KO. Los perros compiten todos contra todos
               60 => 'K.O. Round 2',
               61 => 'K.O. Round 3',
               62 => 'K.O. Round 4',
               63 => 'K.O. Round 5',
               64 => 'K.O. Round 6',
               65 => 'K.O. Round 7',
               66 => 'K.O. Round 8',
               // tandas para games/wao hasta 2021 ( cuatro categorias, siete mangas distintas )
               67 => 'Agility A 600',
               68 => 'Agility A 500',
               69 => 'Agility A 400',
               70 => 'Agility A 300',
               71 => 'Agility B 600',
               72 => 'Agility B 500',
               73 => 'Agility B 400',
               74 => 'Agility B 300',
               75 => 'Jumping A 600',
               76 => 'Jumping A 500',
               77 => 'Jumping A 400',
               78 => 'Jumping A 300',
               79 => 'Jumping B 600',
               80 => 'Jumping B 500',
               81 => 'Jumping B 400',
               82 => 'Jumping B 300',
               83 => 'Snooker 600',
               84 => 'Snooker 500',
               85 => 'Snooker 400',
               86 => 'Snooker 300',
               87 => 'Gambler 600',
               88 => 'Gambler 500',
               89 => 'Gambler 400',
               90 => 'Gambler 300',
               91 => 'SpeedStakes 600',
               92 => 'SpeedStakes 500',
               93 => 'SpeedStakes 400',
               94 => 'SpeedStakes 300',
               95 => 'Junior 1 Large',
               96 => 'Junior 1 Medium',
               97 => 'Junior 1 Small',
               98 => 'Junior 1 Toy',
               99 => 'Junior 2 Large',
               100 => 'Junior 2 Medium',
               101 => 'Junior 2 Small',
               102 => 'Junior 2 Toy',
               103 => 'Senior 1 Large',
               104 => 'Senior 1 Medium',
               105 => 'Senior 1 Small',
               106 => 'Senior 1 Toy',
               107 => 'Senior 2 Large',
               108 => 'Senior 2 Medium',
               109 => 'Senior 2 Small',
               110 => 'Senior 2 Toy',
               // tandas para cinco alturas (X-Large
               111	=> 'Junior 1 XLarge',
               112	=> 'Junior 2 XLarge',
               113	=> 'Senior 1 XLarge',
               114	=> 'Senior 2 XLarge',
               115	=> 'Agility-1 GI XLarge',
               116	=> 'Agility-2 GI XLarge',
               117	=> 'Agility-3 GI XLarge',
               118	=> 'Agility GII XLarge',
               119	=> 'Jumping GII XLarge',
               120	=> 'Agility GIII XLarge',
               121	=> 'Jumping GIII XLarge',
               122	=> 'Agility XLarge',
               123	=> 'Jumping XLarge',
               124	=> 'Agility Team XLarge',
               125	=> 'Jumping Team XLarge',
               126	=> 'Special Round XLarge',
               // jornadas team mixtas extras para cinco alturas
               127	=> 'Ag. team XLarge/Large', // team combined
               128	=> 'Jp. team XLarge/Large', // team combined
               129	=> 'Ag. team Med/Small/Toy', // team combined
               130	=> 'Jp. team Med/Small/Toy', // team combined
               // JAMC 2021-06-11 add children and para-agility rounds
               131	=> 'Children Agility XLarge',
               132	=> 'Children Jumping XLarge',
               133	=> 'Children Agility Large',
               134	=> 'Children Jumping Large',
               135	=> 'Children Agility Medium',
               136	=> 'Children Jumping Medium',
               137	=> 'Children Agility Small',
               138	=> 'Children Jumping Small',
               139	=> 'Children Agility Toy',
               140	=> 'Children Jumping Toy',
               141	=> 'ParaAgility Agility XLarge',
               142	=> 'ParaAgility Jumping XLarge',
               143	=> 'ParaAgility Agility Large',
               144	=> 'ParaAgility Jumping Large',
               145	=> 'ParaAgility Agility Medium',
               146	=> 'ParaAgility Jumping Medium',
               147	=> 'ParaAgility Agility Small',
               148	=> 'ParaAgility Jumping Small',
               149	=> 'ParaAgility Agility Toy',
               150	=> 'ParaAgility Jumping Toy',
               // tandas extra para games/wao desde 2021( cinco alturas , siete mangas distintas )
               151 => 'Agility A 250',
               152 => 'Agility B 250',
               153 => 'Jumping A 250',
               154 => 'Jumping B 250',
               155 => 'Snooker 250',
               156 => 'Gambler 250',
               157 => 'SpeedStakes 250'
           ),
           'TipoMangas' => array(
               0 => array(0, 'Nombre Manga largo', 'Grado corto', 'Nombre manga', 'Grado largo', 'IsAgility'),
               1 => array(1, 'Pre-Agility Round 1', 'P.A.', 'PreAgility 1', 'Pre-Agility', 1),
               2 => array(2, 'Pre-Agility Round 2', 'P.A.', 'PreAgility 2', 'Pre-Agility', 2),
               3 => array(3, 'Agility Grade I Round 1', 'GI', 'Agility-1 GI', 'Grade I', 1),
               4 => array(4, 'Agility Grade I Round 2', 'GI', 'Agility-2 GI', 'Grade I', 2),
               5 => array(5, 'Agility Grade II', 'GII', 'Agility GII', 'Grade II', 1),
               6 => array(6, 'Agility Grade III', 'GIII', 'Agility GIII', 'Grade III', 1),
               7 => array(7, 'Agility', '-', 'Agility', 'Individual', 1), // Open
               8 => array(8, 'Agility Teams', '-', 'Ag. Teams', 'Teams', 1), // team best
               9 => array(9, 'Agility Teams', '-', 'Ag. Teams.', 'Teams', 1), // team combined
               10 => array(10, 'Jumping Grade II', 'GII', 'Jumping GII', 'Grade II', 2),
               11 => array(11, 'Jumping Grade III', 'GIII', 'Jumping GIII', 'Grade III', 2),
               12 => array(12, 'Jumping', '-', 'Jumping', 'Individual', 2), // Open
               13 => array(13, 'Jumping Teams', '-', 'Jmp. Teams', 'Teams', 2), // team best
               14 => array(14, 'Jumping Teams', '-', 'Jmp. Teams', 'Teams', 2), // team combined
               15 => array(15, 'K.O. First Round', '-', 'K.O. Round 1', 'K.O.', 1),
               16 => array(16, 'Special Round', '-', 'Special Round', 'Individual', 1), // special round, no grades
               17 => array(17, 'Agility Grade I Round 3', 'GI', 'Agility-3 GI', 'Grade I', 3), // on RFEC special G1 3rd round
               // mangas extra para K.O.
               18 => array(18, 'K.O. Second round', '-', 'K.O. Round 2', 'K.O. R2', 2),
               19 => array(19, 'K.O. Third round', '-', 'K.O. Round 3', 'K.O. R3', 3),
               20 => array(20, 'K.O. Fourth round', '-', 'K.O. Round 4', 'K.O. R4', 4),
               21 => array(21, 'K.O. Fifth round', '-', 'K.O. Round 5', 'K.O. R5', 5),
               22 => array(22, 'K.O. Sixth round', '-', 'K.O. Round 6', 'K.O. R6', 6),
               23 => array(23, 'K.O. Seventh round', '-', 'K.O. Round 7', 'K.O. R7', 7),
               24 => array(24, 'K.O. Eight round', '-', 'K.O. Round 8', 'K.O. R8', 8),
               // mandas extras para wao
               25 => array(25, 'Agility A', '-', 'Agility A', 'Ag. A', 1),
               26 => array(26, 'Agility B', '-', 'Agility B', 'Ag. B', 3),
               27 => array(27, 'Jumping A', '-', 'Jumping A', 'Jp. A', 2),
               28 => array(28, 'Jumping B', '-', 'Jumping B', 'Jp. B', 4),
               29 => array(29, 'Snooker', '-', 'Snooker', 'Snkr', 5),
               30 => array(30, 'Gambler', '-', 'Gambler', 'Gmblr', 6),
               31 => array(31, 'SpeedStakes', '-', 'SpeedStakes', 'SpdStk', 7), // single round
               32 => array(32, 'Junior Agility', 'Jr', 'Junior Ag', 'Jr. 1', 1),
               33 => array(33, 'Junior Jumping', 'Jr', 'Junior Jp', 'Jr. 2', 2),
               34 => array(34, 'Senior Agility', 'Sr', 'Senior Ag', 'Sr. 1', 1),
               35 => array(35, 'Senior Jumping', 'Sr', 'Senior Jp', 'Sr. 2', 2),
               36 => array(36, 'Children Agility', 'Sr', 'Children Ag', 'Ch. A', 1),
               37 => array(37, 'Children Jumping', 'Sr', 'Children Jp', 'Ch. J', 2),
               38 => array(38, 'ParaAgility Agility', 'Sr', 'ParaAgility Ag', 'PA. A', 1),
               39 => array(39, 'ParaAgility Jumping', 'Sr', 'ParaAgility Jp', 'PA. J', 2)
           ),
           'TipoRondas' => array(
               /* 0 */ array(/* 0x0000 */ 0,	''),
                /* 1 */ array(/* 0x0001 */ 1,	    _('Pre-Agility') ),
                /* 2 */ array(/* 0x0002 */ 2,	    _('Pre-Agility') ), // 2-rounds pre-agility. No longer use since 3.4.X
                /* 3 */ array(/* 0x0004 */ 4,	    _('Grade I') ),
                /* 4 */ array(/* 0x0008 */ 8,	    _('Grade II') ),
                /* 5 */ array(/* 0x0010 */ 16,	    _('Grade III') ),
                /* 6 */ array(/* 0x0020 */ 32,	    _('Individual') ), // Open
                /* 7 */ array(/* 0x0040 */ 64,	    _('Teams Best') ),
                /* 8 */ array(/* 0x0080 */ 128,	    _('Teams All') ),
                /* 9 */ array(/* 0x0100 */ 256,	    _('K.O. Round') ),
                /*10 */ array(/* 0x0200 */ 512,	    _('Special Round') ),
                /*11 */ array(/* 0x0018 */ 24,	    _('Grade II-III') ),
                /*12 */ array(/* 0x0400 */ 1024,	_('Teams 2best') ), // not used since 4.2.x
                /*13 */ array(/* 0x0800 */ 2048,	_('Teams 2') ), // not used since 4.2.x
                /*14 */ array(/* 0x1000 */ 4096,	_('Teams 3') ), // not used since 4.2.x
                /*15 */ array(/* 0x2000 */ 8192,	_('Games / WAO') ),
                /*16 */ array(/* 0x4000 */ 16384,   _('Young') ),
                /*17 */ array(/* 0x8000 */ 32768,   _('Senior') ),
                /*18 */ array(/* 0x10000 */ 65536,  _('Children') ),
                /*19 */ array(/* 0x20000 */ 131072, _('ParaAgility') ),
            )
        );
    }

    public function getConfig() {
        return $this->config;
    }

    function getTipoRondas(){ return $this->config['TipoRondas']; }

    /**
     * Translate requested manga type and index to federation dependent i18n'd Manga data
     * @param {integer} $type manga type 0..17
     * @param {integer} $idx data index index 0..5 as declared in Mangas.php
     * @return {mixed} requested data
     */
    public function getTipoManga($type,$idx) {
        if (!array_key_exists('TipoMangas',$this->config)) return Mangas::$tipo_manga[$type][$idx];
        if (!array_key_exists($type,$this->config['TipoMangas'])) return Mangas::$tipo_manga[$type][$idx];
        return $this->config['TipoMangas'][$type][$idx];
    }

    /**
     * Translate requested manga mode to federation dependent i18n'd Manga mode data
     * @param {integer} $mode manga mode 0..12
     * @param {integer} $idx tipo de resultado 0:largo 1:abreviado
     * @return {string} requested data
     */
    public function getMangaMode($mode,$idx=0) {
        // on idx==1 every data have same name, so use global to avoid errors
        if ($idx!=0) return Mangas::$manga_modes[$mode][$idx];
        if (!array_key_exists('IndexedModes',$this->config)) return Mangas::$manga_modes[$mode][$idx];
        if (!array_key_exists($mode,$this->config['IndexedModes'])) return Mangas::$manga_modes[$mode][$idx];
        return $this->config['IndexedModes'][$mode];
    }

    /**
     * Translate requested recorrido indexto federation dependent i18n'd one
     * @param {integer} $idx recorrido 0:common 1:mixed 2:separated
     * @return string resulting i18n'd string
     */
    public function getRecorrido($idx) {
        $a= $this->config['Recorridos'][$idx];
        return _($a);
    }

    /**
     * Get manga modes per recorrido
     * @param {integer} $idx recorrido 0:common 1:mixed 2:separated
     * @return {integer} array of modes for this recorrido
     */
    public function getRecorridoModes($idx) {
        $modes = $this->config['Modes'][$idx];
        array_unshift($modes, array_pop($modes)); // Put X category at the beginning
        return array_unique(array_filter($modes, function($v) { return $v != -1; }), SORT_NUMERIC);
    }

    /**
     * retrieve license mode
     */
    public function getLicenseType() {
        if (array_key_exists('LicenseType',$this->config))  return $this->get('LicenseType');
        else return Federations::$LICENSE_REQUIRED_NONE;
    }

    /**
     * shortland to check wide license
     */
    public function hasWideLicense() {
        return ($this->getLicenseType()===Federations::$LICENSE_REQUIRED_WIDE)?true:false;
    }

    /**
     * Common function to retrieve i18n'd string matching requested category/grade in long/short format
     * @param {string} $key Value to search for
     * @param {string} $name Array to search into
     * @return {string} i18n'd requested name
     */
    public function getI18nCatGrade($key,$name) {
        if (!array_key_exists($name,$this->config)) return _($key);
        if (!array_key_exists($key,$this->config[$name])) return _($key);
        return _($this->config[$name][$key]);
    }

    // Translate requested grade key to federation dependent i18n'd one ( long format )
    public function getGrade($key) { return $this->getI18nCatGrade($key,'ListaGrados');  }
    // Translate requested category key to federation dependent i18n'd one (long format)
    public function getCategory($key) { return $this->getI18nCatGrade($key,'ListaCategorias');  }
    public function getHandlerCategory($key) { return $this->getI18nCatGrade($key,'ListaCatGuias');  }
    // Translate requested grade key to federation dependent i18n'd one (short name)
    public function getGradeShort($key) { return $this->getI18nCatGrade($key,'ListaGradosShort');  }
    // Translate requested category key to federation dependent i18n'd one (short name)
    public function getCategoryShort($key) { return $this->getI18nCatGrade($key,'ListaCategoriasShort');  }

    /**
     * check for international federation
     * @return bool
     */
    public function isInternational() { return ( intval($this->config['International']) !=0)?true:false; }

    /**
     * Ask if current federation has rounds of requested type
     * @return bool
     */
    public function hasRoundsOf($grade) {
       switch ($grade) {
            // dog dependent categories
            case 'P.A.':
            case 'GI':
            case 'GII':
            case 'GIII':
                return array_key_exists($grade,$this->config['ListaGrados']);
            // next categories depends on handler not on dog
            case 'Ch': return array_key_exists('I',$this->config['ListaCatGuias']);
            case 'Jr': return array_key_exists('J',$this->config['ListaCatGuias']);
            case 'Sr': return array_key_exists('S',$this->config['ListaCatGuias']);
            case 'Par': return array_key_exists('P',$this->config['ListaCatGuias']);
        }
        return false; // default
    }

    public function hasPreAgility() { return $this->hasRoundsOf('P.A.'); }
    public function hasChildren() { return $this->hasRoundsOf('Ch'); }
    public function hasParaAgility() { return $this->hasRoundsOf('Par'); }
    public function hasJunior() { return $this->hasRoundsOf('Jr'); }
    public function hasSenior() { return $this->hasRoundsOf('Sr'); }
    public function hasGrade3() { return $this->hasRoundsOf('GIII'); }

    /**
     * Ask if this federation has Games rounds.
     * @return bool
     */
    public function hasGames() { return ($this->config['Games']!==0)?true:false; }

    /**
     * @return string either i18n'd 'Club' or 'Contry' according federation
     */
    public function getClubString() { return $this->isInternational()?_('Country'):_('Club'); }

    /**
     * Generic data getter
     * @param {string} $key field to retrive
     * @return {object} requested object or null if not found
     */
    public function get($key) {
        if (array_key_exists($key,$this->config)) return $this->config[$key];
        return null;
    }

    /**
     * Search federation data by providing ID/Name
     * @param {int} $id Federation ID
     * @return {object} requested federation or null if not found
     */
    static function getFederation($id) {
        $fedList=array();
        // analize sub-directories looking for matching ID or name
        // Notice that module class name should be the same as uppercase'd module directory name
        foreach( glob(__DIR__.'/federaciones/*',GLOB_ONLYDIR) as $federation) {
            $name=strtoupper( basename($federation));
            require_once("{$federation}/config.php");
            $fed=new $name;
            if (!$fed) continue;
            if ($fed->get('ID')==$id) return $fed; // use == instead of === to handle int/string
            if ($fed->get('Name')===$id) return $fed;
        }
        // arriving here means requested federation not found
        return null;
    }

    /**
     * Retrieve list of available federation modules
     * @return array $id => $fedData
     */
    static function getFederationList() {
        $fedList=array();
        foreach( glob(__DIR__.'/federaciones/*',GLOB_ONLYDIR) as $federation) {
            $name=strtoupper( basename($federation));
            require_once("{$federation}/config.php");
            $fed=new $name;
            if (!$fed) continue;
            $id=$fed->get('ID');
            $fedList[$id]=$fed->getConfig();
        }
        ksort($fedList);
        return $fedList;
    }

    /*
     * As getFederationList, but return data as expected by jquery-easyui
     */
    static function enumerate() {
        $list=Federations::getFederationList();
        $data=array();
        foreach ($list as $fed) { array_push($data,$fed); }
        $result=array('total' => count($data),'rows' => $data);
        return $result;
    }

    /**
     * Parse federations and compose bitmap mask on every international feds
     * @return int
     */
    static function getInternationalMask() {
        $list=Federations::getFederationList();
        $data=0;
        foreach ($list as $fed) {
            if(intval($fed['International'])==1) $data |= (1<< intval($fed['ID']));
        }
        return $data;
    }

    /**
     * Check if provided logo name matches with existing one
     * @param {string} $name logo to seach
     * @return {boolean} true or false
     */
    static function logoMatches($name) {
        $name=basename($name); // stip dir info
        $list=Federations::getFederationList();
        foreach ($list as $fed) {
            if (basename($fed['Logo'])===$name) return true;
            if (basename($fed['ParentLogo'])===$name) return true;
        }
        // arriving here means not found
        return false;
    }
}
?>