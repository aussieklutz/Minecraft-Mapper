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
/*if(isset($_POST['l_u']))
{
    $username = addslashes($_POST['l_u']);
    $password = md5($db_settings['hashsalt'] . $_POST['l_p']);
    $q = mysql_query("SELECT * FROM users WHERE `username`=\"$username\" AND `password`=\"$password\"");
    if(mysql_num_rows($q) > 0)
    {
        $r = mysql_fetch_assoc($q);
        mysql_query('UPDATE sessions SET `uid`=' . $r['id']);
        $sessiondata['uid'] = $r['id'];
    } else {
        $errors .= "Username/password combination not found.<br />\n";
    }
}*/
$q = mysql_query('SELECT * FROM users WHERE id="' . $sessiondata['uid'] . '"');
$userdata = mysql_fetch_assoc($q);

$x_size = $userdata['dimension_x'];
$z_size = $userdata['dimension_y'];

function x($x, $z)
{
    global $x_size;
    $new_x = $x_size-$z;
    return $new_x;
}

function y($x, $z)
{
    $new_y = $x;
    return $new_y;
}

$fogradius = 25;
$roadwidth = 4;

if($sessiondata['uid'] > 1 && isset($_GET['zoom']) && $_GET['zoom'] == 1)
{
$r['min_x'] = $userdata['x']-($z_size/2);
$r['max_x'] = $userdata['x']+($z_size/2);
$r['min_z'] = $userdata['z']-($x_size/2);
$r['max_z'] = $userdata['z']+($x_size/2);
} else {
$q = mysql_query('SELECT * FROM bounds');
$r = mysql_fetch_assoc($q);
}
$world_x_min = $r['min_x'];
$world_x_max = $r['max_x'];
$world_z_min = $r['min_z'];
$world_z_max = $r['max_z'];
$world_x_size = abs($world_x_max - $world_x_min);
$world_z_size = abs($world_x_max - $world_x_min);
$world_x_min = $r['min_x']-(0.05*$world_x_size);
$world_x_max = $r['max_x']+(0.05*$world_x_size);
$world_z_min = $r['min_z']-(0.05*$world_x_size);
$world_z_max = $r['max_z']+(0.05*$world_x_size);
$world_x_size = ($world_x_max - $world_x_min);
$world_z_size = ($world_x_max - $world_x_min);

$ratio = 1;
if(($x_size/$world_z_size) < ($z_size/$world_x_size))
{
    $ratio = $x_size/$world_z_size;
} else {
    $ratio = $z_size/$world_x_size;
}

header("Content-type: image/png");
$im = imagecreatetruecolor($x_size, $z_size);
$white = imagecolorallocate($im, 255, 255, 255);
$black = imagecolorallocate($im, 0, 0, 0);
$blueish = imagecolorallocate($im, 0, 0, 64);
$grey = imagecolorallocate($im, 127, 127, 127);
$darkgrey = imagecolorallocate($im, 31, 31, 31);
$medgrey = imagecolorallocate($im, 63, 63, 63);
$green = imagecolorallocate($im, 0, 255, 31);
$red = imagecolorallocate($im, 191, 0, 0);
$darkred = imagecolorallocate($im, 48, 0, 0);
$redish = imagecolorallocate($im, 32, 0, 0);
$nextgrey = imagecolorallocate($im, 43, 43, 43);
$nextred = imagecolorallocate($im, 28, 0, 0);
imagefill($im, 0, 0, $black);

// draw fogofwar
$q = mysql_query('SELECT * FROM breadcrumbs ORDER BY id DESC LIMIT 0, 100');
while ($r = mysql_fetch_assoc($q))
{
    $world_x = $r['x'];
    $world_y = $r['z'];
    $im_x = $ratio * ($world_x - $world_x_min);
    $im_y = $ratio * ($world_y - $world_z_min);
    
    imagefilledellipse($im, x($im_x, $im_y), y($im_x, $im_y), round($fogradius*$ratio)+1, round($fogradius*$ratio)+1, $darkgrey);
}

