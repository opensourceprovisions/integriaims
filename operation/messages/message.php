<?php

// Integria 2.0 - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
global $config;


function create_message($usuario_origen, $usuario_destino, $subject, $mensaje){
	global $config;
	$ahora=date("Y/m/d H:i:s");
	
	$sql='INSERT INTO tmensajes (id_usuario_origen, id_usuario_destino, subject, mensaje, timestamp) VALUES ("'.$usuario_origen.'", "'.$usuario_destino.'", "'.$subject.'", "'.$mensaje.'","'.$ahora.'")';
	$result=mysql_query($sql);
	if ($result)
		echo "<h3 class='suc'>".__('Message successfully sent')."</h3>";
	else 
		echo "<h3 class='error'>".__('There was a problem sending message')."</h3>";
	}

function create_message_g($usuario_origen, $usuario_destino, $subject, $mensaje){
	global $config;
	$ahora=date("Y/m/d H:i:s");
	$sql='INSERT INTO tmensajes (id_usuario_origen, id_usuario_destino, subject, mensaje, timestamp) VALUES ("'.$usuario_origen.'", "'.$usuario_destino.'", "'.$subject.'", "'.$mensaje.'","'.$ahora.'")';
	$result=mysql_query($sql);
	if ($result)
		return 0;
	return 1;
}

//First Queries
$iduser=$_SESSION['id_usuario'];

$sql2='SELECT COUNT(*) FROM tmensajes WHERE id_usuario_destino="'.$iduser.'" AND estado="FALSE";';
$resultado2=mysql_query($sql2);
$row2=mysql_fetch_array($resultado2);
$sql3='SELECT * FROM tgrupo';
$resultado3=mysql_query($sql3);
	
if (isset($_GET["nuevo_mensaje"])){
	// Create message
	$usuario_destino = get_parameter ("u_destino");
	$subject = get_parameter ("subject");
	$mensaje = get_parameter ("mensaje");
	create_message($iduser, $usuario_destino, $subject, $mensaje);
}

