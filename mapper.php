<?php
  
if(isset($_GET['packet']) && strlen($_GET['packet']) == 68)
{
$packet = $_GET['packet'];
$email = $_GET['email'];
// parse packet into components
mysql_connect('', '', '');
mysql_select_db('minecraft');
//mysql_query('INSERT INTO entities (`id`, `name`, `packet`) VALUES (NULL, "' . $name . '", "' . $packet . '")');
echo mysql_error();

/** Complementary functions to CONVERT PHP FLOATING POINT NUMBERS or DECIMALS
 *  (IEEE 754 single-precision 32 bit) TO HEXADECIMAL AND BACK.
 *   
 *  @author NSTIAC (Jorge D. Baigorri Salas) <http://www.nstiac.com> <http://www.n2works.com>
 *  @created on 28/Jan/2010 / time spent: 2hours approx.
 *  @special thanks to Thomas Finley's article "Floating Point"
 *  <http://tfinley.net/notes/cps104/floating.html>
 *
 *  These functions allow to convert any php floating point numbers with the
 *  notation 1.234 (or fixed point numbers) into their corresponding 8 digits
 *  hexadecimal notation. (i.e.- 1557897.40 -> 49BE2C4B <- 1557897.40) by
 *  disregarding their implicit format and treating them at will as either
 *  integer, decimals, binary numbers, hexadecimal values, or plain text.
 *       
**/

/** HEX2FLOAT32n
  * (Convert 8 digit hexadecimal values to fixed decimals float numbers (single-precision 32bits)
  * Accepts 8 digit hexadecimal values on a string (i.e.- F1A9B02C) and single integer to fix number of decimals
  * @usage:
  * hex2float32n("c9be2c49",2); returns -> "-1557897.13"
**/ 

/**
Modified to instead work with 64 bit Double values...
**/

function hex2float32n($number,$nd) {
    $binfinal = '';
   //Separate each hexadecimal digit
   for ($i=0; $i<strlen($number); $i++) {
       $hex[]=substr($number,$i,1);
   }
   //Convert each hexadecimal digit to integer
   for ($i=0; $i<count($hex); $i++) {
       $dec[]=hexdec($hex[$i]);
   }
   //Convert each decimal value to 4bit binary and join on a string
   for ($i=0; $i<count($dec); $i++) {
       $binfinal.=sprintf("%04d",decbin($dec[$i]));
   }
   //Get sign 1bit value
   $sign=substr($binfinal,0,1);
   //Get exponent 8bit value
   $exp=substr($binfinal,1,11);
   //Get mantissa 23bit value
   $mantissa=substr($binfinal,12);
   //Convert & adjunt binary exponent to integer
   $exp=bindec($exp);
   $exp-=1023;
   //Assign mantissa to pre-scientific binary notation
   $scibin=$mantissa;
   //Split $scibin into integral & fraction parts through exponent
   $binint=substr($scibin,0,$exp);
   $binpoint=substr($scibin,$exp);
   //Convert integral binary part to decimal integer
   $intnumber=bindec("1".$binint);
   //Split each binary fractional digit
   for ($i=0; $i<strlen($binpoint); $i++) {
       $tmppoint[]=substr($binpoint,$i,1);
   }
   //Reverse order to work backwards
   $tmppoint=array_reverse($tmppoint);
   //Convert base 2 digits to decimal
   $tpointnumber=number_format($tmppoint[0]/2,strlen($binpoint),'.','');
   for ($i=1; $i<strlen($binpoint); $i++) {
       $pointnumber=number_format($tpointnumber/2,strlen($binpoint),'.','');
       $tpointnumber=$tmppoint[$i+1].substr($pointnumber,1);
   }
   //Join both decimal section to get final number
   $floatfinal=$intnumber+$pointnumber;
   //Convert to positive or negative based on binary sign bit
   if ($sign==1) { $floatfinal=-$floatfinal; }
  
   //Format float number to fixed decimals required
   return number_format($floatfinal,$nd,'.','');
  
}

function decode_hex($hex)
{
    //$bin = hex2bin($hex);
    $result = hex2float32n($hex,2);
    //print_r($result);
    return $result;
}

    $packet_type = substr($packet, 0, 2);
    $packet_x = substr($packet, 2, 16);
    $packet_y = substr($packet, 18, 16);
    $packet_stance = substr($packet, 34, 16);
    $packet_z = substr($packet, 50, 16);
    $packet_onground = substr($packet, 66, 2);
    $x = round(decode_hex($packet_x));
    $y = round(decode_hex($packet_y));
    $z = round(decode_hex($packet_z));
mysql_query("UPDATE users SET x=$x, y=$y, z=$z WHERE email='$email'");
mysql_query('INSERT INTO breadcrumbs (`id`, `x`, `z`) VALUES (NULL, "' . $x . '", "' . $z . '")');
$bounds=mysql_fetch_assoc(mysql_query('SELECT * FROM bounds'));
print_r($bounds);
if($bounds['min_x'] > $x && ($bounds['min_x'] - $x) < 20) mysql_query("UPDATE bounds SET min_x=\"$x\"");
echo mysql_error();
if($bounds['max_x'] < $x && ($x - $bounds['max_x']) < 20) mysql_query("UPDATE bounds SET max_x=\"$x\"");
echo mysql_error();
if($bounds['min_z'] > $z && ($bounds['min_z'] - $z) < 20) mysql_query("UPDATE bounds SET min_z=\"$z\"");
echo mysql_error();
if($bounds['max_z'] < $z && ($z - $bounds['max_z']) < 20) mysql_query("UPDATE bounds SET max_z=\"$z\"");
echo mysql_error();
echo "x: $x z: $z\n";
echo 'done';

}

?>