<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('conference_center_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get posted data
	if (is_array($_POST['conference_centers'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$conference_centers = $_POST['conference_centers'];
	}

/*
//copy the conference centers
	if (permission_exists('conference_center_add')) {
		if ($action == 'copy' && is_array($conference_centers) && @sizeof($conference_centers) != 0) {
			//copy
				$obj = new conference_centers;
				$obj->copy_conference_centers($conference_centers);
			//redirect
				header('Location: conference_centers.php'.($search != '' ? '?search='.urlencode($search) : null));
				exit;
		}
	}

//toggle the conference centers
	if (permission_exists('conference_center_edit')) {
		if ($action == 'toggle' && is_array($conference_centers) && @sizeof($conference_centers) != 0) {
			//toggle
				$obj = new conference_centers;
				$obj->toggle_conference_centers($conference_centers);
			//redirect
				header('Location: conference_centers.php'.($search != '' ? '?search='.urlencode($search) : null));
				exit;
		}
	}

//delete the conference centers
	if (permission_exists('conference_center_delete')) {
		if ($action == 'delete' && is_array($conference_centers) && @sizeof($conference_centers) != 0) {
			//delete
				$obj = new conference_centers;
				$obj->delete_conference_centers($conference_centers);
			//redirect
				header('Location: conference_centers.php'.($search != '' ? '?search='.urlencode($search) : null));
				exit;
		}
	}
*/

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = "and ( ";
		$sql_search .= "lower(conference_center_name) like :search ";
		$sql_search .= "or lower(conference_center_extension) like :search ";
		$sql_search .= "or lower(conference_center_greeting) like :search ";
		$sql_search .= "or lower(conference_center_description) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//prepare to page the results
	$sql = "select count(*) from v_conference_centers ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= $sql_search;
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(*)', '*', $sql);
	$sql .= order_by($order_by, $order);
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$conference_centers = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-conference_centers']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('conference_center_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'link'=>'conference_center_edit.php']);
	}
	/*
	if (permission_exists('conference_center_add') && $conference_centers) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'onclick'=>"if (confirm('".$text['confirm-copy']."')) { list_action_set('copy'); list_form_submit('form_list'); } else { this.blur(); return false; }"]);
	}
	if (permission_exists('conference_center_edit') && $conference_centers) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'onclick'=>"if (confirm('".$text['confirm-toggle']."')) { list_action_set('toggle'); list_form_submit('form_list'); } else { this.blur(); return false; }"]);
	}
	if (permission_exists('conference_center_delete') && $conference_centers) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'onclick'=>"if (confirm('".$text['confirm-delete']."')) { list_action_set('delete'); list_form_submit('form_list'); } else { this.blur(); return false; }"]);
	}
	*/
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'conference_centers.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo "	<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo 		"<span style='margin-left: 15px;'>";
	if (permission_exists('conference_active_advanced_view')) {
		echo button::create(['type'=>'button','label'=>$text['button-view'],'icon'=>'bolt','link'=>PROJECT_PATH.'/app/conferences_active/conferences_active.php']);
	}
	echo button::create(['type'=>'button','label'=>$text['button-rooms'],'icon'=>'door-open','link'=>'conference_rooms.php']);
	echo 		"</span>\n";
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['title_description-conference_centers']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	/*
	if (permission_exists('conference_center_add') || permission_exists('conference_center_edit') || permission_exists('conference_center_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($conference_centers ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	*/
	echo th_order_by('conference_center_name', $text['label-conference_center_name'], $order_by, $order);
	echo th_order_by('conference_center_extension', $text['label-conference_center_extension'], $order_by, $order);
	echo th_order_by('conference_center_greeting', $text['label-conference_center_greeting'], $order_by, $order);
	echo th_order_by('conference_center_pin_length', $text['label-conference_center_pin_length'], $order_by, $order);
	echo th_order_by('conference_center_enabled', $text['label-conference_center_enabled'], $order_by, $order);
	echo th_order_by('conference_center_description', $text['label-conference_center_description'], $order_by, $order, null, "class='hide-sm-dn'");
	if (permission_exists('conference_center_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($conference_centers) && @sizeof($conference_centers) != 0) {
		$x = 0;
		foreach($conference_centers as $row) {
			if (permission_exists('conference_center_edit')) {
				$list_row_url = "conference_center_edit.php?id=".$row['conference_center_uuid'];
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			/*
			if (permission_exists('conference_center_add') || permission_exists('conference_center_edit') || permission_exists('conference_center_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='conference_centers[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='conference_centers[$x][uuid]' value='".escape($row['conference_center_uuid'])."' />\n";
				echo "	</td>\n";
			}
			*/
			echo "	<td><a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['conference_center_name'])."</a>&nbsp;</td>\n";
			echo "	<td>".escape($row['conference_center_extension'])."&nbsp;</td>\n";
			echo "	<td>".escape($row['conference_center_greeting'])."&nbsp;</td>\n";
			echo "	<td>".escape($row['conference_center_pin_length'])."&nbsp;</td>\n";
			echo "	<td>".$text['label-'.$row['conference_center_enabled']]."&nbsp;</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['conference_center_description'])."&nbsp;</td>\n";
			if (permission_exists('conference_center_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($conference_centers);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>