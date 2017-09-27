<?php

include_once ('../include/functions_db.mysql.php');

///FUNCTIONS

function print_help() {
	echo "HELP: \n\n";
	echo "<php import_users_from_csv.php <file_csv> <config_file> <assigned_by> <id_group> <profile> <global_profile> <password_policy> [<log_file>]\n\n";
	echo "file_csv: fichero csv para importar los usuarios\n";
	echo "config_file: fichero de configuración con los datos de conexión a la base de datos\n";
	echo "assigned_by: quien crea el usuario\n";
	echo "id_group: grupo al que pertenecen los usuarios\n";
	echo "profile: perfil del usuario: \n";
	echo "global_profile: puede ser un usuario estándar o un usuario externo\n";
	echo "password_policy: indica si está habilitada la política de password\n";
	echo "log_file: (opcional) fichero donde se van a guardar los eventos durante la carga de usuarios\n\n";
	echo "EXAMPLE: php import_users_from_csv.php config_file.conf 1 2 1 0\n";

	exit;
}

function parse_config ($config_file) {
	
	if (file_exists($config_file)) {
		$fp = fopen($config['homedir']."/extras/".$config_file, 'r');
		
		$connection = array();
		if ($fp != false) {
			while (!feof($fp)) {
				$buffer = fgets($fp);

				if (preg_match('/db_host=([\w*\s*\.*]*)/', $buffer, $matchs)) {
					$connection['db_host'] = rtrim($matchs[1]);
				}
				
				if (preg_match('/db_user=([\w*\s*]*)/', $buffer, $matchs)) {
					$connection['db_user'] = rtrim($matchs[1]);
				}
				if (preg_match('/db_name=([\w*\s*]*)/', $buffer, $matchs)) {
					$connection['db_name'] = rtrim($matchs[1]);
				}
				if (preg_match('/db_pass=([\w*\s*]*)/', $buffer, $matchs)) {
					$connection['db_pass'] = rtrim($matchs[1]);
				}
			}
			fclose($fp);
			return $connection;
		}
	} else {
		echo "[ERROR] CONFIGURATION FILE NOT EXITS\n";
	}
}


function get_all_rows_sql ($sql) {
	$return = process_sql ($sql);
	
	if (! empty ($return))
		return $return;

	return false;
}

function get_value ($field, $table, $field_search = 1, $condition = 1) {
	if (is_int ($condition)) {
		$sql = sprintf ("SELECT %s FROM %s WHERE %s = %d LIMIT 1",
				$field, $table, $field_search, $condition);
	}
	else if (is_float ($condition) || is_double ($condition)) {
		$sql = sprintf ("SELECT %s FROM %s WHERE %s = %f LIMIT 1",
				$field, $table, $field_search, $condition);
	}
	else {
		$sql = sprintf ("SELECT %s FROM %s WHERE %s = '%s' LIMIT 1",
				$field, $table, $field_search, $condition);
	}
	$result = get_all_rows_sql ($sql);
	
	if ($result === false)
		return false;
	if ($field[0] == '`')
		$field = str_replace ('`', '', $field);
	return $result[0][$field];
}

function sql_insert ($table, $values) {
	 //Empty rows or values not processed
	if (empty ($values))
		return false;
	
	$values = (array) $values;
		
	$query = sprintf ("INSERT INTO `%s` ", $table);
	$fields = array ();
	$values_str = '';
	$i = 1;
	$max = count ($values);
	foreach ($values as $field => $value) { //Add the correct escaping to values
		if ($field[0] != "`") {
			$field = "`".$field."`";
		}
		
		array_push ($fields, $field);
		
		if (is_null ($value)) {
			$values_str .= "NULL";
		} elseif (is_int ($value) || is_bool ($value)) {
			$values_str .= sprintf ("%d", $value);
		} else if (is_float ($value) || is_double ($value)) {
			$values_str .= sprintf ("%f", $value);
		} else {
			$values_str .= sprintf ("'%s'", $value);
		}
		
		if ($i < $max) {
			$values_str .= ",";
		}
		$i++;
	}
	
	$query .= '('.implode (', ', $fields).')';
	
	$query .= ' VALUES ('.$values_str.')';
	
	return process_sql ($query, 'insert_id');
}

//CONEXIÓN A LA BASE DE DATOS
function connection_db ($connection) {
	
	$db_server = $connection['db_host'];
	$db_user = $connection['db_user'];
	$db_name = $connection['db_name'];
	$db_pass = $connection['db_pass'];

	$conexion = mysql_connect($db_server, $db_user, $db_pass);
	$select_db = mysql_select_db ($db_name, $conexion);
}

