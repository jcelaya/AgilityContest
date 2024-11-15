/*
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


/***************************************************************************************************************/
/*
* browser-less implementation of AgilityContest event protocol parser
*
* Requires nodejs >=4
* Required npm modules 'https' 'follow-redirects' 'querystring' 'os' 'ip'
*
*/
/***************************************************************************************************************/
var querystring = require('querystring');
var https = require('follow-redirects').https;
var ip=require('ip');
var os = require('os');

var workingData = {
	// Comment as desired
	//************ Option 1- when server hostname is not set you must provide ring name to search for
	ring: 1, // must be set by user in range (1..4)
	hostname: '0.0.0.0', // set to '0.0.0.0' to let the app find server by itself
	sesionID: 0,
	//************* Option 2 we already know hostname and sessionID
	// ring: 0, // ignored as implicit in sessionID
	// hostname: 'localhost',
	// sessionID: 2,

	// variables to store start timestamp mark
	cronomanual: 0,
	cronoauto: 0,
	coursewalk: 0,
	// method to handle received events
	callback: event_parser
};

var eventHandler= {
	'null': null,// null event: no action taken
	'init': function(event,time) { // operator starts tablet application
		console.log(event['Type'] + "- Tablet connected");
	},
	'open': function(event,time){
		console.log(event['Type'] + " - Round selected");
		console.log("    Contest : "+ event.NombrePrueba);
		console.log("    Journey : "+ event.NombreJornada);
		console.log("    Round   : "+ event.NombreManga);
		console.log("    Ring    : "+ event.NombreRing);
        workingData.cronomanual=0;
		workingData.cronoauto=0;
		workingData.coursewalk=0;
	},
	'close': function(event,time){ // no more dogs in tabla
		console.log(event['Type'] + " - Operator ends tablet session");
	},
	'datos': function(event,time) {      // actualizar datos (si algun valor es -1 o nulo se debe ignorar)
		console.log(event['Type'] + " - Competitor's data received");
		console.log("    Faults       : "+ event.Flt);
		console.log("    Touchs       : "+ event.Toc);
		console.log("    Refusals     : "+ event.Reh);
		console.log("    Eliminated   : "+ event.Eli);
		console.log("    Not present  : "+ event.NPr);
		console.log("    Time         : "+ event.Tim);
	},
	'llamada': function(event,time) {    // llamada a pista
		console.log(event['Type'] + " - Competitor call to ring");
		console.log("    Dog name  : "+ event.Nombre);
		console.log("    Long name : "+ event.NombreLargo);
		console.log("    Handler   : "+ event.NombreGuia);
		console.log("    Club      : "+ event.NombreClub);
		console.log("    Team      : "+ event.NombreEquipo);
		console.log("    Category  : "+ event.Categoria);
        console.log("    Grade     : "+ event.Grado);
        console.log("    Heat      : "+ event.Hot);

        console.log("    - Previously stored competitor data ");
        console.log("    Faults       : "+ event.Flt);
        console.log("    Touchs       : "+ event.Toc);
        console.log("    Refusals     : "+ event.Reh);
        console.log("    Eliminated   : "+ event.Eli);
        console.log("    Not present  : "+ event.NPr);
        console.log("    Time         : "+ event.Tim);
	},
	'salida': function(event,time){     // orden de salida
		console.log(event['Type'] + " - Start 15 seconds countdown");
	},
	'start': function(event,time) {      // start crono manual
		console.log(event['Type'] + " - Manual chrono started");
        console.log("    Value: "+event.Value);
        workingData.cronomanual=parseInt(event.Value);
	},
	'stop': function(event,time){      // stop crono manual
		console.log(event['Type'] + " - Manual chrono stopped");
        console.log("    Value: "+event.Value);
        console.log("    Time:  "+(parseInt(event.Value)-workingData.cronomanual).toString()+" msecs");
	},
	// nada que hacer aqui: el crono automatico se procesa en el tablet
	'crono_start':  function(event,time){ // arranque crono automatico
		console.log(event['Type'] + " - Electronic chrono starts");
        console.log("    Value: "+event.Value);
        workingData.cronoauto=parseInt(event.Value);
	},
	'crono_restart': function(event,time){	// paso de tiempo manual a automatico
		console.log(event['Type'] + " - Manual-to-Electronic chrono transition");
        console.log("    Start: "+event.start);
        console.log("    Stop:  "+event.stop);
	},
	'crono_int':  	function(event,time){	// tiempo intermedio crono electronico
		console.log(event['Type'] + "- Intermediate time mark");
        console.log("    Value:             "+event.Value);
        console.log("    Intermediate Time: "+(parseInt(event.Value)-workingData.cronoauto).toString()+" msecs");
	},
	'crono_stop':  function(event,time){	// parada crono electronico
		console.log(event['Type'] + " - Electronic chrono stop");
        console.log("    Value: "+event.Value);
        console.log("    Time:  "+(parseInt(event.Value)-workingData.cronoauto).toString()+" msecs");
	},
	'crono_reset':  function(event,time){	// puesta a cero del crono electronico
		console.log(event['Type'] + "- Reset all counters");
        workingData.cronomanual=0;
		workingData.cronoauto=0;
		workingData.coursewalk=0;
	},
	'crono_dat': function(event,time) {      // actualizar datos -1:decrease 0:ignore 1:increase
		console.log(event['Type'] + " - Competitor data from electronic chronometer");
		console.log("    Faults       : "+ event.Flt);
		console.log("    Touchs       : "+ event.Toc);
		console.log("    Refusals     : "+ event.Reh);
		console.log("    Eliminated   : "+ event.Eli);
		console.log("    Not present  : "+ event.NPr);
		console.log("    Interm. time : "+ event.TInt);
		console.log("    Time         : "+ event.Tim);
	},
	'crono_rec':  function(event,time) { // course walk
		var val=event.start;
		if (val===0) {
			var elapsed=(parseInt(event.Value)-workingData.coursewalk)/1000;
			workingData.coursewalk=0;
			console.log(event['Type'] + " - Course walk stop");
            console.log("    Timestamp value:"+event.Value);
			console.log("    Elapsed time: "+elapsed+" secs.");
		} else {
			workingData.coursewalk=parseInt(event.Value);
			console.log(event['Type'] + " - Course walk start");
            console.log("    Timestamp value:"+event.Value);
			console.log("    Remaining Time: "+val+" secs");
		}
	},
	'crono_error':  function(event,time) { // fallo en los sensores de paso
		var val=parseInt(event.Value);
		console.log(event['Type'] + " - Chronometer notifies sensor error event:"+val);
        if (val===1) console.log("    Sensor status: Failed");
        else console.log("    Sensor status: OK");
	},
	'crono_ready':  function(event,time) { // crono activo y escuchando
		var val=parseInt(event.Value);
		console.log(event['Type'] + " - Chronometer notifies chrono is ready and listening state:"+val);
		if (val===1) console.log("    Sensor status: Failed");
		else console.log("    Sensor status: OK");
	},
	'user': function(event,time) { // funcion definida por el usuario
        console.log(event['Type'] + " - User defined function number "+event.Value);
	},
	'aceptar':	function(event,time){ // operador pulsa aceptar
		console.log(event['Type'] + " - Assistant console operator accepts competitor result");
	},
	'cancelar': function(event,time){  // operador pulsa cancelar
		console.log(event['Type'] + " - Assistant console operator cancels current competitor data");
	},
	'camera':	function(event,time){ // change video source
		console.log(event['Type'] + "- Video source for embedded livestream screans changed");
	},
    'command':	function(event,time) {  // videowall remote control
        console.log(event['Type'] + " - Remote comand from main console");
        var str=""+event['Oper']+"EVT_CMD_UNKNOWN";
        switch (parseInt(event['Oper'])) {
            case 0: str= "0 EVTCMD_NULL"; break;
            case 1: str= "1 EVTCMD_SWITCH_SCREEN"; break;
            case 2: str= "2 EVTCMD_SETFONTFAMILY"; break;
            case 3: str= "3 EVTCMD_NOTUSED3"; break;
            case 4: str= "4 EVTCMD_SETFONTSIZE"; break;
            case 5: str= "5 EVTCMD_OSDSETALPHA"; break;
            case 6: str= "6 EVTCMD_OSDSETDELAY"; break;
            case 7: str= "7 EVTCMD_NOTUSED7"; break;
            case 8: str= "8 EVTCMD_MESSAGE"; break;
            case 9: str= "9 EVTCMD_ENABLEOSD"; break;
        }
        console.log(" Command: "+str+ "Value: "+event['Value']);
    },
    'reconfig':	function(event,time) {  // reload configuration from server
        console.log(event['Type'] + " - Configuration changed from main console");
    },
	'info':	function(event,time) { // click on user defined tandas
		console.log(event['Type'] + " - Assistant console operator choose a --sin asignar-- ring round");
		console.log("    Contest : "+ event.NombrePrueba);
		console.log("    Journey : "+ event.NombreJornada);
		console.log("    Round   : "+ event.NombreManga);
		console.log("    Ring    : "+ event.NombreRing);
	}
};

