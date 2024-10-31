<?php
/*
Plugin Name: Elitwee MyTwitter
Description: A simple Twitter status widget. Easily display your tweets.
Author: Calvin Freitas
Version: 3.0.3
Plugin URI: http://calvinf.com/projects/elitwee/mytwitter/
Author URI: http://calvinf.com/
License:  Creative Commons Attribution-Share Alike 3.0 Unported License
Warranties: None
Last Modified: December 03, 2009

This work is licensed under the Creative Commons Attribution-Share Alike 3.0 Unported License. To view a copy of this license, visit http://creativecommons.org/licenses/by-sa/3.0/ or send a letter to Creative Commons, 543 Howard Street, 5th Floor, San Francisco, California, 94105, USA.

Warranty: This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
*/

require_once('elitwee.php');

class ElitweeMyTwitter {
  function __construct() {
    //register hooks
    add_action('admin_init', array(&$this, 'add_settings'));
    add_action('admin_menu', array(&$this, 'register_settings'));
    add_action('wp_ajax_elitwee_post', array(&$this, 'elitwee_post_callback'));

    $plugin = plugin_basename(__FILE__);
    add_filter("plugin_action_links_$plugin", array(&$this, 'add_action_links'));

  }

  function add_settings() {
    register_setting( 'elitwee-mytwitter', 'elitwee_cache', array(&$this, 'validate_cache_options') );
    register_setting( 'elitwee-mytwitter', 'elitwee_display', array(&$this, 'validate_display_options') );
    register_setting( 'elitwee-mytwitter', 'elitwee_format', array(&$this, 'validate_format_options') );
    register_setting( 'elitwee-mytwitter', 'elitwee_twitter', array(&$this, 'validate_twitter_options') );
  }
  
  function validate_cache_options($input) {
  	//nothing in here right now
  	return $input;
  }
  
  function validate_display_options($input) {
  	$input['show_avatar'] = ($input['show_avatar'] == 'yes') ? 'yes' : 'no';
  	$input['title'] = wp_filter_post_kses($input['title']);
  	$input['count'] = intval($input['count']);
  	return $input;
  }
  
  function validate_format_options($input) {
  	//nothing in here right now
  	return $input;
  }
  
	function validate_twitter_options($input) {
		//nothing in here right now
		return $input;
	}
  
  function register_settings() {
	  $admin_page = add_options_page('Elitwee MyTwitter','Elitwee MyTwitter', 8, 'elitwee-mytwitter', array(&$this, 'plugin_options'));
	  add_action( "admin_print_scripts-$admin_page", array(&$this, 'admin_head') );
  }
  
