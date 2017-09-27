<?php 

// Integria IMS - http://integriaims.com
// ==================================================
// Copyright (c) 2007-2011 Artica Soluciones Tecnologicas
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// (LGPL) as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.

function get_download_files () {
	$base_dir = 'attachment/downloads';
	$files = list_files ($base_dir, "", 0, false);
	
	$retval = array ();
	foreach ($files as $file) {
		$retval[$file] = $file;
	}
	
	return $retval;
}

function delete_type_file ($id_download = false, $id_type = false) {
	
	if ($id_download && $id_type) {
		$result = process_sql_delete("tdownload_type_file", array('id_download' => $id_download, 'id_type' => $id_type));
	} elseif ($id_download) {
		$result = process_sql_delete("tdownload_type_file", array('id_download' => $id_download));
	} elseif ($id_type) {
		$result = process_sql_delete("tdownload_type_file", array('id_type' => $id_type));
	} else {
		$result = false;
	}

	return $result;
}

function insert_type_file ($id_download, $id_type) {
	
	delete_type_file ($id_download);

	$sql_insert = "INSERT INTO tdownload_type_file (id_download, id_type) VALUES ($id_download, $id_type)";
	$result = process_sql($sql_insert);

	return $result;
}

function get_file_types ($only_name = false, $all = false) {

	$types = process_sql("SELECT * FROM tdownload_type ORDER BY name DESC");

	if (!$all) {
		$condition = get_filter_by_fr_category_accessibility();

		$types_aux = array();

		foreach ($types as $type) {
			$result = process_sql("SELECT COUNT(tdownload.id) AS num_files
									FROM tdownload, tdownload_type_file
									WHERE tdownload.id = tdownload_type_file.id_download
										AND tdownload_type_file.id_type = ".$type['id']."
										$condition");
			
			if ($result) {
				$num_files = $result[0]['num_files'];
			} else {
				$num_files = 0;
			}

			if ($num_files > 0) {
				$types_aux[] = $type;
			}
		}
		$types = $types_aux;
		$types_aux = null;
	}

	if ($only_name) {
		$types_name = array();

		$types_name[-1] = __('Without type');
		foreach ($types as $type) {
			$types_name[$type["id"]] = $type["name"];
		}

		$types = $types_name;
		$types_name = null;
	}

	return $types;
}

function get_download_type_icon ($id_type) {
	$type = get_db_row("tdownload_type", "id", $id_type);

	if ($type) {
		$image = print_image("images/download_type/".$type["icon"], true, array('title' => safe_output($type["name"]), 'alt' => ''));
	} else {
		$image = print_image("images/download_type/default.png", true, array('title' => __('Without type'), 'alt' => ''));
	}
	
	return $image;
}

function get_file_releases ($id_category = 0, $id_type = 0, $limit = 0, $only_name = false) {
	
	$filter = "";
	if ($id_category > 0) {
		$filter .= " AND id_category = $id_category ";
	}
	if ($id_type > 0) {
		$filter .= " AND id IN (SELECT id_download FROM tdownload_type_file WHERE id_type = $id_type) ";
	} if ($id_type == -1) {
		$filter .= " AND id NOT IN (SELECT id_download FROM tdownload_type_file) ";
	}

	if ($limit < 1) {
		$limit = "";
	} else {
		$limit = "LIMIT $limit";
	}

	$file_releases = process_sql("SELECT * FROM tdownload WHERE 1=1 $filter ORDER BY date DESC $limit");

	if ($only_name) {
		$file_releases_name = array();

		foreach ($file_releases as $file_release) {
			$file_releases_name[$file_release["id"]] = $file_release["name"];
		}

		$file_releases = $file_releases_name;
		$file_releases_name = null;
	}

	return $file_releases;
}

function print_file_types_table ($return = false) {

	$condition = get_filter_by_fr_category_accessibility();

	$types = process_sql("SELECT tdownload_type.id AS id,
								tdownload_type.name AS name,
								tdownload_type.description AS description,
								tdownload_type.icon AS icon,
								COUNT(tdownload.id) AS num_files,
								MAX(tdownload.date) AS last_update
						FROM tdownload, tdownload_type, tdownload_type_file
						WHERE tdownload.id = tdownload_type_file.id_download
							AND tdownload_type.id = tdownload_type_file.id_type
							$condition
						GROUP BY name
						ORDER BY last_update DESC");

	if (!$types) {
		$types = array();
	}

	$without_type = process_sql("SELECT -1 AS id,
										'' AS name,
										'' AS description,
										'default.png' AS icon,
										COUNT(id) AS num_files,
										MAX(date) AS last_update
								FROM tdownload
								WHERE id NOT IN (SELECT id_download FROM tdownload_type_file)
									$condition
								ORDER BY last_update DESC");
	if ($without_type) {
		$without_type = $without_type[0];
		$without_type["name"] = __('Without type');

		if (!$types) {
			$types[0] = $without_type;
		}
		elseif ($types[0]["last_update"] < $without_type["last_update"]) {
			array_unshift($types, $without_type);
		} 
		elseif ($types[count($types)-1]["last_update"] > $without_type["last_update"]) {
			array_push($types, $without_type);
		}
		else {
			$types_aux = array();
			for ($i = 0; $i < count($types); $i++) {
				$types_aux[] = $types[$i];
				if (isset($types[$i]["last_update"]) && isset($types[$i+1]["last_update"])) {
					if ($types[$i]["last_update"] > $without_type["last_update"] && $types[$i+1]["last_update"] < $without_type["last_update"]) {
						$types_aux[] = $without_type;
					}
				}
			}
			$types = $types_aux;
			$types_aux = null;
		}
	}

	$table = new stdClass;
	$table->width = '100%';
	$table->class = 'blank';
	$table->style = array();
	$table->style[0] = "min-width:50%; width:50%; max-width:50%";
	$table->style[1] = "min-width:50%; width:50%; max-width:50%";
	$table->valign = array();
	$table->valign[0] = "top";
	$table->valign[1] = "top";
	$table->data = array();

	$column_num = 2;
	$column_count = 0;
	$column = 0;

	foreach ($types as $type) {
		
		$table_type = new stdClass;
		$table_type->width = '100%';
		$table_type->class = 'search-table-white';
		$table_type->style = array();
		$table_type->style[0] = "vertical-align:top; min-width:25px; width:25px; max-width:25px";
		$table_type->data = array();
		
		if ((int)$type["num_files"] > 0) {
			$file_releases = get_file_releases (0, $type["id"], 10, true);
			$fr_names = __('Last updated file releases') . ":";
			foreach ($file_releases as $fr) {
				$fr_names .= "\n" . $fr;
			}
		} else {
			$fr_names = "";
		}

		$table_type->data[0][0] = print_image("images/download_type/".$type["icon"], true);
		$table_type->data[0][1] = "<a style='font-weight: bold;' href='index.php?sec=download&sec2=operation/download/browse&id_type=".$type["id"]."'>".$type["name"]."</a>";
		$table_type->data[0][1] .= " <div style='display:inline;' title='$fr_names'>(".(int)$type["num_files"].")</div>";
		$table_type->data[0][1] .= "<br>";
		$table_type->data[0][1] .= "<div>".$type["description"]."</div>";
		$table_type->data[0][1] .= "<div style='color: #FF9933;'><i>".__('Last update').": ".human_time_comparation($type["last_update"])."</i></div>";;

		$table->data[0][$column] .= print_table($table_type, true);

		$column_count += 1;
		$column += 1;

		if ($column_count >= $column_num) {
			$column_count = 0;
			$column = 0;
		}
	}

	print_table($table, $return);

}

?>