/**
 * Generic event handler browser-less event monitor
 * Every screen has a 'eventHandler' table with pointer to functions to be called
 * @param {int} id Event ID
 * @param {array} event Event data
 */
function event_parser(id,event) {
	event['ID']=id; // fix real id on stored eventData
	var time=event['Value'];
	if (typeof(eventHandler[event['Type']])==="function") eventHandler[event['Type']](event,time);
}

/**
 * This function explores network trying to locate an AgilityContest server
 * When found, deduce desired sesion ID and set up working data parameters
 *
 * @param {int} ring Ring number (1..4) to search their sessionID for
 * @returns {boolean} true when server and session found. otherwise false
 */
function findServer(ring) {

	var addresses = []; // to be evaluated

	// locate any non local interface ipaddress/mask
	// take care on multiple interfaced hosts
	function getInterfaces() {
		var interfaces = os.networkInterfaces();
		for (var k in interfaces) {
			for (var k2 in interfaces[k]) {
				var address = interfaces[k][k2];
				if (address.family === 'IPv4' && !address.internal) {
					addresses.push(address);
				}
			}
		}
	}

	/**
	 * Try to connect AgilityContest server.
	 * on success retrieve Session ID and update working Data
	 * @param hostaddr IP address of host to check
	 * @return true on success; otherwise false
     */
	function connectServer(hostaddr){

    	var options = {
        	protocol: 	'https:',
        	hostname:	hostaddr,
        	port: 		443,
        	path: 		'/agility/ajax/database/sessionFunctions.php?Operation=selectring',
        	method: 	'GET',
			timeout: 	250,
			rejectUnauthorized: false,
        	headers: 	{
        		// this is for some routers that return "Auth required" to fail and send proper error code
				authorization: 'Basic ' + new Buffer('AgilityContest' + ':' + 'AgilityContest', 'ascii').toString('base64')
        	}
    	};

		var req = https.request(options,function(result){
			result.on('data', (res) =>{
				if (result.statusCode===404) {
					console.log("HTTP Server at: "+hostaddr+" is not AgilityContest server");
					return;
				}
				console.log("Found AgilityContest server at: "+hostaddr);
				var data = JSON.parse(res);
				// this code assumes that first returned row matches ring 1, second ring 2 and so
				workingData.hostname=hostaddr;
				workingData.sessionID=parseInt(data['rows'][ring-1]['ID']);
				console.log("SessionID for ring:"+ring+" is:"+workingData.sessionID);
			});
			result.on('end', function() { /* empty */  });
		});
		req.on('error', (error) => {
			console.log("Host: "+hostaddr+" Error: "+error);
		});
		req.end();
	}

	function iterate(n,end){
		if (workingData.hostname!=="0.0.0.0") return;
		if(n>end) return;
		var hostname=ip.fromLong(n).toString();
		connectServer(hostname);
		setTimeout( function () {iterate(n+1,end); },500);
	}

	getInterfaces(); // retrieve network interface information
	for (var item in addresses) { // iterate on every ip/mask found
		var ipaddr=addresses[item].address;
		var mask=addresses[item].netmask;
		var start=ip.toLong(ip.subnet(ipaddr,mask).firstAddress);
		var stop=ip.toLong(ip.subnet(ipaddr,mask).lastAddress);
		if (workingData.hostname!=="0.0.0.0") return;
		iterate(start,stop);
	}
	// arriving here means server not found. Notify and return
	console.log("Cannot locate server. exiting");
}


