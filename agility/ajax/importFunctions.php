<?php
/*
importFunctions.php

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

require_once(__DIR__ . "/../server/logging.php");
require_once(__DIR__ . "/../server/tools.php");
require_once(__DIR__ . "/../server/auth/Config.php");
require_once(__DIR__ . "/../server/auth/AuthManager.php");
require_once(__DIR__ . "/../server/database/classes/ImportContest.php");

$response = "";
try {
    $result    = null;
    $config    = Config::getInstance();
    $operation = http_request("Operation", "s", "");
    if ($operation === null) throw new Exception("Call to importFunctions without 'Operation' requested");

    $am = AuthManager::getInstance("importFunctions");

    switch ($operation) {
        case "import":
            $am->access(PERMS_OPERATOR);
            // Read raw JSON — no MySQL escaping, json_decode handles it
            $data = isset($_REQUEST['Data']) ? strval($_REQUEST['Data']) : '';
            if ($data === '') throw new Exception("No JSON data provided");
            $json = json_decode($data, true);
            if ($json === null) throw new Exception("Invalid JSON: " . json_last_error_msg());
            $importer = new ImportContest();
            $result   = $importer->importFromJSON($json);
            break;
        default:
            throw new Exception("importFunctions: invalid operation: '$operation'");
    }

    if ($result === null) throw new Exception("importFunctions: null result");
    if (is_string($result)) {
        if ($result === "") $result = array('success' => true);
        else $result = array('errorMsg' => $result);
    }
    echo json_encode($result);

} catch (Exception $e) {
    do_log($e->getMessage());
    echo json_encode(array('errorMsg' => $e->getMessage()));
}
?>
