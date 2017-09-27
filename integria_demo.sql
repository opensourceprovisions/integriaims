-- MySQL dump 10.13  Distrib 5.1.66, for redhat-linux-gnu (i386)
--
-- Host: localhost    Database: integria
-- ------------------------------------------------------
-- Server version	5.1.66

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `tagenda`
--

LOCK TABLES `tagenda` WRITE;
/*!40000 ALTER TABLE `tagenda` DISABLE KEYS */;
INSERT INTO `tagenda` VALUES (1,'2013-05-29 10:02:00','admin',0,0,0,'Elena&#039;s&#x20;Aniversary','I need to search something cool for her');
/*!40000 ALTER TABLE `tagenda` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tattachment`
--

LOCK TABLES `tattachment` WRITE;
/*!40000 ALTER TABLE `tattachment` DISABLE KEYS */;
/*!40000 ALTER TABLE `tattachment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tbuilding`
--

LOCK TABLES `tbuilding` WRITE;
/*!40000 ALTER TABLE `tbuilding` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbuilding` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tcompany`
--

LOCK TABLES `tcompany` WRITE;
/*!40000 ALTER TABLE `tcompany` DISABLE KEYS */;
INSERT INTO `tcompany` VALUES (1,'Your&#x20;big&#x20;company',
	'calle&#x20;Alberto&#x20;Aguilera&#x20;7,&#x20;3&#x0d;&#x0a;28015&#x20;Madrid&#x0d;&#x0a;Espa&ntilde;a&#x0d;&#x0a;',
	'324234324N','Spain','http://www.artica.es','',0,0,'admin','0000-00-00 00:00:00',30);
