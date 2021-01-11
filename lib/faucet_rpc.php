<?php

class simple_faucet_rpc {
	protected $config;

	protected $db = false;
	protected $db_error = true;


	public function __construct($config) {
		$this->config = $config;
		if ($this->check_valid_request()) {
			if (isset($config["mysql_user"],
			          $config["mysql_password"],
			          $config["mysql_host"],
			          $config["mysql_database"]))
			{
				$this->db = @new mysqli($config["mysql_host"],$config["mysql_user"],$config["mysql_password"],$config["mysql_database"]);
				if (!mysqli_connect_error()) {
					$this->db_error = false;
				}
			}
		}

	}

	private function send_response($response) {
		header('Content-Type: application/json');
		echo json_encode($response);
	}

	private function insert_new_promo() {
		$promo_code = $this->db->escape_string($_POST["new_promo"]);
		$min_payout = (float) $_POST["min_payout"];
		$max_payout = (float) $this->db->escape_string($_POST["max_payout"]);
		$uses = (int) $_POST["uses"];
		$this->db->query("DELETE FROM `".$this->config["mysql_table_prefix"]."promo_codes` WHERE `code` = '".$promo_code."'");
		$this->db->query("INSERT INTO `".$this->db->escape_string($this->config["mysql_table_prefix"])."promo_codes` (`code`,`minimum_payout`,`maximum_payout`,`uses`) VALUES ('".$promo_code."',".$min_payout.",".$max_payout.",".$uses.")"); // insert the transaction into the payout log
	}

	private function check_promo_isvalid() {
		$promo_code = $this->db->escape_string($_POST["old_promo"]);
		$result = $this->db->query("SELECT `minimum_payout`,`maximum_payout`,`uses` FROM `".$this->config["mysql_table_prefix"]."promo_codes` WHERE `code` = '".$promo_code."'");
		if ($promo = @$result->fetch_assoc()) {
			$promo["uses"] = intval($promo["uses"]); // MySQLi
			if ($promo["uses"] !== 0 && $promo["uses"] > 0) {
				return true;
			} else {
				$this->db->query("DELETE FROM `".$this->config["mysql_table_prefix"]."promo_codes` WHERE `code` = '".$promo_code."'");
				return false;
			}
		} else {
			return false;
		}
	}

	public function process_request() {
		if ($this->check_valid_request() && !$this->db_error) {
			if ($this->check_promo_isvalid()) {
				$data = array(
					"promo_code_mode" => "old_valid",
				);
			} else {
				$this->insert_new_promo();
				$data = array(
					"promo_code_mode" => "new_added",
				);
			}
			$this->send_response($data);
			die();
		} else {
			return false;
		}
	}

	private function check_valid_request() {
		if (isset($_POST["api_key"])
		    && isset($_POST["new_promo"], $_POST["old_promo"], $_POST["min_payout"], $_POST["max_payout"], $_POST["uses"])
		    && (password_verify($_POST["api_key"], $this->config["api_key"]))
		    && (in_array($_SERVER["REMOTE_ADDR"], $this->config["whitelisted_ips"])))
		{
			return true;
		} else {
			return false;
		}
	}
}
