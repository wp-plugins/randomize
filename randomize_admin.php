<?php

$randomize_adminurl = admin_url().'options-general.php?page=randomize';

add_action('admin_menu', 'randomize_menu');

function randomize_menu() {
  add_options_page('Randomize', 'Randomize', 'update_plugins', 'randomize', 'randomize_options');
}

// Add settings link on plugin page
function randomize_settings_link($links) { 
  $settings_link = '<a href="options-general.php?page=randomize">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
add_filter("plugin_action_links_$plugin_basename", 'randomize_settings_link' );


function randomize_options() {
	if($_POST) {
		// process the posted data and display summary page - not pretty :(
		randomize_save($_POST);
	}

	$action = isset($_GET['action']) ? $_GET['action'] : false;
	switch($action){
		case 'new' :
			randomize_edit();
			break;
		case 'edit' :
			$id = intval($_GET['id']);
			randomize_edit($id);
			break;
		case 'delete' :
			$id = intval($_GET['id']);
			check_admin_referer('randomize_delete'.$id);
			randomize_delete($id);
			// now display summary page
			randomize_list();
			break;
		default:
			randomize_list();
	}
}

function randomize_pagetitle($suffix='') {
 return '
 <div id="icon-options-general" class="icon32"><br/></div><h2>Randomize '.$suffix.'</h2>
 ';
}

function randomize_error($text='An undefined error has occured.') {
	echo '<div class="wrap">'.randomize_pagetitle(' - ERROR!').'<h3>'.$text.'</h3></div>';
}
 
function randomize_list() {
	global $wpdb, $user_ID, $randomize_adminurl;
	$table_name = $wpdb->prefix . 'randomize';
	$pageURL = $randomize_adminurl;
	$cat = isset($_GET['cat']) ? $_GET['cat'] : false;
	$author_id = isset($_GET['author_id']) ? intval($_GET['author_id']) : 0;
	$where = $page_params = '';

	if($cat) {
		$where = " WHERE category = '$cat'";
		$page_params = '&cat='.urlencode($cat);
	}
	if($author_id) {
		$where = " WHERE user_id = $author_id";
		$page_params .= '&author_id='.$author_id;
	}
	
	// pagination related

	$item_count = $wpdb->get_row("Select count(*) items FROM $table_name $where");
	if(isset($item_count->items)) {
		$totalrows = 	$item_count->items;
	} else {
		echo '<h3>Achtung! The expected database table "<i>'.$table_name.'</i>" does not appear to exist.</h3>';
	}
	
	$perpage = 20;
	$paged = isset($_GET['paged']) ? intval($_GET['paged']) : 0;
	$paged = $paged ? $paged : 1;

	$num_pages = 1+floor($totalrows/$perpage);

	if($paged > $num_pages) { $paged = $num_pages; }
	
	$del_paged = ($paged > 1) ? '&paged='.$paged : '';	 // so we stay on the current page if we delete an item
	
	$paging = paginate_links( array(
		'base' => $pageURL.$page_params.'%_%', // add_query_arg( 'paged', '%#%' ),
		'format' => '&paged=%#%',
		'prev_text' => __('&laquo;'),
		'next_text' => __('&raquo;'),
		'total' => $num_pages,
		'current' => $paged
		));
	
	// now load the data to display

	$startrow = ($paged-1)*$perpage;	
	$rows = $wpdb->get_results("SELECT * FROM $table_name $where ORDER BY randomize_id LIMIT $startrow, $perpage");
	$item_range = count($rows);
	if($item_range>1) {
		$item_range = ($startrow+1).' - '.($startrow+$item_range);
	}
	
	$author = array();

	?>
<div class="wrap">
	<?php echo randomize_pagetitle(); ?>
	<div class="tablenav">
		<div class="alignleft actions">
			<input type="submit" class="button-secondary action" id="randomize_add" name="randomize_add" value="Add New" onclick="location.href='options-general.php?page=randomize&action=new'"/>
			Category: <select id="randomize_category" name="randomize_category" onchange="javascript:window.location='<?php echo $pageURL.'&cat='; ?>'+(this.options[this.selectedIndex].value);">
			<option value="">View all categories </option>
			<?php echo randomize_get_category_options($cat); ?>
			</select>
		</div>
		<div class="tablenav-pages">
			<span class="displaying-num">Displaying <?php echo $item_range.' of '.$totalrows; ?></span>
			<?php echo $paging; ?>
		</div>
	</div>

	<table class="widefat">
	<thead><tr>
		<th width="10%">ID</th>
		<th width="60%">Text</th>
		<th width="10%">Category</th>
		<th width="10%">Author</th>
		<th width="10%">Action</th>
	</tr></thead>
	<tbody>
<?php		
	$alt = '';
	foreach($rows as $row) {
		$alt = ($alt) ? '' : ' class="alternate"'; // stripey :)
		if(!isset($author[$row->user_id])){
			$user_info = get_userdata($row->user_id);
			$author[$row->user_id] = $user_info->display_name;
		}
		$status = ($row->visible=='yes') ? 'visible' : 'hidden';
		$bytes = strlen($row->text);
		if(strlen($row->text) > 200)
			$row->text = trim(mb_substr($row->text,0,350,'UTF-8')).'...';
		echo '<tr'.$alt.'>
		<td>'.$row->randomize_id.'</td>
		<td>'.esc_html($row->text).'</td>
		<td><a href="'.$pageURL.'&cat='.$row->category.'">'.$row->category.'</a><br />'.$status.'</td>
		<td class="author column-author"><a href="'.$pageURL.'&author_id='.$row->user_id.'">'.$author[$row->user_id].'</a><br />'.$bytes.' bytes</td>
		<td><a href="'.$pageURL.'&action=edit&id='.$row->randomize_id.'">Edit</a><br />';
		$del_link = wp_nonce_url($pageURL.$del_paged.'&action=delete&id='.$row->randomize_id, 'randomize_delete' . $row->randomize_id);
		echo '<a onclick="if ( confirm(\'You are about to delete post #'.$row->randomize_id.'\n Cancel to stop, OK to delete.\') ) { return true;}return false;" href="'.$del_link.'" title="Delete this post" class="submitdelete">Delete</a>';
		echo '</td></tr>';		
	}
	echo '</tbody></table>';

  echo '</div>';
}

function randomize_edit($randomize_id=0) {
	
	echo '<div class="wrap">';
	$title = '- Add New';
	if($randomize_id) {
		$title = '- Edit';
		
		global $wpdb;
		$table_name = $wpdb->prefix . 'randomize';
		$sql = "SELECT * from $table_name where randomize_id=$randomize_id";
		$row = $wpdb->get_row($sql);
		if(!$row)
			$error_text = '<h2>The requested entry was not found.</h2>';
	} else {
		$row = new stdClass();
		$row->text = '';
		$row->visible = 'yes';
	}
	echo randomize_pagetitle($title); 
	
	if($randomize_id && !$row) {
		echo '<h3>The requested entry was not found.</h3>';
	} else {
	// display the add/edit form 
	global $randomize_adminurl;
	
	echo '<form method="post" action="'.$randomize_adminurl.'">
		'.wp_nonce_field('randomize_edit' . $randomize_id).'
		<input type="hidden" id="randomize_id" name="randomize_id" value="'.$randomize_id.'">
		<h3>Text To Display</h3>
		<textarea name="randomize_text" style="width: 80%; height: 100px;">'.apply_filters('format_to_edit',$row->text).'</textarea>
		<h3>Category</h3>
		<p>Select a category from the list or enter a new one.</p>
		<label for="randomize_category">Category: </label><select id="randomize_category" name="randomize_category">'; 
	echo randomize_get_category_options($row->category);
	echo '</select></p>
		<p><label for="randomize_category_new">New Category: </label><input type="text" id="randomize_category_new" name="randomize_category_new"></p>';
		
		echo '<h3>Is visible.</h3>
			<p><label for="randomize_visible_yes"><input type="radio" id="randomize_visible_yes" name="randomize_visible" value="yes" '.checked($row->visible,'yes',false).' /> Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;
			<label for="randomize_visible_no"><input type="radio" id="randomize_visible_no" name="randomize_visible" value="no" '.checked($row->visible,'no',false).' /> No</label></p>';
		if(!$randomize_id) {
			// don't offer Bulk Insert on edit
			echo '<h3>Use Bulk Insert</h3>
			<p><input type="radio" name="randomize_bulkinsert" value="yes" /> Yes&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="randomize_bulkinsert" value="no" checked="checked" /> No</p>
			<small>Bulk insert will create a new record for each line (delimited by carriage return) within the text box above using the same category selected.<br />Empty lines will be ignored.</small>';
		}
		echo '<div class="submit">
			<input class="button-primary" type="submit" name="randomize_Save" value="Save Changes" />
			</div>
			</form>
			
			<p>Return to <a href="'.$randomize_adminurl.'">Randomize summary page</a>.</p>';
	}
  echo '</div>';	
}

function randomize_save($data) {
	global $wpdb, $user_ID;
	$table_name = $wpdb->prefix . 'randomize';
	
	$randomize_id = intval($data['randomize_id']);
	check_admin_referer('randomize_edit'.$randomize_id);
	
	$sqldata = array();
	$category_new = trim($data['randomize_category_new']);
	$sqldata['category'] = ($category_new) ? $category_new : $data['randomize_category'];
	$sqldata['user_id'] = $user_ID;
	$sqldata['visible'] = $data['randomize_visible'];
	
	// check for "Bulk Insert"
	$do_bulkinsert = isset($data['randomize_bulkinsert']) ? $data['randomize_bulkinsert'] : 'no';
	if ($do_bulkinsert == 'yes') {
		// Split the data by carriage returns
		$lines = preg_split("/[\n|\r]/", trim(stripslashes($data['randomize_text'])));
		foreach ($lines as $key=>$value) {
			// Ignore empty lines
			if (!empty($value)) {
				// Set the datavalue and insert
				$sqldata['text'] = $value;
				$wpdb->insert($table_name, $sqldata);
			}
		}
	} else {
		// single record insert/update
		$sqldata['text'] = trim(stripslashes($data['randomize_text']));
		if($randomize_id)
			$wpdb->update($table_name, $sqldata, array('randomize_id'=>$randomize_id));
		else
			$wpdb->insert($table_name, $sqldata);
	}

}

function randomize_delete($id) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'randomize';
	$id = intval($id);
	$sql = "DELETE FROM $table_name WHERE randomize_id = $id";
	$wpdb->query($sql);
}


?>