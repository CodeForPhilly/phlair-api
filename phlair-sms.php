<?php

/**
 * A Tropo app for querying the PHLAIR API via SMS and phone.
 * @author Mark Headd
 * @copyright Civic.io 2012
 */

// Base URL for the PHLAIR API.
define("PHLAIR_URL_BASE", "http://api.phlair.info/1.0");

/**
 * 
 * Get flight data from PHLAIR API
 * @param string $direction
 * @param string $num
 */
function getFlightData($direction, $num) {
	
	$url = PHLAIR_URL_BASE . "/$direction/$num";
	_log("*** $url ***");
	$flight_data = file_get_contents($url);
	return json_decode($flight_data);
	
}

/**
 * 
 * Format response based on channel
 * @param object $flight_info
 * @param string $direction
 * @param string $channel
 */
function formatResponse($flight_info, $direction, $channel) {
	
	// Determine if the flight is an arrival or departure.
	$leaveorarrive = $direction == 'departure' ? "leaving for" : "arriving from";
	$gate = (strtolower($direction) == "departure") ? " from " : " at ";
	
	// Format the flight number for the channel used.
	$flight_num = $channel == "VOICE" ? implode(" ", str_split($flight_info->flight_number)) : $flight_info->flight_number;
	
	// Properly case destination an remarks.
	$destination = $direction == 'departure' ? $flight_info->destination : $flight_info->origin;
	$destination = ucwords(strtolower($destination));
	$remarks = ucwords(strtolower($flight_info->remarks));
	
	// Build response to user.
	$say = $flight_info->airline . " Flight " . $flight_num . " $leaveorarrive " . $destination . " at " . $flight_info->time . "$gate Gate " . $flight_info->gate . ": " . $remarks;
	return $say;
}

// Get flight info. from SMS.
if($currentCall->initialText) {
	
	$flight_info = explode(" ", $currentCall->initialText);
	$flight_num = $flight_info[0];
	$direction = $flight_info[1] = 'd' ? 'departure' : 'arrival';
	
}

// Get flight info. from Phone.
else {
	
	say("Thank you for calling the flair, Philly airport information app.");
	$flight = ask("Please say or enter your numeric flight number.", array("choices" => "[1-4 DIGITS]", "attempts" => 3, "timeout" => 5));
	$flight_type = ask("Is your flight an arrival or departure?", array("choices" => "arrival, departure", "attempts" => 3, "timeout" => 5));
	
	$flight_num = $flight->value;
	$direction = $flight_type->value;
	
}

// Look up flight information and play/send to the user.
try {
	
	$flight_info = getFlightData($direction, $flight_num);

	if(count($flight_info) == 0) {
		say("No information found for flight $flight_num.");
	}
	else {
		$say = formatResponse($flight_info[0], $direction, $currentCall->channel);
		say($say);	
	}
	
}

catch (Exception $ex) {
	say("Sorry, could not look up flight info. Please try again later.");
}

?>