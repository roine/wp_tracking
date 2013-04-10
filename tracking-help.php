<?php
/*
Plugin Name: Tracking Help
Plugin URI: http://jdemont.com/b
Description: Help me tracking my websites
version: 1.1
Author: Jonathan de MOntalembert
Author URI: http://jdemont.com/b
*/

class TrackingHelp{


  private $url = 'http://url_to.send/data';

  private $option_name = 'uid';
  private $table_track = '';
  private $table_version = '';
  private $wpdb = null;
  // every 50 row, will push the data to $url defined above
  private $push_live_at = 50;

  function __construct(){
    global $wpdb;
    $this->wpdb = $wpdb;

    $this->table_version = $wpdb->prefix."tracking_version";
    $this->table_track = $wpdb->prefix."tracking_data";

    // create tables, uid
    add_action('activate_tracking-help/tracking-help.php', array($this, 'install_track'));

    add_action('deactivate_tracking-help/tracking-help.php', array($this, 'remove_options'));

    // create an admin page to view the UID
    add_action('admin_menu', array($this, 'show_uid'));

    // get the information and send it via cURL
    add_action('wp', array($this, 'update_info'));
  }


  function update_info(){
    // Don't run in admin panel
    if(is_admin()) return;

    $data = $this->get_info();

    $this->save_locally($data);

    // check how many row added since last push
    // total data existing
    $total =  $this->total_data();
    // last total when pushed, took from db, col = `last_push` which is equal to current of previous row
    $last_push = $this->current();
    $delta_last_push = $total - $last_push;

    // from which delta to push, default 200
    $push_live_at = get_option('push_live_at') ? get_option('push_live_at') : $this->push_live_at;
    // if the limit is reached then push it live
    if($delta_last_push >= $push_live_at){
      $data = $this->prepare_data($last_push, $total);
      $success = $this->send_data($data);
      if($success){
        $current = $this->current();
        $this->update_version($current, $total);
      }
    }

  }

  function install_track(){
    $theme_name = str_replace(' ', '-', strtolower(get_current_theme().'_'));
    $uid = uniqid($theme_name);
    add_option($this->option_name, $uid);
    add_option('push_live_at', $this->push_live_at);
    $this->create_table();
    return $uid;
  }

  function remove_options(){
    // if($this->option_name && get_option( $this->option_name ))
    //   return delete_option( $this->option_name ) && delete_option( 'push_live_at' );
    return delete_option( 'push_live_at' );
  }

  function show_uid(){
    add_options_page("UID", "UID", 10, "jon-uid", array($this, "uid_menu"));
  }

  function uid_menu(){
    include 'tracking-admin.php';
  }

  private function update_version($current, $total){
    $this->wpdb->insert($this->table_version, array(
      'last_push' => $current,
      'current' => $total
      ));
  }

  private function save_locally($data = array()){
    if(is_null($this->wpdb)) return;

    $this->wpdb->insert($this->table_track, array(
      'ip' => $data['ip'],
      'referer' => $data['referer'],
      'website' => $data['website'],
      'url' => $data['url'],
      'browser_language' => $data['browser_language'],
      'uid' => $data['uid']
      ), array('%s', '%s', '%s', '%s', '%s', '%s'));

  }

  private function get_info(){
    $data = array();
    $data['ip'] = $this->get_client_ip();
    $data['referer'] = $this->get_client_referer();
    $data['website'] = $this->website();
    $data['url'] = $this->url();
    $data['uid'] = $this->current_uid();
    $data['browser_language'] = $this->lang();
    return $data;
  }

  private function last_push(){
    return $this->wpdb->get_var( "SELECT last_push FROM $this->table_version ORDER BY id DESC LIMIT 1" );
  }

  private function current(){
    return $this->wpdb->get_var( "SELECT current FROM $this->table_version ORDER BY id DESC LIMIT 1" );
  }

  private function total_data(){
    return $this->wpdb->get_var( "SELECT count(*) FROM $this->table_track" );
  }

  private function send_data($data = array()){
    if(!$data){
      return $this->update_info();
    }

    $start = microtime(true);
    $response = wp_remote_post( $this->url, array('body' => $data) );
    if ( is_wp_error ($response) ) {
      return false;
    }
    $end = microtime(true);
    $diff = $end - $start;
      // echo $diff;
    return $response;
  }

  // using wp library instead, it has a fallback
  private function send_data_curl($data = array()){
    $encoded = '';
    foreach($data as $key => $value){
      $encoded .= urlencode($key).'='.urlencode($value).'&';
    }
    $encoded = substr($encoded, 0, strlen($encoded)-1);
    $curl = curl_init($this->url);
    curl_setopt($curl, CURLOPT_POSTFIELDS,  $encoded);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, 1);
    $result = curl_exec($curl);
    $info = curl_getinfo($curl);

    curl_close($curl);
  }

  private function get_client_ip() {
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
      $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
      $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
      $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
      $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
      $ipaddress = getenv('HTTP_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
      $ipaddress = getenv('REMOTE_ADDR');
    else
      $ipaddress = 'UNKNOWN';

    return $ipaddress;
  }

  private function get_client_referer(){
    return $_SERVER['HTTP_REFERER'];
  }

  private function website(){
    return get_current_theme();
  }

  private function url(){
    $pageURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
    if ($_SERVER["SERVER_PORT"] != "80")
    {
      $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    }
    else
    {
      $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }
    return $pageURL;
  }

  private function current_uid(){
    return get_option($this->option_name);
  }

  private function lang(){
    return substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
  }

  private function prepare_data($from, $to){
    $data = array();
    $result = $this->wpdb->get_results("
      SELECT ip, referer, website, url, browser_language, uid, visited_at FROM $this->table_track
      ORDER by id
      LIMIT $from, $to
      ");
    $data['uid'] = $this->current_uid();
    $data['json'] = json_encode($result);
    return $data;
  }

  private function create_table(){

    if(is_null($this->wpdb)) return;

    if ( ! empty($wpdb->charset) )
      $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
    if ( ! empty($wpdb->collate) )
      $charset_collate .= " COLLATE $wpdb->collate";

    require_once(ABSPATH . '/wp-admin/includes/upgrade.php');
    // to remove when working
    //$wpdb->query("DROP TABLE IF EXISTS $this->table_version");
    $create_version = "CREATE TABLE $this->table_version(
      id INT UNSIGNED NOT NULL auto_increment,
      last_push smallint(10) NOT NULL DEFAULT 0,
      current smallint(10) DEFAULT 0,
      PRIMARY KEY (id)
      ) $charset_collate";

    dbDelta( $create_version);
          // insert first version
    $this->wpdb->insert($this->table_version, array(
      'last_push' => 0,
      'current' => 0
      ));
          // to remove when working
    //$wpdb->query("DROP TABLE IF EXISTS $this->table_track");
    $create_track = "CREATE TABLE $this->table_track (
      id INT UNSIGNED NOT NULL auto_increment,
      ip varchar(50) DEFAULT '',
      referer text DEFAULT '',
      website varchar(500) DEFAULT '',
      url text DEFAULT '',
      browser_language varchar(10) DEFAULT '',
      uid varchar(100) DEFAULT '',
      visited_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id)
      ) $charset_collate";
    dbDelta( $create_track);

  }
}
// essential to run the plugin
$tracking = new TrackingHelp();