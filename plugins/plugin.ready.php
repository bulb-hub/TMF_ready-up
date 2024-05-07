<?php

Aseco::registerEvent('onPlayerInfoChanged', 'ready_PlayerInfoChanged');
Aseco::registerEvent('onStatusChangeTo5', 'ready_Status5');

$ready_logins = array();
$ready_max = 0;

function ready_PlayerInfoChanged($aseco, $player_info) {
	global $ready_logins;
	
	// if we're on the podium screen
	if ($aseco->currstatus == 5) {
		foreach ($aseco->server->players->player_list as $player) {
			// find player by login
			$login = $player->login;
			if ($login == $player_info['Login']) {
				// spectators can't ready-up, they are ignored
				if (!$player->isspectator) {
					$is_ready = floor($player_info['Flags'] / 100) % 10 == 1;
					
					// php treats empty arrays as null for some fucking reason, casuing errors when calling in_array
					if (empty($ready_logins)) {
						// it's safe to assume nobody is ready if the array is empty
						if (!$is_ready) {
							// if we're already in this state, don't duplicate output
							return;
						}
					}
					// if the array is not empty ie. not null
					else {
						if ((in_array($login, $ready_logins) && $is_ready) || !in_array($login, $ready_logins) && !$is_ready) {
							// if we're already in this state, don't duplicate output
							return;
						}
					}

					// first calculate
					ready_calc($aseco);
					
					// and THEN print, in a separate function
					ready_print($aseco, stripColors($player_info['NickName'], false), $is_ready);
				}
			}
		}
	}
}

function ready_Status5($aseco, $call) {
	// reset all ready statuses to prevent unnecessary output
	$ready_logins = array();
	$ready_max = 0;
	
	$message = formatText('{#server}>> $f00Non-spectators: {#admin}Press {#highlite}DEL {#admin}to ready-up and go to the next race!');
	$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
}

function ready_calc($aseco) {
	global $ready_logins, $ready_max;
	
	$ready_logins = array();
	$ready_max = 0;
	
	foreach ($aseco->server->players->player_list as &$player) {
		// spectators can't ready-up, they are ignored
		if ($player->isspectator) {
			continue;
		}
		
		$ready_max++;
		
		$login = $player->login;
		$aseco->client->query('GetPlayerInfo', $login, 1);
		$player_info = $aseco->client->getResponse();
		
		if (floor($player_info['Flags'] / 100) % 10 == 1) {
			array_push($ready_logins, $login);
		}
	}
}

function ready_print($aseco, $nickname, $is_ready) {
	global $ready_logins, $ready_max;
	
	$status = '$f00not ready';
	if ($is_ready) {
		$status = '$0f0ready';
	}
	
	$message = formatText('{#server}>> {#highlite}{1} {#admin}is {2} {#admin}({3}/{4})', $nickname, $status, count($ready_logins), $ready_max);
	$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
}

?>