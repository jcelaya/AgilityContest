<?php
/*
pruebaFunctions.php

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


require_once(__DIR__ . "/../../server/logging.php");
require_once(__DIR__ . "/../../server/tools.php");
require_once(__DIR__ . "/../../server/auth/AuthManager.php");
require_once(__DIR__ . "/../../server/modules/Competitions.php");

try {
	$result=null;
	$am= AuthManager::getInstance("ligaFunctions");
	$operation=http_request("Operation","s",null);
    $federation=http_request("Federation","i",0);
    $grado=http_request("Grado","s","GI");
    $perro=http_request("Perro","i",0);
	if ($operation===null) throw new Exception("Call to pruebaFunctions without 'Operation' requested");
	// verificamos permisos de acceso
    $am->access(PERMS_GUEST);
    // obtenemos instancia del gestor de ligas adecuado a la federacion
    $l=Competitions::getLigasInstance("ligaFunctions",$federation);
	switch ($operation) {
        case "shortData":
            $result=$l->getShortData($federation,$grado);
            break;
        case "longData":
            // need grado cause dog may change
            $result=$l->getLongData($perro,$federation,$grado);
            break;
		default: throw new Exception("ligaFunctions:: invalid operation: $operation provided");
	}
	if ($result===null) 
		throw new Exception($result->errorMsg);
	if ($result==="") 
		echo json_encode(array('success'=>true,'insert_id'=>$pruebas->conn->insert_id,'affected_rows'=>$pruebas->conn->affected_rows));
	else echo json_encode($result);
} catch (Exception $e) {
	do_log($e->getMessage());
	echo json_encode(array('errorMsg'=>$e->getMessage()));
}

?>