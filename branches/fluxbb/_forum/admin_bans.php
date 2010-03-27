<?php
// Tell header.php to use the admin template
define('PUN_ADMIN_CONSOLE', 1);
require SHELL_PATH . 'include/common.php';
require SHELL_PATH . 'include/common_admin.php';
if ($_user['g_id'] != PUN_ADMIN && ($_user['g_moderator'] != '1' || $_user['g_mod_ban_users'] == '0'))
    message($lang_common['No permission']);
// Add/edit a ban (stage 1)
if (isset($_REQUEST['add_ban']) || isset($_GET['edit_ban'])) {
    if (isset($_GET['add_ban']) || isset($_POST['add_ban'])) {
        // If the ID of the user to ban was provided through GET (a link from profile.php)
        if (isset($_GET['add_ban'])) {
            $add_ban = intval($_GET['add_ban']);
            if ($add_ban < 2)
                message($lang_common['Bad request']);
            $user_id = $add_ban;
            $db->setQuery('SELECT ud.forumGroupId, username, email FROM w3_user INNER JOIN w3_user_details AS ud WHERE id=' . $user_id) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
            if ($db->num_rows())
                list($forumGroupId, $ban_user, $ban_email) = $db->fetch_row();
            else
                message('No user by that ID registered.');
        } else { // Otherwise the username is in POST
            $ban_user = trim($_POST['new_ban_user']);
            if ($ban_user != '') {
                $db->setQuery('SELECT id, ud.forumGroupId, username, email FROM w3_user INNER JOIN w3_user_details AS ud WHERE username=\'' . $db->escape($ban_user) . '\' AND id>1') or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
                if ($db->num_rows())
                    list($user_id, $forumGroupId, $ban_user, $ban_email) = $db->fetch_row();
                else
                    message('No user by that username registered. If you want to add a ban not tied to a specific username just leave the username blank.');
            }
        }
        // Make sure we're not banning an admin
        if (isset($forumGroupId) && $forumGroupId == PUN_ADMIN)
            message('The user ' . _CHtml::encode($ban_user) . ' is an administrator and can\'t be banned. If you want to ban an administrator, you must first demote him/her to moderator or user.');
        // If we have a $user_id, we can try to find the last known IP of that user
        if (isset($user_id)) {
            $db->setQuery('SELECT poster_ip FROM forum_posts WHERE poster_id=' . $user_id . ' ORDER BY posted DESC LIMIT 1') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
            $ban_ip = ($db->num_rows()) ? $db->result() : '';
        }
        $mode = 'add';
    } else { // We are editing a ban
        $ban_id = intval($_GET['edit_ban']);
        if ($ban_id < 1)
            message($lang_common['Bad request']);
        $db->setQuery('SELECT username, ip, email, message, expire FROM forum_bans WHERE id=' . $ban_id) or error('Unable to fetch ban info', __FILE__, __LINE__, $db->error());
        if ($db->num_rows())
            list($ban_user, $ban_ip, $ban_email, $ban_message, $ban_expire) = $db->fetch_row();
        else
            message($lang_common['Bad request']);
        $ban_expire = ($ban_expire != '') ? date('Y-m-d', $ban_expire) : '';
        $mode = 'edit';
    }
    $focus_element = array('bans2', 'ban_user');
    require SHELL_PATH . 'header.php';
    generate_admin_menu('bans');

    ?>
	<div class="blockform">
		<h2><span>Ban advanced settings</span></h2>
		<div class="box">
			<?php echo _CHtml::form('admin_bans', 'POST', array('id' => 'bans2'));?>
				<div class="inform">
				<input type="hidden" name="mode" value="<?php echo $mode ?>" />
<?php if ($mode == 'edit'): ?>				<input type="hidden" name="ban_id" value="<?php echo $ban_id ?>" />
<?php endif; ?>				<fieldset>
						<legend>Supplement ban with IP and email</legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">Username</th>
									<td>
										<input type="text" name="ban_user" size="25" maxlength="25" value="<?php if (isset($ban_user)) echo _CHtml::encode($ban_user); ?>" tabindex="1" />
										<span>The username to ban.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">IP-addresses</th>
									<td>
										<input type="text" name="ban_ip" size="45" maxlength="255" value="<?php if (isset($ban_ip)) echo $ban_ip; ?>" tabindex="2" />
										<span>The IP or IP-ranges you wish to ban (e.g. 150.11.110.1 or 150.11.110). Separate addresses with spaces. If an IP is entered already it is the last known IP of this user in the database.<?php if ($ban_user != '' && isset($user_id)) echo ' Click ' . _CHtml::link('here', array('forum/admin_users', 'ip_stats' => $user_id));?> to see IP statistics for this user.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Email/domain</th>
									<td>
										<input type="text" name="ban_email" size="40" maxlength="50" value="<?php if (isset($ban_email)) echo strtolower($ban_email); ?>" tabindex="3" />
										<span>The email or email domain you wish to ban (e.g. someone@somewhere.com or somewhere.com). See "Allow banned email addresses" in Permissions for more info.</span>
									</td>
								</tr>
							</table>
							<p class="topspace"><strong class="warntext">You should be very careful when banning an IP-range because of the possibility of multiple users matching the same partial IP.</strong></p>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend>Ban message and expiry</legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">Ban message</th>
									<td>
										<input type="text" name="ban_message" size="50" maxlength="255" value="<?php if (isset($ban_message)) echo _CHtml::encode($ban_message); ?>" tabindex="4" />
										<span>A message that will be displayed to the banned user when he/she visits the forums.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Expire date</th>
									<td>
										<input type="text" name="ban_expire" size="17" maxlength="10" value="<?php if (isset($ban_expire)) echo $ban_expire; ?>" tabindex="5" />
										<span>The date when this ban should be automatically removed (format: YYYY-MM-DD). Leave blank to remove manually.</span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="add_edit_ban" value=" Save " tabindex="6" /></p>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>
<?php require SHELL_PATH . 'footer.php';
}
// Add/edit a ban (stage 2)
else if (isset($_POST['add_edit_ban'])) {
    confirm_referrer('admin_bans.php');
    $ban_user = trim($_POST['ban_user']);
    $ban_ip = trim($_POST['ban_ip']);
    $ban_email = strtolower(trim($_POST['ban_email']));
    $ban_message = trim($_POST['ban_message']);
    $ban_expire = trim($_POST['ban_expire']);
    if ($ban_user == '' && $ban_ip == '' && $ban_email == '')
        message('You must enter either a username, an IP address or an email address (at least).');
    else if (strtolower($ban_user) == 'guest')
        message('The guest user cannot be banned.');
    // Validate IP/IP range (it's overkill, I know)
    if ($ban_ip != '') {
        $ban_ip = preg_replace('/[\s]{2,}/', ' ', $ban_ip);
        $addresses = explode(' ', $ban_ip);
        $addresses = array_map('_trim', $addresses);
        for ($i = 0; $i < count($addresses); ++$i) {
            if (strpos($addresses[$i], ':') !== false) {
                $octets = explode(':', $addresses[$i]);
                for ($c = 0; $c < count($octets); ++$c) {
                    $octets[$c] = ltrim($octets[$c], "0");
                    if ($c > 7 || (!empty($octets[$c]) && !ctype_xdigit($octets[$c])) || intval($octets[$c], 16) > 65535)
                        message('You entered an invalid IP/IP-range.');
                }
                $cur_address = implode(':', $octets);
                $addresses[$i] = $cur_address;
            } else {
                $octets = explode('.', $addresses[$i]);
                for ($c = 0; $c < count($octets); ++$c) {
                    $octets[$c] = (strlen($octets[$c]) > 1) ? ltrim($octets[$c], "0") : $octets[$c];
                    if ($c > 3 || preg_match('/[^0-9]/', $octets[$c]) || intval($octets[$c]) > 255)
                        message('You entered an invalid IP/IP-range.');
                }
                $cur_address = implode('.', $octets);
                $addresses[$i] = $cur_address;
            }
        }
        $ban_ip = implode(' ', $addresses);
    }
    require SHELL_PATH . 'include/email.php';
    if ($ban_email != '' && !is_valid_email($ban_email)) {
        if (!preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/', $ban_email))
            message('The email address (e.g. user@domain.com) or partial email address domain (e.g. domain.com) you entered is invalid.');
    }
    if ($ban_expire != '' && $ban_expire != 'Never') {
        $ban_expire = strtotime($ban_expire);
        if ($ban_expire == - 1 || $ban_expire <= time())
            message('You entered an invalid expire date. The format should be YYYY-MM-DD and the date must be at least one day in the future.');
    } else
        $ban_expire = 'NULL';
    $ban_user = ($ban_user != '') ? '\'' . $db->escape($ban_user) . '\'' : 'NULL';
    $ban_ip = ($ban_ip != '') ? '\'' . $db->escape($ban_ip) . '\'' : 'NULL';
    $ban_email = ($ban_email != '') ? '\'' . $db->escape($ban_email) . '\'' : 'NULL';
    $ban_message = ($ban_message != '') ? '\'' . $db->escape($ban_message) . '\'' : 'NULL';
    if ($_POST['mode'] == 'add')
        $db->setQuery('INSERT INTO forum_bans (username, ip, email, message, expire, ban_creator) VALUES(' . $ban_user . ', ' . $ban_ip . ', ' . $ban_email . ', ' . $ban_message . ', ' . $ban_expire . ', ' . $_user['id'] . ')')->execute() or error('Unable to add ban', __FILE__, __LINE__, $db->error());

    else
        $db->setQuery('UPDATE forum_bans SET username=' . $ban_user . ', ip=' . $ban_ip . ', email=' . $ban_email . ', message=' . $ban_message . ', expire=' . $ban_expire . ' WHERE id=' . intval($_POST['ban_id']))->execute() or error('Unable to update ban', __FILE__, __LINE__, $db->error());
    // Regenerate the bans cache
    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
        require SHELL_PATH . 'include/cache.php';
    generate_bans_cache();
    redirect('admin_bans.php', 'Ban ' . (($_POST['mode'] == 'edit') ? 'edited' : 'added') . '. Redirecting &hellip;');
}
// Remove a ban
else if (isset($_GET['del_ban'])) {
    confirm_referrer('admin_bans.php');
    $ban_id = intval($_GET['del_ban']);
    if ($ban_id < 1)
        message($lang_common['Bad request']);
    $db->setQuery('DELETE FROM forum_bans WHERE id=' . $ban_id)->execute() or error('Unable to delete ban', __FILE__, __LINE__, $db->error());
    // Regenerate the bans cache
    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
        require SHELL_PATH . 'include/cache.php';
    generate_bans_cache();
    redirect('admin_bans.php', 'Ban removed. Redirecting &hellip;');
}
$focus_element = array('bans', 'new_ban_user');
require SHELL_PATH . 'header.php';
generate_admin_menu('bans');

?>
	<div class="blockform">
		<h2><span>New ban</span></h2>
		<div class="box">
			<?php echo _CHtml::form(array('admin_bans', 'action' => 'more'), 'POST', array('id' => 'bans'));?>
				<div class="inform">
					<fieldset>
						<legend>Add ban</legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">Username<div><input type="submit" name="add_ban" value=" Add " tabindex="2" /></div></th>
									<td>
										<input type="text" name="new_ban_user" size="25" maxlength="25" tabindex="1" />
										<span>The username to ban (case-insensitive). The next page will let you enter a custom IP and email. If you just want to ban a specific IP/IP-range or email just leave it blank.</span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
			</form>
		</div>		<h2 class="block2"><span>Existing bans</span></h2>
		<div class="box">
			<div class="fakeform">
<?php $db->setQuery('SELECT b.id, b.username, b.ip, b.email, b.message, b.expire, b.ban_creator, u.username AS ban_creator_username FROM forum_bans AS b LEFT JOIN w3_user AS u ON b.ban_creator=u.id ORDER BY b.id') or error('Unable to fetch ban list', __FILE__, __LINE__, $db->error());
if ($db->num_rows()) {
    while ($cur_ban = $db->fetch_assoc()) {
        $expire = MDate::format($cur_ban['expire'], true);

        ?>
				<div class="inform">
					<fieldset>
						<legend>Ban expires: <?php echo $expire ?></legend>
						<div class="infldset">
							<table cellspacing="0">
<?php if ($cur_ban['username'] != ''): ?>								<tr>
									<th>Username</th>
									<td><?php echo _CHtml::encode($cur_ban['username']) ?></td>
								</tr>
<?php endif; ?><?php if ($cur_ban['email'] != ''): ?>								<tr>
									<th>Email</th>
									<td><?php echo $cur_ban['email'] ?></td>
								</tr>
<?php endif; ?><?php if ($cur_ban['ip'] != ''): ?>								<tr>
									<th>IP/IP-ranges</th>
									<td><?php echo $cur_ban['ip'] ?></td>
								</tr>
<?php endif; ?><?php if ($cur_ban['message'] != ''): ?>								<tr>
									<th>Reason</th>
									<td><?php echo _CHtml::encode($cur_ban['message']) ?></td>
								</tr>
<?php endif; ?><?php if (!empty($cur_ban['ban_creator_username'])): ?>								<tr>
									<th>Banned by</th>
									<td><?php echo _CHtml::link(_CHtml::encode($cur_ban['ban_creator_username']), array('forum/profile', 'id' => $cur_ban['ban_creator']));?></td>
								</tr>
<?php endif; ?>							</table>
							<p class="linkactions"><?php echo _CHtml::link('Edit', array('forum/admin_bans', 'edit_ban' => $cur_ban['id'])) . ' - ' . _CHtml::link('Remove', array('forum/admin_bans', 'del_ban' => $cur_ban['id']));?></p>
						</div>
					</fieldset>
				</div>
<?php }
} else
    echo "\t\t\t\t" . '<p>No bans in list.</p>' . "\n";

?>
			</div>
		</div>
	</div>
	<div class="clearer"></div>
</div>
<?php
require SHELL_PATH . 'footer.php';