  function admin_head() {
  	$plugindir = get_option('siteurl') . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__));
    $elitwee_script_url = $plugindir . '/js/mytwitter.js';
    
    wp_enqueue_script('jquery');
		wp_enqueue_script('elitwee-mytwitter', $elitwee_script_url);
  }

  function elitwee_post_callback() {
    $options_twitter = get_option('elitwee_twitter');
    $status = $_POST['status']; 

    if (isset($status) && $status != '' && isset($options_twitter['user']) && isset($options_twitter['pass'])) {
      $et = new Elitwee($options_twitter['user'], $options_twitter['pass'], 1);
      try {
        $et->post(stripslashes($status));
        echo json_encode( array(
          'success' => 1,
          'status'  => stripslashes($status)
        ));
      }
      catch (Exception $e) {
        echo json_encode( array(
          'error' => 1,
          'error_message' => $e->getMessage()
        ));
      }
    }
    else {
      echo json_encode( array(
        'error' => 1,
        'error_message' => 'Tweet cannot be empty.'
      ));
    }
    die();
  }

  function plugin_options() {
 	  //get options
		$options_cache   = get_option('elitwee_cache');   //location (directory), life (seconds)
		$options_display = get_option('elitwee_display'); //show_avatar, title, count
		$options_format  = get_option('elitwee_format');  //order, separator, beforeall, afterall, beforeitem, afteritem
		$options_twitter = get_option('elitwee_twitter'); //user, pass
		
		//twitter account options
		$options_twitter['user'] = $options_twitter['user'] ? $options_twitter['user'] : 'Elitwee'; //get user	
		
		//display options
		$options_display['show_avatar'] = $options_display['show_avatar'] ? $options_display['show_avatar'] : "no";
		$options_display['title'] = $options_display['title'] ? $options_display['title'] : "My Twitter";
		$options_display['count'] = $options_display['count'] ? $options_display['count'] : 5;
		
		//cache options
		$options_cache['location'] = $options_cache['location'] ? $options_cache['location'] : addslashes(WP_PLUGIN_DIR) . '/mytwitter/cache/';
		$options_cache['life'] = $options_cache['life'] ? $options_cache['life'] : 900; //default to 15 minutes
		
		//formatting options
		$options_format['order']      = $options_format['order'] ? $options_format['order'] : 'putfirst_twitter'; //putfirst_twitter, putfirst_time, tweet_only
		$options_format['separator']  = $options_format['separator'] ? $options_format['separator'] : ' -- ';
		$options_format['beforeall']  = $options_format['beforeall'] ? $options_format['beforeall'] : '<ul class="mytwitter">';
		$options_format['afterall']   = $options_format['afterall'] ? $options_format['afterall'] : '</ul>';
		$options_format['beforeitem'] = $options_format['beforeitem'] ? $options_format['beforeitem'] : '<li class="mytwitter">';
		$options_format['afteritem']  = $options_format['afteritem'] ? $options_format['afteritem'] : '</li>';

		//create Elitwee object for displaying Most Recent tweet and for grabbing avatar
		$tweets = array();
		try {
			$et = new Elitwee($options_twitter['user'], $options_twitter['pass'], 1);
			$cache_location = htmlspecialchars(stripslashes($options_cache['location']));
			if ($cache_location == null) {$cache_location = addslashes(WP_PLUGIN_DIR) . '/mytwitter/cache/';}
			$cache_life = 60; //shorter since this should only be used on admin panel
			$et->set_cache_time($cache_life);
			$et->set_user_timeline_format('json');
			$tweets = $et->get_user_timeline();
			
			if (isset($tweets[0]->user->profile_image_url)) {
				$avatar_url =  $tweets[0]->user->profile_image_url;
			}
		}
		catch (Exception $e) {
			$avatar_url = '';
		}
		?>
		
    <div class="wrap">
			<h2>Elitwee MyTwitter</h2>
			<?php if(!current_user_can('publish_posts')) { ?>
				<p><b>Twitter Status Update Disabled:</b> You do not have sufficient user privileges in Wordpress to apply status updates.</p>
			<?php } ?>
			
			<?php if(current_user_can('publish_posts')) {?>
				<fieldset name="fields_update" class="options">
					<legend><h2>Update Twitter Status</h2></legend>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><b><label for="mytwitter_status">What's happening?</label></b></th>
							<td>
								<p><textarea cols="40" maxlength="140" name="mytwitter_status" id="mytwitter_status" onkeyup="charCount();" ></textarea><br/>
		  					<span id="mytwitter_characters">140 characters remaining.</span><br />
		  					<input type="button" id="tweet_submit" value="Update Status" onclick="javascript:ajaxTweet();" /></p>
                <div id="tweet_submit_status">&nbsp;</div>
							</td>
						</tr>
						<tr>
							<th scope="row"><b>Most Recent Update:</b></th>
							<td><?php 
								$tweet_text = $tweets[0]->text;
								$tweet_text = ( $tweet_text != '' ) ? $et->format($tweet_text) : '<i>Not available.</i>';
								echo $tweet_text;
							?></td>
						</tr>
					</table>
				</fieldset>
			<?php } ?>
			
			<form name="mytwitter_options" id="mytwitter_options" method="post" action="options.php" onsubmit="javascript:return ValidateForm(this)" autocomplete="off" >
				<?php settings_fields('elitwee-mytwitter'); ?>
								
				<?php if(current_user_can('manage_options')) { ?>
				<fieldset name="fields_twitter" class="options">
					<legend><h2>Twitter Account Settings</h2></legend>
					<p>These settings are for the default Twitter username and password -- these can also be set on a per-widget basis.</p>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><b><label for="elitwee_twitter[user]">Username:</label></b></th>
							<td><input type="text" name="elitwee_twitter[user]" id="elitwee_twitter[user]" value="<?php echo $options_twitter['user']; ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><b><label for="elitwee_twitter[pass]">Password:</label></b></th>
							<td><input type="password" name="elitwee_twitter[pass]" id="elitwee_twitter[pass]" value="<?php echo $options_twitter['pass']; ?>" /></td>
						</tr>
					</table>
				</fieldset>
				<?php } ?>
				
				<?php if(current_user_can('manage_options')) { ?>
				<fieldset name="fields_display" class="options">
					<legend><h2>Display Settings</h2>
					<p>These are the default display settings -- they can be overridden on a per-widget basis.</p>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><b><label for='elitwee_display[title]'>Title:</label></b></th>
							<td><input type="text" name="elitwee_display[title]" id="elitwee_display[title]" value="<?php echo $options_display['title']; ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><b><label for="elitwee_display[show_avatar]">Show Avatar:</label></b></th>
							<td>
								
								
								<?php if (isset($avatar_url) && $avatar_url != '') { ?><img src="<?php echo $avatar_url;?>" width="48" height="48" class="avatar" alt="avatar" style="float: left;margin-right:4px;" /><?php } ?>
								<select name="elitwee_display[show_avatar]" id="elitwee_display[show_avatar]">
					      	<option value='yes'<?php echo ($options_display['show_avatar'] == 'yes') ? " selected=\"selected\"" : ''; ?>>Yes</option>
					      	<option value='no'<?php echo ($options_display['show_avatar'] == 'no' or $options_display['show_avatar'] == NULL) ? " selected=\"selected\"" : ''; ?>>No</option>
					      </select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><b><label for="elitwee_display[count]">Number of Tweets:</label></b></th>
							<td>
								<select name="elitwee_display[count]" id="elitwee_display[count]">
									<?php $i=1;
									while($i<=20) { ?>
  									<option value="<?php echo $i;?>" <?php if($i == $options_display['count']){echo "selected=\"selected\"";}?>><?php echo $i;?></option>
  								<?php $i++;} ?>
  							</select>
							</td>
						</tr>
					</table>
				</fieldset>
			<?php } ?>
				
				<?php if(current_user_can('manage_options')) { ?>
				<fieldset name="fields_cache" class="options">
					<legend><h2>Cache Settings</h2></legend>
					<p>Please specify that absolute path to the cache directory on your web server.</p>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><b><label for="elitwee_cache[location]">Location:</label></b></th>
							<td><input type="text" name="elitwee_cache[location]" id="elitwee_cache[location]" size="50" value="<?php echo $options_cache['location']; ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><b><label for="elitwee_cache[life]">Cache Life (in seconds):</label></b></th>
							<td><input type="text" name="elitwee_cache[life]" id="elitwee_cache[life]" style="width:40px;" value="<?php echo $options_cache['life']; ?>" /></td>
						</tr>
					</table>
				</fieldset>
				<?php } ?>
				
				<?php if(current_user_can('manage_options')) { ?>
				<fieldset name="fields_formatting" class="options">
					<legend><h2>Formatting Options</h2></legend>
					<p>These are the universal formatting options for Elitwee MyTwitter -- they cannot be set on a per-widget basis.</p>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><b><label for="elitwee_format[order]">Order of Elements:</label></b></th>
							<td>
								<select name="elitwee_format[order]" id="elitwee_format[order]">
									<option value="putfirst_twitter"<?php if($options_format['order'] == "putfirst_twitter") {echo ' selected="selected"';}?>>Tweet - Time</option>
									<option value="putfirst_time"<?php if($options_format['order'] == "putfirst_time") {echo ' selected="selected"';}?>>Time - Tweet</option>
									<option value="tweet_only"<?php if($options_format['order'] == "tweet_only") {echo ' selected="selected"';}?>>Tweet only (no time)</option>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><b><label for="elitwee_format[separator]">Separator</label></b></th>
							<td><input type="text" name="elitwee_format[separator]" id="elitwee_format[separator]" value="<?php echo htmlspecialchars(stripslashes($options_format['separator'])); ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><b><label for="elitwee_format[beforeall]">Before All Tweets:</label></b></th>
							<td><input type="text" name="elitwee_format[beforeall]" id="elitwee_format[beforeall]" value="<?php echo htmlspecialchars(stripslashes($options_format['beforeall'])); ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><b><label for="elitwee_format[afterall]">After All Tweets:</label></b></th>
							<td><input type="text" name="elitwee_format[afterall]" id="elitwee_format[afterall]" value="<?php echo htmlspecialchars(stripslashes($options_format['afterall'])); ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><b><label for="elitwee_format[beforeitem]">Before Each Tweet</label></b></th>
							<td><input type="text" name="elitwee_format[beforeitem]" id="elitwee_format[beforeitem]" value="<?php echo htmlspecialchars(stripslashes($options_format['beforeitem'])); ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><b><label for="elitwee_format[afteritem]">After Each Tweet</label></b></th>
							<td><input type="text" name="elitwee_format[afteritem]" id="elitwee_format[afteritem]" value="<?php echo htmlspecialchars(stripslashes($options_format['afteritem'])); ?>" /></td>
						</tr>
					</table>
				</fieldset>
				<?php } ?>
				
				
				<p class="submit">
					<input type="submit" class="button-primary" value="Save Changes" />	
				</p>
				
				<p><b>If you want to use Elitwee MyTwitter anywhere on your site with the default settings and not as a widget, you can add the following code in in the appropriate place in your theme.</b><br />
&lt;?php mytwitter();?&gt</p>
			</form>
			
			</div>
			
			<input type="hidden" id="elitwee_admin_ajax_url" value="<?php echo admin_url("admin-ajax.php");?>" />
    <?
  }

  function add_action_links( $links ) {
    //link to settings page
    $settings_link = '<a href="options-general.php?page=elitwee-mytwitter">Settings</a>';
    array_unshift( $links, $settings_link );
    return $links;
  }
} //end class

