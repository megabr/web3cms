<?php
require SHELL_PATH . 'include/common.php';
if ($_user['g_read_board'] == '0')
    message($lang_common['No view']);
$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
if ($id < 1 && $pid < 1)
    message($lang_common['Bad request']);
// Load the viewtopic.php language file
require SHELL_PATH . 'lang/' . $_user['language'] . '/topic.php';
// If a post ID is specified we determine topic ID and page number so we can redirect to the correct message
if ($pid) {
    $db->setQuery('SELECT topic_id FROM forum_posts WHERE id=' . $pid) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
    if (!$db->num_rows())
        message($lang_common['Bad request']);
    $id = $db->result();
    // Determine on what page the post is located (depending on $_user['disp_posts'])
    $db->setQuery('SELECT id FROM forum_posts WHERE topic_id=' . $id . ' ORDER BY posted') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
    $num_posts = $db->num_rows();
    for ($i = 0; $i < $num_posts; ++$i) {
        $cur_id = $db->result($result, $i);
        if ($cur_id == $pid)
            break;
    }
    ++$i; // we started at 0
    $_GET['p'] = ceil($i / $_user['disp_posts']);
}
// If action=new, we redirect to the first new post (if any)
else if ($action == 'new' && !$_user['is_guest']) {
    // We need to check if this topic has been viewed recently by the user
    $tracked_topics = get_tracked_topics();
    $last_viewed = isset($tracked_topics['topics'][$id]) ? $tracked_topics['topics'][$id] : $_user['last_visit'];
    $db->setQuery('SELECT MIN(id) FROM forum_posts WHERE topic_id=' . $id . ' AND posted>' . $last_viewed) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
    $first_new_post_id = $db->result();
    if ($first_new_post_id)
        Yii::app()->request->redirect(Yii::app()->createUrl('forum/viewtopic', array('pid' => $first_new_post_id . '#p' . $first_new_post_id)));
    else // If there is no new post, we go to the last post
        Yii::app()->request->redirect(Yii::app()->createUrl('forum/viewtopic', array('id' => $id, 'action' => 'last')));
}
// If action=last, we redirect to the last post
else if ($action == 'last') {
    $db->setQuery('SELECT MAX(id) FROM forum_posts WHERE topic_id=' . $id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
    $last_post_id = $db->result();
    if ($last_post_id) {
        Yii::app()->request->redirect(Yii::app()->createUrl('forum/viewtopic', array('pid' => $last_post_id . '#p' . $last_post_id)));
    }
}
// Fetch some info about the topic
if (!$_user['is_guest'])
    $db->setQuery('SELECT t.subject, t.closed, t.num_replies, t.sticky, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies, s.user_id AS is_subscribed FROM forum_topics AS t INNER JOIN forum_forums AS f ON f.id=t.forum_id LEFT JOIN forum_subscriptions AS s ON (t.id=s.topic_id AND s.user_id=' . $_user['id'] . ') LEFT JOIN forum_forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id=' . $_user['g_id'] . ') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id=' . $id . ' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
else
    $db->setQuery('SELECT t.subject, t.closed, t.num_replies, t.sticky, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies, 0 FROM forum_topics AS t INNER JOIN forum_forums AS f ON f.id=t.forum_id LEFT JOIN forum_forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id=' . $_user['g_id'] . ') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id=' . $id . ' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows())
    message($lang_common['Bad request']);
$cur_topic = $db->fetch_assoc();
// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_topic['moderators'] != '') ? unserialize($cur_topic['moderators']) : array();
$is_admmod = ($_user['g_id'] == PUN_ADMIN || ($_user['g_moderator'] == '1' && array_key_exists($_user['username'], $mods_array))) ? true : false;
// Can we or can we not post replies?
if ($cur_topic['closed'] == '0') {
    if (($cur_topic['post_replies'] == '' && $_user['g_post_replies'] == '1') || $cur_topic['post_replies'] == '1' || $is_admmod)
        $post_link = _CHtml::link($lang_topic['Post reply'], array('forum/post', 'tid' => $id));
    else
        $post_link = '&nbsp;';
}else {
    $post_link = $lang_topic['Topic closed'];
    if ($is_admmod)
        $post_link .= ' / ' . _CHtml::link($lang_topic['Post reply'], array('forum/post', 'tid' => $id));
}
// Add/update this topic in our list of tracked topics
if (!$_user['is_guest']) {
    $tracked_topics = get_tracked_topics();
    $tracked_topics['topics'][$id] = time();
    set_tracked_topics($tracked_topics);
}
// Determine the post offset (based on $_GET['p'])
$num_pages = ceil(($cur_topic['num_replies'] + 1) / $_user['disp_posts']);
$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : intval($_GET['p']);
$start_from = $_user['disp_posts'] * ($p - 1);
// Generate paging links
$paging_links = $lang_common['Pages'] . ': ' . paginate($num_pages, $p, 'viewtopic.php?id=' . $id);
if ($_config['o_censoring'] == '1')
    $cur_topic['subject'] = censor_words($cur_topic['subject']);
$quickpost = false;
if ($_config['o_quickpost'] == '1' && !$_user['is_guest'] &&
    ($cur_topic['post_replies'] == '1' || ($cur_topic['post_replies'] == '' && $_user['g_post_replies'] == '1')) &&
        ($cur_topic['closed'] == '0' || $is_admmod)) {
    $required_fields = array('req_message' => $lang_common['Message']);
    $quickpost = true;
}
if (!$_user['is_guest'] && $_config['o_subscriptions'] == '1') {
    if ($cur_topic['is_subscribed'])
        // I apologize for the variable naming here. It's a mix of subscription and action I guess :-)
        $subscraction = '<p class="subscribelink clearb"><span>' . $lang_topic['Is subscribed'] . ' - </span>' . _CHtml::link($lang_topic['Unsubscribe'], array('forum/misc', 'unsubscribe' => $id)) . '</p>' . "\n";
    else
        $subscraction = '<p class="subscribelink clearb">' . _CHtml::link($lang_topic['Subscribe'], array('forum/misc', 'subscribe' => $id)) . '</p>' . "\n";
}else
    $subscraction = '';
$page_title = _CHtml::encode($this->PageTitle . ' / ' . $cur_topic['subject']);
define('PUN_ALLOW_INDEX', 1);
require SHELL_PATH . 'header.php';
?>
<div class="linkst">
	<div class="inbox">
		<ul class="crumbs">
			<li><?php echo _CHtml::link($lang_common['Index'], array('forum/'));?></li>
			<li>&raquo;&nbsp;
			<?php echo _CHtml::link(_CHtml::encode($cur_topic['forum_name']), array('forum/viewforum', 'id' => $cur_topic['forum_id']));?></li>
			<li>&raquo;&nbsp;<?php echo _CHtml::encode($cur_topic['subject']) ?></li>
		</ul>
		<p class="pagelink conl"><?php echo $paging_links ?></p>
		<p class="postlink conr"><?php echo $post_link ?></p>
		<div class="clearer"></div>
	</div>
</div><?php
require SHELL_PATH . 'include/parser.php';
$post_count = 0; // Keep track of post numbers// Retrieve the posts (and their respective poster/online status)
$db->setQuery('SELECT u.email, ud.title, ud.url, ud.location, ud.signature, ud.email_setting, ud.num_posts, u.createTime, p.id, p.poster AS username, p.poster_id, p.poster_ip, p.poster_email, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by, g.g_id, g.g_user_title, o.user_id AS is_online FROM forum_posts AS p INNER JOIN w3_user AS u ON u.id=p.poster_id INNER JOIN w3_user_details AS ud INNER JOIN forum_groups AS g ON g.g_id=ud.forumGroupId LEFT JOIN forum_online AS o ON (o.user_id=u.id AND o.user_id!=1 AND o.idle=0) WHERE p.topic_id=' . $id . ' ORDER BY p.id LIMIT ' . $start_from . ',' . $_user['disp_posts'], true) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
while ($cur_post = $db->fetch_assoc()) {
    $post_count++;
    $user_avatar = '';
    $user_info = array();
    $user_contacts = array();
    $post_actions = array();
    $is_online = '';
    $signature = '';
    // If the poster is a registered user
    if ($cur_post['poster_id'] > 1) {
        if ($_user['g_view_users'] == '1')
            $username = _CHtml::link(_CHtml::encode($cur_post['username']), array('forum/profile', 'id' => $cur_post['poster_id']));
        else
            $username = _CHtml::encode($cur_post['username']);
        $user_title = get_title($cur_post);
        if ($_config['o_censoring'] == '1')
            $user_title = censor_words($user_title);
        // Format the online indicator
        $is_online = ($cur_post['is_online'] == $cur_post['poster_id']) ? '<strong>' . $lang_topic['Online'] . '</strong>' : $lang_topic['Offline'];
        if ($_config['o_avatars'] == '1' && $_user['show_avatars'] != '0') {
            if (isset($user_avatar_cache[$cur_post['poster_id']]))
                $user_avatar = $user_avatar_cache[$cur_post['poster_id']];
            else
                $user_avatar = $user_avatar_cache[$cur_post['poster_id']] = generate_avatar_markup($cur_post['poster_id']);
        }
        // We only show location, register date, post count and the contact links if "Show user info" is enabled
        if ($_config['o_show_user_info'] == '1') {
            if ($cur_post['location'] != '') {
                if ($_config['o_censoring'] == '1')
                    $cur_post['location'] = censor_words($cur_post['location']);
                $user_info[] = '<dd>' . $lang_topic['From'] . ': ' . _CHtml::encode($cur_post['location']);
            }
            $user_info[] = '<dd>' . $lang_common['Registered'] . ': ' . MDate::format($cur_post['registered'], true);
            if ($_config['o_show_post_count'] == '1' || $_user['is_admmod'])
                $user_info[] = '<dd>' . $lang_common['Posts'] . ': ' . forum_number_format($cur_post['num_posts']);
            // Now let's deal with the contact links (Email and URL)
            if ((($cur_post['email_setting'] == '0' && !$_user['is_guest']) || $_user['is_admmod']) && $_user['g_send_email'] == '1')
                $user_contacts[] = _CHtml::link($lang_common['Email'], 'mailto:' . $cur_post['email']);
            else if ($cur_post['email_setting'] == '1' && !$_user['is_guest'] && $_user['g_send_email'] == '1')
                $user_contacts[] = _CHtml::link($lang_common['Email'], array('forum/misc', 'email' => $cur_post['poster_id']));
            if ($cur_post['url'] != '')
                $user_contacts[] = _CHtml::link($lang_topic['Website'], _CHtml::encode($cur_post['url']));
        }
        if ($_user['is_admmod']) {
            $user_info[] = '<dd>' . $lang_topic['IP'] . ': ' . _CHtml::link($cur_post['poster_ip'], array('forum/moderate', 'get_host' => $cur_post['id']));
        }
    }
    // If the poster is a guest (or a user that has been deleted)
    else {
        $username = _CHtml::encode($cur_post['username']);
        $user_title = get_title($cur_post);
        if ($_user['is_admmod'])
            $user_info[] = '<dd>' . $lang_topic['IP'] . ': ' . _CHtml::link($cur_post['poster_ip'], array('forum/moderate', 'get_host' => $cur_post['id']));
        if ($_config['o_show_user_info'] == '1' && $cur_post['poster_email'] != '' && !$_user['is_guest'] && $_user['g_send_email'] == '1')
            $user_contacts[] = _CHtml::link($lang_common['Email'], 'mailto:' . $cur_post['poster_email']);
    }
    // Generation post action array (quote, edit, delete etc.)
    if (!$is_admmod) {
        if (!$_user['is_guest'])
            $post_actions[] = '<li class="postreport">' . _CHtml::link($lang_topic['Report'], array('forum/misc', 'report' => $cur_post['id']));
        if ($cur_topic['closed'] == '0') {
            if ($cur_post['poster_id'] == $_user['id']) {
                if ((($start_from + $post_count) == 1 && $_user['g_delete_topics'] == '1') || (($start_from + $post_count) > 1 && $_user['g_delete_posts'] == '1'))
                    $post_actions[] = '<li class="postdelete">' . _CHtml::link($lang_topic['Delete'], array('forum/delete', 'id' => $cur_post['id']));
                if ($_user['g_edit_posts'] == '1')
                    $post_actions[] = '<li class="postedit">' . _CHtml::link($lang_topic['Edit'], array('forum/edit', 'id' => $cur_post['id']));
            }
            if (($cur_topic['post_replies'] == '' && $_user['g_post_replies'] == '1') || $cur_topic['post_replies'] == '1')
                $post_actions[] = '<li class="postquote">' . _CHtml::link($lang_topic['Quote'], array('forum/post', 'tid' => $id, 'qid' => $cur_post['id']));
        }
    }else
        $post_actions[] = '<li class="postreport">' . _CHtml::link($lang_topic['Report'], array('forum/misc', 'report' => $cur_post['id'])) . $lang_common['Link separator'] . '</li><li class="postdelete">' . _CHtml::link($lang_topic['Delete'], array('forum/delete', 'id' => $cur_post['id'])) . $lang_common['Link separator'] . '</li><li class="postedit">' . _CHtml::link($lang_topic['Edit'], array('forum/edit', 'id' => $cur_post['id'])) . $lang_common['Link separator'] . '</li><li class="postquote">' . _CHtml::link($lang_topic['Quote'], array('forum/post', 'tid' => $id, 'qid' => $cur_post['id']));
    // Perform the main parsing of the message (BBCode, smilies, censor words etc)
    $cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);
    // Do signature parsing/caching
    if ($_config['o_signatures'] == '1' && $cur_post['signature'] != '' && $_user['show_sig'] != '0') {
        if (isset($signature_cache[$cur_post['poster_id']]))
            $signature = $signature_cache[$cur_post['poster_id']];
        else {
            $signature = parse_signature($cur_post['signature']);
            $signature_cache[$cur_post['poster_id']] = $signature;
        }
    }
    ?>
<div id="p<?php echo $cur_post['id'] ?>" class="blockpost<?php echo ($post_count % 2 == 0) ? ' roweven' : ' rowodd' ?>
<?php if (($post_count + $start_from) == 1) echo ' firstpost'; ?>">
	<h2><span><span class="conr">#<?php echo ($start_from + $post_count) ?></span>
	<?php echo _CHtml::link(MDate::format($cur_post['posted']), array('forum/viewtopic', 'pid' => $cur_post['id'] . '#p' . $cur_post['id']));?></a></span></h2>
	<div class="box">
		<div class="inbox">
			<div class="postbody">
				<div class="postleft">
					<dl>
						<dt><strong><?php echo $username ?></strong></dt>
						<dd class="usertitle"><strong><?php echo $user_title ?></strong></dd>
<?php if ($user_avatar != '') echo "\t\t\t\t\t\t" . '<dd class="postavatar">' . $user_avatar . '</dd>'; ?>
<?php if (count($user_info)) echo "\t\t\t\t\t\t" . implode('</dd>' . "\n\t\t\t\t\t\t", $user_info) . '</dd>' . "\n"; ?>
<?php if (count($user_contacts)) echo "\t\t\t\t\t\t" . '<dd class="usercontacts">' . implode('&nbsp;&nbsp;', $user_contacts) . '</dd>' . "\n"; ?>
					</dl>
				</div>
				<div class="postright">
					<h3><?php if (($post_count + $start_from) > 1) echo 'Re: '; ?><?php echo _CHtml::encode($cur_topic['subject']) ?></h3>
					<div class="postmsg">
						<?php echo $cur_post['message'] . "\n" ?>
<?php if ($cur_post['edited'] != '') echo "\t\t\t\t\t\t" . '<p class="postedit"><em>' . $lang_topic['Last edit'] . ' ' . _CHtml::encode($cur_post['edited_by']) . ' (' . MDate::format($cur_post['edited']) . ')</em></p>' . "\n"; ?>
					</div>
<?php if ($signature != '') echo "\t\t\t\t\t" . '<div class="postsignature postmsg"><hr />' . $signature . '</div>' . "\n"; ?>
				</div>
			</div>
		</div>
		<div class="inbox">
			<div class="postfoot clearb">
				<div class="postfootleft"><?php if ($cur_post['poster_id'] > 1) echo '<p>' . $is_online . '</p>'; ?></div>
				<div class="postfootright"><?php echo (count($post_actions)) ? '<ul>' . implode($lang_common['Link separator'] . '</li>', $post_actions) . '</li></ul></div>' . "\n" : '<div>&nbsp;</div></div>' . "\n" ?>
			</div>
		</div>
	</div>
</div><?php } ?>
<div class="postlinksb">
	<div class="inbox">
		<p class="postlink conr"><?php echo $post_link ?></p>
		<p class="pagelink conl"><?php echo $paging_links ?></p>
		<ul class="crumbs">
			<li><?php echo _CHtml::link($lang_common['Index'], array('forum/'));?></li>
			<li>&raquo;&nbsp;
			<?php echo _CHtml::link(_CHtml::encode($cur_topic['forum_name']), array('forum/viewforum', 'id' => $cur_topic['forum_id']));?></li>
			<li>&raquo;&nbsp;<?php echo _CHtml::encode($cur_topic['subject']) ?></li>
		</ul>
		<?php echo $subscraction ?>
		<div class="clearer"></div>
	</div>
</div><?php
// Display quick post if enabled
if ($quickpost) {?>
<div id="quickpost" class="blockform">
	<h2><span><?php echo $lang_topic['Quick post'] ?></span></h2>
	<div class="box">
		<?php echo _CHtml::form(array('post', 'tid' => $id), 'POST', array('onsubmit' => 'this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}'));?>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_common['Write message legend'] ?></legend>
					<div class="infldset txtarea">
						<input type="hidden" name="form_sent" value="1" />
						<input type="hidden" name="form_user" value="<?php echo _CHtml::encode($_user['username']) ?>" />
<?php if ($_config['o_subscriptions'] == '1' && ($_user['auto_notify'] == '1' || $cur_topic['is_subscribed'])): ?>						<input type="hidden" name="subscribe" value="1" />
<?php endif; ?>						<label><textarea name="req_message" rows="7" cols="75" tabindex="1"></textarea></label>
						<ul class="bblinks">
							<li><?php echo _CHtml::link($lang_common['BBCode'], array('forum/help#bbcode'), array('onclick' => 'window.open(this.href); return false;'));?>: <?php echo ($_config['p_message_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
							<li><?php echo _CHtml::link($lang_common['img tag'], array('forum/help#img'), array('onclick' => 'window.open(this.href); return false;'));?>: <?php echo ($_config['p_message_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
							<li><?php echo _CHtml::link($lang_common['Smilies'], array('forum/help#smilies'), array('onclick' => 'window.open(this.href); return false;'));?>: <?php echo ($_config['o_smilies'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
						</ul>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="submit" tabindex="2" value="<?php echo $lang_common['Submit'] ?>" accesskey="s" /></p>
		</form>
	</div>
</div>
<?php }
// Increment "num_views" for topic
if ($_config['o_topic_views'] == '1')
    $db->setQuery('UPDATE forum_topics SET num_views=num_views+1 WHERE id=' . $id)->execute() or error('Unable to update topic', __FILE__, __LINE__, $db->error());
$forum_id = $cur_topic['forum_id'];
$footer_style = 'viewtopic';
require SHELL_PATH . 'footer.php';