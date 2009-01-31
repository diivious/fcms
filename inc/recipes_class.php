<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('language.php');

class Recipes {

	var $db;
	var $db2;
	var $tz_offset;
	var $cur_user_id;

	function Recipes ($current_user_id, $type, $host, $database, $user, $pass) {
		$this->cur_user_id = $current_user_id;
		$this->db = new database($type, $host, $database, $user, $pass);
		$this->db2 = new database($type, $host, $database, $user, $pass);
		$this->db->query("SELECT `timezone` FROM `fcms_users` WHERE `id` = $current_user_id") or die('<h1>Timezone Error (recipes_class.php 17)</h1>' . mysql_error());
		$row = $this->db->get_row();
		$this->tz_offset = $row['timezone'];
	}

	function showRecipes () {
		global $LANG;
		echo "\t\t\t<h2>".$LANG['recipe_cats']."</h2>\n\t\t\t<div class=\"cat_row clearfix\"><div class=\"cat\"><a href=\"?category=1\">".$LANG['appetizer']."</a><br/><small>".$this->getRecipeCountInCategory($LANG['appetizer'])."</small></div><div class=\"cat\"><a href=\"?category=2\">".$LANG['breakfast']."</a><br/><small>".$this->getRecipeCountInCategory($LANG['breakfast'])."</small></div><div class=\"cat\"><a href=\"?category=3\">".$LANG['dessert']."</a><br/><small>".$this->getRecipeCountInCategory($LANG['dessert'])."</small></div></div>\n\t\t\t";
		echo "<div class=\"cat_row clearfix\"><div class=\"cat\"><a href=\"?category=4\">".$LANG['entree_meat']."</a><br/><small>".$this->getRecipeCountInCategory($LANG['entree_meat'])."</small></div><div class=\"cat\"><a href=\"?category=5\">".$LANG['entree_seafood']."</a><br/><small>".$this->getRecipeCountInCategory($LANG['entree_seafood'])."</small></div><div class=\"cat\"><a href=\"?category=6\">".$LANG['entree_veg']."</a><br/><small>".$this->getRecipeCountInCategory($LANG['entree_veg'])."</small></div></div>\n\t\t\t";
		echo "<div class=\"cat_row clearfix\"><div class=\"cat\"><a href=\"?category=7\">".$LANG['salad']."</a><br/><small>".$this->getRecipeCountInCategory($LANG['salad'])."</small></div><div class=\"cat\"><a href=\"?category=8\">".$LANG['side_dish']."</a><br/><small>".$this->getRecipeCountInCategory($LANG['side_dish'])."</small></div><div class=\"cat\"><a href=\"?category=9\">".$LANG['soup']."</a><br/><small>".$this->getRecipeCountInCategory($LANG['soup'])."</small></div></div>\n\t\t\t";
		$this->db->query("SELECT * FROM `fcms_recipes` ORDER BY `date` DESC LIMIT 1") or die("<h1>Get Last Recipe Error (recies_class.php 24)</h1>" . mysql_error());
		if ($this->db->count_rows() > 0) {
			echo "<p>&nbsp;</p><h2>".$LANG['latest_recipe']."</h2><br/>";
			while($r = $this->db->get_row()) {
				$displayname = getUserDisplayName($r['user']);
				$date = fixDST(gmdate('n/j/Y g:i a', strtotime($r['date'] . $this->tz_offset)), $_SESSION['login_id'], 'F j, Y, g:i a');
				echo "\t\t\t<h4>".$r['name']."</h4><small><i>Submitted by <a href=\"profile.php?member=" . $r['user'] . "\">$displayname</a> on $date</i></small><p>";
				parse($r['recipe']);
				echo "</p><p>&nbsp;</p>\n\t\t\t";
			}
		} else {
			echo "<div class=\"info-alert\"><h2>".$LANG['info_recipes1']."</h2><p><i>".$LANG['info_recipes2']."</i></p><p><b>".$LANG['info_recipes3']."</b><br/>".$LANG['info_recipes4']." <a href=\"?addrecipe=yes\">".$LANG['add_recipe']."</a> ".$LANG['info_recipes5']."</p></div>\n";
		}
	}

