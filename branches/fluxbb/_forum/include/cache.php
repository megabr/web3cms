<?php
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
    exit;
// If we are running pre PHP 4.2.0, we add our own implementation of var_export
if (!function_exists('var_export')) {
    function var_export()
    {
        $args = func_get_args();
        $indent = (isset($args[2])) ? $args[2] : '';
        if (is_array($args[0])) {
            $output = 'array (' . "\n";
            foreach ($args[0] as $k => $v) {
                if (is_numeric($k))
                    $output .= $indent . '  ' . $k . ' => ';
                else
                    $output .= $indent . '  \'' . str_replace('\'', '\\\'', str_replace('\\', '\\\\', $k)) . '\' => ';
                if (is_array($v))
                    $output .= var_export($v, true, $indent . '  ');
                else {
                    if (gettype($v) != 'string' && !empty($v))
                        $output .= $v . ',' . "\n";
                    else
                        $output .= '\'' . str_replace('\'', '\\\'', str_replace('\\', '\\\\', $v)) . '\',' . "\n";
                }
            }
            $output .= ($indent != '') ? $indent . '),' . "\n" : ')';
        }else
            $output = $args[0];
        if ($args[1] == true)
            return $output;
        else
            echo $output;
    }
}
// Generate the bans cache PHP script
function generate_bans_cache()
{
    global $db;
    // Get the ban list from the DB
    $db->setQuery('SELECT * FROM forum_bans', true) or error('Unable to fetch ban list', __FILE__, __LINE__, $db->error());
    $output = array();
    while ($cur_ban = $db->fetch_assoc())
    $output[] = $cur_ban;
    // Output ban list as PHP code
    $fh = @fopen(FORUM_CACHE_DIR . 'cache_bans.php', 'wb');
    if (!$fh)
        error('Unable to write bans cache file to cache directory. Please make sure PHP has write access to the directory \'cache\'', __FILE__, __LINE__);
    fwrite($fh, '<?php' . "\n\n" . 'define(\'PUN_BANS_LOADED\', 1);' . "\n\n" . '$_bans = ' . var_export($output, true) . ';' . "\n\n" . '?>');
    fclose($fh);
}
// Generate the ranks cache PHP script
function generate_ranks_cache()
{
    global $db;
    // Get the rank list from the DB
    $db->setQuery('SELECT * FROM forum_ranks ORDER BY min_posts', true) or error('Unable to fetch rank list', __FILE__, __LINE__, $db->error());
    $output = array();
    while ($cur_rank = $db->fetch_assoc())
    $output[] = $cur_rank;
    // Output ranks list as PHP code
    $fh = @fopen(FORUM_CACHE_DIR . 'cache_ranks.php', 'wb');
    if (!$fh)
        error('Unable to write ranks cache file to cache directory. Please make sure PHP has write access to the directory \'cache\'', __FILE__, __LINE__);
    fwrite($fh, '<?php' . "\n\n" . 'define(\'PUN_RANKS_LOADED\', 1);' . "\n\n" . '$_ranks = ' . var_export($output, true) . ';' . "\n\n" . '?>');
    fclose($fh);
}
// Generate quick jump cache PHP scripts
function generate_quickjump_cache($forumGroupId = false)
{
    global $db, $lang_common, $_user;
    // If a forumGroupId was supplied, we generate the quick jump cache for that group only
    if ($forumGroupId !== false)
        $groups[0] = $forumGroupId;
    else {
        // A forumGroupId was now supplied, so we generate the quick jump cache for all groups
        $db->setQuery('SELECT g_id FROM forum_groups') or error('Unable to fetch user group list', __FILE__, __LINE__, $db->error());
        $num_groups = $db->num_rows();
        for ($i = 0; $i < $num_groups; ++$i)
        $groups[] = $db->result($result, $i);
    }
    // Loop through the groups in $groups and output the cache for each of them
    while (list(, $forumGroupId) = @each($groups)) {
        // Output quick jump as PHP code
        $fh = @fopen(FORUM_CACHE_DIR . 'cache_quickjump_' . $forumGroupId . '.php', 'wb');
        if (!$fh)
            error('Unable to write quick jump cache file to cache directory. Please make sure PHP has write access to the directory \'cache\'', __FILE__, __LINE__);
        $output = '<?php' . "\n\n" . 'if (!defined(\'PUN\')) exit;' . "\n" . 'define(\'PUN_QJ_LOADED\', 1);' . "\n\n" . '?>';
        $output .= "\t\t\t\t" . _CHtml::form('forum/viewforum', 'GET', array('id' => 'qjump')) . "\n\t\t\t\t\t" . '<div><label><?php echo $lang_common[\'Jump to\'] ?>' . "\n\n\t\t\t\t\t" . '<br /><select name="id" onchange="window.location=(\'' . Yii::app()->createUrl('forum/viewforum', array('id' => '+this.options[this.selectedIndex].value')) . '\')">' . "\n";
        $db->setQuery('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.redirect_url FROM forum_categories AS c INNER JOIN forum_forums AS f ON c.id=f.cat_id LEFT JOIN forum_forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id=' . $forumGroupId . ') WHERE fp.read_forum IS NULL OR fp.read_forum=1 ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());
        $cur_category = 0;
        while ($cur_forum = $db->fetch_assoc()) {
            if ($cur_forum['cid'] != $cur_category) { // A new category since last iteration?
                    if ($cur_category)
                        $output .= "\t\t\t\t\t\t" . '</optgroup>' . "\n";
                    $output .= "\t\t\t\t\t\t" . '<optgroup label="' . _CHtml::encode($cur_forum['cat_name']) . '">' . "\n";
                    $cur_category = $cur_forum['cid'];
                }
                $redirect_tag = ($cur_forum['redirect_url'] != '') ? ' &gt;&gt;&gt;' : '';
                $output .= "\t\t\t\t\t\t\t" . '<option value="' . $cur_forum['fid'] . '"<?php echo ($forum_id == ' . $cur_forum['fid'] . ') ? \' selected="selected"\' : \'\' ?>>' . _CHtml::encode($cur_forum['forum_name']) . $redirect_tag . '</option>' . "\n";
            }
            $output .= "\t\t\t\t\t\t" . '</optgroup>' . "\n\t\t\t\t\t" . '</select>' . "\n\t\t\t\t\t" . '<input type="submit" value="<?php echo $lang_common[\'Go\'] ?>" accesskey="g" />' . "\n\t\t\t\t\t" . '</label></div>' . "\n\t\t\t\t" . '</form>' . "\n";
            fwrite($fh, $output);
            fclose($fh);
        }
    }
    define('FORUM_CACHE_FUNCTIONS_LOADED', true);