class MyTwitter {
	public function __construct($user, $pass, $count, $title) {
		$this->options_cache   = get_option('elitwee_cache');
		$this->options_display = get_option('elitwee_display');
		$this->options_format  = get_option('elitwee_format');
		$this->options_twitter = get_option('elitwee_twitter');
		
		$this->options_twitter['user']  = ($user && $user != '') ? $user : $this->options_twitter['user'];
		$this->options_twitter['pass']  = ($pass && $pass != '') ? $pass : $this->options_twitter['pass'];
		$this->options_display['count'] = ($count && $count > 0 && $count < 20) ? $count : $this->options_display['count'];
		$this->options_display['title'] = ($title && $title != '') ? $title : $this->options_display['title'];
	}
	
	public function setDisplayAvatar($show_avatar) {
		$this->options_display['show_avatar'] = ($show_avatar == 'yes') ? 'yes' : 'no';
	}
	
	private function cacheLocation() {
		$cache_location = htmlspecialchars(stripslashes($this->options_cache['location']));
		if ($cache_location == null) {
			$cache_location = "cache/";
		}
		return $cache_location;
	}
	
	private function cacheLife() {
		$cache_life = ($this->options_cache['life'] > 0) ? $this->options_cache['life'] : 900;
		return $cache_life;
	}
	