	function showRecipeInCategory ($cat, $page = '1', $id = '0') {
		global $LANG;
		$from = (($page * 5) - 5);
		if ($id > 0) { echo "<h2><a href=\"recipes.php?category=$cat\">"; } else { echo "<h2>"; }
		switch ($cat) {
			case 1: echo $LANG['appetizer']; $cat_name = $LANG['appetizer']; break;
			case 2: echo $LANG['breakfast']; $cat_name = $LANG['breakfast']; break;
			case 3: echo $LANG['dessert']; $cat_name = $LANG['dessert']; break;
			case 4: echo $LANG['entree_meat']; $cat_name = $LANG['entree_meat']; break;
			case 5: echo $LANG['entree_seafood']; $cat_name = $LANG['entree_seafood']; break;
			case 6: echo $LANG['entree_veg']; $cat_name = $LANG['entree_veg']; break;
			case 7: echo $LANG['salad']; $cat_name = $LANG['salad']; break;
			case 8: echo $LANG['side_dish']; $cat_name = $LANG['side_dish']; break;
			case 9: echo $LANG['soup']; $cat_name = $LANG['soup']; break;
			default: echo "<p class=\"error-alert\">".$LANG['err_no_cat']."</p>"; break;
		}
		if ($id > 0) { echo "</a></h2><br/>"; } else { echo "</h2><br/>"; }
		$sql = "SELECT * FROM `fcms_recipes` WHERE `category` LIKE '$cat_name' ";
		if ($id > 0) { $sql .= "AND `id` = $id"; } else { $sql .= "ORDER BY `date` DESC LIMIT ".$from.", 5"; }
		$this->db->query($sql) or die("<h1>Get Category Recipes Error (recies_class.php 92)</h1>" . mysql_error());
		if ($this->db->count_rows() > 0) {
			while($r = $this->db->get_row()) {
				$displayname = getUserDisplayName($r['user']);
				$date = fixDST(gmdate('n/j/Y g:i a', strtotime($r['date'] . $this->tz_offset)), $_SESSION['login_id'], 'F j, Y, g:i a');
				echo "\t\t\t<h4>";
				if ($id > 0) { echo $r['name']; } else { echo "<a href=\"recipes.php?category=$cat&amp;id=".$r['id']."\">".$r['name']."</a>"; }
				if ($this->cur_user_id == $r['user'] || checkAccess($this->cur_user_id) < 2) {
					echo " &nbsp;<form class=\"frm_line\" method=\"post\" action=\"recipes.php\"><div><input type=\"hidden\" name=\"id\" value=\"".$r['id']."\"/><input type=\"hidden\" name=\"name\" value=\"".htmlentities($r['name'], ENT_COMPAT, 'UTF-8')."\"/><input type=\"hidden\" name=\"category\" value=\"".htmlentities($r['category'], ENT_COMPAT, 'UTF-8')."\"/><input type=\"hidden\" name=\"post\" value=\"".htmlentities($r['recipe'], ENT_COMPAT, 'UTF-8')."\"/><input type=\"submit\" name=\"editrecipe\" value=\" \" class=\"editbtn\" title=\"".$LANG['edit_recipe']."\"/></div></form>";
				}
				if (checkAccess($_SESSION['login_id']) < 2) {
					echo " &nbsp;<form class=\"frm_line\" method=\"post\" action=\"recipes.php\"><div><input type=\"hidden\" name=\"id\" value=\"".$r['id']."\"/><input type=\"submit\" name=\"delrecipe\" value=\" \" class=\"delbtn\" title=\"".$LANG['del_recipe']."\" onclick=\"javascript:return confirm('".$LANG['js_del_recipe']."');\"/></div></form>";
				}
				echo "</h4><small><i>Submitted by <a href=\"profile.php?member=" . $r['user'] . "\">$displayname</a> on $date</i></small><p>";
				parse($r['recipe']);
				echo "</p><p>&nbsp;</p>\n\t\t\t";
			}
			if ($id <= 0) {
				$this->db2->query("SELECT count(`id`) AS c FROM `fcms_recipes` WHERE `category` LIKE '$cat_name'")  or die('<h1>Count Error (recipes_class.php 74)</h1>' . mysql_error());
				while ($r = $this->db2->get_row()) { $recipecount = $r['c']; }
				$total_pages = ceil($recipecount / 5); 
				if ($total_pages > 1) {
					echo "<div class=\"pages clearfix\"><ul>"; 
					if ($page > 1) { 
						$prev = ($page - 1); 
						echo "<li><a title=\"".$LANG['title_first_page']."\" class=\"first\" href=\"recipes.php?category=$cat&amp;page=1\"></a></li>"; 
						echo "<li><a title=\"".$LANG['title_prev_page']."\" class=\"previous\" href=\"recipes.php?category=$cat&amp;page=$prev\"></a></li>"; 
					} 
					if ($total_pages > 8) {
						if($page > 2) {
							for($i = ($page-2); $i <= ($page+5); $i++) {
								if($i <= $total_pages) { echo "<li><a href=\"recipes.php?category=$cat&amp;page=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>"; }
							} 
						} else {
							for($i = 1; $i <= 8; $i++) { echo "<li><a href=\"recipes.php?category=$cat&amp;page=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>"; } 
						}
					} else {
						for($i = 1; $i <= $total_pages; $i++) {
							echo "<li><a href=\"recipes.php?category=$cat&amp;page=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>";
						} 
					}
					if ($page < $total_pages) { 
						$next = ($page + 1); 
						echo "<li><a title=\"".$LANG['title_next_page']."\" class=\"next\" href=\"recipes.php?category=$cat&amp;page=$next\"></a></li>"; 
						echo "<li><a title=\"".$LANG['title_last_page']."\" class=\"last\" href=\"recipes.php?category=$cat&amp;page=$total_pages\"></a></li>"; 
					} 
					echo "</ul></div>";
				}
			}
		} else {
			echo "<div class=\"info-alert\"><h2>".$LANG['err_no_recipes1']."</h2><p><i>".$LANG['err_no_recipes2']."</i></p><h3>".$LANG['err_no_recipes3']."</h3><p>".$LANG['err_no_recipes4']." <a href=\"?addrecipe=yes\">".$LANG['add_recipe']."</a> ".$LANG['err_no_recipes5']." <a href=\"recipes.php\">".$LANG['err_no_recipes6']."</a>.</p></div>\n";
		}
	}

