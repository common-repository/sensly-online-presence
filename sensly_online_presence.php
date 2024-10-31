<?php
/**
 * @package Sensly-online-presence
 * @author Tommie Podzemski
 * @version 0.1
 */
/*
Plugin Name: Sensly Online Presence
Plugin URI: http://sensly.net/wordpress
Description: Show your online presences on your Wordpress Blog using the sensly network. Don't forget to change this plugins <a href="plugins.php?page=sensly_online_presence.php">preferences</a>!
Author: Tommie Podzemski
Version: 0.6
Author URI: http://tommie.nu
*/

function sensly_get_presence() {
	//ping network if our tracked user is online
	if (is_user_logged_in()) {
		//ensure it's our tracked user
		global $current_user;
		get_currentuserinfo();
		if ($current_user->user_login == get_option("sensly_user")) {
			//sensly user is online
			sensly_set_presence(get_option("sensly_email"));
		}
	}
	//Grab presence from the network
	$tmpSenslyEmail = get_option("sensly_email");
	return "http://sensly.net/check/" . md5($tmpSenslyEmail) . "/image";
}

function sensly_set_presence($user) {
	//set user presence
	
	$pingUrl = "http://sensly.net/presence/" . $user . "/wordpressplugin/loggedin/";
	
	if (function_exists('curl_init')) {
		//using curl
		$curl_handle = curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, $pingUrl);
		$buffer = curl_exec($curl_handle);
		curl_close($curl_handle);
	} else if (function_exists('fopen')) {
		//try default php5
		$handle = fopen($pingUrl, "r");
		fclose($handle);
	}
}

function sensly_check_presence() {
	if (get_option("sensly_email")) {
		$presence = sensly_get_presence();
		echo "<p id='sensly_presence'><center>My current <a href=\"http://sensly.net\" target=_blank>Sensly</a> Online Presence is: <img src=$presence /></center></p>";
	}
}

//
// Check how user which to display the data
//

if (get_option("sensly_display") == 'widget') {
	// Widget code
	function widget_senslywidget_init() {
		function widget_senslywidget($args) {
				extract($args);
		?>
						<?php echo $before_widget; ?>
								<?php echo $before_title
										. 'My Online Presence'
										. $after_title; ?>
										<br />
										<center><img src="<?php echo sensly_get_presence(); ?>" width="43" height="9"></center><br />

										<small style="font-size:8px;">Powered by <a href="http://sensly.net/">Sensly</a> </small>

						<?php echo $after_widget; ?>
		<?php
		}
		register_sidebar_widget('Sensly Widget','widget_senslywidget');
	}

	// Delays plugin execution until Dynamic Sidebar has loaded first.
	add_action('plugins_loaded', 'widget_senslywidget_init');
	
} else {
	//just assume in the footer
	add_action('wp_footer', 'sensly_check_presence');
}

// options page
function sensly_options() {
	//always grab current user attached to this email
	global $current_user;
	
	$dbSenslyEmail = get_option('sensly_email');
	$dbSenslyDisplay = get_option('sensly_display');
	update_option('sensly_user', $current_user->user_login);
	
		// if submitted, process results
		if ( $_POST["sensly_submit"] ) {
			$dbSenslyEmail = stripslashes($_POST["sensly_email"]);
			$dbSenslyDisplay = stripslashes($_POST["display"]);
			update_option('sensly_email', stripslashes($_POST["sensly_email"]));
			update_option('sensly_display', stripslashes($_POST["display"]));
		}

	// options form
	echo '<div><form method="post">';
	echo "<div class=\"wrap\"><h2>Sensly Online Presence</h2>";
	echo '
				<h3 class="title">What is Sensly, and how does it work?</h3>
				<p>The idea with Sensly is to incooperate this awareness framework into as many internet application (social networks, browsers, email clients etc) as possible using a modular approach, effectively telling the Sensly-network wherever you or your friends are online using active actions rather that conventional sensing (if the application is active the user is active ideology). In the Wordpress context, this means: If you\'re logged in and active on your blog, you\'re most probably online.</p>
				<p>Sensly supports a wide variety of modules including Firefox, Thunderbird, Pidgin, It\'s own Windows Tray Client etc. Over time, the development-team will roll out more modules as they\'re ready. You\'re also encourage to take an active part in the network to create your own modules. Check out <a href="http://sensly.net" target=_blank>Sensly.net</a> for more info and more modules!</p>
				
			';
	echo '<h3 class="title">Sensly plugin settings</h3><p>There is no registration process involved when starting your tracking, just add your email to the field below and the system will automatically start tracking and publish your online status on your blog in the footer. Notice that you should use the same email if you wish to use any of the other modules to make your tracking even more accurate.</p><table class="form-table">';
	/*
	// who are we tracking?
	echo '<tr valign="top"><th scope="row">Tracked user on this WP-blog:</th>';
	echo '<td><input type=text value="'.$current_user->user_login.'" name="sensly_user" DISABLED></td></tr>';
	*/
	// sensly email setting 
	echo '<tr valign="top"><th scope="row">Email to use on <a href="http://Sensly.net" target="_blank">Sensly.net</a>:</th>';
	echo '<td><input type=text value="'.$dbSenslyEmail.'" name="sensly_email"><p>This email is never shown in plain-text anywhere while doing the presence activity.<br /> Though the system supports a md5-hash of the email if you so please.</p></td></tr>';
	
	// sensly display setting 
	
	echo "apa:" . $dbSenslyDisplay;
	
	echo '<tr valign="top"><th scope="row">How-to display my presence:</th>';
	echo '<td valign="top"><input type=radio value="widget" name="display" ';
					if ($dbSenslyDisplay == "widget") {
						echo 'checked ';
					} 
	
	
	echo '> <b>Using the Widget System</b>
				<p>Decide where Wordpress should put the Sensly information using the internal Widget system. After enabling this, go to the <a href="widgets.php">Widget System</a> to configure it </p>
				<input type=radio value="footer" name="display" ';
					if ($dbSenslyDisplay != "widget") {
						echo 'checked ';
					} 
	echo 	' /> <b>Place it in the Themes Footer</b>
				<p>Automatically put it in the footer of your current template</p></td></tr>';
	echo '<input type="hidden" name="sensly_submit" value="true"></input>';
	echo '</table>';
	echo '<p class="submit"><input type="submit" value="Update Options &raquo;"></input></p>';
	echo "</div>";
	echo '</form></div>';
}

function addsenslytoasubmenu() {
    add_submenu_page('plugins.php', 'Sensly Online Presence', 'Sensly Plugin', 10, __FILE__, 'sensly_options');
}
add_action('admin_menu', 'addsenslytoasubmenu');






?>