/**
 * This function call to server and waits for events to be returned ( or empty data on timeout )
 * When one or several events are received iterate on them by calling callback function declared
 * in workingData structure
 * @param evtID Last received event ID
 * @param timestamp last timestamp mark received from server
 */
function waitForEvents(evtID,timestamp){

    var postData = querystring.stringify({
        Operation:	'getEvents',
        ID:			evtID,
        Session:	workingData.sessionID,
        TimeStamp:	timestamp
    });

    var options = {
        protocol: 'https:',
        hostname: workingData.hostname,
        port: 443,
        path: '/agility/ajax/database/eventFunctions.php',
        method: 'POST',
		rejectUnauthorized: false,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Content-Length': postData.length
        }
    };

	function handleSuccess(data){
        var response=JSON.parse(data);
		var timestamp= response.TimeStamp;
		var lastID=evtID;
		for (var n=0;n<parseInt(response.total);n++) {
			var row=response.rows[n];
			lastID=row['ID'];// store last evt id
			if (row['Type']==='reconfig') setTimeout(loadConfiguration,0);
			else workingData.callback(lastID,JSON.parse(row['Data']));
		}
		// re-queue event
		setTimeout(function(){ waitForEvents(lastID,timestamp);},1000);
	}

	function handleError(data) {
		console.log("WaitForEvents() Error: response.message");
		setTimeout(function(){ waitForEvents(evtID,timestamp);},5000); // retry in 5 seconds
	}

    var req = https.request(options,function(res){
        res.setEncoding('utf8');
        res.on('data', handleSuccess);
        res.on('end', function() { /* empty */  });
    });
    req.on('error',handleError);
    // write data to request body
    req.write(postData);
    req.end();
}