	/**
	 * getRecipeCountInCategory
	 * 
	 * Returns the recipe count for the desired category
	 */
	function getRecipeCountInCategory ($cat) {
		global $LANG;
		$this->db->query("SELECT count(*) FROM `fcms_recipes` WHERE `category` = '$cat'") or die("<h1>Get Recipe Count Error (recies_class.php 121)</h1>" . mysql_error());
		if ($this->db->count_rows() > 0) {
			$r = $this->db->get_row();
			return $r[0] . " " . $LANG['link_recipes'];
		} else {
			return "0 " . $LANG['link_recipes'];
		}
	}

	function displayForm ($type, $id = '0', $name = 'error', $category = 'error', $recipe = 'error') {
		global $LANG;
		echo "<script type=\"text/javascript\" src=\"inc/livevalidation.js\"></script>\n\t\t\t<script type=\"text/javascript\" src=\"inc/messageboard.inc.js\"></script>\n\t\t\t";
		if ($type == 'edit') {
			echo "<form method=\"post\" name=\"editform\" action=\"recipes.php\">\n\t\t\t\t<br/><h3>".$LANG['edit_recipe']."</h3>\n\t\t\t\t";			
		} else {
			echo "<form method=\"post\" name=\"addform\" action=\"recipes.php\">\n\t\t\t\t<h3>".$LANG['add_recipe']."</h3>\n\t\t\t\t";
		}
		echo "\t\t\t\t<div><label for=\"name\">".$LANG['recipe_name']."</label>: <input type=\"text\" name=\"name\" id=\"name\" class=\"required\"";
		if ($type == 'edit') {
			echo " value=\"$name\"";
		}
		echo " size=\"50\"/></div><br/>\n\t\t\t\t<script type=\"text/javascript\">\n\t\t\t\t\tvar fname = new LiveValidation('name', { validMessage: \"".$LANG['lv_thanks']."\", wait: 500});\n\t\t\t\t\tfname.add(Validate.Presence, {failureMessage: \"".$LANG['lv_sorry_req']."\"});\n\t\t\t\t</script>\n\t\t\t\t";
		echo "<div><label for=\"category\">".$LANG['recipe_cat']."</label>: <select name=\"category\">";
		echo "<option value=\"".$LANG['appetizer']."\""; if($category == $LANG['appetizer']) { echo " selected=\"selected\""; } echo ">".$LANG['appetizer']."</option>";
		echo "<option value=\"".$LANG['breakfast']."\"\""; if($category == $LANG['breakfast']) { echo " selected=\"selected\""; } echo ">".$LANG['breakfast']."</option>";
		echo "<option value=\"".$LANG['dessert']."\""; if($category == $LANG['dessert']) { echo " selected=\"selected\""; } echo ">".$LANG['dessert']."</option>";
		echo "<option value=\"".$LANG['entree_meat']."\""; if($category == $LANG['entree_meat']) { echo " selected=\"selected\""; } echo ">".$LANG['entree_meat']."</option>";
		echo "<option value=\"".$LANG['entree_seafood']."\""; if($category == $LANG['entree_seafood']) { echo " selected=\"selected\""; } echo ">".$LANG['entree_seafood']."</option>";
		echo "<option value=\"".$LANG['entree_veg']."\""; if($category == $LANG['entree_veg']) { echo " selected=\"selected\""; } echo ">".$LANG['entree_veg']."</option>";
		echo "<option value=\"".$LANG['salad']."\""; if($category == $LANG['salad']) { echo " selected=\"selected\""; } echo ">".$LANG['salad']."</option>";
		echo "<option value=\"".$LANG['side_dish']."\""; if($category == $LANG['side_dish']) { echo " selected=\"selected\""; } echo ">".$LANG['side_dish']."</option>";
		echo "<option value=\"".$LANG['soup']."\""; if($category == $LANG['soup']) { echo " selected=\"selected\""; } echo ">".$LANG['soup']."</option>";
		echo "</select> &nbsp;<a href=\"#\" onclick=\"window.open('inc/upimages.php','name','width=700,height=500,scrollbars=yes,resizable=no,location=no,menubar=no,status=no'); return false;\">(".$LANG['upload_image'].")</a></div><br/>\n\t\t\t\t";
		echo "<script type=\"text/javascript\">var bb = new BBCode();</script>\n";
		displayMBToolbar();
		echo "<div><textarea name=\"post\" id=\"post\" class=\"required\" rows=\"10\" cols=\"63\">";
		if ($type == 'edit') { echo $recipe; }
		echo "</textarea></div><script type=\"text/javascript\">bb.init('post');</script><br/>\n\t\t\t\t";
		echo "<script type=\"text/javascript\">\n\t\t\t\t\tvar frecipe = new LiveValidation('post', { validMessage: \"".$LANG['lv_thanks']."\", wait: 500});\n\t\t\t\t\tfrecipe.add(Validate.Presence, {failureMessage: \"".$LANG['lv_sorry_req']."\"});\n\t\t\t\t</script>\n\t\t\t\t";
		if ($type == 'add') {
			echo "<div><input type=\"submit\" name=\"submitadd\" value=\"".$LANG['submit']."\"/></div>";
		} else {
			echo "<div><input type=\"hidden\" name=\"id\" value=\"$id\"/><input type=\"submit\" name=\"submitedit\" value=\"".$LANG['edit']."\"/></div>";
		}
		echo "</form>\n\t\t\t<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>\n";
	}

