<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor!
 */
$startDate = date('Y-m-d');
$endDate = date('Y-m-d', strtotime('+1 week'));
$users = array('lis');
$events = array();
require('zendcal.php');
echo $events[0]->_title;
?>
