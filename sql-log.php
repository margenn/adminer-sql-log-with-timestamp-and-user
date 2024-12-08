<?php

/** Log all queries to SQL file (manual queries through SQL command are not logged)
* @link https://www.adminer.org/plugins/#use
* @author Jakub Vrana, https://www.vrana.cz/
* @author Marcelo Gennari, https://gren.com.br -> Log mais detalhado
* @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
* @version 1.0.1
* @param string path defaults to "./"
*/

class AdminerSqlLogGren {

	var $path;

	function __construct($path = "./") {
		$this->path = $path;
	}

	function messageQuery($query, $time, $failed = false) {
		$this->_log($query, $time, 'messageQuery');
	}

	function sqlCommandQuery($query) {
		$this->_log($query, '', "sqlCommandQuery");
	}

	function _log($query, $time = '', $origem = '') {
		// QUERY INFO
		$connection = connection();
		$timestamp = date("Y-m-d\TH:i:sP"); // ISO 8601
		$username = $_GET['username'] ? $_GET['username'] : '';
		$ip = $_SERVER["REMOTE_ADDR"];
		$time = $time ? str_replace(' ', '', $time) : '';
		$affected_rows = trim($connection->affected_rows);
		$error_list = $connection->error_list;
		$warn_list = '';
		if ($connection->warning_count) {
			$result = $connection->query("SHOW WARNINGS");
			if ($result && $result->num_rows) {
				$warn_list = $result->fetch_all();
			}
		}
		// MONTA O LOG
		$log = json_encode( (object) [
			'timestamp' => $timestamp,
			'username' => $username,
			'ip' => $ip,
			'time' => $time,
			'affected_rows' => $affected_rows,
			'error_list' => $error_list,
			'warn_list' => $warn_list,
			'origem' => $origem,
			'query' => $query
		]) . "\n";
		// GRAVA
		$fullPath = rtrim($this->path, '/') . '/' . $username . '_' . substr($timestamp, 0, 4) . ".log";
		try {
			$handle = fopen($fullPath, 'a+');
			if ($handle === false) { throw new Exception("Erro ao abrir o arquivo para gravação."); }
			if (flock($handle, LOCK_EX)) {
				fwrite($handle, $log); // grava
				flock($handle, LOCK_UN); // libera
			} else {
				throw new Exception("Não foi possível obter o bloqueio do arquivo.");
			}
		} finally {
			if ($handle !== false) {
				fclose($handle);
			}
		}
	}
}
