<?php
/*

Plugin name: Randomize
Plugin URI: 
Description: Display randomized text in a widget or on posts/pages by shortcode. Originally scripted by Greg Jackson.
Version: 1.0
Author: Sebastian Schwarz
Author URI: http://mesign.eu

Copyright 2014  Sebastian Schwarz (email : se-schwarz@gmx.de)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/


class randomize extends WP_Widget {

	function randomize() {
	  $widget_ops = array('classname' => 'randomize',
                      'description' => ' Display randomized text from the selected category.');
		$this->WP_Widget('randomize', 'Randomize', $widget_ops);
	}

	function get_randomize($category='', $random=false) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'randomize';
	  $sql = 'SELECT randomize_id, text FROM '. $table_name." WHERE visible='yes' ";
		$sql .= ($category!='') ? " AND category = '$category'" : '' ;
		if($random)
			$sql .= ' ORDER BY RAND() LIMIT 1 ';
		else
			$sql .= ' ORDER BY timestamp, randomize_id LIMIT 1 ';
		$row = $wpdb->get_row($sql);
		
		// update the timestamp of the row we just seleted (used by rotator, not by random)
		if(!$random AND intval($row->randomize_id)) {
			$sql = 'UPDATE '.$table_name.' SET timestamp = Now() WHERE randomize_id = '.intval($row->randomize_id);
			$wpdb->query($sql);
		}
		
		// now we can safely render shortcodes without self recursion (unless there is only one item containing [randomize] shortcode - don't do that, it's just silly!)
		$snippet = do_shortcode($row->text);
		
		return $snippet;
	}

	function widget($args, $instance) {
		extract($args);
		$title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title']);
		$category = empty($instance['category']) ? '' : $instance['category'];
		$random = intval($instance['random']);
		$snippet = $this->get_randomize($category, $random);
		if($snippet!='') {
			echo $before_widget;
			if($title)
				echo $before_title.$title.$after_title;
			echo $instance['pretext'].$snippet.$instance['posttext'];
			echo $after_widget;
		}
	}
	
	function update($new_instance, $old_instance) {
	  $instance = $old_instance;
	  $instance['title'] = strip_tags(stripslashes($new_instance['title']));
		$instance['category'] = strip_tags(strip_tags(stripslashes($new_instance['category'])));
		$instance['pretext'] = $new_instance['pretext'];
		$instance['posttext'] = $new_instance['posttext'];
		$instance['random'] = intval($new_instance['random']);
	  return $instance;
	}
	
	function form($instance) {
		
	  $instance = wp_parse_args((array)$instance, array('title' => 'Randomize', 'category' => '', 'pretext' => '', 'posttext' => ''));
		
	  $title = htmlspecialchars($instance['title']);
	  $category = htmlspecialchars($instance['category']);
		$pretext = htmlspecialchars($instance['pretext']);
		$posttext = htmlspecialchars($instance['posttext']);
		if(!isset($instance['random'])) { $instance['random'] = 0; }
  
		echo '<p>
				<label for="'.$this->get_field_name('title').'">Title: </label> 
				<input type="text" id="'.$this->get_field_id('title').'" name="'.$this->get_field_name('title').'" value="'.$title.'"/>
			</p><p>
				<label for="'.$this->get_field_name('pretext').'">Pre-Text: </label> 
				<input type="text" id="'.$this->get_field_id('pretext').'" name="'.$this->get_field_name('pretext').'" value="'.$pretext.'"/>
			</p><p>
				<label for="'.$this->get_field_name('category').'">Category: </label>
				<select id="'.$this->get_field_id('category').'" name="'.$this->get_field_name('category').'">
				<option value="">All Categories </option>';
		echo randomize_get_category_options($instance['category']);
		echo '</select></p>
			<p>
				<label for="'.$this->get_field_name('posttext').'">Post-Text: </label> 
				<input type="text" id="'.$this->get_field_id('posttext').'" name="'.$this->get_field_name('posttext').'" value="'.$posttext.'"/>
			</p>
			<p>
				<label for="'.$this->get_field_name('random').'">Selection: </label> 
				<select id="'.$this->get_field_id('random').'" name="'.$this->get_field_name('random').'">
				<option value="1" '.selected(intval($instance['random']),1).'>Random</option>
				<option value="0" '.selected(intval($instance['random']),0).'>Rotation</option>
				</select><br/>
				<span class="description">Note: Random can be more intensive with large record sets, and some items may never appear.</span>
			</p>'; 
	}
	
}

function randomize($category, $random=FALSE){
	$randomize = new randomize;
	echo $randomize->get_randomize($category,$random);
}

function randomize_init() {
  register_widget('randomize');
}

function randomize_get_category_options($category='') {
	global $wpdb;
	$table_name = $wpdb->prefix . 'randomize';
	$sql = 'SELECT category FROM '.$table_name.' GROUP BY category ORDER BY category';
	$rows = $wpdb->get_results($sql);
	
	$option_nocategory = false;
	$nocategory_name = 'No Category';
	
	foreach($rows as $row){
		$selected = ($category==$row->category) ? 'SELECTED' : '';
		$categoryname = $row->category;		
		if(trim($categoryname)==''){
			$categoryname = $nocategory_name;
			$option_nocategory = true;
		}
		$result .= '<option value="'.$row->category.'" '.$selected.'>'.$categoryname.' </option>';
	}
	if(!$option_nocategory)
		$result = '<option value="">'.$nocategory_name.' </option>'.$result;
	return $result;
}


function randomize_install() {
	global $wpdb, $user_ID;
	$table_name = $wpdb->prefix . 'randomize';
	// create the table if it doesn't exist 
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		$sql = "CREATE TABLE `$table_name` (
			`randomize_id` int(10) unsigned NOT NULL auto_increment,
			`category` varchar(32) character set utf8 NOT NULL,
			`text` text character set utf8 NOT NULL,
			`visible` enum('yes','no') NOT NULL default 'yes',
			`user_id` int(10) unsigned NOT NULL,
			`timestamp` timestamp NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY  (`randomize_id`),
			KEY `visible` (`visible`),
			KEY `category` (`category`),
			KEY `timestamp` (`timestamp`) 
		)";
		$results = $wpdb->query( $sql );
		// add some test data
		$data = array ('category' => 'Installer', 'user_id'=> $user_ID, 'text' => 'Creativity is the ability to introduce order into the randomness of nature. - Eric Hoffer' );
		$wpdb->insert($table_name, $data);
		$data['text'] = 'So much of life, it seems to me, is determined by pure randomness. - Sidney Poitier';
		$wpdb->insert($table_name, $data);
	}
}

add_action('widgets_init', 'randomize_init');
register_activation_hook(__FILE__,'randomize_install');

if(is_admin()) {
	$plugin_basename = plugin_basename(__FILE__); 
	include 'randomize_admin.php';
}
	
// Shortcode implementation
function randomize_shortcode($attribs) {
	extract(shortcode_atts(array('category' => '', 'random' => FALSE, ), $attribs));
	$randomize = new randomize;
	return $randomize->get_randomize($category,$random);
}

add_shortcode('randomize', 'randomize_shortcode');

?>