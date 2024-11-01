<?php
/*
* Plugin Name: Vote Up/Down
* Author: WebAddict
* Author URI: http://webaddict.in
* Version: 1.0.0
* Description: Add voting system to your single post using [show_votes] shortcode.
*/

//Create table upon activation of plugin.

function wd_create_voting_table() {
	global $table_prefix, $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $table_prefix . 'votes';

	if($wpdb->get_var( "show tables like '$table_name'" ) != $table_name) 
    {
		$sql = "CREATE TABLE $table_name(
					id INT(11) AUTO_INCREMENT NOT NULL,
					server_ip VARCHAR(255),
					vote_up INT(11),
					vote_down INT(11),
					post_id INT(255),
					created_at TIMESTAMP NOT NULL,
					UNIQUE KEY id (id)
				) $charset_collate";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
}
register_activation_hook(__FILE__, 'wd_create_voting_table');

/**
* Vote class
*/
class wd_Vote 
	{
		
		public function __construct()
		{

			$this->register_shortcode();
			$this->register_scripts();
		}

		public function InsertData($tablefield) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'votes';
			try{
				$wpdb->insert($table_name, $tablefield);
			}
			catch(PDOException $e)
			{
				echo "ERROR! Could not insert into Database";
			}
		}

		public function buttons() {
			global $wpdb;
			$table_name = $wpdb->prefix . 'votes';
			$id = get_the_ID();
			$up = $wpdb->get_var("SELECT SUM(vote_up) FROM $table_name WHERE post_id = $id");
			$down = $wpdb->get_var("SELECT SUM(vote_down) FROM $table_name WHERE post_id = $id");
			$ip = $wpdb->get_var("SELECT server_ip FROM $table_name WHERE post_id = $id");
			if($_SERVER['REMOTE_ADDR'] == $ip) {
				echo '
					<div class="vote_buttons">
						<form method="POST">
							<button disabled name="up" type="submit" value="' . $id . '"><i class="fa fa-thumbs-up" aria-hidden="true"></i> <span class="up_votes"> ' . $up . '</span></button>
							<button disabled name="down" type="submit" value="' . $id . '"><i class="fa fa-thumbs-down" aria-hidden="true"></i> <span class="up_down"> ' . $down . '</span></button>
						</form>
					</div>
				';
			} else {
				echo '
					<div class="vote_buttons">
						<form method="POST">
							<button name="up" type="submit" value="' . $id . '"><i class="fa fa-thumbs-up" aria-hidden="true"></i> <span class="up_votes"> ' . $up . '</span></button>
							<button name="down" type="submit" value="' . $id . '"><i class="fa fa-thumbs-down" aria-hidden="true"></i> <span class="up_down"> ' . $down . '</span></button>
						</form>
					</div>
				';
			}
		}

		public function register_shortcode() {
			add_shortcode('show_votes',array($this, 'buttons'));
		}

		public function register_scripts() {
			add_action('wp_enqueue_scripts',array($this, 'scripts'));
		}

		public function scripts() {
			wp_enqueue_script('FontAwesome','https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
		}
	}

$vote = new wd_Vote();

//Save votes in database
if(isset($_POST['up'])) {
	$data = array(
		'server_ip' => $_SERVER['REMOTE_ADDR'],
		'vote_up' => 1,
		'vote_down' => 0,
		'post_id' => intval($_POST['up']),
	);
	$vote->InsertData($data);
}

if(isset($_POST['down'])) {
	$data = array(
		'server_ip' => $_SERVER['REMOTE_ADDR'],
		'vote_up' => 0,
		'vote_down' => 1,
		'post_id' => intval($_POST['down']),
	);
	$vote->InsertData($data);
}