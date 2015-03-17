<?php
/*
 * Plugin Name: Ptengine - Real time web analytics and Heatmap
 * Version: 1.0.0
 * Plugin URI: http://www.ptengine.com/
 * Description: To get started: activate this plugin, go to <a href="admin.php?page=ptengine_setting">option page</a> and (1) sign up (2) create a profile (3) start to see your real time traffic! Go to Ptengine for the full version if you want more conversions.
 * Author: Ptengine
 * Author URI: http://www.ptengine.com/
 */

// define
define('ptengine_menu_position', '7', true);
define('wpp_api_report', admin_url(). 'admin.php?page=ptengine_report', true);
define('wpp_api_setting', admin_url(). 'admin.php?page=ptengine_setting', true);
define('ptengine_api_login', 'https://report.ptengine.com', true);

// option init
add_option('key_ptengine_badge_visible', '0');
add_option('key_ptengine_tag_position', 'footer');

add_option('key_ptengine_account', '');
add_option('key_ptengine_pwd', '');
add_option('key_ptengine_uid', '');

add_option('key_ptengine_sid', '');
add_option('key_ptengine_site_id', '');
add_option('key_ptengine_pgid', '');
add_option('key_ptengine_site_name', '');
add_option('key_ptengine_timezone', '');
add_option('key_ptengine_code', '');

add_option('key_ptengine_area', '');
add_option('key_ptengine_dc_init', '1');

/*******************param process start**********************************/
// after registed or login, set option
$account = $_GET['account'];
$pwd = $_GET['pwd'];
$uid = $_GET['uid'];
$set_first = $_GET['setFirst'];
if ($account && $pwd && $uid) {
    update_option('key_ptengine_account', $account);
    update_option('key_ptengine_pwd', $pwd);
    update_option('key_ptengine_uid', $uid);
    update_option('key_ptengine_area', '');
    if ($set_first === '0'){
        update_option('key_ptengine_dc_init', $set_first);
    }
}

// after profile created, set option
$sid = $_GET['sid'];
$site_id = $_GET['siteId'];
$pgid = $_GET['groupId'];
$site_name = $_GET['siteName'];
$timezone = $_GET['timezone'];
$code = $_GET['code'];
if ($sid && $site_id && $pgid && $site_name && $timezone && $code) {
    update_option('key_ptengine_sid', $sid);
    update_option('key_ptengine_site_id', $site_id);
    update_option('key_ptengine_pgid', $pgid);
    update_option('key_ptengine_site_name', $site_name);
    update_option('key_ptengine_timezone', $timezone);
    update_option('key_ptengine_code', str_replace(' ','+',$code));
}
/*******************param process end**********************************/

/*******************plugin start/stop process start**********************************/
register_activation_hook( __FILE__, 'ptengine_plugin_install');   
register_deactivation_hook( __FILE__, 'ptengine_plugin_remove' );  

function ptengine_plugin_install(){
	$t_uid = get_option('key_ptengine_uid');
	if (!$t_uid) {
		$t_area = ptengine_getArea('a='. $_SERVER['HTTP_ACCEPT_LANGUAGE']. '&b='. getIP());
		if ($t_area) {
			update_option('key_ptengine_area', $t_area);
		}
	}
}

function ptengine_plugin_remove(){
    ptengine_logout();
}
function getIP(){
	if(!empty($_SERVER["HTTP_CLIENT_IP"])){
  		return $_SERVER["HTTP_CLIENT_IP"];
	}
	if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
  		return $_SERVER["HTTP_X_FORWARDED_FOR"];
	}
	if(!empty($_SERVER["REMOTE_ADDR"])){
  		return $_SERVER["REMOTE_ADDR"];
  	}
  	return '';
}
/*******************plugin start/stop process end**********************************/

/*******************add tag process start**********************************/
// after profile created or profile found
if(get_option('key_ptengine_sid')){
    $tag_position = get_option('key_ptengine_tag_position');
    if ($tag_position == "footer") {
        add_action('wp_footer', 'add_ptengine_tag');
    } else {
        add_action('wp_head', 'add_ptengine_tag');
    }
    // add ptengine tag
    function add_ptengine_tag() {
        $t_code = get_option('key_ptengine_code');
        echo $t_code ? base64_decode(str_replace(' ','+',$t_code)) : '';
    }
}
/*******************add tag process end**********************************/