/**
 * Call "connect" to retrieve last "open" event for provided session ID
 * fill working data with received info
 * If no response wait two seconds and try again
 * On sucess invoke waitForEvents and enter in event parsing loop
 */
function startEventMgr() {

	//  source:ringsessid:type:mode:sessname
	var sname="EventMonitor:1:0:0:eventmon@1.2.3.4";
    var postData = querystring.stringify({
        Operation:	'connect',
        Session:	workingData.sessionID,
		SessionName: sname
    });

    var options = {
        protocol: 'https:',
        hostname: workingData.hostname,
        port: 443,
        path: '/agility/ajax/database/eventFunctions.php',
        method: 'POST',
		rejectUnauthorized: false,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Content-Length': postData.length
        }
    };

    var req = https.request(options,function(res){
        res.setEncoding('utf8');
        res.on('data', function(data) {
            var response=JSON.parse(data);
            var timeout=5000;
            if (typeof(response['errorMsg'])!=="undefined") {
                console.log(response['errorMsg']);
                setTimeout(function(){ startEventMgr();},timeout );
                return;
            }
            if ( parseInt(response['total'])!==0) {
                var row=response['rows'][0];
                var evtID=parseInt(row['ID'])-1; /* dont loose first "init" event */
				console.log("Connected to AgilityContest Event bus");
                setTimeout(function(){ waitForEvents(evtID,0);},0);
            } else {
                setTimeout(function(){ startEventMgr(); },timeout );
            }
        });
        res.on('end', function() { /* empty */  });
    });

    req.on('error',function(e){
        console.log('problem with request: ' + e.message);
        setTimeout(function(){ startEventMgr(); },5000 );
    });

    // write data to request body
    req.write(postData);
    req.end();

	return false;
}

function waitForHost() {
	if (workingData.hostname === "0.0.0.0") startEventMgr();
	else setTimeout(waitForHost, 500);
}
if (workingData.hostname==="0.0.0.0") findServer(workingData.ring);
waitForHost();
