<?php
/**
 * @package AuthByGoogle
 * @version 0.2.1
 */
/*
Plugin Name: Authenticate By Google
Plugin URI: http://www.redleafsolutions.ca/wordpress/plugins/authbygoog/
Description: This pluging allows you to authenticate visitors by google without creating WordPress users
Author: iGAL Roytblat
Version: 0.2.2
Author URI: http://www.redleafsolutions.ca/
*/

session_start();

function authbygoog ($text) {
	global $id;
	
	/* Now for the updated format that will take precedence */
	$authbygoog = get_post_meta($id, authbygoog, true);
	if (!$authbygoog || ($authbygoog == 'default')) {
		$authbygoogopt = get_option (authbygoog_options);
		$authbygoog = $authbygoogopt[defaultauth];
	}

	if ($authbygoog == "yes") {
		if (!authbygoog_isAuth ()) {
			if ($_POST[authbygoog_email] && $_POST[authbygoog_password]) {
				require_once 'googleclientlogin.php';
				$google = new GoogleClientLogin ();
				if ($google->Authenticate ($_POST[authbygoog_email], $_POST[authbygoog_password])) {
					$_SESSION[authbygoog_username] = $_POST[authbygoog_email];
				} else {
					echo '<div class="updated"><p>Username or password are not correct.</p></div>';
					return authbygoog_loginForm ();
				}
			} else {
				return authbygoog_loginForm ();
			}
		}
	}
	return $text;
}
add_filter('the_content', 'authbygoog');

function authbygoog_isAuth () {
	return !empty ($_SESSION[authbygoog_username]);
}
function authbygoog_loginForm () {
	$thisdir = WP_PLUGIN_URL.dirname (substr(__FILE__, strlen (WP_PLUGIN_DIR)));
	return file_get_contents("${thisdir}/login.php");
}

function authbygoog_option_page() {
	global $wpdb;
?>

<div class="wrap">
	<h2>Authentication By Google Settings</h2>
	<p>Set up the default settings for your blog Google Authentication.</p>
<?php
	$authbygoogopt = get_option (authbygoog_options);
	if ($_POST['authbygoog_default']) {
		$authbygoogopt[defaultauth] = $_POST['authbygoog_default'];
		update_option (authbygoog_options, $authbygoogopt);
		echo '<div class="updated"><p>Authentication By Google default settings updated.</p></div>';
	}
?>
	
	<form method="post">
	<fieldset class="options">
		<legend>Default Authentication Settings</legend>
			<select name=authbygoog_default>
				<option value="yes" <?php if ($authbygoogopt[defaultauth] == 'yes') echo SELECTED; ?>>Yes</option>
				<option value="no" <?php if ($authbygoogopt[defaultauth] == 'no') echo SELECTED; ?>>No</option>
			</select>
			<p></p><input type="submit" class="button-primary" value="Save Settings" />
	</fieldset>
	</form>
</div>
<?php
}

function authbygoog_add_options() {
	add_options_page ('Authenticate By Google Options', 'Auth By Google', 8, __FILE__, 'authbygoog_option_page');
	$authbygoogopt = get_option (authbygoog_options);
	if (empty ($authbygoogopt)) {
		$authbygoogopt[defaultauth] = yes;
		update_option (authbygoog_options, $authbygoogopt);
	}
}

add_action('admin_menu', 'authbygoog_add_options');

add_filter('edit_post', 'authbygoog_edit_post');
add_filter('publish_post', 'authbygoog_edit_post');
add_filter('admin_footer', 'authbygoog_admin_footer');

function authbygoog_edit_post ($id) {
	global $wpdb, $id;

	if (!isset($id)) $id = $_REQUEST['post_ID'];
	
	if ($id && $_POST['authbygoog_needauth']) {
		update_post_meta ($id, authbygoog, $_POST['authbygoog_needauth']);
	}
}

function authbygoog_admin_footer($content) {
	global $id;

	if (!isset($id)) $id = $_REQUEST['post'];

	// Are we on the right page?
	if(preg_match('|post.php|i', $_SERVER['SCRIPT_NAME']) && $_REQUEST['action'] == 'edit') {
		$authbygoogopt = get_option (authbygoog_options);
		if(isset($_REQUEST['post'])) {
			$needauth = get_post_meta ($id, authbygoog, true);
			if (empty ($needauth)) {
				$needauth = $authbygoogopt[defaultauth];
			}
		}
		
		?>
		<div class="postbox " id="mtspp">
		<div title="Click to toggle" class="handlediv"><br></div><h3 class="hndle"><span>Authenticate By Google</span></h3>
		<div class="inside">
			<fieldset class="options">
				<legend></legend>
				<select name=authbygoog_needauth>
					<option value="default" <?php if (!$needauth || ($needauth == 'default')) echo SELECTED; ?>>Default</option>
					<option value="yes" <?php if ($needauth == 'yes') echo SELECTED; ?>>Yes</option>
					<option value="no" <?php if ($needauth == 'no') echo SELECTED; ?>>No</option>
				</select>
				<i>Default is <?php echo strtoupper ($authbygoogopt[defaultauth]); ?></i>
			</fieldset>
		</div>
		</div>
		<script language="JavaScript" type="text/javascript"><!--
		var placement = document.getElementById("side-sortables");
		var substitution = document.getElementById("mtspp");
		var mozilla = document.getElementById&&!document.all;
		if(mozilla)
			placement.parentNode.appendChild(substitution);
		else
			placement.parentElement.appendChild(substitution);
		//--></script>
		<?php
	}
}