/*******************validate process start**********************************/
// when open DC or Setting page, login
function ptengine_login($arg_area){
    $t_account = get_option('key_ptengine_account');
    $t_pwd = get_option('key_ptengine_pwd');
    if ($t_account && $t_pwd) {
        // if account and pwd exist
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $arg_area. '/interface/login.pt?user.email='. $t_account. '&user.md5password='. $t_pwd);  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // for https request
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $r = curl_exec($ch);
        curl_close($ch);
        if ($r != 'error') {
            return $r;
        }
    }
    return false;
}

// when remove plugin, logout
function ptengine_logout(){
    update_option('key_ptengine_pwd', '');
}

// get area api
function ptengine_getArea($arg_param) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, ptengine_api_login . '/interface/getArea.pt?'. $arg_param);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // for https request
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    $r = curl_exec($ch);
    curl_close($ch);
    if ($r != 'error') {
        return substr($r, 1);
    }
    return '';
}
/*******************validate process end**********************************/

/*******************page process start**********************************/
// add page
function ptengine_admin_menu() {
    add_menu_page(__('Ptengine Report'), __('Ptengine'), 'activate_plugins', 'ptengine_report', 'display_ptengine_report', plugins_url('menu-icon.png',__FILE__), ptengine_menu_position);
    add_submenu_page('ptengine_report', __('Ptengine Report'), __('Data Center'), 'activate_plugins', 'ptengine_report', 'display_ptengine_report');
    add_submenu_page('ptengine_report', __('Setting'), __('Setting'), 'activate_plugins', 'ptengine_setting', 'display_ptengine_setting');
}

// show dc page
function display_ptengine_report() {
    $t_flag = $_GET['flag'];
    if ($t_flag == 'api'){
        return;
    }
    
    $t_sid = get_option('key_ptengine_sid');
    $t_dc_init = get_option('key_ptengine_dc_init');
    $t_uid = get_option('key_ptengine_uid');
    $t_account = get_option('key_ptengine_account');
    $t_pwd = get_option('key_ptengine_pwd');
    $t_site_id = get_option('key_ptengine_site_id');
    $t_timezone = get_option('key_ptengine_timezone');
    $t_pgid = get_option('key_ptengine_pgid');

    if($t_sid){
        // if sid exist, try login, get area api
        $t_area = ptengine_getArea('uid='. $t_uid);
        if (!$t_area){
        	return 'error';
        }
        $t_token = ptengine_login($t_area);
        $t_api_dc = $t_area. '/interface/wpPlugin.pt?page=dc';
        if (!$t_token) {
            // if login failed
            update_option('key_ptengine_pwd', '');
            $t_pwd = '';
        }
        $query_str = $t_api_dc
                . '&data={'
                    . 'isfirst:' . $t_dc_init . ','
                    . 'user:{'
                        . 'uid:"' . $t_uid . '",'
                        . 'email:"' . $t_account . '",'
                        . 'md5password:"' . $t_pwd . '"},'
                    . 'site:{'
                        . 'sid:"' . $t_sid . '",'
                        . 'siteId:"' . $t_site_id . '",'
                        . 'timezone:"' . $t_timezone . '",'
                        . 'groupId:"' . $t_pgid . '",'
                        . 'token:"' . $t_token . '"},'
                    . 'url:{'
                        . 'wppAPI:"' . wpp_api_report . '%26flag=api"},';
    	?>
		<iframe id='ptengine_report_frame' frameborder='no' border='0'  allowtransparency='true'  style='border:none;' src='' width='100%' height='1460px'><p>Your browser does not support iframes.</p></iframe>
		<script type='text/javascript'>
			var vh = 600;
    		vh = document.documentElement.clientHeight ? document.documentElement.clientHeight : document.body.clientHeight;
    		document.getElementById("ptengine_report_frame").src = '<?php echo $query_str; ?>' + "vh:" + vh + "}";	
    	</script>
		<?php
		echo "<script type='text/javascript'>
			(function(){
				if (document.readyState == 'complete') {
					//ptFun_changeIframeSize();
				} else {
					var oldWindowHandler = window.onload;
					window.onload = function() {
						if (!!oldWindowHandler) {
							oldWindowHandler();
						}
						//ptFun_changeIframeSize();
					};
				};
			})()

			//function ptFun_changeIframeSize(){
				//var minW = Math.max(document.getElementById('ptengine_report_frame').parentNode.offsetWidth, 840);
				//var minH = Math.max(document.getElementById('ptengine_report_frame').parentNode.offsetHeight, 1390);
				//document.getElementById('ptengine_report_frame').style.width = minW+'px';
				//document.getElementById('ptengine_report_frame').style.height = minH+'px';
			//}
		</script>";
		return;
    } else {
        // if profile do not exist, turn to Setting page
		echo '<script type="text/javascript">window.location.href ="'. wpp_api_setting. '";</script>';
    }
}

