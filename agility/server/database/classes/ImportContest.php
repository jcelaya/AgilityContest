<?php
/*
ImportContest.php

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

require_once(__DIR__."/DBObject.php");
require_once(__DIR__."/Inscripciones.php");
require_once(__DIR__."/../../tools.php");

/**
 * Import a competition from a JSON description.
 *
 * Existing entities are matched by normalised name (accent-folded, lowercased,
 * non-alphanumeric characters stripped).  Dogs are matched by chip number first,
 * then by fuzzy name.  Missing entities are created with safe defaults.
 */
class ImportContest extends DBObject {

    private $created  = 0;
    private $updated  = 0;
    private $warnings = array();
    private $report   = array(
        'handlers_created' => array(),  // handler names
        'handlers_matched' => array(),  // ['name' => ..., 'changes' => [...]]
        'dogs_created'     => array(),  // dog names
        'dogs_matched'     => array(),  // ['name' => ..., 'changes' => [...]]
    );

    function __construct() {
        parent::__construct("ImportContest");
    }

    // -------------------------------------------------------------------------
    // Normalisation helpers
    // -------------------------------------------------------------------------

    /**
     * Fold a string for fuzzy comparison:
     * transliterate accents to ASCII, lowercase, strip every non-alphanumeric char.
     */
    private function norm($str) {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT', strval($str));
        return preg_replace('/[^a-z0-9]+/', '', strtolower($ascii));
    }

