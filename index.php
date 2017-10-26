<?php
/*
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * Or, point your browser to http://www.gnu.org/copyleft/gpl.html
 * 
 */

/*
Header is needed to initialize all classes and check if a cookie exists
on client side
*/
include("include/header.php");

/*
Main authentification : if session variable have not been initialised, go 
to login form, else verify datas entered in this form
*/
if(!isset($_SESSION['id']) || $_SESSION['valid'] != 'Y') {
	if(!isset($_REQUEST['username']) || !isset($_REQUEST['password'])) {
		loginform();
	} else {
	$verification = new Login;
	$verification->identify($_REQUEST['username'],$_REQUEST['password']);
	}
} else {
/*

*/
include("include/whattodo.php");
}
?>