// draw areas
$q = mysql_query('SELECT * FROM areas WHERE owner=0 OR owner=' . $sessiondata['uid'] . ' ORDER BY id');
while ($r = mysql_fetch_assoc($q))
{
    //get points
    $area = $r['id'];
    $points = array();
    $q1 = mysql_query("SELECT * FROM areanodes WHERE area_number=$area ORDER BY id");
    while($r1 = mysql_fetch_assoc($q1))
    {
        $world_x = $r1['x'];
        $world_y = $r1['z'];
        $im_x = $ratio * ($world_x - $world_x_min);
        $im_y = $ratio * ($world_y - $world_z_min);
        array_push($points, x($im_x, $im_y), y($im_x, $im_y));
    }
    if(count($points)>=6)
    {
        if($r['owner'] == 0)
        {
            imagefilledpolygon($im, $points, count($points)/2, $blueish);
        } else {
            imagefilledpolygon($im, $points, count($points)/2, $redish);
        }
    }
}

// draw road
imagesetthickness($im, round($roadwidth*$ratio)+1);
$lastroad = -1;
$last_x = 0;
$last_y = 0;
$q = mysql_query('SELECT * FROM roadnodes WHERE owner=0 OR owner=' . $sessiondata['uid'] . ' ORDER BY road_number, id');
while ($r = mysql_fetch_assoc($q))
{
    $world_x = $r['x'];
    $world_y = $r['z'];
    $im_x = $ratio * ($world_x - $world_x_min);
    $im_y = $ratio * ($world_y - $world_z_min);
    if($r['road_number'] == $lastroad)
    {
        if($r['tunnel'] == 0)
        {
            if($r['owner'] == 0)
            {
                imageline($im, x($im_x, $im_y), y($im_x, $im_y), x($last_x, $last_y), y($last_x, $last_y), $medgrey);
            } else {
                imageline($im, x($im_x, $im_y), y($im_x, $im_y), x($last_x, $last_y), y($last_x, $last_y), $darkred);
            }
        } else {
            if($r['owner'] == 0)
            {
                imageline($im, x($im_x, $im_y), y($im_x, $im_y), x($last_x, $last_y), y($last_x, $last_y), $nextgrey);
            } else {
                imageline($im, x($im_x, $im_y), y($im_x, $im_y), x($last_x, $last_y), y($last_x, $last_y), $nextred);
            }
        }
    }
    $lastroad = $r['road_number'];
    $last_x = $im_x;
    $last_y = $im_y;
}
imagesetthickness($im, 1);

// draw breadcrumbs
$q = mysql_query('SELECT * FROM breadcrumbs ORDER BY id DESC LIMIT 0, 100');
while ($r = mysql_fetch_assoc($q))
{
    $world_x = $r['x'];
    $world_y = $r['z'];
    $im_x = $ratio * ($world_x - $world_x_min);
    $im_y = $ratio * ($world_y - $world_z_min);
    
    imagesetpixel($im, x($im_x, $im_y), y($im_x, $im_y), $darkgrey);
}

// draw places
$q = mysql_query('SELECT * FROM places WHERE owner=0 OR owner=' . $sessiondata['uid']);
while ($r = mysql_fetch_assoc($q))
{
    $name = stripslashes($r['name']);
    $world_x = $r['x'];
    $world_y = $r['z'];
    $im_x = $ratio * ($world_x - $world_x_min);
    $im_y = $ratio * ($world_y - $world_z_min);
    
    imagesetpixel($im, x($im_x, $im_y), y($im_x, $im_y), $white);
    if($r['owner'] == 0)
    {
        imagestring($im, 1, x($im_x, $im_y)+5, y($im_x, $im_y)-3, $name, $grey);
    } else {
        imagestring($im, 1, x($im_x, $im_y)+5, y($im_x, $im_y)-3, $name, $red);
    }
}

// draw players
$q = mysql_query('SELECT * FROM users WHERE id>1');
while ($r = mysql_fetch_assoc($q))
{
    $name = $r['username'];
    $world_x = $r['x'];
    $world_y = $r['z'];
    $im_x = $ratio * ($world_x - $world_x_min);
    $im_y = $ratio * ($world_y - $world_z_min);
    
    imagesetpixel($im, x($im_x, $im_y), y($im_x, $im_y), $white);
    imagestring($im, 1, x($im_x, $im_y)+5, y($im_x, $im_y)-3, $name, $green);
}

if($userdata['id'] > 1)
{
    imagestring($im, 1, 5, 5, 'Player Depth: ' . $userdata['y'], $green);
}

imagepng($im);
imagedestroy($im);

?>