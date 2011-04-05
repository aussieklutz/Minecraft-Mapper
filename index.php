<?php
include 'includes/config.inc';
$errors = '';
mysql_connect($db_settings['localhost'], $db_settings['username'], $db_settings['password']);
mysql_select_db($db_settings['database']);
$session_length = '1:00';
mysql_query("DELETE FROM sessions WHERE `timestamp`<SUBTIME(CURRENT_TIMESTAMP, $session_length)");
Session_name('kmc_');
session_start();
$sid = session_id();
$q = mysql_query("SELECT * FROM sessions WHERE sid=\"$sid\"");
if(mysql_num_rows($q) <= 0)
{
    mysql_query("INSERT INTO sessions (`sid`, `uid`, `timestamp`) VALUES (\"$sid\", \"1\", CURRENT_TIMESTAMP)");
    $q = mysql_query("SELECT * FROM sessions WHERE sid=\"$sid\"");
}
$sessiondata = mysql_fetch_assoc($q);
if(isset($_POST['l_u']))
{
    $username = addslashes($_POST['l_u']);
    $password = md5($db_settings['hashsalt'] . $_POST['l_p']);
    $q = mysql_query("SELECT * FROM users WHERE `username`=\"$username\" AND `password`=\"$password\"");
    if(mysql_num_rows($q) > 0)
    {
        $r = mysql_fetch_assoc($q);
        mysql_query('UPDATE sessions SET `uid`=' . $r['id'] . ' WHERE `sid`="' . $sid . '"');
        $sessiondata['uid'] = $r['id'];
    } else {
        $errors .= "Username/password combination not found.<br />\n";
    }
}
if(isset($_POST['l_s']))
{
    mysql_query('UPDATE sessions SET `uid`=1 WHERE `sid`="' . $sid . '"');
    $sessiondata['uid'] = 1;
}
$q = mysql_query('SELECT * FROM users WHERE id="' . $sessiondata['uid'] . '"');
$userdata = mysql_fetch_assoc($q);
if($userdata['id'] > 1)
{
    if(isset($_POST['ch_p']))
    {
        mysql_query('UPDATE users SET `password`="' . md5($db_settings['hashsalt'] . $_POST['ch_p']) . '" WHERE `id`=' . $sessiondata['uid']);
    }
    if(isset($_POST['ch_dim']))
    {
        $dim_parts = explode('x', $_POST['ch_dim']);
        mysql_query('UPDATE users SET `dimension_x`="' . $dim_parts[0] . '", `dimension_y`="' . $dim_parts[1] . '" WHERE `id`=' . $sessiondata['uid']);
    }
    if(isset($_POST['ch_rot']))
    {
        if($_POST['ch_rot_world'] == 1)
        {
            mysql_query('UPDATE users SET `world_compass`="1" WHERE `id`=' . $sessiondata['uid']);
            $userdata['world_compass'] = 1;
        } else {
            mysql_query('UPDATE users SET `world_compass`="0" WHERE `id`=' . $sessiondata['uid']);
            $userdata['world_compass'] = 0;
        }
    }
    
    if(isset($_POST['usr_place']))
    {
        $placename = addslashes($_POST['usr_place']);
        mysql_query('INSERT INTO places (`id`, `owner`, `name`, `x`, `z`) VALUES (NULL, "' . $sessiondata['uid'] . '", "' . $placename . '", "' . $userdata['x'] . '", "' . $userdata['z'] . '")');
    }
    // usr_road[name]
    if(isset($_POST['usr_road']))
    {
        if($_POST['usr_road_tunnel'] == 1)
        {
            $tunnel = 1;
        } else {
            $tunnel = 0;
        }
        $roadname = addslashes($_POST['usr_road']);
        mysql_query('INSERT INTO roads (`id`, `owner`, `name`, `tunnel`, `completed`) VALUES (NULL, "' . $sessiondata['uid'] . '", "' . $roadname . '", ' . $tunnel . ', 0)');
    }
    // usr_node[road_number]
    if(isset($_POST['usr_roadnode']))
    {
        $road = $_POST['usr_roadnode'];
        mysql_query('INSERT INTO roadnodes (`id`, `owner`, `road_number`, `x`, `z`) VALUES (NULL, "' . $sessiondata['uid'] . '", "' . $road . '", "' . $userdata['x'] . '", "' . $userdata['z'] . '")');
    }
    // usr_road_completed[id]
    if(isset($_POST['usr_road_completed']))
    {
        $road = $_POST['usr_road_completed'];
        mysql_query('UPDATE roads SET `completed`=1 WHERE id=' . $road);
    }
    
    // usr_area[name]
    if(isset($_POST['usr_area']))
    {
        $areaname = addslashes($_POST['usr_area']);
        mysql_query('INSERT INTO areas (`id`, `owner`, `name`, `completed`) VALUES (NULL, "' . $sessiondata['uid'] . '", "' . $areaname . '", 0)');
    }
    // usr_areanode[area_number]
    if(isset($_POST['usr_areanode']))
    {
        $area = $_POST['usr_areanode'];
        mysql_query('INSERT INTO areanodes (`id`, `owner`, `area_number`, `x`, `z`) VALUES (NULL, "' . $sessiondata['uid'] . '", "' . $area . '", "' . $userdata['x'] . '", "' . $userdata['z'] . '")');
    }
    // usr_area_completed[id]
    if(isset($_POST['usr_area_completed']))
    {
        $area = $_POST['usr_area_completed'];
        mysql_query('UPDATE areas SET `completed`=1 WHERE id=' . $area);
    }
    
    //publisher
    if($userdata['publisher'] == 1)
    {
        //places
        if(isset($_POST['usr_place_pub']))
        {
            $place = $_POST['usr_place_pub'];
            mysql_query("UPDATE places SET owner=0 WHERE id=$place");
        }
    
        //roads
        if(isset($_POST['usr_road_pub']))
        {
            $road = $_POST['usr_road_pub'];
            mysql_query("UPDATE roads SET owner=0 WHERE id=$road");
            mysql_query("UPDATE roadnodes SET owner=0 WHERE road_number=$road");
        }
        
        //areas
        if(isset($_POST['usr_area_pub']))
        {
            $area = $_POST['usr_area_pub'];
            mysql_query("UPDATE areas SET owner=0 WHERE id=$area");
            mysql_query("UPDATE areanodes SET owner=0 WHERE area_number=$area");
        }
    } else {
        //places
        if(isset($_POST['usr_place_pub']))
        {
            $place = $_POST['usr_place_pub'];
            mysql_query("UPDATE places SET submit=1 WHERE id=$place");
        }
    
        //roads
        if(isset($_POST['usr_road_pub']))
        {
            $road = $_POST['usr_road_pub'];
            mysql_query("UPDATE roads SET submit=1 WHERE id=$road");
        }
        
        //areas
        if(isset($_POST['usr_area_pub']))
        {
            $area = $_POST['usr_area_pub'];
            mysql_query("UPDATE areas SET submit=1 WHERE id=$area");
        }
    }
    
    //delete
    //places
    if(isset($_POST['usr_place_del']))
    {
        $place = $_POST['usr_place_del'];
        mysql_query("DELETE FROM places WHERE id=$place AND owner=" . $sessiondata['uid']);
    }

    //roads
    if(isset($_POST['usr_road_del']))
    {
        $road = $_POST['usr_road_del'];
        mysql_query("DELETE FROM roads WHERE id=$road AND owner=" . $sessiondata['uid']);
        mysql_query("DELETE FROM roadnodes WHERE road_number=$road AND owner=" . $sessiondata['uid']);
    }
    
    //areas
    if(isset($_POST['usr_area_del']))
    {
        $area = $_POST['usr_area_del'];
        mysql_query("DELETE FROM areas WHERE id=$area AND owner=" . $sessiondata['uid']);
        mysql_query("DELETE FROM areanodes WHERE area_number=$area AND owner=" . $sessiondata['uid']);
    }
    
    if($userdata['useradmin'] == 1)
    {
        if(isset($_POST['add_user']))
        {
            $temp_password = md5($db_settings['hashsalt'] . $_POST['add_username']);
            if(isset($_POST['add_publisher']) && $_POST['add_publisher'] == 1)
            {
                $publish = 1;
            } else {
                $publish = 0;
            }
            if(isset($_POST['add_admin']) && $_POST['add_admin'] == 1)
            {
                $admin = 1;
            } else {
                $admin = 0;
            }
            mysql_query('INSERT INTO users (`id`, `username`, `password`, `email`, `publisher`, `useradmin`) VALUES (NULL, "' . $_POST['add_username'] . '", "' . md5($db_settings['hashsalt'] . $temp_password) . '", "' . $_POST['add_email'] . '", ' . $publish . ', ' . $admin . ')');
            //email password to user
            $headers = 'From: noreply@randomcomplexity.com' . "\n" .
            'Reply-To: noreply@randomcomplexity.com' . "\n" .
            'X-Mailer: PHP/' . phpversion();
            $message = 'You have had an account created at http://randomcomplexity.com/minecraft/' . "\n";
            $message .= 'Your username is: ' . $_POST['add_username'] . "\n";
            $message .= 'Your temporary password is: ' . $temp_password . "\n";
            mail($_POST['add_email'], 'Minecraft Mapper', $message, $headers);
        }
    }
}
?>
<html>
    <head>
        <title>Minecraft</title>
        <script language="JavaScript">
            <!--
            var $zoom=-1;
            function reloadImg() {
                var obj = document.getElementById('map');
                var src = obj.src;
                var pos = src.indexOf('?');
                if (pos >= 0) {
                   src = src.substr(0, pos);
                }
                var date = new Date();
                obj.src = src + '?zoom=' + $zoom + '&v=' + date.getTime();
            }
            
            function zoom()
            {
                $zoom *= -1;
                reloadImg();
            }
            
            setInterval('reloadImg()', 5000);
            //-->
        </script>
    </head>
    <body bgcolor="#000000" text="#aabbc;">
        <table>
            <tr>
                <td valign="top">
                    <a href="#" onClick="zoom()" border="0"><img src="./watch<?php if($userdata['world_compass'] == 1) echo '2'; ?>.php?zoom=-1" id="map" border="0" /></a>
                </td>
                <td valign="top">
                    
                    <a href="./" border="0">Minecraft Mapper</a><br /><br />
                    
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                        <table>
                            <?php if($sessiondata['uid'] == 1) { ?>
                            <tr><td><label for="l_u">Username: </label></td><td><input type="text" id="l_u" name="l_u" /></td></tr>
                            <tr><td><label for="l_p">Password: </label></td><td><input type="password" id="l_p" name="l_p" /></td></tr>
                            <tr><td>&nbsp;</td><td><input type="submit" value="Login" /></td></tr>
                            <?php } else { ?>
                            <tr><td>User: <?php echo $userdata['username'] ?></td></tr>
                            <tr><td><input type="submit" id="l_s" name="l_s" value="Logoff" /></td></tr>
                            <?php } ?>
                        </table>
                    </form>
                    
                    <?php if($sessiondata['uid'] > 1) { ?>
                    <br />
                    <script language="JavaScript">
                    <!--
                        var $passoptions = -1;
                        function switch_pass()
                        {
                            $passoptions *= -1;
                            if($passoptions == 1)
                            {
                                document.getElementById('passoptions').style.display='block';
                            } else {
                                document.getElementById('passoptions').style.display='none';
                            }
                        }
                    //-->
                    </script>
                    <a href="#" onClick="switch_pass()">Change Password</a><br />
                    <div id="passoptions" style="display: none">
                        <table>
                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                                <tr><td><label for="ch_p">New Password: </td><td><input type="password" name="ch_p" id="ch_p" /></td></tr>
                                <tr><td>&nbsp;</td><td><input type="submit" value="Change Password" /></td></tr>
                            </form>
                        </table>
                    </div>
                    <br />
                    <script language="JavaScript">
                    <!--
                        var $mapoptions = -1;
                        function switch_map()
                        {
                            $mapoptions *= -1;
                            if($mapoptions == 1)
                            {
                                document.getElementById('mapoptions').style.display='block';
                            } else {
                                document.getElementById('mapoptions').style.display='none';
                            }
                        }
                    //-->
                    </script>
                    <a href="#" onClick="switch_map()">Change Map Settings</a><br />
                    <div id="mapoptions" style="display: none">
                        <table>
                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                                <tr><td><label for="ch_dim">New Size: </td><td><input type="text" name="ch_dim" id="ch_dim" value="<?php echo $userdata['dimension_x']; ?>x<?php echo $userdata['dimension_y']; ?>" /></td></tr>
                                <tr><td>&nbsp;</td><td><input type="submit" value="Change Size" /></td></tr>
                            </form>
                        </table>
                        <br />
                        <table>
                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                                <tr><td><label for="ch_rot">Align with world compass: </td><td><input type="checkbox" name="ch_rot_world" id="ch_rot_world" value="1" <?php if($userdata['world_compass'] == 1) echo 'checked="checked"'; ?> /></td></tr>
                                <tr><td>&nbsp;</td><td><input type="submit" name="ch_rot" id="ch_rot" value="Change Compass" /></td></tr>
                            </form>
                        </table>
                    </div>
                    <br />
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                        <table>
                            <tr><td><label for="usr_place">Placename: </label></td><td><input type="text" id="usr_place" name="usr_place" /></td></tr>
                            <tr><td>&nbsp;</td><td><input type="submit" value="Place" /></td></tr>
                        </table>
                    </form>
                    <br />
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                        <table>
                            <tr><td><label for="usr_road">Roadname: </label></td><td><input type="text" id="usr_road" name="usr_road" /></td></tr>
                            <tr><td>Tunnel: <input type="checkbox" name="usr_road_tunnel" id="usr_road_tunnel" value="1" /></td><td><input type="submit" value="Start" /></td></tr>
                        </table>
                    </form>
                    <br />
                    <?php
                    $q = mysql_query('SELECT * FROM roads WHERE owner=' . $sessiondata['uid'] . ' AND completed=0');
                    while($r = mysql_fetch_assoc($q))
                    {
                    $nodes = mysql_result(mysql_query("SELECT COUNT(*) FROM roadnodes WHERE road_number=" . $r['id']), 0);
                    ?>
                    <table>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                            <tr><td><?php echo $r['name'] ?>: <?php echo $nodes; ?> nodes<input type="hidden" name="usr_roadnode" id="usr_roadnode" value="<?php echo $r['id'] ?>" /></td><td><input type="submit" value="Add Node" /></td></tr>
                        </form>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                            <tr><td>&nbsp;<input type="hidden" name="usr_road_completed" id="usr_road_completed" value="<?php echo $r['id'] ?>" /></td><td><input type="submit" value="Finish Road" /></td></tr>
                        </form>
                    </table>
                    <?php } ?>
                    <br />
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                        <table>
                            <tr><td><label for="usr_area">Areaname: </label></td><td><input type="text" id="usr_area" name="usr_area" /></td></tr>
                            <tr><td>&nbsp;</td><td><input type="submit" value="Start" /></td></tr>
                        </table>
                    </form>
                    <br />
                    <?php
                    $q = mysql_query('SELECT * FROM areas WHERE owner=' . $sessiondata['uid'] . ' AND completed=0');
                    while($r = mysql_fetch_assoc($q))
                    {
                    $nodes = mysql_result(mysql_query("SELECT COUNT(*) FROM areanodes WHERE area_number=" . $r['id']), 0);
                    ?>
                    <table>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                            <tr><td><?php echo $r['name'] ?>: <?php echo $nodes; ?> nodes<input type="hidden" name="usr_areanode" id="usr_areanode" value="<?php echo $r['id'] ?>" /></td><td><input type="submit" value="Add Node" /></td></tr>
                        </form>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                            <tr><td>&nbsp;<input type="hidden" name="usr_area_completed" id="usr_area_completed" value="<?php echo $r['id'] ?>" /></td><td><input type="submit" value="Finish Area" /></td></tr>
                        </form>
                    </table>
                    <?php } ?>
                    
                    
                    <script language="JavaScript">
                    <!--
                        var $publishoptions = -1;
                        function switch_publish()
                        {
                            $publishoptions *= -1;
                            if($publishoptions == 1)
                            {
                                document.getElementById('publishoptions').style.display='block';
                            } else {
                                document.getElementById('publishoptions').style.display='none';
                            }
                        }
                    //-->
                    </script>
                    <a href="#" onClick="switch_publish()">Publish</a><br /><br />
                    <div id="publishoptions" style="display: none">
                    <table>
                        <tr><td>Places</td><td></td></tr>
                        <?php
                        //places
                        $q = mysql_query('SELECT * FROM places WHERE owner=' . $sessiondata['uid']);
                        while($r = mysql_fetch_assoc($q))
                        {
                        ?>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                            <tr><td><?php echo $r['name'] ?><input type="hidden" id="usr_place_pub" name="usr_place_pub" value="<?php echo $r['id'] ?>" /></td><td><input type="submit" value="Publish" /></td></tr>
                        </form>
                        <?php } ?> 
                    </table>
                    
                    <table>
                        <tr><td>Roads</td><td></td></tr>
                        <?php
                        //roads
                        $q = mysql_query('SELECT * FROM roads WHERE owner=' . $sessiondata['uid']);
                        while($r = mysql_fetch_assoc($q))
                        {
                        ?>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                            <tr><td><?php echo $r['name'] ?><input type="hidden" id="usr_road_pub" name="usr_road_pub" value="<?php echo $r['id'] ?>" /></td><td><input type="submit" value="Publish" /></td></tr>
                        </form>
                        <?php } ?> 
                    </table>
                    
                    <table>
                        <tr><td>Areas</td><td></td></tr>
                        <?php
                        //places
                        $q = mysql_query('SELECT * FROM areas WHERE owner=' . $sessiondata['uid']);
                        while($r = mysql_fetch_assoc($q))
                        {
                        ?>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                            <tr><td><?php echo $r['name'] ?><input type="hidden" id="usr_area_pub" name="usr_area_pub" value="<?php echo $r['id'] ?>" /></td><td><input type="submit" value="Publish" /></td></tr>
                        </form>
                        <?php } ?> 
                    </table>
                    
                    <?php if($userdata['publisher'] == 1) { ?>
                    <br /><br />
                    Submitted by others:<br />
                    
                    <table>
                        <tr><td>Places</td><td></td></tr>
                        <?php
                        //places
                        $q = mysql_query('SELECT * FROM places WHERE submit=1');
                        while($r = mysql_fetch_assoc($q))
                        {
                        ?>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                            <tr><td><?php echo $r['name'] ?><input type="hidden" id="usr_place_pub" name="usr_place_pub" value="<?php echo $r['id'] ?>" /></td><td><input type="submit" value="Publish" /></td></tr>
                        </form>
                        <?php } ?> 
                    </table>
                    <br />
                    <table>
                        <tr><td>Roads</td><td></td></tr>
                        <?php
                        //roads
                        $q = mysql_query('SELECT * FROM roads WHERE submit=1');
                        while($r = mysql_fetch_assoc($q))
                        {
                        ?>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                            <tr><td><?php echo $r['name'] ?><input type="hidden" id="usr_road_pub" name="usr_road_pub" value="<?php echo $r['id'] ?>" /></td><td><input type="submit" value="Publish" /></td></tr>
                        </form>
                        <?php } ?> 
                    </table>
                    <br />
                    <table>
                        <tr><td>Areas</td><td></td></tr>
                        <?php
                        //places
                        $q = mysql_query('SELECT * FROM areas WHERE submit=1');
                        while($r = mysql_fetch_assoc($q))
                        {
                        ?>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                            <tr><td><?php echo $r['name'] ?><input type="hidden" id="usr_area_pub" name="usr_area_pub" value="<?php echo $r['id'] ?>" /></td><td><input type="submit" value="Publish" /></td></tr>
                        </form>
                        <?php } ?> 
                    </table><br />
                    
                    <?php } ?>
                    </div>
                    
                    
                    <script language="JavaScript">
                    <!--
                        var $deleteoptions = -1;
                        function switch_delete()
                        {
                            $deleteoptions *= -1;
                            if($deleteoptions == 1)
                            {
                                document.getElementById('deleteoptions').style.display='block';
                            } else {
                                document.getElementById('deleteoptions').style.display='none';
                            }
                        }
                    //-->
                    </script>
                    <a href="#" onClick="switch_delete()">Delete</a><br /><br />
                    <div id="deleteoptions" style="display: none">
                    <table>
                        <tr><td>Places</td><td></td></tr>
                        <?php
                        //places
                        $q = mysql_query('SELECT * FROM places WHERE owner=' . $sessiondata['uid']);
                        while($r = mysql_fetch_assoc($q))
                        {
                        ?>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                            <tr><td><?php echo $r['name'] ?><input type="hidden" id="usr_place_del" name="usr_place_del" value="<?php echo $r['id'] ?>" /></td><td><input type="submit" value="Delete" /></td></tr>
                        </form>
                        <?php } ?> 
                    </table>
                    <br />
                    <table>
                        <tr><td>Roads</td><td></td></tr>
                        <?php
                        //roads
                        $q = mysql_query('SELECT * FROM roads WHERE owner=' . $sessiondata['uid']);
                        while($r = mysql_fetch_assoc($q))
                        {
                        ?>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                            <tr><td><?php echo $r['name'] ?><input type="hidden" id="usr_road_del" name="usr_road_del" value="<?php echo $r['id'] ?>" /></td><td><input type="submit" value="Delete" /></td></tr>
                        </form>
                        <?php } ?> 
                    </table>
                    <br />
                    <table>
                        <tr><td>Areas</td><td></td></tr>
                        <?php
                        //places
                        $q = mysql_query('SELECT * FROM areas WHERE owner=' . $sessiondata['uid']);
                        while($r = mysql_fetch_assoc($q))
                        {
                        ?>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                            <tr><td><?php echo $r['name'] ?><input type="hidden" id="usr_area_del" name="usr_area_del" value="<?php echo $r['id'] ?>" /></td><td><input type="submit" value="Delete" /></td></tr>
                        </form>
                        <?php } ?> 
                    </table>
                    </div>
                    
                    <?php if($userdata['useradmin'] == 1) { ?>
                    <script language="JavaScript">
                    <!--
                        var $adminoptions = -1;
                        function switch_admin()
                        {
                            $adminoptions *= -1;
                            if($adminoptions == 1)
                            {
                                document.getElementById('adminoptions').style.display='block';
                            } else {
                                document.getElementById('adminoptions').style.display='none';
                            }
                        }
                    //-->
                    </script>
                    <a href="#" onClick="switch_admin()">User Administration</a><br />
                    <div id="adminoptions" style="display: none">
                        <table>
                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                                <tr><td>Add User</td><td></td></tr>
                                <tr><td><label for="add_username">Username: </td><td><input type="input" name="add_username" id="add_username" /></td></tr>
                                <tr><td><label for="add_email">Email: </td><td><input type="input" name="add_email" id="add_email" /></td></tr>
                                <tr><td><label for="add_publisher">Publish: </td><td><input type="checkbox" name="add_publisher" id="add_publisher" value="1" /></td></tr>
                                <tr><td><label for="add_admin">Add users: </td><td><input type="checkbox" name="add_admin" id="add_admin" value="1" /></td></tr>
                                <tr><td>&nbsp;</td><td><input type="submit" name="add_user" id="add_user" value="Add User" /></td></tr>
                            </form>
                        </table>
                    </div><br />
                    <?php } ?> 
                    <a href="./MapUpdater.zip">MapUpdater</a>
                    <?php } ?> 
                </td>
            </tr>
        </table>
    </body>
</html>