	private function displayShowAvatar() {
		$show_avatar = ($this->options_display['show_avatar'] === "yes") ? TRUE : FALSE;
		return $show_avatar;
	}
	
	private function displayTitle() {
		return $this->options_display['title'];
	}
	
	private function displayCount() {
		return $this->options_display['count'];
	}
	
	private function formatOrder() {
		$format_order = isset($this->options_format['order']) ? stripslashes($this->options_format['order']) : "putfirst_twitter";
		return $format_order;
	}
	
	private function formatSeparator() {
		$separator  = isset($this->options_format['separator']) ? stripslashes($this->options_format['separator']) : " -- ";
		return $separator;
	}
	
	private function formatBeforeAll() {
		$beforeall  = isset($this->options_format['beforeall']) ? stripslashes($this->options_format['beforeall']) : '<ul class="mytwitter">';
		return $beforeall;
	}
	
	private function formatAfterAll() {
		$afterall  = isset($this->options_format['afterall']) ? stripslashes($this->options_format['afterall']) : '</ul>';
		return $afterall;
	}
	
	private function formatBeforeItem() {
		$beforeitem  = isset($this->options_format['beforeitem']) ? stripslashes($this->options_format['beforeitem']) : '<li class="mytwitter">';
		return $beforeitem;
	}
	
