-----------------------------------------------------
---------------- tmenu_visibility -------------------
-----------------------------------------------------
DELETE FROM tmenu_visibility WHERE id = 3;

INSERT INTO `tmenu_visibility` (`menu_section`,`id_group`,`mode`) VALUES ('projects',1,0),('reports',1,0);
