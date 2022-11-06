<?php
class RFEC extends Federations {

    function __construct() {
        parent::__construct();
        // combine global data with specific data for this federation
        $this->config= array_merge ($this->config, array(
            'ID'    => 1,
            'Name'  => 'RFEC',
            'ClassName' => get_class($this),
            'LongName' => 'Real Federacion Española de Caza',
            // use basename http absolute path for icons, as need to be used in client side
            'OrganizerLogo'     => 'rfec.png',
            'Logo'     => 'rfec.png',
            'ParentLogo'   => 'csd.png',
            'WebURL' => 'http://www.fecaza.com/',
            'ParentWebURL' => 'http://www.csd.gob.es/',
            'Email' => 'yvonneagility@fecaza.com',
            'Heights' => 5,
            'Grades' => 2,
            'Games' => 0,
            'International' => 0,
            'LicenseType' => Federations::$LICENSE_REQUIRED_WIDE, // license long numbers
            'RoundsG1' => 3,
            'ReverseXLMST' => true, // default order is TSMLX instead of XLMST
            'Recorridos' => array(
                _('Common course'),
                "60+50 / 40+30+20", // 2 groups
                _("Separate courses"),
                "60 / 50 / 40 / 30+20" // 4 groups
            ),
            'ListaGradosShort' => array(
                '-' => 'Sin especificar',
                // 'Jr' => 'Junr',
                // 'Sr' => 'Sr.',
                // 'Ch' => 'Inf.',
                // 'Par' => 'Par.',
                'GI' => 'Prom',
                'GII'=> 'Comp',
                // 'GIII' => 'G3',
                'P.A.' => 'Inic',
                'P.B.' => 'P.B.' // "perro en blanco"
            ),
            'ListaGrados'    => array (
                '-' => 'Sin especificar',
                // 'Jr' => 'Junior',
                // 'Sr' => 'Senior',
                // 'Ch' => 'Infantil',
                //'Par' => 'ParaAgility',
                'GI' => 'Promocion',
                'GII'=> 'Competicion',
                // 'GIII' => '(G3) no disponible',
                'P.A.' => 'Iniciacion',
                'P.B.' => 'Perro en Blanco'
            ),
            'ListaCategoriasShort' => array (
                '-' => '-',
                'X' => '60',
                'L' => '50',
                'M' => '40',
                'S' => '30',
                'T' => '20'
            ),
            'ListaCategorias' => array (
                '-' => 'Sin especificar',
                'X' => 'Clase 60',
                'L' => 'Clase 50',
                'M' => 'Clase 40',
                'S' => 'Clase 30',
                'T' => 'Clase 20'
            ),
            'ListaCatGuias' => array (
                '-' => 'Sin especificar',
                'I' => 'Infantil',
                'J' => 'Juvenil',
                'A' => 'Adulto',
                'S' => 'Senior',
                'R' => 'Retirado',
                'P' => 'Para-Agility',
            ),
            'InfoManga5' => array(
                array('L' => _('Clase 50'),'M' => _('Clase 40'),'S' => _('Clase 30'),'T' => _('Clase 20'),'X' => _('Clase 60') ), // separate courses
                array('L' => '',           'M' => _('40+30+20'),'S' => '',           'T' => '',           'X' => _('60+50')), // mixed (2 groups) courses
                array('L' => '',           'M' => '',           'S' => '',           'T' => '',           'X' => _('6+5+4+3+2')), // common ( single height ) course
                array('L' => _('Clase 50'),'M' => _('Clase 40'),'S' => _('Clase 30+20'),   'T' => '',           'X' => _('Clase 60'),   ) // 4 group courses
            ),
            'Modes' => array(
                //  categorias            L   M   S   T   X
                array(/* 0: separado */   0,  1,  2,  5,  9 ),
                array(/* 1: 2 groups */  10, 11, 11, 11, 10 ),
                array(/* 2: conjunto */  12, 12, 12, 12, 12 ), // pre-agility is declared as -XLMST
                array(/* 3: 4 grupos */   0,  1,  7,  7,  9 )
            ),
            'ModeStrings' => array( // text to be shown on each category

                // category  L M S T X
                array(/* separado  */_('Clase 50'),     _('Clase 40'),      _('Clase 30'),      _('Clase 20'),     _('Clase 60') ),
                array(/* 2 grupos */ _('Clase 60+50'),  _('Clase 40+30+20'),_('Clase 40+30+20'), _('Clase 40+30+20'),_('Clase 60+50') ),
                array(/* conjunto */ "Recorrido comun", "Recorrido comun",  "Recorrido comun",  "Recorrido comun", "Recorrido comun"),
                array(/* 4 grupos */ _('Clase 50'),     _('Clase 40'),       _('Clase 30+20'),   _('Clase 30+20'),  _('Clase 60'))
            ),
            'IndexedModes' => array (
                /* 0 - L    */ "Clase 50",
                /* 1 - M    */ "Clase 40",
                /* 2 - S    */ "Clase 30",
                /* 3 - MS   */ "Clase 40+30",
                /* 4 - LMS  */ "Clase 50+40+30",
                /* 5 - T    */ "Clase 20",
                /* 6 - LM   */ "Clase 50+40",
                /* 7 - ST   */ "Clase 30+20",
                /* 8 - LMST */ "50+40+30+20",
                /* 9 - X    */ "Clase 60",
                /*10 - XL   */ "Clase 60+50",
                /*11 - MST  */ "Clase 40+30+20",
                /*12 - XLMST*/ "60+50+40+30+20"
            ),
            'IndexedModeStrings' => array(
                "-" => "",
                "L"=>"Clase 50",
                "M"=>"Clase 40",
                "S"=>"Clase 30",
                "T"=>"Clase 20",
                "LM"=>"Clase 60+50", // // invalid in 5 heights
                "ST"=>"Clase 30+20", // 3 groups mode
                "MS"=>"Clase 40+30", // invalid in 5 heights
                "LMS" => 'Conjunta 5+4+3', // invalid in 5 heights
                "-LMS" => 'Conjunta 5+4+3', // invalid in 5 heights
                "LMST" =>'Conjunta 5+4+3+2', // invalid in 5 heights
                "-LMST" =>'Conjunta 5+4+3+2', // invalid in 5 heights
                "X" => "Clase 60",
                "XL" => "Clase 60+50", // 3 groups mode
                "MST" => "Clase 40+30+20", // 2 groups mode
                "XLMST" => "", // common course
                "-XLMST"=> ""
            ),
            'NombreTandas' => array(
                0	=> '-- Sin especificar --',
                1	=> 'Iniciacion 1',
                2	=> 'Iniciacion 2',
                3	=> 'Promocion Manga 1 50',
                4	=> 'Promocion Manga 1 40',
                5	=> 'Promocion Manga 1 30',
                6	=> 'Promocion Manga 2 50', // en temporada 2019-2020 la segunda manga es jumping
                7	=> 'Promocion Manga 2 40',
                8	=> 'Promocion Manga 2 30',
                9	=> 'Agility Competicion 50',
                10	=> 'Agility Competicion 40',
                11	=> 'Agility Competicion 30',
                12	=> 'Agility GIII 50', // no existe en rfec
                13	=> 'Agility GIII 40', // no existe en rfec
                14	=> 'Agility GIII 30', // no existe en rfec
                15	=> 'Agility Clase 50', //  Individual-Open
                16	=> 'Agility Clase 40',	//  Individual-Open
                17	=> 'Agility Clase 30', //  Individual-Open
                18	=> 'Agility Equipos 50', // team best
                19	=> 'Agility Equipos 40',// team best
                20	=> 'Agility Equipos 30',	 // team best
                // en jornadas por equipos conjunta tres alturas se mezclan categorias M y S
                21	=> 'Ag. Equipos 50',// team combined
                22	=> 'Ag. Equipos 40/30', // team combined
                23	=> 'Jumping Competicion 50',
                24	=> 'Jumping Competicion 40',
                25	=> 'Jumping Competicion 30',
                26	=> 'Jumping GIII 50', // no existe en rfec
                27	=> 'Jumping GIII 40', // no existe en rfec
                28	=> 'Jumping GIII 30', // no existe en rfec
                29	=> 'Jumping Clase 50',//  Individual-Open
                30	=> 'Jumping Clase 40',	//  Individual-Open
                31	=> 'Jumping Clase 30', //  Individual-Open
                32	=> 'Jumping Equipos 50',	// team best
                33	=> 'Jumping Equipos 40',// team best
                34	=> 'Jumping Equipos 30',	// team best
                // en jornadas por equipos conjunta 3 alturas se mezclan categorias M y S
                35	=> 'Jp. Equipos 50',// team combined
                36	=> 'Jp. Equipos 40/30', // team combined
                // en las rondas KO, los perros compiten todos contra todos
                37	=> 'K.O. Manga 1',
                38	=> 'Manga Especial - 50',
                39	=> 'Manga Especial - 40',
                40	=> 'Manga Especial - 30',
                // "Tiny" support for Pruebas de cuatro alturas. EN RFEC 5 alturas ahora pasa a clase 20
                41	=> 'Promocion Manga 1 20',
                42	=> 'Promocion Manga 2 20',
                43	=> 'Agility Competicion 20',
                44	=> 'Agility GIII 20',	// no existe
                45	=> 'Agility Clase 20', //  Individual-Open
                46	=> 'Agility Equipos 20',// team best
                // en equipos4  cuatro alturas  agrupamos por LM y ST. No existen en RFEC 5h
                47	=> 'Ag. Equipos 50/40', // team combined
                48	=> 'Ag. Equipos 30/20', // team combined
                49	=> 'Jumping Competicion 20',
                50	=> 'Jumping GIII 20', // no existe
                51	=> 'Jumping Clase 20', //  Individual-Open
                52	=> 'Jumping Equipos 20',	 // team best
                53	=> 'Jp. Equipos 50/40',  // team combined
                54	=> 'Jp. Equipos 30/20',// team combined
                55	=> 'Manga Especial - 20',
                56	=> 'Promocion Manga 3 50',	 // extra rounds for GI RFEC
                57	=> 'Promocion Manga 3 40',
                58	=> 'Promocion Manga 3 30',
                59	=> 'Promocion Manga 3 20',
                // resto de las rondas KO. Los perros compiten todos contra todos
                60	=> 'K.O. Manga 2',
                61	=> 'K.O. Manga 3',
                62	=> 'K.O. Manga 4',
                63	=> 'K.O. Manga 5',
                64	=> 'K.O. Manga 6',
                65	=> 'K.O. Manga 7',
                66	=> 'K.O. Manga 8',
                // tandas para games/wao ( cuatro categorias, siete mangas distintas )
                67	=> 'Agility A 650',
                68	=> 'Agility A 525',
                69	=> 'Agility A 400',
                70	=> 'Agility A 300',
                71	=> 'Agility B 650',
                72	=> 'Agility B 525',
                73	=> 'Agility B 400',
                74	=> 'Agility B 300',
                75	=> 'Jumping A 650',
                76	=> 'Jumping A 525',
                77	=> 'Jumping A 400',
                78	=> 'Jumping A 300',
                79	=> 'Jumping B 650',
                80	=> 'Jumping B 525',
                81	=> 'Jumping B 400',
                82	=> 'Jumping B 300',
                83	=> 'Snooker 650',
                84	=> 'Snooker 525',
                85	=> 'Snooker 400',
                86	=> 'Snooker 300',
                87	=> 'Gambler 650',
                88	=> 'Gambler 525',
                89	=> 'Gambler 400',
                90	=> 'Gambler 300',
                91	=> 'SpeedStakes 650',
                92	=> 'SpeedStakes 525',
                93	=> 'SpeedStakes 400',
                94	=> 'SpeedStakes 300',
                95  => 'Junior Ag. 50',
                96  => 'Junior Ag. 40',
                97  => 'Junior Ag. 30',
                98  => 'Junior Ag. 20',
                99   => 'Junior Jp. 50',
                100  => 'Junior Jp. 40',
                101  => 'Junior Jp. 30',
                102  => 'Junior Jp. 20',
                103 => 'Senior Ag. 50',
                104 => 'Senior Ag. 40',
                105 => 'Senior Ag. 30',
                106 => 'Senior Ag. 20',
                107 => 'Senior Jp. 50',
                108 => 'Senior Jp. 40',
                109 => 'Senior Jp. 30',
                110 => 'Senior Jp. 20',
                // tandas nuevas para xl y toy en rfec 5 alturas
                111	=> 'Junior Ag. 60',
                112	=> 'Junior Jp. 60',
                113	=> 'Senior Ag. 60',
                114	=> 'Senior Jp. 60',
                115	=> 'Promocion Manga 1 60',
                116	=> 'Promocion Manga 2 60',
                117	=> 'Promocion Manga 3 60',
                118	=> 'Agility Competicion 60',
                119	=> 'Jumping Competicion 60',
                120	=> 'Agility GIII XLarge', // no existe en rfec
                121	=> 'Jumping GIII XLarge',
                122	=> 'Agility Clase 60',
                123	=> 'Jumping Clase 60',
                124	=> 'Agility Equipos 60',
                125	=> 'Jumping Equipos 60',
                126	=> 'Manga especial - 60',
                // jornadas team mixtas extras para cinco alturas
                127	=> 'Ag. Equipos 60/50', // team combined
                128	=> 'Jp. Equipos 60/50', // team combined
                129	=> 'Ag. Equipos 40/30/20',  // team combined
                130	=> 'Jp. Equipos 40/30/20',// team combined
               // JAMC 2021-06-11 add children and para-agility rounds
               131	=> 'Infantil Agility 60',
               132	=> 'Infantil Jumping 60',
               133	=> 'Infantil Agility 50',
               134	=> 'Infantil Jumping 50',
               135	=> 'Infantil Agility 40',
               136	=> 'Infantil Jumping 40',
               137	=> 'Infantil Agility 30',
               138	=> 'Infantil Jumping 30',
               139	=> 'Infantil Agility 20',
               140	=> 'Infantil Jumping 20',
               141	=> 'ParaAgility Agility 60',
               142	=> 'ParaAgility Jumping 60',
               143	=> 'ParaAgility Agility 50',
               144	=> 'ParaAgility Jumping 50',
               145	=> 'ParaAgility Agility 40',
               146	=> 'ParaAgility Jumping 40',
               147	=> 'ParaAgility Agility 30',
               148	=> 'ParaAgility Jumping 30',
               149	=> 'ParaAgility Agility 20',
               150	=> 'ParaAgility Jumping 20'

            ),
            'TipoMangas' => array(
                0 =>	array( 0, 'Nombre Manga largo',	'Grado corto',	'Nombre manga',	'Grado largo', 'IsAgility'),
                1 =>	array( 1, 'Iniciacion Manga 1', 		'P.A.',	'Iniciacion 1',	'Iniciacion',  1),
                2 => 	array( 2, 'Iniciacion Manga 2', 		'P.A.',	'Iniciacion 2',	'Iniciacion',  2),
                3 =>	array( 3, 'Promocion Manga 1',	        'GI',	'Promocion-1',	'Promocion',   1),
                4 => 	array( 4, 'Promocion Manga 2',	        'GI',	'Promocion-2',	'Promocion',   2),
                5 =>	array( 5, 'Agility Competicion', 		'GII',	'Ag. Competicion',	'Competicion', 1),
                6 =>	array( 6, 'Agility Grade III', 			'GIII',	'Agility GIII',	'Grade III',   1), // no existe en caza
                7 =>	array( 7, 'Agility', 	        		'Open',	'Agility',		'Individual',  1), // Open
                8 =>	array( 8, 'Agility Equipos',			'Team',	'Ag. Teams',	'Teams',       1), // team best
                9 =>	array( 9, 'Agility Equipos',            'Team',	'Ag. Teams.',	'Teams',       1), // team combined
                10 =>	array( 10,'Jumping Competicion',		'GII',	'Jp. Competicion',	'Competicion', 2),
                11 =>	array( 11,'Jumping Grade III',			'GIII',	'Jumping GIII',	'Grado III',   2), // no existe en caza
                12 =>	array( 12,'Jumping',    				'Open',	'Jumping',		'Individual',  2), // Open
                13 =>	array( 13,'Jumping Equipos',            'Team', 'Jp. Equipos',	'Equipos',     2), // team best
                14 =>	array( 14,'Jumping Equipos',            'Team',	'Jp. Equipos',	'Equipos',     2), // team combined
                15 =>	array( 15,'K.O. Manga 1', 				'K.O.',	'K.O. - 1',	    'K.O.',        1),
                16 =>	array( 16,'Manga Especial', 			'K.O.',	'Manga Especial','Individual', 1), // special round, no grades
                17 => 	array( 17,'Promocion Manga 3',	        'GI',	'Promocion-3',	'Promocion',   3), // on RFEC special G1 3rd round
                // mangas extra para K.O.
                18 =>	array( 18,'K.O. Segunda manga',			'K.O.',	'K.O. - 2',     'K.O.',         2),
                19 =>	array( 19,'K.O. Tercera manga',			'K.O.',	'K.O. - 3',	    'K.O.',         3),
                20 =>	array( 20,'K.O. Cuarta manga',			'K.O.',	'K.O. - 4',	    'K.O.',         4),
                21 =>	array( 21,'K.O. Quinta manga',			'K.O.',	'K.O. - 5',	    'K.O.',         5),
                22 =>	array( 22,'K.O. Sexta manga',			'K.O.',	'K.O. - 6',	    'K.O.',         6),
                23 =>	array( 23,'K.O. Septima manga',			'K.O.',	'K.O. - 7',	    'K.O.',         7),
                24 =>	array( 24,'K.O. Octava manga',			'K.O.',	'K.O. - 8',	    'K.O.',         8),
                // mandas extras para wao
                25 =>	array( 20,'Agility A',			        'WAO',	'Agility A',	'Ag. A',        1),
                26 =>	array( 26,'Agility B',			        'WAO',	'Agility B',	'Ag. B',        3),
                27 =>	array( 27,'Jumping A',			        'WAO',	'Jumping A',	'Jp. A',        2),
                28 =>	array( 28,'Jumping B',			        'WAO',	'Jumping B',	'Jp. B',        4),
                29 =>	array( 29,'Snooker',			        'WAO',	'Snooker',	    'Snkr ',        5),
                30 =>	array( 30,'Gambler',			        'WAO',	'Gambler',	    'Gmblr',        6),
                31 =>	array( 31,'SpeedStakes',			    'WAO',	'SpeedStakes',	'SpdStk',       7), // single round
                // PENDING: revise grade. perhaps need to create an specific 'Jr' grade for them
                32 =>	array( 32,'Agility Junior',	            'Jr',	'Ag Junior',	    'Ag. Jr.',   1),
                33 => 	array( 33,'Jumping Junior',	            'Jr',	'Jp Junior',	    'Jp. Jr.',   2),
                34 =>	array( 34,'Agility Senior',	            'Sr',	'Senior Ag',	    'Ag. Sr.',   1),
                35 => 	array( 35,'Jumping Senior',	            'Sr',	'Senior Jp',	    'Ag. Sr',    2),
                36 =>	array( 36,'Agility Infantil',	        'Ch',	'Ag Infantil',	    'Ag Inf',    1),
                37 => 	array( 37,'Jumping Infantil',	        'Ch',	'Jp Infantil',	    'Jp Inf' ,   2),
                38 =>	array( 38,'Ag. ParaAgility',	        'PA',	'P.Agility Ag',	    'PA. Ag',    1),
                39 => 	array( 30,'Jp. ParaAgility',	        'PA',	'P.Agility Jp',	    'PA. Jp',    2),
            ),
            'TipoRondas' => array(
                /* 0 */ array(0,	''),
                /* 1 */ array(1,	_('Iniciacion') ),
                /* 2 */ array(2,	_('Iniciacion') ), // 2-round preagility. no longer use since 3.4.X
                /* 3 */ array(4,	_('Promocion') ),
                /* 4 */ array(8,	_('Competicion') ),
                /* 5 */ array(16,	_('Grade').' III' ),   // not used in RFEC
                /* 6 */ array(32,	_('Individual') ), // Open
                /* 7 */ array(64,	_('Teams Best') ),
                /* 8 */ array(128,	_('Teams All') ),
                /* 9 */ array(256,	_('K.O. Round') ),
                /*10 */ array(512,	_('Special Round') ),
                /*11 */ array(24,	_('Grade II-III') ), // not used in RFEC
                /*12 */ array(1024,	_('Teams 2best') ), // not used since 4.2.x
                /*13 */ array(2048,	_('Teams 2') ), // not used since 4.2.x
                /*14 */ array(4096,	_('Teams 3') ), // not used since 4.2.x
                /*15 */ array(8192,	_('Games / WAO') ),
                /*16 */ array(16384,_('Young') ),
                /*17 */ array(32768,_('Senior') ),
                /*18 */ array(65536,  _('Children') ),
                /*19 */ array(131072, _('ParaAgility') ),
            )
        ));
    }


}
?>