	private function formatAfterItem() {
		$afteritem  = isset($this->options_format['afteritem']) ? stripslashes($this->options_format['afteritem']) : '</li>';
		return $afteritem;
	}
	
	private function user() {
		return $this->options_twitter['user'];
	}
	
	private function password() {
		return $this->options_twitter['pass'];
	}
	
	public function output() {
		$output_html = '';
		$title = $this->displayTitle();
		if (isset($title) && $title != '') {
			$output_html .= "<h2 class=\"widgettitle\"><a href='http://twitter.com/" . $this->user() . "' title='View " . $this->user() . " on Twitter'>" . $this->displayTitle() . "</a></h2>\n";
		}
		
		$output_html .= $this->formatBeforeAll() . "\n"; // display before all tweets
	
		try {
			$et = new Elitwee($this->user(), $this->password(), $this->displayCount());
			$et->set_cache_location($this->cacheLocation());
			$et->set_cache_time($this->cacheLife());
			$et->set_user_timeline_format('json');
			
			$tweets = $et->get_user_timeline();
			
			if (isset($tweets[0]->user->profile_image_url) && ( $this->displayShowAvatar() )) {
				$output_html .= "<a href=\"http://twitter.com/" . $tweets[0]->user->screen_name . "\"><img src=\"" . $tweets[0]->user->profile_image_url . "\"" . ' width="48" height="48" class="avatar" alt="avatar" /></a>';
			}
			
			$i = 0;
			foreach ($tweets as $tweet) {
				$i++;
				
				//display the tweet
				if ($this->options_format['order'] == "putfirst_time") {
					$output_html .= '  ' . $this->formatBeforeItem() . '<span class="mytwitter_tweet_time" id="mytwitter_tweet_time-' . $i . '">';
					$output_html .= '<a href="http://twitter.com/' . $tweet->user->screen_name . '/statuses/' . $tweet->id . '" title="view this tweet on Twitter">' . relative_time(strtotime($tweet->created_at), "") . '</a>';
					$output_html .= '</span><span class="mytwitter_separator" id="mytwitter_separator-' . $i . '">' . $this->formatSeparator() . '</span><span class="mytwitter_tweet" id="mytwitter_tweet-' . $i . '">' . $et->format($tweet->text) . '</span>' . $this->formatAfterItem() . "\n";
				}
				elseif ($this->options_format['order'] == "tweet_only") {
					$output_html .= '  ' . $this->formatBeforeItem() . '<span class="mytwitter_tweet" id="mytwitter_tweet-' . $i . '">' . $et->format($tweet->text) . '</span>';
					$output_html .= $this->formatAfterItem() . "\n";
				}
				else {
					$output_html .= '  ' . $this->formatBeforeItem() . '<span class="mytwitter_tweet" id="mytwitter_tweet-' . $i . '">' . $et->format($tweet->text) . '</span><span class="mytwitter_separator" id="mytwitter_separator-' . $i . '">' . $this->formatSeparator() . '</span><span class="mytwitter_tweet_time" id="mytwitter_tweet_time-' . $i . '">';
					$output_html .= '<a href="http://twitter.com/' . $tweet->user->screen_name . '/statuses/' . $tweet->id . '" title="view this tweet on Twitter">' . relative_time(strtotime($tweet->created_at), "") . '</a>';
					$output_html .= '</span>' . $this->formatAfterItem() . "\n";
				}
			}
		}
		catch (Exception $e) {
			$output_html .= 'Error: ' . $e->getMessage();
		}
	
		$output_html .= $this->formatAfterAll() . "\n"; // displays after all tweets
		echo $output_html;
	}
}