    /**
     * Split a name into an array of normalised words.
     * e.g. "Javier Celaya Alastrué" -> ["javier", "celaya", "alastrue"]
     */
    private function normWords($str) {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT', strval($str));
        return preg_split('/[^a-z0-9]+/', strtolower($ascii), -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Check whether two handler names should be considered the same person.
     *
     * Accepts an exact norm match, or a word-prefix match where the shorter
     * name's words are a leading subset of the longer one.  This handles the
     * common case of "Firstname Surname1" stored in the DB matching
     * "Firstname Surname1 Surname2" in the JSON (or vice-versa).
     *
     * Word-by-word comparison prevents false positives like "Juan" matching
     * "Juana" that a plain character-prefix check would produce.
     *
     * @return array|false  false if no match; on match, array with keys
     *                      'how' ("exact"|"prefix") and 'detail' string for logging
     */
    private function handlerNamesMatch($dbName, $jsonName) {
        if ($this->norm($dbName) === $this->norm($jsonName)) {
            return array('how' => 'exact', 'detail' => '');
        }
        $wDb   = $this->normWords($dbName);
        $wJson = $this->normWords($jsonName);
        if (empty($wDb) || empty($wJson)) return false;
        $shorter = (count($wDb) <= count($wJson)) ? $wDb   : $wJson;
        $longer  = (count($wDb) <= count($wJson)) ? $wJson : $wDb;
        if (array_slice($longer, 0, count($shorter)) === $shorter) {
            $detail = "'" . implode(' ', $wDb) . "' ~ '" . implode(' ', $wJson) . "'";
            return array('how' => 'prefix', 'detail' => $detail);
        }
        return false;
    }

    // -------------------------------------------------------------------------
    // Update helpers
    // -------------------------------------------------------------------------

    /**
     * If $newVal is non-empty and differs from $dbVal, add an escaped SQL SET
     * clause and a human-readable change description to the provided arrays.
     */
    private function diffField(array &$sets, array &$changed, $column, $dbVal, $newVal) {
        if ($newVal === '' || strval($dbVal) === $newVal) return;
        $sets[]    = "{$column}='" . $this->conn->real_escape_string($newVal) . "'";
        $changed[] = "{$column}: '{$dbVal}' → '{$newVal}'";
    }

    /**
     * Execute a SET-based UPDATE on $table for the given $id, then log the result.
     * If $sets is empty no query is issued.
     */
    private function applyUpdates($table, $id, array $sets, array $changed, $logContext) {
        if (!empty($sets)) {
            $this->query("UPDATE {$table} SET " . implode(', ', $sets) . " WHERE ID={$id}");
            $this->myLogger->info("{$logContext} (ID={$id}); updated fields: " . implode(', ', $changed));
        } else {
            $this->myLogger->info("{$logContext} (ID={$id}) — all fields match");
        }
    }

    /**
     * INSERT a new row into perros and return its auto-increment ID.
     */
    private function insertDog($name, $breed, $chip, $lic, $categoria, $grado, $guiaId, $federation) {
        $nameEsc   = $this->conn->real_escape_string($name);
        $breedEsc  = $this->conn->real_escape_string($breed);
        $chipEsc   = $this->conn->real_escape_string($chip);
        $licEsc    = $this->conn->real_escape_string($lic);
        $catEsc    = $this->conn->real_escape_string($categoria);
        $gradEsc   = $this->conn->real_escape_string($grado);
        $sql = "INSERT INTO perros
                    (Nombre,Raza,Chip,LOE_RRC,Licencia,Categoria,Grado,Baja,Guia,NombreLargo,Genero,Federation)
                VALUES
                    ('{$nameEsc}','{$breedEsc}','{$chipEsc}','','{$licEsc}',
                     '{$catEsc}','{$gradEsc}',0,{$guiaId},'','',{$federation})";
        if (!$this->query($sql)) {
            throw new Exception("ImportContest: cannot insert dog '{$name}': " . $this->conn->error);
        }
        return $this->conn->insert_id;
    }

    // -------------------------------------------------------------------------
    // Club
    // -------------------------------------------------------------------------

    /**
     * Return club ID for $clubData (int ID or string name).
     * Creates a minimal club record when nothing matches.
     */
    private function findOrCreateClub($clubData, $federation) {
        // Fast path: integer ID
        if (is_int($clubData) && $clubData > 0) {
            $row = $this->__selectObject("ID,Nombre", "clubes", "ID={$clubData}");
            if ($row) {
                $this->myLogger->info("Club matched by ID={$clubData}: '{$row->Nombre}'");
                return intval($row->ID);
            }
            $this->myLogger->warn("Club ID={$clubData} not found in database; falling back to name match.");
            $this->warnings[] = "Club ID={$clubData} not found; will try name match or create.";
        }

        // Name-based fuzzy match
        $input  = is_string($clubData) ? $clubData : strval($clubData);
        $needle = $this->norm($input);
        $this->myLogger->debug("Club fuzzy search: input='{$input}' norm='{$needle}'");
        if ($needle !== '') {
            $rs = $this->query("SELECT ID, Nombre FROM clubes");
            if ($rs) {
                while ($row = $rs->fetch_assoc()) {
                    if ($this->norm($row['Nombre']) === $needle) {
                        $rs->free();
                        $this->myLogger->info("Club matched by name: '{$row['Nombre']}' (ID={$row['ID']}) norm='{$needle}'");
                        return intval($row['ID']);
                    }
                }
                $rs->free();
            }
        }

        // Create with defaults
        $nombre   = is_string($clubData) ? $clubData : "Club {$clubData}";
        $this->myLogger->info("Club not found; creating new club '{$nombre}'");
        $nombreEsc = $this->conn->real_escape_string($nombre);
        $fed_mask  = ($federation > 0) ? (1 << ($federation - 1)) : 1;
        $sql = "INSERT INTO clubes
                    (Nombre,Direccion1,Direccion2,Provincia,Pais,
                     Contacto1,Contacto2,Contacto3,GPS,Web,Email,
                     Federations,Facebook,Google,Twitter,Observaciones,Baja)
                VALUES
                    ('{$nombreEsc}','','','','ESP',
                     '','','','','','',
                     {$fed_mask},'','','','',0)";
        if (!$this->query($sql)) {
            throw new Exception("ImportContest: cannot create club '{$nombre}': " . $this->conn->error);
        }
        $id = $this->conn->insert_id;
        $this->created++;
        $this->myLogger->info("Club created: '{$nombre}' (ID={$id})");
        $this->warnings[] = "Created new club '{$nombre}' (ID={$id}).";
        return $id;
    }

    // -------------------------------------------------------------------------
    // Handler (Guia)
    // -------------------------------------------------------------------------

    /**
     * Return guia ID for the given handler data, creating one if not found.
     */
    private function findOrCreateHandler($handlerData, $clubId, $federation) {
        $name   = toUpperCaseWords(isset($handlerData['name'])     ? strval($handlerData['name'])     : '');
        $catStr = isset($handlerData['category']) ? strval($handlerData['category']) : 'A';
        $needle = $this->norm($name);
        $this->myLogger->debug("Handler fuzzy search: input='{$name}' norm='{$needle}' federation={$federation}");

        $fedCond = ($federation >= 0) ? "Federation={$federation}" : "1";
        $rs = $this->query("SELECT ID, Nombre, Categoria, Club FROM guias WHERE {$fedCond}");
        if ($rs) {
            while ($row = $rs->fetch_assoc()) {
                $match = $this->handlerNamesMatch($row['Nombre'], $name);
                if ($match === false) continue;
                $rs->free();

                $id  = intval($row['ID']);
                $how = ($match['how'] === 'prefix') ? "prefix match ({$match['detail']})" : "exact match";
                $cat = parseHandlerCat($catStr);
                if ($cat === '-') $cat = 'A';

                $sets = array(); $changed = array();
                if ($match['how'] === 'prefix' && $row['Nombre'] !== $name) {
                    $sets[]    = "Nombre='" . $this->conn->real_escape_string($name) . "'";
                    $changed[] = "Nombre: '{$row['Nombre']}' → '{$name}'";
                }
                if ($cat !== $row['Categoria']) {
                    $sets[]    = "Categoria='" . $this->conn->real_escape_string($cat) . "'";
                    $changed[] = "Categoria: '{$row['Categoria']}' → '{$cat}'";
                }
                if ($clubId !== intval($row['Club'])) {
                    $sets[]    = "Club={$clubId}";
                    $changed[] = "Club: {$row['Club']} → {$clubId}";
                }
                $this->applyUpdates('guias', $id, $sets, $changed, "Handler matched by {$how}: '{$row['Nombre']}'");

                $reportName = ($match['how'] === 'prefix' && $row['Nombre'] !== $name) ? $name : $row['Nombre'];
                $this->report['handlers_matched'][] = array('name' => $reportName, 'changes' => $changed);
                return $id;
            }
            $rs->free();
        }

        // Create with defaults
        $cat = parseHandlerCat($catStr);
        if ($cat === '-') {
            $this->myLogger->info("Handler category '{$catStr}' not recognised; defaulting to 'A' (Adult)");
            $cat = 'A';
        }
        $this->myLogger->info("Handler not found; creating '{$name}' category='{$cat}' clubId={$clubId}");
        $nameEsc = $this->conn->real_escape_string($name);
        $sql = "INSERT INTO guias (Nombre,Telefono,Email,Club,Observaciones,Categoria,Federation)
                VALUES ('{$nameEsc}','','',{$clubId},'','{$cat}',{$federation})";
        if (!$this->query($sql)) {
            throw new Exception("ImportContest: cannot create handler '{$name}': " . $this->conn->error);
        }
        $id = $this->conn->insert_id;
        $this->created++;
        $this->myLogger->info("Handler created: '{$name}' (ID={$id})");
        $this->report['handlers_created'][] = $name;
        return $id;
    }

    // -------------------------------------------------------------------------
    // Dog (Perro)
    // -------------------------------------------------------------------------

    /**
     * Return dog ID for the given dog data, creating one if not found.
     * Matches by chip number first, then by fuzzy name under the same handler.
     * If a chip match belongs to a different handler, a new copy is created for this handler.
     */
    private function findOrCreateDog($dogData, $guiaId, $federation) {
        $rawLic    = isset($dogData['license']) ? strval($dogData['license']) : '';
        $name      = toUpperCaseWords(isset($dogData['name']) ? strval($dogData['name']) : '');
        $breed     = isset($dogData['breed']) ? strval($dogData['breed']) : '';
        $chip      = isset($dogData['chip_number']) ? strval($dogData['chip_number']) : '';
        $categoria = isset($dogData['class']) ? parseCategory(strval($dogData['class']), $federation) : '';
        $grado     = isset($dogData['level']) ? parseGrade(strval($dogData['level'])) : '';
        $lic       = normalize_license($rawLic);

        $this->myLogger->debug("Dog search: name='{$name}' chip='{$chip}' guiaId={$guiaId}");

        // 1. Match by chip number among dogs of the same handler
        if ($chip !== '') {
            $chipEsc = $this->conn->real_escape_string($chip);
            $row = $this->__selectObject("ID,Nombre,Raza,Licencia,Categoria,Grado", "perros", "Chip='{$chipEsc}' AND Guia={$guiaId}");
            if ($row) {
                $id = intval($row->ID);
                $sets = array(); $changed = array();
                $this->diffField($sets, $changed, 'Nombre',   $row->Nombre,   $name);
                $this->diffField($sets, $changed, 'Raza',     $row->Raza,     $breed);
                $this->diffField($sets, $changed, 'Licencia', $row->Licencia, $lic);
                $this->diffField($sets, $changed, 'Categoria', $row->Categoria, $categoria);
                $this->diffField($sets, $changed, 'Grado', $row->Grado, $grado);
                $reportName = ($name !== '' && $row->Nombre !== $name) ? $name : $row->Nombre;
                $this->applyUpdates('perros', $id, $sets, $changed, "Dog matched by chip '{$chip}': '{$row->Nombre}'");
                $this->report['dogs_matched'][] = array('name' => $reportName, 'changes' => $changed);
                return $id;
            }
            $this->myLogger->debug("Dog chip '{$chip}' not found; trying name match");
        }

        // 2. Fuzzy name match among dogs of the same handler
        $needle = $this->norm($name);
        $this->myLogger->debug("Dog fuzzy name search: input='{$name}' norm='{$needle}' guiaId={$guiaId}");
        $rs = $this->query("SELECT ID, Nombre, Raza, Chip, Licencia, Categoria, Grado FROM perros WHERE Guia={$guiaId}");
        if ($rs) {
            while ($row = $rs->fetch_assoc()) {
                if ($this->norm($row['Nombre']) !== $needle) continue;
                $rs->free();
                $id = intval($row['ID']);
                $sets = array(); $changed = array();
                $this->diffField($sets, $changed, 'Raza',      $row['Raza'],      $breed);
                $this->diffField($sets, $changed, 'Chip',      $row['Chip'],      $chip);
                $this->diffField($sets, $changed, 'Licencia',  $row['Licencia'],  $lic);
                $this->diffField($sets, $changed, 'Categoria', $row['Categoria'], $categoria);
                $this->diffField($sets, $changed, 'Grado',     $row['Grado'],     $grado);
                $this->applyUpdates('perros', $id, $sets, $changed, "Dog matched by name: '{$row['Nombre']}'");
                $this->report['dogs_matched'][] = array('name' => $row['Nombre'], 'changes' => $changed);
                return $id;
            }
            $rs->free();
        }

        // 3. Create new dog
        $this->myLogger->info("Dog not found for guide {$guiaId}; creating '{$name}' breed='{$breed}' lic='{$lic}' guiaId={$guiaId}");
        $id = $this->insertDog($name, $breed, $chip, $lic, $categoria, $grado, $guiaId, $federation);
        $this->created++;
        $this->myLogger->info("Dog created: '{$name}' (ID={$id})");
        $this->report['dogs_created'][] = $name;
        return $id;
    }

    // -------------------------------------------------------------------------
    // Prueba (Contest)
    // -------------------------------------------------------------------------

    /**
     * Return [pruebaId, isNew] for the contest.
     * Creates the contest (and its 8 default jornadas+teams) when not found.
     */
    private function findOrCreatePrueba($name, $clubId, $federation, $openingReg, $closingReg) {
        $needle = $this->norm($name);
        $this->myLogger->debug("Contest fuzzy search: input='{$name}' norm='{$needle}'");

        $rs = $this->query("SELECT ID, Nombre FROM pruebas");
        if ($rs) {
            while ($row = $rs->fetch_assoc()) {
                if ($this->norm($row['Nombre']) === $needle) {
                    $rs->free();
                    $this->myLogger->info("Contest matched by name: '{$row['Nombre']}' (ID={$row['ID']}) — journeys will NOT be updated");
                    return array(intval($row['ID']), false);
                }
            }
            $rs->free();
        }

        // Create contest
        $this->myLogger->info("Contest not found; creating '{$name}' clubId={$clubId} federation={$federation}");
        $nameEsc  = $this->conn->real_escape_string($name);
        $openEsc  = $this->conn->real_escape_string($openingReg);
        $closeEsc = $this->conn->real_escape_string($closingReg);
        $sql = "INSERT INTO pruebas
                    (Nombre,Club,Ubicacion,Triptico,Cartel,Observaciones,RSCE,Selectiva,Cerrada,OpeningReg,ClosingReg)
                VALUES
                    ('{$nameEsc}',{$clubId},'','','','',{$federation},0,0,'{$openEsc}','{$closeEsc}')";
        if (!$this->query($sql)) {
            throw new Exception("ImportContest: cannot create prueba '{$name}': " . $this->conn->error);
        }
        $pruebaId = $this->conn->insert_id;
        $this->created++;
        $this->myLogger->info("Contest created: '{$name}' (ID={$pruebaId})");

        // Create 8 default jornadas + 1 default team each (mirrors Pruebas::insert())
        $today = date("Y-m-d");
        for ($n = 1; $n < 9; $n++) {
            $sql = "INSERT INTO jornadas (Prueba,Numero,Nombre,Fecha,Hora,Equipos3,Equipos4)
                    VALUES ({$pruebaId},{$n},'-- Sin asignar --','{$today}','08:30:00',0,0)";
            if (!$this->query($sql)) {
                throw new Exception("ImportContest: cannot create jornada {$n}: " . $this->conn->error);
            }
            $jornadaId = $this->conn->insert_id;
            $teamDesc  = $this->conn->real_escape_string("NO BORRAR: PRUEBA {$pruebaId} JORNADA {$jornadaId} - Default Team");
            $sql = "INSERT INTO equipos (Prueba,Jornada,Nombre,Categorias,Observaciones,Miembros,DefaultTeam)
                    VALUES ({$pruebaId},{$jornadaId},'-- Sin asignar --','XLMST','{$teamDesc}','BEGIN,END',1)";
            if (!$this->query($sql)) {
                throw new Exception("ImportContest: cannot create default team for jornada {$n}: " . $this->conn->error);
            }
            $teamId = $this->conn->insert_id;
            if (!$this->query("UPDATE jornadas SET Default_Team={$teamId} WHERE ID={$jornadaId}")) {
                throw new Exception("ImportContest: cannot link default team for jornada {$n}: " . $this->conn->error);
            }
        }

        return array($pruebaId, true);
    }

    // -------------------------------------------------------------------------
    // Journeys
    // -------------------------------------------------------------------------

    /**
     * Update the first count($journeys) jornadas of a prueba with names and dates.
     * Only called when the prueba is freshly created.
     */
    private function updateJourneys($pruebaId, $journeys) {
        foreach ($journeys as $idx => $j) {
            $numero = $idx + 1; // Jornada Numero is 1-based
            $nombre = isset($j['name']) ? strval($j['name']) : "Jornada {$numero}";
            $fecha  = isset($j['date']) ? strval($j['date']) : date("Y-m-d");
            $this->myLogger->info("Setting journey {$numero}: name='{$nombre}' date='{$fecha}'");
            $nombreEsc = $this->conn->real_escape_string($nombre);
            $fechaEsc  = $this->conn->real_escape_string($fecha);
            if (!$this->query("UPDATE jornadas SET Nombre='{$nombreEsc}', Fecha='{$fechaEsc}' WHERE Prueba={$pruebaId} AND Numero={$numero}")) {
                $this->warnings[] = "Could not update jornada {$numero}: " . $this->conn->error;
            }
        }
    }

    // -------------------------------------------------------------------------
    // Inscription
    // -------------------------------------------------------------------------

    /**
     * Inscribe $dogId in $pruebaId for the given 0-based journey indices.
     * Uses Inscripciones::realInsert() which also handles dorsal and procesaInscripcion().
     */
    private function inscribeDog($pruebaId, $dogId, $journeyIndices) {
        $mask = 0;
        foreach ($journeyIndices as $idx) {
            $mask |= (1 << intval($idx));
        }
        $this->myLogger->info("Inscribing dogId={$dogId} in pruebaId={$pruebaId} journeys=[" . implode(',', $journeyIndices) . "] mask={$mask}");
        $insc   = new Inscripciones("ImportContest", $pruebaId);
        $result = $insc->realInsert($dogId, $pruebaId, $mask, 0, 0, '');
        if ($result === null) {
            throw new Exception("ImportContest: inscription error for dog {$dogId}: " . $insc->errormsg);
        }
    }

    // -------------------------------------------------------------------------
    // Main entry point
    // -------------------------------------------------------------------------

    /**
     * Import a competition from a decoded JSON array.
     * @param array $json decoded JSON data
     * @return array result with keys: success, created, updated, warnings, report
     */
    public function importFromJSON($json) {
        $this->myLogger->enter();

        foreach (array('name','federation','journeys','inscriptions') as $field) {
            if (!isset($json[$field])) {
                throw new Exception("ImportContest: missing required field '{$field}'");
            }
        }

        $federation = intval($json['federation']);
        $openingReg = isset($json['registration_start_date']) ? $json['registration_start_date'] : date("Y-m-d");
        $closingReg = isset($json['registration_end_date'])   ? $json['registration_end_date']   : date("Y-m-d", strtotime($openingReg) + 86400);
        $this->myLogger->info("Importing contest '{$json['name']}' federation={$federation} inscriptions=" . count($json['inscriptions']));

        $contestClub = isset($json['club']) ? $json['club'] : 1;
        $clubId      = $this->findOrCreateClub($contestClub, $federation);

        list($pruebaId, $isNew) = $this->findOrCreatePrueba(
            $json['name'], $clubId, $federation, $openingReg, $closingReg
        );
        $this->updateJourneys($pruebaId, $json['journeys']);

        foreach ($json['inscriptions'] as $insc) {
            if (!isset($insc['handler']) || !isset($insc['dog'])) {
                $this->myLogger->warn("Skipping inscription with missing handler or dog data");
                $this->warnings[] = "Skipping inscription with missing handler or dog data.";
                continue;
            }
            $handlerData = $insc['handler'];
            $dogData     = $insc['dog'];
            $jIndices    = isset($insc['journeys']) ? $insc['journeys'] : array();
            $this->myLogger->info("Processing inscription: handler='{$handlerData['name']}' dog='{$dogData['name']}'");

            $hClubData   = isset($handlerData['club']) ? $handlerData['club'] : $contestClub;
            $handlerClub = $this->findOrCreateClub($hClubData, $federation);
            $guiaId      = $this->findOrCreateHandler($handlerData, $handlerClub, $federation);
            $dogId       = $this->findOrCreateDog($dogData, $guiaId, $federation);

            try {
                $this->inscribeDog($pruebaId, $dogId, $jIndices);
                $this->updated++;
            } catch (Exception $e) {
                $this->myLogger->error($e->getMessage());
                $this->warnings[] = $e->getMessage();
            }
        }

        $this->myLogger->info("Import complete: created={$this->created} updated={$this->updated} warnings=" . count($this->warnings));
        $this->myLogger->leave();
        return array(
            'success'  => true,
            'created'  => $this->created,
            'updated'  => $this->updated,
            'warnings' => $this->warnings,
            'report'   => $this->report
        );
    }
}

?>