if (isset($_GET["nuevo_mensaje_g"])){
	// Create message to groups
	$grupo_destino = get_parameter ("g_destino");
	$subject = get_parameter ("subject");
	$mensaje = get_parameter ("mensaje");
	$sql= 'SELECT id_usuario FROM tusuario_perfil WHERE id_grupo ='. $grupo_destino;
	$result = mysql_query($sql);

	if (mysql_fetch_row($result)){
		while ($row=mysql_fetch_array($result)){
			$error=create_message_g($iduser, $row["id_usuario"], $subject, $mensaje);
		}
		if ($error==0) echo "<h3 class='suc'>".__('Message successfully sended')."</h3>";
		else echo "<h3 class='error'>".__('There was a problem sending message')."</h3>";
	}
	else {echo "<h3 class='error'>".__('There was a problem sending message')."</h3>";}
}
if (isset($_GET["nuevo"]) || isset($_GET["nuevo_g"])){
	if (isset($_GET["nuevo"])){ //create message
		echo '<h2>'.__('New message').'</h2>';
		echo '
		<form name="new_mes" method="POST" action="index.php?sec=messages&sec2=operation/messages/message&nuevo_mensaje=1">
		<table width=500 class="databox_color">
		<tr><td class="datos">'.__('From').':</td><td class="datos"><b>'.$iduser.'</b></td></tr>
		<tr><td class="datos2">'.__('To').':</td><td>';
		if (isset($_POST["u_destino"])) {
			echo '<b>'.$_POST["u_destino"].'</b><input type="hidden" name="u_destino" value='.$_POST["u_destino"].'>';
			}
		else{
			echo '<select name="u_destino" width="120">';

			$sql_1="SELECT * FROM tusuario_perfil WHERE id_usuario = '$iduser'";
			$result_1=mysql_query($sql_1);
			while ($row_1=mysql_fetch_array($result_1)){
				$sql_2="SELECT * FROM tusuario_perfil WHERE id_grupo = ".$row_1["id_grupo"];
				$result_2=mysql_query($sql_2);
				while ($row_2=mysql_fetch_array($result_2)){
					if (give_acl($row_2["id_usuario"], $row_2["id_grupo"], "IR")==1)
					echo "<option value='".$row_2["id_usuario"]."'>".$row_2["id_usuario"]."&nbsp;&nbsp;";
				}
			}
			echo '</select>';
			}
		echo '</td></tr>
		<tr><td class="datos">'.__('Subject').':</td><td class="datos">';
			if (isset($_POST["subject"])) 
				echo '</b><input name="subject" value="'.$_POST["subject"].'" size=60>';
			else 
				echo '<input name="subject" size=60>';
		echo '</td></tr>
		<tr><td class="datos2">'.__('Message').':</td>
		<td class="datos2"><textarea name="mensaje" rows="12" cols=60 >';
			if (isset($_POST["mensaje"])) {
				echo $_POST["mensaje"];
			}
		echo '</textarea></td></tr>
		</table>
		<input type="submit" class="sub create" name="send_mes" value="'.__('Send message').'"></form>';
	}
	
	if (isset($_GET["nuevo_g"])){
		echo '<h2>'.__('New message').'<a href="help/'.$help_code.'/chap2.php#251" target="_help" class="help">&nbsp;<span>'.__('Help').'</span></a></h2>';
		echo '
		<form name="new_mes" method="post" action="index.php?sec=messages&sec2=operation/messages/message&nuevo_mensaje_g=1">
		<table class="databox_color">
		<tr><td class="datos">'.__('From').':</td><td class="datos"><b>'.$iduser.'</b></td></tr>
		<tr><td class="datos2">'.__('To').':</td><td class="datos2">';
			echo '<select name="g_destino" class="w130">';

			$sql_1="SELECT id_grupo FROM tusuario_perfil WHERE id_usuario = '$iduser'";
			$result_1=mysql_query($sql_1);
			while ($row_1=mysql_fetch_array($result_1)){
				echo "<option value=".$row_1["id_grupo"].">".dame_nombre_grupo($row_1["id_grupo"]);
				
			}
			echo '</select>';
		echo '</td></tr>
		<tr><td class="datos">'.__('Subject').':</td><td class="datos"><input name="subject" size=60></td></tr>
		<tr><td class="datos2">'.__('Message').':</td>
		<td class="datos"><textarea name="mensaje" rows="12" cols=60></textarea></td></tr>
		</table>
		<input type="submit" class="sub create" name="send_mes" value="'.__('Send message').'"></form>';
	}
}
else {

	// Get list of messages for this user
	if (isset($_GET["borrar"])){
		$id_mensaje = $_GET["id_mensaje"];
		$sql5='DELETE FROM tmensajes WHERE id_usuario_destino="'.$iduser.'" AND id_mensaje="'.$id_mensaje.'"';
		$resultado5=mysql_query($sql5);
		if ($resultado5) {echo "<h3 class='suc'>".__('Message sucessfully deleted')."</h3>";}
		else {echo "<h3 class='suc'>".__('There was a problem deleting message')."</h3>";}
	}
	
	echo "<h2>".__('Read messages')."</h2>";
	if ($row2["COUNT(*)"] != 0) {
		$messages = $row2["COUNT(*)"];
		echo '<p>';
		echo __("You have <strong>$messages</strong> unread message(s)");
		echo '<img src="images/mail.gif">';
		echo '</p>';
	}
	$sql3='SELECT * FROM tmensajes WHERE id_usuario_destino="'.$iduser.'"';
	$resultado3=mysql_query($sql3);
	$color=1;
	if (mysql_num_rows($resultado3)) {
		echo "<table width=500 class='databox'><tr><th>".__('Read')."</th><th>".__('Sender')."</th><th>".__('Subject')."</th><th>".__('Timestamp')."</th><th>".__('Delete')."</th></tr>";
		while ($row3=mysql_fetch_array($resultado3)){
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
				}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr>";
			if ($row3["estado"]==1) 
				echo "<td align='center' class='$tdcolor'><img src='images/email_open.png' border=0></td>";
			else 
				echo "<td align='center' class='$tdcolor'><img src='images/email_go.png' border=0></td>";

			echo "<td class='$tdcolor'>";
			echo "<b><a href=index.php?sec=usuarios&sec2=operation/users/user_edit&id=".$row3["id_usuario_origen"].">".$row3["id_usuario_origen"]."</b></td><td class='".$tdcolor."'><a href='index.php?sec=messages&sec2=operation/messages/message&leer=1&id_mensaje=".$row3["id_mensaje"]."'>";

			if ($row3["subject"]) 
				echo $row3["subject"]."</a>";
			else 
				echo "<i>".__('No subject')."</i></a>";

			echo "</a></td><td class='".$tdcolor."'>".$row3["timestamp"]."</td>";
			echo "<td class='$tdcolor' align='center'><a href='index.php?sec=messages&sec2=operation/messages/message&borrar=1&id_mensaje=".$row3["id_mensaje"]."'><img src='images/cross.png' border='0'></a></td></tr>";
			}
		echo "</table>";
	}
	else 
		echo "<p>".__('There are no messages')."</p>"; //no messages
	
	// Read message
	if (isset($_GET["leer"])){
		$id_mensaje = $_GET["id_mensaje"];
		$sql4='SELECT * FROM tmensajes WHERE id_usuario_destino="'.$iduser.'" AND id_mensaje="'.$id_mensaje.'"';
		$sql41='UPDATE tmensajes SET estado="1" WHERE id_mensaje="'.$id_mensaje.'"';
		$resultado4=mysql_query($sql4);
		$row4=mysql_fetch_array($resultado4);
		$resultado41=mysql_query($sql41);
		echo '<table class="databox_color" width=500>';
		echo '<form method="post" name="reply_mes" action="index.php?sec=messages&sec2=operation/messages/message&nuevo">';
		echo '<tr><td class="datos">'.__('From').':</td><td class="datos"><b>'.$row4["id_usuario_origen"].'</b></td></tr>';
		// Subject
		echo '<tr><td class="datos2">'.__('Subject').':</td><td class="datos2" valign="top"><b>'.$row4["subject"].'</b></td></tr>';
		// text
		echo '<tr><td class="datos" valign="top">'.__('Message').':</td>
		<td class="datos"><textarea name="mensaje" rows="12" cols=50 readonly>'.$row4["mensaje"].'</textarea></td></tr>
		</table>';
		echo '
		<input type="hidden" name="u_destino" value="'.$row4["id_usuario_origen"].'">
		<input type="hidden" name="subject" value="Re: '.$row4["subject"].'">
		<input type="hidden" name="mensaje" value="'.$row4["id_usuario_origen"].' '.__('wrote').': '.$row4["mensaje"].'">';
		echo '<input type="submit" class="sub create" name="send_mes" value="'.__('Reply').'">';
		echo '</form>';
	}
	
	echo "<table>";
	echo "<tr><td><img src='images/email_open.png'> Message already opened";
	echo "<tr><td><img src='images/email_go.png'> Message unreaded";
	echo "</table>";
}

?>