	function displayWhatsNewRecipes () {
		global $LANG;
		$today = date('Y-m-d');
		$tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
		$this->db->query("SELECT * FROM `fcms_recipes` WHERE `date` >= DATE_SUB(CURDATE() , INTERVAL 30 DAY) ORDER BY `date` DESC LIMIT 0 , 5");
		if ($this->db->count_rows() > 0) {
			echo "\n\t\t\t\t<h3>".$LANG['link_recipes']."</h3>\n\t\t\t\t<ul>\n";
			while ($r = $this->db->get_row()) {
				$name = $r['name'];
				$monthName = gmdate('M', strtotime($r['date'] . $this->tz_offset));
				$date = fixDST(gmdate('n/j/Y g:i a', strtotime($r['date'] . $this->tz_offset)), $this->cur_user_id, '. j, Y, g:i a');
				switch ($r['category']) {
					case $LANG['appetizer']: $url = "recipes.php?category=1&amp;id=".$r['id']; break;
					case $LANG['breakfast']: $url = "recipes.php?category=2&amp;id=".$r['id']; break;
					case $LANG['dessert']: $url = "recipes.php?category=3&amp;id=".$r['id']; break;
					case $LANG['entree_meat']: $url = "recipes.php?category=4&amp;id=".$r['id']; break;
					case $LANG['entree_seafood']: $url = "recipes.php?category=5&amp;id=".$r['id']; break;
					case $LANG['entree_veg']: $url = "recipes.php?category=6&amp;id=".$r['id']; break;
					case $LANG['salad']: $url = "recipes.php?category=7&amp;id=".$r['id']; break;
					case $LANG['side_dish']: $url = "recipes.php?category=8&amp;id=".$r['id']; break;
					case $LANG['soup']: $url = "recipes.php?category=9&amp;id=".$r['id']; break;
					default: $url = "recipes.php"; break;
				}
				echo "\t\t\t\t\t<li";
				if(strtotime($r['date']) >= strtotime($today) && strtotime($r['date']) > $tomorrow) { echo " class=\"new\""; }
				echo "><a href=\"$url\">$name</a> - <span>".$LANG[$monthName]."$date</span></li>\n";			
			}
			echo "\t\t\t\t</ul>\n";
		}
	}

} ?>