//ESCRIBIR LOG DE CARGA DE USUARIOS
function print_log ($log_file = false, $msg) {
	
	if (!$log_file) {
		$log_file = 'create_user.log';
	}
	
	$f_log = fopen ($log_file, 'a');
	
	fwrite ($f_log, $msg);
	fclose ($f_log);
	
}

//CARGA EL FICHERO CSV E INSERTA LOS USUARIOS EN LA BASE DE DATOS
function load_file ($users_file, $assigned_by, $group, $profile, $nivel, $pass_policy, $file_log = false) {
	
	if (file_exists($users_file)) {
		$file_handle = fopen($users_file, "r");

		while (!feof($file_handle)) {
			$line = fgets($file_handle);
			
			preg_match_all('/(.*),/',$line,$matches);

			$values = explode(',',$line);

			if ($values[0] != "") {
				$id_usuario = $values[0];
				$pass = $values[1];
				$pass = md5($pass);
				$nombre_real = $values[2];
				$mail = $values[3];
				$tlf = $values[4];
				$desc = $values[5];
				$avatar = $values[6];
				$disabled = $values[7];
				$id_company = $values[8];
				$num_employee = $values[9];
				$enable_login = $values[10];
				$force_change_pass = 0;
				
				if ($pass_policy) {
					$force_change_pass = 1;
				}
				
				$value = array(
					'id_usuario' => $id_usuario,
					'nombre_real' => $nombre_real,
					'password' => $pass,
					'comentarios' => $desc,
					'direccion' => $mail,
					'telefono' => $tlf,
					'nivel' => $nivel,
					'avatar' => $avatar,
					'disabled' => $disabled,
					'id_company' => $id_company,
					'num_employee' => $num_employee,
					'enable_login' => $enable_login,
					'force_change_pass' => $force_change_pass);
					
					if (($id_usuario!='')&&($nombre_real!='')){
						if ($id_usuario == get_value ('id_usuario', 'tusuario', 'id_usuario', $id_usuario)){
							$msg = "[".date('d/m/Y-H:m:s')."] [ERROR] USER ".$id_usuario." ALREADY EXISTS\n";
							echo $msg;
							print_log($file_log, $msg);
						} else {
							$resul = sql_insert('tusuario', $value);
			
							if ($resul==false){
								$value2 = array(
									'id_usuario' => $id_usuario,
									'id_perfil' => $profile,
									'id_grupo' => $group,
									'assigned_by' => $assigned_by
								);
								
								if ($id_usuario!=''){
									sql_insert('tusuario_perfil', $value2);
								}
							}
						}
					}
			}		
		}

		fclose($file_handle);
		echo "[".date('d/m/Y-H:m:s')."] [INFO] FILE LOADED\n";
		return;
	} else {
		echo "[".date('d/m/Y-H:m:s')."] [ERROR] CSV FILE NOT EXISTS\n";
	}
}
/// END FUNCTIONS

//check number of parameters
if (($argc < 8) || ($argc > 9)) {
	echo "[ERROR] INVALID NUMBER OF PARAMETERS\n";
	print_help();
}

if (isset($argv[1])) {
	$file = $argv[1];
	if ($file == 'help') {
		print_help();
	}
} else {
	echo "ERROR: Missing CSV File.\n";
	exit;
}

if (isset($argv[2])) {
	$config_file = $argv[2];
	$connection = parse_config ($config_file);
} else {
	echo "ERROR: Missing Configuration File.\n";
	exit;
}

if (isset($argv[3])) {
	$assigned_by = $argv[3];
} else {
	echo "ERROR: Missing Assigned by user.\n";
	exit;
}

if (isset($argv[4])) {
	$group = $argv[4];
} else {
	echo "[ERROR] Missing Group.\n";
	exit;
}
	
if (isset($argv[5])) {	
	$profile = $argv[5];
} else {
	echo "[ERROR] Missing Profile.\n";
	exit;
}	
if (isset($argv[6])) {	
	$nivel = $argv[6];
} else {
	echo "[ERROR] Missing Global Profile.\n";
	exit;
}
if (isset($argv[7])) {	
	$pass_policy = $argv[7];
} else {
	echo "[ERROR] Missing Password Policy.\n";
	exit;
}

$log_file = false;
if (isset($argv[8])) {	
	$log_file = $argv[8];
}

if (isset($connection)) {
	connection_db ($connection);
} else {
	echo "[ERROR] Error loading config file.\n";
	exit;
}

load_file ($file, $assigned_by, $group, $profile, $nivel, $pass_policy, $log_file);

?>
