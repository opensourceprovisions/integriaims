<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


/*Incident statistics constants*/
define("INCIDENT_METRIC_USER", "user_time");
define("INCIDENT_METRIC_STATUS", "status_time");
define("INCIDENT_METRIC_GROUP", "group_time");
define("INCIDENT_METRIC_TOTAL_TIME", "total_time");
define("INCIDENT_METRIC_TOTAL_TIME_NO_THIRD", "total_w_third");

/*Incident tracking constants*/
define ('INCIDENT_CREATED', 0);
define ('INCIDENT_UPDATED', 1);
define ('INCIDENT_WORKUNIT_ADDED', 2);
define ('INCIDENT_FILE_ADDED', 3);
define ('INCIDENT_NOTE_ADDED', 4);
define ('INCIDENT_FILE_REMOVED', 5);
define ('INCIDENT_PRIORITY_CHANGED', 6);
define ('INCIDENT_STATUS_CHANGED', 7);
define ('INCIDENT_RESOLUTION_CHANGED', 8);
define ('INCIDENT_NOTE_DELETED', 9);
define ('INCIDENT_INVENTORY_ADDED', 10);
define ('INCIDENT_USER_CHANGED', 17);
define ('INCIDENT_DELETED', 18);
define ('INCIDENT_CONTACT_ADDED', 19);
define ('INCIDENT_GROUP_CHANGED', 28);
define ('INCIDENT_INVENTORY_REMOVED', 20);

/*Task tracking constants*/
define ('TASK_CREATED', 11);
define ('TASK_UPDATED', 12);
define ('TASK_NOTE_ADDED', 13);
define ('TASK_WORKUNIT_ADDED', 14);
define ('TASK_FILE_ADDED', 15);
define ('TASK_COMPLETION_CHANGED', 16);
define ('TASK_FINISHED', 17);
define ('TASK_MEMBER_ADDED', 18);
define ('TASK_MOVED', 19);
define ('TASK_MEMBER_DELETED', 20);

/*Project tracking constants*/
define ('PROJECT_CREATED', 21);
define ('PROJECT_UPDATED', 22);
define ('PROJECT_DISABLED', 23);
define ('PROJECT_ACTIVATED', 24);
define ('PROJECT_DELETED', 25);
define ('PROJECT_TASK_ADDED', 26);
define ('PROJECT_TASK_DELETED', 27);

// Incident status constants
define ('STATUS_NEW', 1);
define ('STATUS_UNCONFIRMED', 2);
define ('STATUS_ASSIGNED', 3);
define ('STATUS_REOPENED', 4);
define ('STATUS_VERIFIED', 5);
define ('STATUS_RESOLVED', 6);
define ('STATUS_PENDING_THIRD_PERSON', 6);
define ('STATUS_CLOSED', 7);

// Incident status colors constants
define ('STATUS_COLOR_NEW', '#FF3333');
define ('STATUS_COLOR_PENDING', '#FFCC33');
define ('STATUS_COLOR_CLOSED', '#66CC00');

// Incident resolution constants
define ('RES_FIXED', 1);
define ('RES_INVALID', 2);
define ('RES_WONTFIX', 3);
define ('RES_DUPLICATE', 4);
define ('RES_WORKSFORME', 5);
define ('RES_INCOMPLETE', 6);
define ('RES_EXPIRED', 7);
define ('RES_MOVED', 8);
define ('RES_INPROCESS', 9);

//Incident priority values
define ('PRIORITY_INFORMATIVE', 0);
define ('PRIORITY_LOW', 1);
define ('PRIORITY_MEDIUM', 2);
define ('PRIORITY_SERIOUS', 3);
define ('PRIORITY_VERY_SERIOUS', 4);
define ('PRIORITY_MAINTENANCE', 10);

//Incident priority colors
define ('PRIORITY_COLOR_INFORMATIVE', '#999999');
define ('PRIORITY_COLOR_LOW', '#66CC00');
define ('PRIORITY_COLOR_MEDIUM', '#FFCC33');
define ('PRIORITY_COLOR_SERIOUS', '#FF9900');
define ('PRIORITY_COLOR_VERY_SERIOUS', '#FF3333');
define ('PRIORITY_COLOR_MAINTENANCE', '#3399FF');

/*Inventory tracking constants*/
define ('INVENTORY_CREATED', 0);
define ('INVENTORY_UPDATED', 1);
define ('INVENTORY_INCIDENT_ADDED', 2);
define ('INVENTORY_OWNER_CHANGED', 3);
define ('INVENTORY_PARENT_UPDATED', 4);
define ('INVENTORY_PARENT_CREATED', 5);
define ('INVENTORY_OBJECT_TYPE', 6);
define ('INVENTORY_PUBLIC', 7);
define ('INVENTORY_PRIVATE', 8);
define ('INVENTORY_NAME_CHANGED', 9);
define ('INVENTORY_CONTRACT_CHANGED', 10);
define ('INVENTORY_MANUFACTURER_CHANGED', 11);
define ('INVENTORY_STATUS_CHANGED', 12);
define ('INVENTORY_OBJECT_TYPE_CHANGED', 13);
define ('INVENTORY_RECEIPT_DATE_CHANGED', 14);
define ('INVENTORY_ISSUE_DATE_CHANGED', 15);
define ('INVENTORY_DESCRIPTION_CHANGED', 16);
define ('INVENTORY_COMPANIES_CREATED', 17);
define ('INVENTORY_COMPANIES_UPDATED', 18);
define ('INVENTORY_USERS_CREATED', 19);
define ('INVENTORY_USERS_UPDATED', 20);
define ('INCIDENT_GOLD_MEDAL_ADDED', 29);
define ('INCIDENT_GOLD_MEDAL_REMOVED', 30);
define ('INCIDENT_BLACK_MEDAL_ADDED', 31);
define ('INCIDENT_BLACK_MEDAL_REMOVED', 32);

/*Text truncate constant*/
define('GENERIC_SIZE_TEXT', 25);

/* Seconds in a time unit constants */
define('SECONDS_1MINUTE', 60);
define('SECONDS_2MINUTES', 120);
define('SECONDS_5MINUTES', 300);
define('SECONDS_10MINUTES', 600);
define('SECONDS_15MINUTES', 900);
define('SECONDS_30MINUTES', 1800);
define('SECONDS_1HOUR', 3600);
define('SECONDS_2HOUR', 7200);
define('SECONDS_3HOUR', 10800);
define('SECONDS_5HOUR', 18000);
define('SECONDS_6HOURS', 21600);
define('SECONDS_12HOURS', 43200);
define('SECONDS_1DAY', 86400);
define('SECONDS_2DAY', 172800);
define('SECONDS_4DAY', 345600);
define('SECONDS_5DAY', 432000);
define('SECONDS_1WEEK', 604800);
define('SECONDS_10DAY', 864000);
define('SECONDS_2WEEK', 1209600);
define('SECONDS_15DAYS', 1296000);
define('SECONDS_1MONTH', 2592000);
define('SECONDS_2MONTHS', 5184000);
define('SECONDS_3MONTHS', 7776000);
define('SECONDS_6MONTHS', 15552000);
define('SECONDS_1YEAR', 31104000);
define('SECONDS_2YEARS', 62208000);
define('SECONDS_3YEARS', 93312000);

define('SUBTYPE_TICKETS', 1);

?>