/*!40000 ALTER TABLE `tcompany` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tcompany_activity`
--

LOCK TABLES `tcompany_activity` WRITE;
/*!40000 ALTER TABLE `tcompany_activity` DISABLE KEYS */;
/*!40000 ALTER TABLE `tcompany_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tcompany_contact`
--

LOCK TABLES `tcompany_contact` WRITE;
/*!40000 ALTER TABLE `tcompany_contact` DISABLE KEYS */;
INSERT INTO `tcompany_contact` VALUES (1,1,'Sancho&#x20;Lerena','slerena@artica.es','324324343','32423434','CTO','This&#x20;guy&#x20;knows&#x20;everything&#x20;about&#x20;Integria&#x20;IMS',0);
/*!40000 ALTER TABLE `tcompany_contact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tcompany_role`
--

LOCK TABLES `tcompany_role` WRITE;
/*!40000 ALTER TABLE `tcompany_role` DISABLE KEYS */;
INSERT INTO `tcompany_role` VALUES (1,'Customer',''),(2,'Ex-Customer',''),(3,'Partner',''),(4,'Provider',''),(5,'Other','');
/*!40000 ALTER TABLE `tcompany_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tconfig`
--

LOCK TABLES `tconfig` WRITE;
/*!40000 ALTER TABLE `tconfig` DISABLE KEYS */;
INSERT INTO `tconfig` (`token`, `value`) VALUES ('enterprise_installed','0'),('timezone','Europe/Madrid'),('want_chat','0'),('incident_creation_wu','0'),('lead_warning_time','7'),('months_to_delete_incidents','12'),('max_file_size','50M'),('autowu_completion','0'),('no_wu_completion',''),('incident_reporter','0'),('show_creator_incident','1'),('show_owner_incident','1'),('pwu_defaultime','4'),('iwu_defaultime','0.25'),('api_acl','127.0.0.1'),('auto_incident_close','72'),('email_on_incident_update','0'),('error_log','1'),('iw_creator_enabled','0'),('update_manager_installed', 1),('license', 'INTEGRIA-FREE'),('current_package', 58),('minor_release', 56);
/*!40000 ALTER TABLE `tconfig` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tcontract`
--

LOCK TABLES `tcontract` WRITE;
/*!40000 ALTER TABLE `tcontract` DISABLE KEYS */;
INSERT INTO `tcontract` VALUES (1,'Sample&#x20;contract','453457/12','','2013-02-12','2014-02-12',1,NULL,NULL,0,1);
/*!40000 ALTER TABLE `tcontract` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tcrm_template`
--

LOCK TABLES `tcrm_template` WRITE;
/*!40000 ALTER TABLE `tcrm_template` DISABLE KEYS */;
/*!40000 ALTER TABLE `tcrm_template` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tcustom_search`
--

LOCK TABLES `tcustom_search` WRITE;
/*!40000 ALTER TABLE `tcustom_search` DISABLE KEYS */;
/*!40000 ALTER TABLE `tcustom_search` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tdownload`
--

LOCK TABLES `tdownload` WRITE;
/*!40000 ALTER TABLE `tdownload` DISABLE KEYS */;
INSERT INTO `tdownload` VALUES (1,'Sample&#x20;File','attachment/downloads/ejemplo.sh','2013-02-12 10:03:52','This&#x20;is&#x20;a&#x20;sample&#x20;file&#x20;for&#x20;public&#x20;file.&#x20;This&#x20;can&#x20;be&#x20;shared&#x20;by&#x20;external&#x20;HASH&#x20;URL','',1,'admin',1,'048030125bdfd9c63c4c7396b057ee118ec8c483');
/*!40000 ALTER TABLE `tdownload` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tdownload_category`
--

LOCK TABLES `tdownload_category` WRITE;
/*!40000 ALTER TABLE `tdownload_category` DISABLE KEYS */;
INSERT INTO `tdownload_category` VALUES (1,'Software','pandora.png');
/*!40000 ALTER TABLE `tdownload_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tdownload_category_group`
--

LOCK TABLES `tdownload_category_group` WRITE;
/*!40000 ALTER TABLE `tdownload_category_group` DISABLE KEYS */;
INSERT INTO `tdownload_category_group` VALUES (1,1);
/*!40000 ALTER TABLE `tdownload_category_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tdownload_tracking`
--

LOCK TABLES `tdownload_tracking` WRITE;
/*!40000 ALTER TABLE `tdownload_tracking` DISABLE KEYS */;
/*!40000 ALTER TABLE `tdownload_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tevent`
--

LOCK TABLES `tevent` WRITE;
/*!40000 ALTER TABLE `tevent` DISABLE KEYS */;
INSERT INTO `tevent` VALUES (1,'OBJECT TYPE CREATED','2013-02-12 09:24:28','admin',1,0,'Computer'),(2,'OBJECT TYPE CREATED','2013-02-12 09:24:39','admin',1,1,'Serial&#x20;Number'),(3,'OBJECT TYPE CREATED','2013-02-12 09:24:47','admin',2,1,'CPU'),(4,'OBJECT TYPE CREATED','2013-02-12 09:24:56','admin',3,1,'Memory'),(5,'OBJECT TYPE CREATED','2013-02-12 09:25:10','admin',4,1,'IP&#x20;Address'),(6,'OBJECT TYPE CREATED','2013-02-12 09:25:20','admin',5,1,'MAC&#x20;Address'),(7,'MANUFACTURER CREATED','2013-02-12 09:26:42','admin',1,0,'Arduino&#x20;Researc'),(8,'MANUFACTURER','2013-02-12 09:26:50','admin',1,0,'Arduino&#x20;Researchs'),(9,'COMPANY ROLE CREATED','2013-02-12 09:27:02','admin',1,0,'Customer'),(10,'COMPANY ROLE CREATED','2013-02-12 09:27:08','admin',2,0,'Ex-Customer'),(11,'COMPANY ROLE CREATED','2013-02-12 09:27:13','admin',3,0,'Partner'),(12,'COMPANY ROLE CREATED','2013-02-12 09:27:18','admin',4,0,'Provider'),(13,'COMPANY ROLE CREATED','2013-02-12 09:27:22','admin',5,0,'Other'),(14,'COMPANY CREATED','2013-02-12 09:29:01','admin',1,0,'Your&#x20;big&#x20;company'),(15,'INCIDENT TYPE CREATED','2013-02-12 09:31:13','admin',1,0,'Software&#x20;issues'),(16,'PWU INSERT','2013-02-12 10:00:15','admin',3,0,'lot&#039;s&#x20;of&#x20;work&#x20;done&#x20;today&#x20;:&#41;'),(17,'PWU INSERT','2013-02-12 10:01:31','admin',0,0,'days&#x20;of&#x20;holidays'),(18,'PWU INSERT','2013-02-12 10:01:31','admin',3,0,'Doing&#x20;something&#x20;very&#x20;weird&#x20;:&#41;'),(19,'INSERT CALENDAR EVENT','2013-02-12 10:02:36','admin',0,0,'Elena&#039;s&#x20;Aniversary'),(20,'DOWNLOAD CATEGORY CREATED','2013-02-12 10:03:08','admin',1,0,'Software'),(21,'DOWNLOAD ITEM CREATED','2013-02-12 10:03:52','admin',1,0,'Sample&#x20;File'),(22,'CATEGORY CREATED','2013-02-12 10:04:23','admin',1,0,'Articles'),(23,'PRODUCT CREATED','2013-02-12 10:04:39','admin',1,0,'Packages'),(24,'KB ITEM CREATED','2013-02-12 10:05:25','admin',1,0,'Restart&#x20;resolution&#x20;on&#x20;vertical&#x20;upload&#x20;change');
/*!40000 ALTER TABLE `tevent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tholidays`
--

LOCK TABLES `tholidays` WRITE;
/*!40000 ALTER TABLE `tholidays` DISABLE KEYS */;
/*!40000 ALTER TABLE `tholidays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tincidencia`
--

LOCK TABLES `tincidencia` WRITE;
/*!40000 ALTER TABLE `tincidencia` DISABLE KEYS */;
INSERT INTO `tincidencia` VALUES (1,'2013-02-12 09:50:27','0000-00-00 00:00:00','Something&#x20;seems&#x20;to&#x20;be&#x20;wrong&#x20;on&#x20;Mauertbe','THis&#x20;should&#x20;be&#x20;fixed&#x20;ASAP.&#x20;Seems&#x20;to&#x20;have&#x20;something&#x20;wrong&#x20;in&#x20;XXX&#x20;','demo',3,3,2,'2013-02-12 09:53:45','admin',1,0,'',NULL,0,0,1,0,'','admin',3,'2013-02-12 09:50:29','', '',0,0,0,0,0,'','','',0,0);
/*!40000 ALTER TABLE `tincidencia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tincident_contact_reporters`
--

LOCK TABLES `tincident_contact_reporters` WRITE;
/*!40000 ALTER TABLE `tincident_contact_reporters` DISABLE KEYS */;
/*!40000 ALTER TABLE `tincident_contact_reporters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tincident_field_data`
--

LOCK TABLES `tincident_field_data` WRITE;
/*!40000 ALTER TABLE `tincident_field_data` DISABLE KEYS */;
INSERT INTO `tincident_field_data` VALUES (1,1,1,'3.1'),(2,1,2,'Centos&#x20;6.2'),(3,1,3,'Execute&#x20;&#x0d;&#x0a;&#x0d;&#x0a;./ewiruer.sh'),(4,1,4,'Return&#x20;OK,&#x20;instead&#x20;gives&#x20;a&#x20;CRASH');
/*!40000 ALTER TABLE `tincident_field_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tincident_inventory`
--

LOCK TABLES `tincident_inventory` WRITE;
/*!40000 ALTER TABLE `tincident_inventory` DISABLE KEYS */;
INSERT INTO `tincident_inventory` VALUES (1,2);
/*!40000 ALTER TABLE `tincident_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tincident_stats`
--

LOCK TABLES `tincident_stats` WRITE;
/*!40000 ALTER TABLE `tincident_stats` DISABLE KEYS */;
INSERT INTO `tincident_stats` VALUES (1,1,0,'status_time','',1,0),(2,1,0,'user_time','admin',0,0),(3,1,0,'group_time','',0,3),(4,1,0,'total_time','',0,0),(5,1,0,'total_w_third','',0,0);
/*!40000 ALTER TABLE `tincident_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tincident_track`
--

LOCK TABLES `tincident_track` WRITE;
/*!40000 ALTER TABLE `tincident_track` DISABLE KEYS */;
INSERT INTO `tincident_track` VALUES (1,1,10,'2013-02-12 09:50:27','admin','2','Added inventory object: Cenutrio',''),(2,1,7,'2013-02-12 09:50:27','admin','1','Status changed -> New',''),(3,1,17,'2013-02-12 09:50:27','admin','admin','Assigned user changed -> Default Admin',''),(4,1,28,'2013-02-12 09:50:27','admin','3','Group has changed -> Customer #B',''),(5,1,0,'2013-02-12 09:50:27','admin','0','Created',''),(6,1,2,'2013-02-12 09:53:45','demo','0','Workunit added','');
/*!40000 ALTER TABLE `tincident_track` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tincident_type`
--

LOCK TABLES `tincident_type` WRITE;
/*!40000 ALTER TABLE `tincident_type` DISABLE KEYS */;
INSERT INTO `tincident_type` VALUES (1,'Software&#x20;issues','This&#x20;kind&#x20;of&#x20;incidents&#x20;are&#x20;for&#x20;software&#x20;&#40;bugs,&#x20;feature&#x20;lack&#x20;reports,&#x20;etc&#41;',NULL,0);
/*!40000 ALTER TABLE `tincident_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tincident_type_field`
--

LOCK TABLES `tincident_type_field` WRITE;
/*!40000 ALTER TABLE `tincident_type_field` DISABLE KEYS */;
INSERT INTO `tincident_type_field` VALUES (1,1,'Version','combo','3,3.1,4',0, NULL, 0, '', 0),(2,1,'Base&#x20;OS','text','',0, NULL,0,'',0),(3,1,'Way&#x20;to&#x20;reproduce','textarea','',0, NULL,0,'',0),(4,1,'Expected&#x20;behaviour','textarea','',0, NULL,0,'',0);
/*!40000 ALTER TABLE `tincident_type_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tinventory`
--

LOCK TABLES `tinventory` WRITE;
/*!40000 ALTER TABLE `tinventory` DISABLE KEYS */;
INSERT INTO `tinventory` VALUES (1,0,'admin','An&#x20;object',0,'',0,0,0,0, '0000-00-00 00:00:00', 'new', '2013-08-19 10:10:10', '0000-00-00 00:00:00'),(2,1,'admin','Cenutrio',0,'Sancho&#039;s&#x20;computer',0,0,0,0,'0000-00-00 00:00:00', 'new', '2013-08-19 10:10:10', '0000-00-00 00:00:00');
/*!40000 ALTER TABLE `tinventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tinventory_relationship`
--

LOCK TABLES `tinventory_relationship` WRITE;
/*!40000 ALTER TABLE `tinventory_relationship` DISABLE KEYS */;
/*!40000 ALTER TABLE `tinventory_relationship` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tinventory_reports`
--

LOCK TABLES `tinventory_reports` WRITE;
/*!40000 ALTER TABLE `tinventory_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `tinventory_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tinventory_track`
--

LOCK TABLES `tinventory_track` WRITE;
/*!40000 ALTER TABLE `tinventory_track` DISABLE KEYS */;
INSERT INTO `tinventory_track` VALUES (1,1,'0',0,'2013-02-12 09:24:00','admin','Created'),(2,1,'0',8,'2013-02-12 09:24:00','admin','Inventory private'),(3,2,'0',0,'2013-02-12 09:26:06','admin','Created'),(4,2,'0',8,'2013-02-12 09:26:06','admin','Inventory private'),(5,2,'1',6,'2013-02-12 09:26:06','admin','Inventory object type added -> Computer'),(6,2,'1',2,'2013-02-12 09:50:27','admin','inventory object in incident -> Something&#x20;seems&#x20;to&#x20;be&#x20;wrong&#x20;on&#x20;Mauertbe');
/*!40000 ALTER TABLE `tinventory_track` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tinvoice`
--

LOCK TABLES `tinvoice` WRITE;
/*!40000 ALTER TABLE `tinvoice` DISABLE KEYS */;
/*!40000 ALTER TABLE `tinvoice` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tkb_category`
--

LOCK TABLES `tkb_category` WRITE;
/*!40000 ALTER TABLE `tkb_category` DISABLE KEYS */;
INSERT INTO `tkb_category` VALUES (1,'Articles','General&#x20;articles&#x20;about&#x20;XXX','plugin.png',0);
/*!40000 ALTER TABLE `tkb_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tkb_data`
--

LOCK TABLES `tkb_data` WRITE;
/*!40000 ALTER TABLE `tkb_data` DISABLE KEYS */;
INSERT INTO `tkb_data` VALUES (1,'Restart&#x20;resolution&#x20;on&#x20;vertical&#x20;upload&#x20;change','This&#x20;test&#x20;may&#x20;not&#x20;meaning&#x20;anything&#x20;for&#x20;you.&#x20;For&#x20;me&#x20;either.&#x0d;&#x0a;&#x0d;&#x0a;Nihilism&#x20;was&#x20;for&#x20;us&#x20;a&#x20;window&#x20;of&#x20;clarity&#x20;in&#x20;a&#x20;munged&#x20;world.&#x20;It&rsquo;s&#x20;the&#x20;same&#x20;way&#x20;now:&#x20;the&#x20;media&#x20;can&#x20;report&#x20;well&#x20;on&#x20;incidents,&#x20;but&#x20;the&#x20;vast&#x20;majority&#x20;of&#x20;people&#x20;in&#x20;society&#x20;spend&#x20;ungodly&#x20;amounts&#x20;of&#x20;time,&#x20;effort&#x20;and&#x20;money&#x20;repeating&#x20;a&#x20;narrative&#x20;to&#x20;each&#x20;other.&#x20;Roughly,&#x20;it&rsquo;s&#x20;that&#x20;liberal&#x20;democracy&#x20;is&#x20;the&#x20;best&#x20;society&#x20;ever,&#x20;technology&#x20;will&#x20;solve&#x20;all&#x20;our&#x20;problems,&#x20;and&#x20;the&#x20;type&#x20;of&#x20;individualistic&#x20;selfishness&#x20;required&#x20;for&#x20;consumerism&#x20;is&#x20;the&#x20;best&#x20;way&#x20;to&#x20;live.&#x20;We&#x20;are&#x20;not&#x20;encouraged&#x20;to&#x20;improve&#x20;ourselves,&#x20;only&#x20;to&#x20;make&#x20;more&#x20;money&#x20;and&#x20;to&#x20;conform.&#x20;This&#x20;is&#x20;for&#x20;the&#x20;convenience&#x20;of&#x20;others,&#x20;by&#x20;the&#x20;way.&#x20;Government&#x20;didn&rsquo;t&#x20;invent&#x20;this.&#x20;It&rsquo;s&#x20;a&#x20;cultural&#x20;response&#x20;to&#x20;us&#x20;having&#x20;obliterated&#x20;culture&#x20;so&#x20;that&#x20;individuals&#x20;could&#x20;&ldquo;have&#x20;it&#x20;your&#x20;way&rdquo;&#x20;&#40;Burger&#x20;King!&#41;&#x20;and&#x20;not&#x20;be&#x20;responsible&#x20;to&#x20;any&#x20;kind&#x20;of&#x20;social&#x20;standards,&#x20;higher&#x20;order,&#x20;higher&#x20;power,&#x20;values,&#x20;culture&#x20;or&#x20;measurement&#x20;of&#x20;meaning.&#x20;Basically,&#x20;it&rsquo;s&#x20;rampant&#x20;individualism&#x20;run&#x20;amok.&#x20;Nihilism&#x20;rejects&#x20;all&#x20;of&#x20;this&#x20;by&#x20;saying&#x20;that&#x20;there&#x20;are&#x20;no&#x20;inherent&#x20;values&#x20;to&#x20;life,&#x20;and&#x20;instead,&#x20;everything&#x20;is&#x20;a&#x20;choice,&#x20;and&#x20;by&#x20;observing&#x20;reality,&#x20;we&#x20;can&#x20;tell&#x20;what&#x20;the&#x20;results&#x20;of&#x20;our&#x20;actions&#x20;will&#x20;be.&#x20;We&#x20;can&rsquo;t&#x20;hide&#x20;behind&#x20;morality,&#x20;laws&#x20;and&#x20;social&#x20;sentiments&#x20;which&#x20;allow&#x20;us&#x20;to&#x20;do&#x20;whatever&#x20;we&#x20;want&#x20;and&#x20;then&#x20;justify&#x20;it&#x20;with&#x20;nice-sounding&#x20;goals&#x20;like&#x20;ending&#x20;poverty,&#x20;civil&#x20;rights,&#x20;stopping&#x20;global&#x20;warming,&#x20;saving&#x20;the&#x20;whales,&#x20;etc.&#x20;Morality&#x20;and&#x20;those&#x20;other&#x20;human&#x20;judgments&#x20;remove&#x20;us&#x20;from&#x20;reality.&#x20;It&rsquo;s&#x20;easy&#x20;to&#x20;satisfy&#x20;the&#x20;justification,&#x20;and&#x20;avoid&#x20;breaking&#x20;the&#x20;rules,&#x20;but&#x20;also&#x20;do&#x20;something&#x20;vile,&#x20;selfish&#x20;and&#x20;stupid.&#x20;That&rsquo;s&#x20;why&#x20;people&#x20;love&#x20;rules&#x20;&mdash;&#x20;they&rsquo;re&#x20;easy&#x20;to&#x20;circumvent!&#x20;Lawmaking&#x20;is&#x20;a&#x20;constant&#x20;game&#x20;of&#x20;whack-a-mole.&#x20;We&#x20;tell&#x20;people&#x20;that&#x20;it&rsquo;s&#x20;illegal&#x20;to&#x20;shoot&#x20;each&#x20;other&#x20;in&#x20;the&#x20;head,&#x20;and&#x20;they&#x20;shoot&#x20;each&#x20;other&#x20;in&#x20;the&#x20;groin.&#x20;Up&#x20;pops&#x20;another&#x20;mole;&#x20;they&rsquo;re&#x20;shooting&#x20;each&#x20;other&#x20;in&#x20;the&#x20;gut.&#x20;Make&#x20;that&#x20;illegal,&#x20;too.&#x20;Make&#x20;it&#x20;all&#x20;illegal.&#x20;Who&#x20;will&#x20;enforce&#x20;it?&#x20;No&#x20;one&#x20;is&#x20;sure.&#x20;Nihilists&#x20;remove&#x20;all&#x20;of&#x20;this&#x20;and&#x20;look&#x20;at&#x20;life&#x20;entirely&#x20;as&#x20;choices&#x20;and&#x20;consequences.&#x20;We&#x20;know&#x20;that&#x20;if&#x20;we&#x20;pull&#x20;the&#x20;pin&#x20;from&#x20;a&#x20;hand&#x20;grenade&#x20;and&#x20;then&#x20;hold&#x20;it&#x20;for&#x20;five&#x20;seconds,&#x20;we&#x20;die.&#x20;We&#x20;also&#x20;know&#x20;that&#x20;if&#x20;we&#x20;pollute&#x20;our&#x20;planet,&#x20;we&#x20;die.&#x20;However,&#x20;we&rsquo;ve&#x20;made&#x20;these&#x20;rules&#x20;that&#x20;say&#x20;it&rsquo;s&#x20;OK&#x20;to&#x20;hold&#x20;a&#x20;hand&#x20;grenade&#x20;for&#x20;up&#x20;to&#x20;ten&#x20;seconds,&#x20;and&#x20;it&rsquo;s&#x20;OK&#x20;to&#x20;pollute&#x20;somewhat&#x20;per&#x20;person,&#x20;with&#x20;no&#x20;limit&#x20;on&#x20;the&#x20;number&#x20;of&#x20;people.&#x20;Those&#x20;rules&#x20;are&#x20;easily&#x20;evaded&#x20;and&#x20;we&#x20;still&#x20;stumble&#x20;onward&#x20;toward&#x20;our&#x20;doom,&#x20;pushed&#x20;forward&#x20;by&#x20;the&#x20;desires,&#x20;judgments&#x20;and&#x20;emotions&#x20;of&#x20;billions&#x20;of&#x20;people.&#x20;Nihilism&#x20;refutes&#x20;all&#x20;of&#x20;that.&#x20;&ndash;&#x20;Interview&#x20;with&#x20;Vijay&#x20;Prozak','2013-02-12 10:05:25','en_GB','admin',1,1);
/*!40000 ALTER TABLE `tkb_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tkb_product`
--

LOCK TABLES `tkb_product` WRITE;
/*!40000 ALTER TABLE `tkb_product` DISABLE KEYS */;
INSERT INTO `tkb_product` VALUES (1,'Packages','','box.png',0);
/*!40000 ALTER TABLE `tkb_product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tlead`
--

LOCK TABLES `tlead` WRITE;
/*!40000 ALTER TABLE `tlead` DISABLE KEYS */;
INSERT INTO `tlead` (`id`, `id_company`, `id_language`, `id_category`, `owner`, `fullname`, `email`, `phone`, `mobile`, `position`, `company`, `country`, `description`, `creation`, `modification`, `progress`, `estimated_sale`, `id_campaign`) VALUES (1, 0, 'en_GB', 0, 'admin', 'Peter&#x20;McNee', 'peter@nothere.net', '3434324233', '3247473863', 'CTO', 'Not&#x20;There&#x20;Computer&#x20;Ltd', 'USA', 'Hi&#x20;there,&#x20;&#x0d;&#x0a;&#x0d;&#x0a;Im&#x20;interested&#x20;in&#x20;XXXXX.&#x20;Please,&#x20;give&#x20;me&#x20;more&#x20;information.&#x0d;&#x0a;', '2013-02-12 09:28:27', '2013-02-12 09:28:27', 0, 2300, 0);
/*!40000 ALTER TABLE `tlead` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tlead_activity`
--

LOCK TABLES `tlead_activity` WRITE;
/*!40000 ALTER TABLE `tlead_activity` DISABLE KEYS */;
INSERT INTO `tlead_activity` VALUES (1,1,'admin','I&#x20;call&#x20;him,&#x20;but&#x20;not&#x20;reply.&#x0d;&#x0a;','2013-02-12 09:30:17');
/*!40000 ALTER TABLE `tlead_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tlead_history`
--

LOCK TABLES `tlead_history` WRITE;
/*!40000 ALTER TABLE `tlead_history` DISABLE KEYS */;
INSERT INTO `tlead_history` VALUES (1,1,'admin','Created lead','2013-02-12 09:28:27'),(2,1,'admin','Added comments','2013-02-12 09:30:17');
/*!40000 ALTER TABLE `tlead_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tmanufacturer`
--

LOCK TABLES `tmanufacturer` WRITE;
/*!40000 ALTER TABLE `tmanufacturer` DISABLE KEYS */;
INSERT INTO `tmanufacturer` VALUES (1,'Arduino&#x20;Researchs','Nowhere&#x20;Street&#x20;23th&#x0d;&#x0a;23834&#x20;CA&#x0d;&#x0a;','',0,0);
/*!40000 ALTER TABLE `tmanufacturer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tmenu_visibility`
--

LOCK TABLES `tmenu_visibility` WRITE;
/*!40000 ALTER TABLE `tmenu_visibility` DISABLE KEYS */;
/*!40000 ALTER TABLE `tmenu_visibility` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tmilestone`
--

LOCK TABLES `tmilestone` WRITE;
/*!40000 ALTER TABLE `tmilestone` DISABLE KEYS */;
INSERT INTO `tmilestone` VALUES (1,1,'2013-04-01 00:00:00','Beta&#x20;release','');
/*!40000 ALTER TABLE `tmilestone` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tnewsboard`
--

LOCK TABLES `tnewsboard` WRITE;
/*!40000 ALTER TABLE `tnewsboard` DISABLE KEYS */;
INSERT INTO `tnewsboard` VALUES (1,'Welcome&#x20;to&#x20;Integria&#x20;4.0','Remember,&#x20;this&#x20;is&#x20;a&#x20;development&#x20;version.&#x20;Some&#x20;screens&#x20;are&#x20;ugly&#x20;yet,&#x20;but&#x20;will&#x20;be&#x20;much&#x20;nicer!.&#x20;Most&#x20;features&#x20;need&#x20;to&#x20;be&#x20;improved,&#x20;but&#x20;the&#x20;base&#x20;of&#x20;the&#x20;system&#x20;is&#x20;already&#x20;done.&#x0d;&#x0a;','2013-02-12 00:00:00',0,0,0,'');
/*!40000 ALTER TABLE `tnewsboard` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tobject_field_data`
--

LOCK TABLES `tobject_field_data` WRITE;
/*!40000 ALTER TABLE `tobject_field_data` DISABLE KEYS */;
INSERT INTO `tobject_field_data` VALUES (1,2,1,'2347234873'),(2,2,2,'Xeon&#x20;3.2GHZ'),(3,2,3,'4GB'),(4,2,4,'192.168.4.242'),(5,2,5,'FE:AE:C4:35:3A:4F');
/*!40000 ALTER TABLE `tobject_field_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tpending_mail`
--

LOCK TABLES `tpending_mail` WRITE;
/*!40000 ALTER TABLE `tpending_mail` DISABLE KEYS */;
INSERT INTO `tpending_mail` VALUES (1,'2013-02-12 09:50:29',0,0,'admin@integria.sf.net','[TicketID#1/6c5de/admin] [Integria IMS - the ITIL Management System] NEW incident #1 Something seems to be wrong on Mauertbe.','Hello, \n\nThis is an automated message coming from Integria\n\n\r\nA NEW incident has been created: #1 Something seems to be wrong on Mauertbe\n\nYou can reply to this mail to add a Workunit to this incident. You also could track this incident in the following URL (need to use your credentials):\n\n   http://192.168.70.82/integria/index.php?sec=incidents&sec2=operation/incidents/incident&id=1\n\n===================================================\n   ID          : #1 - Something seems to be wrong on Mauertbe\n   CREATED ON  : 2013-02-12 09:50:27\n   GROUP       : Customer #A\n   AUTHOR      : Default Admin\n   ASSIGNED TO : Default Admin\n   PRIORITY    : High\n===================================================\n\nTHis should be fixed ASAP. Seems to have something wrong in XXX \n\n---------------------------------------------------------------------\n\n\r\nPlease do not respond directly this email, has been automatically created by Integria (http://integria.sourceforge.net).\n\nThanks for your time and have a nice day\n\n','',''),(2,'2013-02-12 09:53:45',0,0,'admin@integria.sf.net','[TicketID#1/6c5de/admin] [Integria IMS - the ITIL Management System] Incident #1 Something seems to be wrong on Mauertbe has a new WORKUNIT.','Hello, \n\nThis is an automated message coming from Integria\n\n\r\nIncident #1 ((Something seems to be wrong on Mauertbe)) has been UPDATED.  \n\nYou can reply to this mail to add a Workunit to this incident. You also could track this incident in the following URL (need to use your credentials):\n\n   http://192.168.70.82/integria/index.php?sec=incidents&sec2=operation/incidents/incident&id=1\n\n===================================================\n ID          : #1 - Something seems to be wrong on Mauertbe\n CREATED ON  : 2013-02-12 09:50:27\n LAST UPDATE : 2013-02-12 09:50:27\n GROUP       : Customer #A\n AUTHOR      : Default Admin\n ASSIGNED TO : Default Admin\n PRIORITY    : High\n STATUS      : New\n RESOLUTION  : None\n TIME USED   : 0.25\n===================================================\n\nTHis should be fixed ASAP. Seems to have something wrong in XXX \n\n===================================================\nWORKUNIT added by : Mr. Demo Potato (Your big company)\n===================================================\n\nI\'ve not entered yet in this, please, give me a few days to do more tests.\n\n===================================================\n		\n\r\nPlease do not respond directly this email, has been automatically created by Integria (http://integria.sourceforge.net).\n\nThanks for your time and have a nice day\n\n','',''),(3,'2013-02-12 09:53:45',0,0,'demo@demo.com','[TicketID#1/11797/demo] [Integria IMS - the ITIL Management System] Incident #1 Something seems to be wrong on Mauertbe has a new WORKUNIT.','Hello, \n\nThis is an automated message coming from Integria\n\n\r\nIncident #1 ((Something seems to be wrong on Mauertbe)) has been UPDATED.  \n\nYou can reply to this mail to add a Workunit to this incident. You also could track this incident in the following URL (need to use your credentials):\n\n   http://192.168.70.82/integria/index.php?sec=incidents&sec2=operation/incidents/incident&id=1\n\n===================================================\n ID          : #1 - Something seems to be wrong on Mauertbe\n CREATED ON  : 2013-02-12 09:50:27\n LAST UPDATE : 2013-02-12 09:50:27\n GROUP       : Customer #A\n AUTHOR      : Default Admin\n ASSIGNED TO : Default Admin\n PRIORITY    : High\n STATUS      : New\n RESOLUTION  : None\n TIME USED   : 0.25\n===================================================\n\nTHis should be fixed ASAP. Seems to have something wrong in XXX \n\n===================================================\nWORKUNIT added by : Mr. Demo Potato (Your big company)\n===================================================\n\nI\'ve not entered yet in this, please, give me a few days to do more tests.\n\n===================================================\n		\n\r\nPlease do not respond directly this email, has been automatically created by Integria (http://integria.sourceforge.net).\n\nThanks for your time and have a nice day\n\n','',''),(4,'2013-02-12 09:59:57',0,0,'admin@integria.sf.net','[Integria IMS - the ITIL Management System] Updated TO-DO from \'admin\' : Do a public release of first version for testing','Hello, \n\nThis is an automated message coming from Integria\n\n\r\nTO-DO \'Do a public release of first version for testing\' has been UPDATED by user demo. This TO-DO was created by user admin. You could track this todo in the following URL (need to use your credentials): http://192.168.70.82/integria/index.php?sec=todo&sec2=operation/todo/todo&operation=update&id=1\n\n\n---------------------------------------------------------------------\nTO-DO NAME  : Do a public release of first version for testing\nDATE / TIME : 2013-02-12 09:58:56\nCREATED BY  : admin\nASSIGNED TO : demo\nPROGRESS    : 0%\nPRIORITY    : 2\nDESCRIPTION\n---------------------------------------------------------------------\nCreate RPM and DEB packages for testing purposes.\n\n\r\nPlease do not respond directly this email, has been automatically created by Integria (http://integria.sourceforge.net).\n\nThanks for your time and have a nice day\n\n','',''),(5,'2013-02-12 09:59:57',0,0,'demo@demo.com','[Integria IMS - the ITIL Management System] Updated TO-DO from \'admin\' : Do a public release of first version for testing','Hello, \n\nThis is an automated message coming from Integria\n\n\r\nTO-DO \'Do a public release of first version for testing\' has been UPDATED by user demo. This TO-DO was created by user admin. You could track this todo in the following URL (need to use your credentials): http://192.168.70.82/integria/index.php?sec=todo&sec2=operation/todo/todo&operation=update&id=1\n\n\n---------------------------------------------------------------------\nTO-DO NAME  : Do a public release of first version for testing\nDATE / TIME : 2013-02-12 09:58:56\nCREATED BY  : admin\nASSIGNED TO : demo\nPROGRESS    : 0%\nPRIORITY    : 2\nDESCRIPTION\n---------------------------------------------------------------------\nCreate RPM and DEB packages for testing purposes.\n\n\r\nPlease do not respond directly this email, has been automatically created by Integria (http://integria.sourceforge.net).\n\nThanks for your time and have a nice day\n\n','',''),(6,'2013-02-12 10:00:15',0,0,'admin@integria.sf.net','[Integria IMS - the ITIL Management System] Task \"Development\" has a new work report from Default Admin \n','Hello, \n\nThis is an automated message coming from Integria\n\n\r\n\n\nTask Development of project Super Vaporware v1.0 has been updated by user Default Admin and a new workunit has been added to history. You could track this workunit in the following URL (need to use your credentials):\n\n	http://192.168.70.82/integria/index.php?sec=projects&sec2=operation/projects/task_workunit&id_project=1&id_task=3\n\n============================================================\n    TASK        : Development \n    DATE        : 2013-02-12 00:00:00\n    REPORTED by : Default Admin\n    TIME USED   : 4.00 \n============================================================\n\nlot\'s of work done today :)\n\n============================================================\n\n\r\nPlease do not respond directly this email, has been automatically created by Integria (http://integria.sourceforge.net).\n\nThanks for your time and have a nice day\n\n','',''),(7,'2013-02-12 10:01:31',0,0,'admin@integria.sf.net','[Integria IMS - the ITIL Management System] Task \"Development\" has a new work report from Default Admin \n','Hello, \n\nThis is an automated message coming from Integria\n\n\r\nThis is part of a multi-workunit assigment of 40 hours\n\nTask Development of project Super Vaporware v1.0 has been updated by user Default Admin and a new workunit has been added to history. You could track this workunit in the following URL (need to use your credentials):\n\n	http://192.168.70.82/integria/index.php?sec=projects&sec2=operation/projects/task_workunit&id_project=1&id_task=3\n\n============================================================\n    TASK        : Development \n    DATE        : 2013-02-26 00:00:00\n    REPORTED by : Default Admin\n    TIME USED   : 8.00 \n============================================================\n\nDoing something very weird :)\n\n============================================================\n\n\r\nPlease do not respond directly this email, has been automatically created by Integria (http://integria.sourceforge.net).\n\nThanks for your time and have a nice day\n\n','',''),(8,'2013-02-12 10:02:36',0,0,'admin@integria.sf.net','[Integria IMS - the ITIL Management System] New calendar event','Hello, \n\nThis is an automated message coming from Integria\n\n\r\nHello, \n\nThis is an automated message coming from Integria\n\nA new entry in calendar has been created by user admin (Default Admin)\n\n\n		Date and time: 2013-05-29 10:02\n\n		Description  : Elena\'s Aniversary\n\nPlease do not respond directly this email, has been automatically created by Integria (http://integria.sourceforge.net).\n\nThanks for your time and have a nice day\n\n\r\nPlease do not respond directly this email, has been automatically created by Integria (http://integria.sourceforge.net).\n\nThanks for your time and have a nice day\n\n','Array','');
/*!40000 ALTER TABLE `tpending_mail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tproject`
--

LOCK TABLES `tproject` WRITE;
/*!40000 ALTER TABLE `tproject` DISABLE KEYS */;
INSERT INTO `tproject` VALUES (1,'Super&#x20;Vaporware&#x20;v1.0','','2013-05-01','2013-05-01','admin',0,0, "");

