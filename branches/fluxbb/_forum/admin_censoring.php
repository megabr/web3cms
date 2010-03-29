<?php
// Tell header.php to use the admin template
define('PUN_ADMIN_CONSOLE', 1);
require SHELL_PATH . 'include/common.php';
require SHELL_PATH . 'include/common_admin.php';
if (!$_user['is_admmod'])
    message($lang_common['No permission']);
// Add a censor word
if (isset($_POST['add_word'])) {
    confirm_referrer('admin_censoring.php');
    $search_for = trim($_POST['new_search_for']);
    $replace_with = trim($_POST['new_replace_with']);
    if ($search_for == '' || $replace_with == '')
        message('You must enter both a word to censor and text to replace it with.');
    $db->setQuery('INSERT INTO forum_censoring (search_for, replace_with) VALUES (\'' . $db->escape($search_for) . '\', \'' . $db->escape($replace_with) . '\')')->execute() or error('Unable to add censor word', __FILE__, __LINE__, $db->error());
   	Yii::app()->request->redirect(Yii::app()->createUrl('forum/admin_censoring'));
}
// Update a censor word
else if (isset($_POST['update'])) {
    confirm_referrer('admin_censoring.php');
    $id = intval(key($_POST['update']));
    $search_for = trim($_POST['search_for'][$id]);
    $replace_with = trim($_POST['replace_with'][$id]);
    if ($search_for == '' || $replace_with == '')
        message('You must enter both text to search for and text to replace with.');
    $db->setQuery('UPDATE forum_censoring SET search_for=\'' . $db->escape($search_for) . '\', replace_with=\'' . $db->escape($replace_with) . '\' WHERE id=' . $id)->execute() or error('Unable to update censor word', __FILE__, __LINE__, $db->error());
   	Yii::app()->request->redirect(Yii::app()->createUrl('forum/admin_censoring'));
}
// Remove a censor word
else if (isset($_POST['remove'])) {
    confirm_referrer('admin_censoring.php');
    $id = intval(key($_POST['remove']));
    $db->setQuery('DELETE FROM forum_censoring WHERE id=' . $id)->execute() or error('Unable to delete censor word', __FILE__, __LINE__, $db->error());
   	Yii::app()->request->redirect(Yii::app()->createUrl('forum/admin_censoring'));
}
$focus_element = array('censoring', 'new_search_for');
require SHELL_PATH . 'header.php';
generate_admin_menu('censoring');
?>
	<div class="blockform">
		<h2><span>Censoring</span></h2>
		<div class="box">
			<?php echo _CHtml::form(array('admin_censoring', 'action' => 'foo'), 'POST', array('id' => 'censoring'));?>
				<div class="inform">
					<fieldset>
						<legend>Add word</legend>
						<div class="infldset">
							<p>Enter a word that you want to censor and the replacement text for this word. Wildcards are accepted (i.e. *some* would match somewhere and lonesome). Censor words also affect usernames. New users will not be able to register with usernames containing any censored words. The search is case-insensitive. <strong>Censor words must be enabled in <?php echo _CHtml::link('Options', array('forum/admin_options#censoring'));?> for this to have any effect.</strong></p>
							<table cellspacing="0">
							<thead>
								<tr>
									<th class="tcl" scope="col">Censored&nbsp;word</th>
									<th class="tc2" scope="col">Replacement&nbsp;text</th>
									<th class="hidehead" scope="col">Action</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><input type="text" name="new_search_for" size="24" maxlength="60" tabindex="1" /></td>
									<td><input type="text" name="new_replace_with" size="24" maxlength="60" tabindex="2" /></td>
									<td><input type="submit" name="add_word" value=" Add " tabindex="3" /></td>
								</tr>
							</tbody>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend>Edit/remove words</legend>
						<div class="infldset">
<?php
$db->setQuery('SELECT id, search_for, replace_with FROM forum_censoring ORDER BY id') or error('Unable to fetch censor word list', __FILE__, __LINE__, $db->error());
if ($db->num_rows()) {?>
							<table cellspacing="0" >
							<thead>
								<tr>
									<th class="tcl" scope="col">Censored&nbsp;word</th>
									<th class="tc2" scope="col">Replacement&nbsp;text</th>
									<th class="hidehead" scope="col">Actions</th>
								</tr>
							</thead>
							<tbody>
<?php while ($cur_word = $db->fetch_assoc())
    echo "\t\t\t\t\t\t\t\t" . '<tr><td><input type="text" name="search_for[' . $cur_word['id'] . ']" value="' . _CHtml::encode($cur_word['search_for']) . '" size="24" maxlength="60" /></td><td><input type="text" name="replace_with[' . $cur_word['id'] . ']" value="' . _CHtml::encode($cur_word['replace_with']) . '" size="24" maxlength="60" /></td><td><input type="submit" name="update[' . $cur_word['id'] . ']" value="Update" />&nbsp;<input type="submit" name="remove[' . $cur_word['id'] . ']" value="Remove" /></td></tr>' . "\n";
    ?>
							</tbody>
							</table>
<?php }else
    echo "\t\t\t\t\t\t\t" . '<p>No censor words in list.</p>' . "\n";
?>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>
<?php
require SHELL_PATH . 'footer.php';