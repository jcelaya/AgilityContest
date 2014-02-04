<?php
	require_once("logging.php");
	require_once("classes/DBConnection.php");
	require_once("classes/Mangas.php");
	require_once("classes/Jornadas.php");
	
	/***************** programa principal **************/

	try {
		$result=null;
		$jornadas= new Jornadas("jornadaFunctions");
		$operation=http_request("Operation","s",null);
		if ($operation===null) throw new Exception("Call to jornadaFunctions without 'Operation' requested");
		switch ($operation) {
			case "insert": $result=$jornadas->insert(); break;
			case "update": $result=$jornadas->update(http_request("ID","i",0)); break;
			case "delete": $result=$jornadas->delete(http_request("ID","i",0)); break;
			case "select": $result=$jornadas->selectByPrueba(http_request("Prueba","i",0)); break;
			case "enumerate": $result=$jornadas->searchByPrueba(http_request("Prueba","i",0)); break;
			default: throw new Exception("pruebaFunctions:: invalid operation: $operation provided");
		}
		if ($result===null) throw new Exception($jornadas->errormsg);
		if ($result==="") echo json_encode(array('success'=>true));
		else echo json_encode($result);
	} catch (Exception $e) {
		do_log($e->getMessage());
		echo json_encode(array('errorMsg'=>$e->getMessage()));
	}
?>