/*!40000 ALTER TABLE `tproject` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tproject_group`
--

LOCK TABLES `tproject_group` WRITE;
/*!40000 ALTER TABLE `tproject_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `tproject_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tproject_track`
--

LOCK TABLES `tproject_track` WRITE;
/*!40000 ALTER TABLE `tproject_track` DISABLE KEYS */;
INSERT INTO `tproject_track` VALUES (1,1,'admin',21,'2013-02-12 09:54:53',0),(2,1,'admin',22,'2013-02-12 09:56:55',0);
/*!40000 ALTER TABLE `tproject_track` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `trole_people_project`
--

LOCK TABLES `trole_people_project` WRITE;
/*!40000 ALTER TABLE `trole_people_project` DISABLE KEYS */;
INSERT INTO `trole_people_project` VALUES (1,'admin',1,1),(2,'demo',4,1);
/*!40000 ALTER TABLE `trole_people_project` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `trole_people_task`
--

LOCK TABLES `trole_people_task` WRITE;
/*!40000 ALTER TABLE `trole_people_task` DISABLE KEYS */;
INSERT INTO `trole_people_task` VALUES (1,'demo',1,1),(2,'demo',1,2),(3,'admin',1,3),(4,'admin',1,4),(5,'demo',1,5);
/*!40000 ALTER TABLE `trole_people_task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tsesion`
--

LOCK TABLES `tsesion` WRITE;
/*!40000 ALTER TABLE `tsesion` DISABLE KEYS */;
INSERT INTO `tsesion` VALUES (1,'admin','192.168.70.101','Logon','Logged in','','2013-02-12 09:22:01',1360657321),(2,'admin','192.168.70.101','SLA Created','Created a new SLA (Regular&#x20;SLA)','INSERT INTO tsla (`name`, `description`, id_sla_base,\n		min_response, max_response, max_incidents, `enforced`, five_daysonly, time_from, time_to, max_inactivity)\n		VALUE (\"Regular&#x20;SLA\", \"\", 0, 48, 480, 10, 1, 1, 8, 18, 96)','2013-02-12 09:23:03',1360657383),(3,'admin','192.168.70.101','Inventory updated','Created','','2013-02-12 09:24:00',1360657440),(4,'admin','192.168.70.101','Inventory updated','Inventory private','','2013-02-12 09:24:00',1360657440),(5,'admin','192.168.70.101','Inventory updated','Created','','2013-02-12 09:26:06',1360657566),(6,'admin','192.168.70.101','Inventory updated','Inventory private','','2013-02-12 09:26:06',1360657566),(7,'admin','192.168.70.101','Inventory updated','Inventory object type added -> Computer','','2013-02-12 09:26:06',1360657566),(8,'admin','','Lead created','Lead named \'Peter&#x20;McNee\' has been added','','2013-02-12 09:28:27',1360657707),(9,'admin','','Contract created','Contract named \'Sample&#x20;contract\' has been added','','2013-02-12 09:29:27',1360657767),(10,'admin','','Contact created','Contact named \'Sancho&#x20;Lerena\' has been added','','2013-02-12 09:30:01',1360657801),(11,'admin','192.168.70.101','Incident updated','Added inventory object: Cenutrio','','2013-02-12 09:50:27',1360659027),(12,'admin','192.168.70.101','Inventory updated','inventory object in incident -> Something&#x20;seems&#x20;to&#x20;be&#x20;wrong&#x20;on&#x20;Mauertbe','','2013-02-12 09:50:27',1360659027),(13,'admin','192.168.70.101','Incident created','User admin created incident #1','','2013-02-12 09:50:27',1360659027),(14,'admin','192.168.70.101','Incident updated','Status changed -> New','','2013-02-12 09:50:27',1360659027),(15,'admin','192.168.70.101','Incident updated','Assigned user changed -> Default Admin','','2013-02-12 09:50:27',1360659027),(16,'admin','192.168.70.101','Incident updated','Group has changed -> Customer #B','','2013-02-12 09:50:27',1360659027),(17,'admin','192.168.70.101','Incident updated','Created','','2013-02-12 09:50:27',1360659027),(18,'admin','192.168.70.101','Logoff','Logged out','','2013-02-12 09:53:13',1360659193),(19,'demo','192.168.70.101','Logon','Logged in','','2013-02-12 09:53:17',1360659197),(20,'demo','192.168.70.101','Incident updated','Workunit added','','2013-02-12 09:53:45',1360659225),(21,'demo','192.168.70.101','Logoff','Logged out','','2013-02-12 09:54:21',1360659261),(22,'admin','192.168.70.101','Logon','Logged in','','2013-02-12 09:54:32',1360659272),(23,'admin','','Project created','User admin created project \'Super&#x20;Vaporware&#x20;v1.0\'','','2013-02-12 09:54:53',1360659293),(24,'admin','','Project tracking updated','Project #1 status #21','','2013-02-12 09:54:53',1360659293),(25,'admin','192.168.70.101','User/Role added to project','User demo added to project Super&#x20;Vaporware&#x20;v1.0','','2013-02-12 09:55:06',1360659306),(26,'admin','192.168.70.101','Task updated','Task \'Planning\' updated to project \'1\'','','2013-02-12 09:56:30',1360659390),(27,'admin','','Task tracking updated','Task #id_task Status #12','','2013-02-12 09:56:30',1360659390),(28,'admin','192.168.70.101','Task updated','Task \'Analysis\' updated to project \'1\'','','2013-02-12 09:56:30',1360659390),(29,'admin','','Task tracking updated','Task #id_task Status #12','','2013-02-12 09:56:30',1360659390),(30,'admin','192.168.70.101','Task updated','Task \'Development\' updated to project \'1\'','','2013-02-12 09:56:30',1360659390),(31,'admin','','Task tracking updated','Task #id_task Status #12','','2013-02-12 09:56:31',1360659391),(32,'admin','192.168.70.101','Task updated','Task \'Tests\' updated to project \'1\'','','2013-02-12 09:56:31',1360659391),(33,'admin','','Task tracking updated','Task #id_task Status #12','','2013-02-12 09:56:31',1360659391),(34,'admin','192.168.70.101','Task updated','Task \'Documentation\' updated to project \'1\'','','2013-02-12 09:56:31',1360659391),(35,'admin','','Task tracking updated','Task #id_task Status #12','','2013-02-12 09:56:31',1360659391),(36,'admin','192.168.70.101','Project updated','Project Super&#x20;Vaporware&#x20;v1.0','','2013-02-12 09:56:55',1360659415),(37,'admin','','Project tracking updated','Project #1 status #22','','2013-02-12 09:56:55',1360659415),(38,'admin','192.168.70.101','Spare work unit added','Workunit for admin added to Task ID #3','','2013-02-12 10:00:15',1360659615),(39,'admin','192.168.70.101','Logoff','Logged out','','2013-02-12 10:02:51',1360659771),(40,'admin','192.168.70.101','Logon','Logged in','','2013-02-12 10:02:56',1360659776),(41,'admin','192.168.70.101','Logoff','Logged out','','2013-02-12 10:05:38',1360659938),(42,'admin','192.168.70.101','Logon','Logged in','','2013-02-12 10:24:47',1360661087);
/*!40000 ALTER TABLE `tsesion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tsla`
--

LOCK TABLES `tsla` WRITE;
/*!40000 ALTER TABLE `tsla` DISABLE KEYS */;
INSERT INTO `tsla` VALUES (1,'Regular&#x20;SLA','',48,480,10,96,1,1,8,18,0,1,0);
/*!40000 ALTER TABLE `tsla` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `ttask`
--

LOCK TABLES `ttask` WRITE;
/*!40000 ALTER TABLE `ttask` DISABLE KEYS */;
INSERT INTO `ttask` VALUES (1,1,0,'Planning','',0,0,0,'2013-01-02','2013-01-17',128,0.00,'none',1,''),
(2,1,0,'Analysis','',0,0,0,'2013-02-04','2013-02-12',72,0.00,'none',1,''),
(3,1,0,'Development','',6,0,0,'2013-02-20','2013-02-28',72,0.00,'none',1,''),
(4,1,0,'Tests','',0,0,0,'2013-03-01','2013-03-30',240,0.00,'none',1,''),
(5,1,0,'Documentation','',0,0,0,'2013-04-01','2013-04-30',240,0.00,'none',1,'');
/*!40000 ALTER TABLE `ttask` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `ttask_inventory`
--

LOCK TABLES `ttask_inventory` WRITE;
/*!40000 ALTER TABLE `ttask_inventory` DISABLE KEYS */;
/*!40000 ALTER TABLE `ttask_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `ttask_track`
--

LOCK TABLES `ttask_track` WRITE;
/*!40000 ALTER TABLE `ttask_track` DISABLE KEYS */;
INSERT INTO `ttask_track` VALUES (1,1,'admin',0,12,'2013-02-12 09:56:30'),(2,2,'admin',0,12,'2013-02-12 09:56:30'),(3,3,'admin',0,12,'2013-02-12 09:56:31'),(4,4,'admin',0,12,'2013-02-12 09:56:31'),(5,5,'admin',0,12,'2013-02-12 09:56:31');
/*!40000 ALTER TABLE `ttask_track` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `ttodo`
--

LOCK TABLES `ttodo` WRITE;
/*!40000 ALTER TABLE `ttodo` DISABLE KEYS */;
INSERT INTO `ttodo` VALUES (1,'Do&#x20;a&#x20;public&#x20;release&#x20;of&#x20;first&#x20;version&#x20;for&#x20;testing',0,'demo','admin',2,'Create&#x20;RPM&#x20;and&#x20;DEB&#x20;packages&#x20;for&#x20;testing&#x20;purposes.','2013-02-12 09:59:57',3,'2013-02-12 00:00:00','0000-00-00 00:00:00','2000-01-01 00:00:00',1,2,0),
(2,'Elena&#039;s&#x20;aniversary',0,'admin','admin',4,'Dont&#x20;forget&#x20;or&#x20;I&#039;m&#x20;dead&#x20;!&#x0d;&#x0a;','2013-02-12 09:59:38',0,'2013-02-12 00:00:00','2013-05-29 00:00:00','2000-01-01 00:00:00',0,3,0);
/*!40000 ALTER TABLE `ttodo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `ttranslate_string`
--

LOCK TABLES `ttranslate_string` WRITE;
/*!40000 ALTER TABLE `ttranslate_string` DISABLE KEYS */;
/*!40000 ALTER TABLE `ttranslate_string` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tuser_report`
--

LOCK TABLES `tuser_report` WRITE;
/*!40000 ALTER TABLE `tuser_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `tuser_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tusuario`
--

LOCK TABLES `tusuario` WRITE;
/*!40000 ALTER TABLE `tusuario` DISABLE KEYS */;
INSERT INTO `tusuario` VALUES ('demo','Mr.&#x20;Demo&#x20;Potato','fe01ce2a7fbac8fafaed7c982a04e229','','2013-02-12 09:53:17','demo@demo.com','',0,'moustache4','en_GB','',0,1,0,'0000-00-00 00:00:00','2013-02-12 09:53:15',1,0,'23928',1,"Paris",0);
/*!40000 ALTER TABLE `tusuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tusuario_perfil`
--

LOCK TABLES `tusuario_perfil` WRITE;
/*!40000 ALTER TABLE `tusuario_perfil` DISABLE KEYS */;
INSERT INTO `tusuario_perfil` VALUES (1,'demo',2,2,'admin');
/*!40000 ALTER TABLE `tusuario_perfil` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tvacationday`
--

LOCK TABLES `tvacationday` WRITE;
/*!40000 ALTER TABLE `tvacationday` DISABLE KEYS */;
/*!40000 ALTER TABLE `tvacationday` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `twizard`
--

LOCK TABLES `twizard` WRITE;
/*!40000 ALTER TABLE `twizard` DISABLE KEYS */;
/*!40000 ALTER TABLE `twizard` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `two_category`
--

LOCK TABLES `two_category` WRITE;
/*!40000 ALTER TABLE `two_category` DISABLE KEYS */;
INSERT INTO `two_category` VALUES (1,'Ideas','idea.png'),(2,'Unplanned&#x20;tasks','puzzle.png'),(3,'Personal','calendar.png');
/*!40000 ALTER TABLE `two_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tworkunit`
--

LOCK TABLES `tworkunit` WRITE;
/*!40000 ALTER TABLE `tworkunit` DISABLE KEYS */;
INSERT INTO `tworkunit` VALUES (1,'2013-02-12 09:53:45',0.25,'demo','I&#039;ve&#x20;not&#x20;entered&#x20;yet&#x20;in&#x20;this,&#x20;please,&#x20;give&#x20;me&#x20;a&#x20;few&#x20;days&#x20;to&#x20;do&#x20;more&#x20;tests.',0,0,'',1,0),(2,'2013-02-12 00:00:00',4.00,'admin','lot&#039;s&#x20;of&#x20;work&#x20;done&#x20;today&#x20;:&#41;',0,0,'',1,0),(3,'2013-04-01 00:00:00',8.00,'admin','days&#x20;of&#x20;holidays',0,0,'',1,0),(4,'2013-04-02 00:00:00',8.00,'admin','days&#x20;of&#x20;holidays',0,0,'',1,0),(5,'2013-04-03 00:00:00',8.00,'admin','days&#x20;of&#x20;holidays',0,0,'',1,0),(6,'2013-04-04 00:00:00',8.00,'admin','days&#x20;of&#x20;holidays',0,0,'',1,0),(7,'2013-04-05 00:00:00',8.00,'admin','days&#x20;of&#x20;holidays',0,0,'',1,0),(8,'2013-02-20 00:00:00',8.00,'admin','Doing&#x20;something&#x20;very&#x20;weird&#x20;:&#41;',0,0,'',1,0),(9,'2013-02-21 00:00:00',8.00,'admin','Doing&#x20;something&#x20;very&#x20;weird&#x20;:&#41;',0,0,'',1,0),(10,'2013-02-22 00:00:00',8.00,'admin','Doing&#x20;something&#x20;very&#x20;weird&#x20;:&#41;',0,0,'',1,0),(11,'2013-02-25 00:00:00',8.00,'admin','Doing&#x20;something&#x20;very&#x20;weird&#x20;:&#41;',0,0,'',1,0),(12,'2013-02-26 00:00:00',8.00,'admin','Doing&#x20;something&#x20;very&#x20;weird&#x20;:&#41;',0,0,'',1,0);
/*!40000 ALTER TABLE `tworkunit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tworkunit_incident`
--

LOCK TABLES `tworkunit_incident` WRITE;
/*!40000 ALTER TABLE `tworkunit_incident` DISABLE KEYS */;
INSERT INTO `tworkunit_incident` VALUES (1,1,1);
/*!40000 ALTER TABLE `tworkunit_incident` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tworkunit_task`
--

LOCK TABLES `tworkunit_task` WRITE;
/*!40000 ALTER TABLE `tworkunit_task` DISABLE KEYS */;
INSERT INTO `tworkunit_task` VALUES (1,3,2),(2,-1,3),(3,-1,4),(4,-1,5),(5,-1,6),(6,-1,7),(7,3,8),(8,3,9),(9,3,10),(10,3,11),(11,3,12);
/*!40000 ALTER TABLE `tworkunit_task` ENABLE KEYS */;
UNLOCK TABLES;