// show setting page
function display_ptengine_setting() {
    $t_flag = $_GET['flag'];
    if ($t_flag == 'api'){
        return;
    }
    
    $t_account = get_option('key_ptengine_account');
    $t_pwd = get_option('key_ptengine_pwd');
    $t_uid = get_option('key_ptengine_uid');
    $t_site_name = get_option('key_ptengine_site_name');
    $t_sid = get_option('key_ptengine_sid');
    $t_site_id = get_option('key_ptengine_site_id');
    $t_site_domain = get_option('home');
    
    if($t_uid){
    	// if account created,but profile do not exist)
    	$t_area = ptengine_getArea('uid='. $t_uid);
    } else {
    	// if account do not exist
    	$t_area = get_option('key_ptengine_area');
    	if (!$t_area) {
    		$t_area = ptengine_getArea('a='. $_SERVER['HTTP_ACCEPT_LANGUAGE']. '&b='. getIP());
    	}
    }
    if (!$t_area){
        return 'error';
    }
    if($t_sid){
        // if sid exist, try login, get area and api
        $status = '1';
        $t_token = ptengine_login($t_area);
        $t_api_setting = $t_area. '/interface/wpPlugin.pt?page=setting';
        if (!$t_token){
            // validate
            update_option('key_ptengine_pwd', '');
            $t_pwd = '';
        }
    } else {
        // if profile do not exist
        $status = '2';
        $t_api_setting = $t_area. '/interface/wpPlugin.pt?page=setting';
        update_option('key_ptengine_account', '');
        update_option('key_ptengine_pwd', '');
        update_option('key_ptengine_site_name', '');
        $t_account = '';
        $t_pwd = '';
        $t_site_name = '';
    }
    $query_str = $t_api_setting
            . '&data={'
                . 'status:' . $status . ','
                . 'user:{'
                    . 'email:"' . $t_account . '",'
                    . 'md5password:"' . $t_pwd . '"},'
                . 'site:{'
                    . 'sid:"' . $t_sid . '",'
                    . 'siteId:"' . $t_site_id . '",'
                    . 'siteName:"' . $t_site_name . '"},'
                . 'url:{'
                    . 'domain:"' . $t_site_domain . '",'
                . 'wppAPI:"' . wpp_api_report . '%26flag=api"},';
            ?>
		<iframe id='ptengine_setting_frame' frameborder='no' border='0'  allowtransparency='true'  style='border:none;' src='' width='100%' height='740px'><p>Your browser does not support iframes.</p></iframe>
		<script type='text/javascript'>
			var vh = 600;
    		vh = document.documentElement.clientHeight ? document.documentElement.clientHeight : document.body.clientHeight;
    		document.getElementById("ptengine_setting_frame").src = '<?php echo $query_str; ?>' + "vh:" + vh + "}";	
    	</script>
		<?php
		echo "<script type='text/javascript'>
			(function(){
				if (document.readyState == 'complete') {
					ptFun_changeIframeSize();
				} else {
					var oldWindowHandler = window.onload;
					window.onload = function() {
						if (!!oldWindowHandler) {
							oldWindowHandler();
						}
						ptFun_changeIframeSize();
					};
				};
			})()

			function ptFun_changeIframeSize(){
				var minW = Math.max(document.getElementById('ptengine_setting_frame').parentNode.offsetWidth, 840);
				var minH = Math.max(document.getElementById('ptengine_setting_frame').parentNode.offsetHeight, 740);
				//document.getElementById('ptengine_setting_frame').style.width = minW+'px';
				document.getElementById('ptengine_setting_frame').style.height = minH+'px';
			}
		</script>";
}
// Create admin menu
add_action('admin_menu', 'ptengine_admin_menu');
/*******************page process end**********************************/

?>