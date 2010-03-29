<?php
require SHELL_PATH . 'include/common.php';
if ($_user['g_read_board'] == '0')
    message($lang_common['No view']);
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
    message($lang_common['Bad request']);
// Fetch some info about the post, the topic and the forum
$db->setQuery('SELECT f.id AS fid, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.id AS tid, t.subject, t.posted, t.first_post_id, t.closed, p.poster, p.poster_id, p.message, p.hide_smilies FROM forum_posts AS p INNER JOIN forum_topics AS t ON t.id=p.topic_id INNER JOIN forum_forums AS f ON f.id=t.forum_id LEFT JOIN forum_forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id=' . $_user['g_id'] . ') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.id=' . $id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows())
    message($lang_common['Bad request']);
$cur_post = $db->fetch_assoc();
// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
$is_admmod = ($_user['g_id'] == PUN_ADMIN || ($_user['g_moderator'] == '1' && array_key_exists($_user['username'], $mods_array))) ? true : false;
$is_topic_post = ($id == $cur_post['first_post_id']) ? true : false;
// Do we have permission to edit this post?
if (($_user['g_delete_posts'] == '0' ||
        ($_user['g_delete_topics'] == '0' && $is_topic_post) || $cur_post['poster_id'] != $_user['id'] || $cur_post['closed'] == '1') && !$is_admmod)
    message($lang_common['No permission']);
// Load the delete.php language file
require SHELL_PATH . 'lang/' . $_user['language'] . '/delete.php';
if (isset($_POST['delete'])) {
    if ($is_admmod)
        confirm_referrer('delete.php');
    require SHELL_PATH . 'include/search_idx.php';
    if ($is_topic_post) {
        // Delete the topic and all of it's posts
        delete_topic($cur_post['tid']);
        update_forum($cur_post['fid']);
       	Yii::app()->request->redirect(Yii::app()->createUrl('forum/viewforum', array('id' => $cur_post['fid'])));
    }else {
        // Delete just this one post
        delete_post($id, $cur_post['tid']);
        update_forum($cur_post['fid']);
       	Yii::app()->request->redirect(Yii::app()->createUrl('forum/viewtopic', array('id' => $cur_post['tid'])));
    }
}
require SHELL_PATH . 'header.php';
require SHELL_PATH . 'include/parser.php';
$cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);
?>
<div class="linkst">
	<div class="inbox">
		<ul class="crumbs">
			<li><?php echo _CHtml::link($lang_common['Index'], array('forum/'));?></li>
			<li>&raquo;&nbsp;<?php echo _CHtml::link(_CHtml::encode($cur_post['forum_name']), array('forum/viewforum', 'id' => $cur_post['fid']));?></a></li>
			<li>&raquo;&nbsp;<?php echo _CHtml::encode($cur_post['subject']) ?></li>
		</ul>
	</div>
</div><div class="blockform">
	<h2><span><?php echo $lang_delete['Delete post'] ?></span></h2>
	<div class="box">
		<?php echo _CHtml::form(array('delete', 'id' => $id), 'POST');?>
			<div class="inform">
				<p><strong><?php echo $lang_delete['Warning'] ?></strong></p>
				<p><strong><?php echo $lang_common['Author'] ?></strong>: <?php echo _CHtml::encode($cur_post['poster']) ?></p>
				<p><strong><?php echo $lang_common['Message'] ?></strong>:</p>
				<div class="deletemsg">
					<div class="postmsg">
						<?php echo $cur_post['message'] . "\n" ?>
					</div>
				</div>
			</div>
			<p class="buttons"><input type="submit" name="delete" value="<?php echo $lang_delete['Delete'] ?>" /><?php echo _CHtml::link($lang_common['Go back'], 'javascript:history.go(-1);');?></p>
		</form>
	</div>
</div>
<?php require SHELL_PATH . 'footer.php';