add_action( 'init', 'ElitweeMyTwitter' );
function ElitweeMyTwitter() {
	global $ElitweeMyTwitter;
	$ElitweeMyTwitter = new ElitweeMyTwitter();
}

class ElitweeMyTwitter_Widget extends WP_Widget {
  function ElitweeMyTwitter_Widget() {
		//parent::WP_Widget(false, $name = 'MyTwitter');
		
		$widget_ops = array('classname' => 'widget_mytwitter', 'description' => __('A Twitter widget to display recent tweets on your blog.') );
		$this->WP_Widget('mytwitter', __('Elitwee MyTwitter'), $widget_ops);
  }

  function widget($args, $instance) {
  	extract( $args );
  	$mytwitter = new MyTwitter($instance['username'], $instance['password'], $instance['count'], $instance['title']);
  	$mytwitter->setDisplayAvatar($instance['show_avatar']);
		$mytwitter->output();
  }

  function update($new_instance, $old_instance) {
		return $new_instance;
  }

  function form($instance) {
		$title    = esc_attr($instance['title']);
		$username = esc_attr($instance['username']);
		$password = esc_attr($instance['password']);
		$count    = esc_attr($instance['count']);
		
		$show_avatar = esc_attr($instance['show_avatar']);
		
		?>
	  	<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
	  	<p><label for="<?php echo $this->get_field_id('username'); ?>"><?php _e('Twitter Username:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('username'); ?>" name="<?php echo $this->get_field_name('username'); ?>" type="text" value="<?php echo $username; ?>" /></label></p>
	  	<p><label for="<?php echo $this->get_field_id('password'); ?>"><?php _e('Twitter Password (Optional):'); ?> <input class="widefat" id="<?php echo $this->get_field_id('password'); ?>" name="<?php echo $this->get_field_name('password'); ?>" type="password" value="<?php echo $password; ?>" /></label></p>
  		<p><label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('Number of Tweets to Display:'); ?> 
  			<select id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>">
					<?php $i=1;
					while($i<=20) { ?>
						<option value="<?php echo $i;?>" <?php if($i == $count){echo "selected=\"selected\"";}?>><?php echo $i;?></option>
					<?php $i++;} ?>
				</select>
  		</label></p>
  		<p><label for="<?php echo $this->get_field_id('show_avatar'); ?>"><?php _e('Show Avatar:'); ?> 
	  		<select name="<?php echo $this->get_field_name('show_avatar'); ?>" id="<?php echo $this->get_field_id('show_avatar'); ?>">
	      	<option value='yes'<?php echo ($show_avatar == 'yes') ? " selected=\"selected\"" : ''; ?>>Yes</option>
	      	<option value='no'<?php echo ($show_avatar == 'no' or $show_avatar == NULL) ? " selected=\"selected\"" : ''; ?>>No</option>
	      </select>
    	</label></p>
	  <?php 
  }
}
add_action('widgets_init', create_function('', 'return register_widget("ElitweeMyTwitter_Widget");'));

//for some level of backwards compatibility, I'm leaving the mytwitter() function here
function mytwitter() {
	$mytwitter = new MyTwitter(null, null, null, null);
	$mytwitter->output();
}//end function mytwitter

?>
