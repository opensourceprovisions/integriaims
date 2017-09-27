<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

//Singleton
class Ui {
	private static $instance;
	
	private $title;
	private $page_name;
	
	private $endHeader = false;
	private $header = array();
	private $endContent = false;
	private $content = array();
	private $endFooter = false;
	private $footer = array();
	private $form = array();
	private $grid = array();
	private $collapsible = true;
	private $endForm = true;
	private $endGrid = true;
	private $endCollapsible = true;
	private $dialogs = array();
	private $dialog = '';
	
	public function __construct() {
	}
	
	public static function getInstance() {
		if (!(self::$instance instanceof self)) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	public function debug($var, $file = false) {
		$more_info = '';
		if (is_string($var)) {
			$more_info = 'size: ' . strlen($var);
		}
		elseif (is_bool($var)) {
			$more_info = 'val: ' . 
				($var ? 'true' : 'false');
		}
		elseif (is_null($var)) {
			$more_info = 'is null';
		}
		elseif (is_array($var)) {
			$more_info = count($var);
		}
		
		if ($file === true)
			$file = '/tmp/logDebug';
		
		if (strlen($file) > 0) {
			$f = fopen($file, "a");
			ob_start();
			echo date("Y/m/d H:i:s") . " (" . gettype($var) . ") " . $more_info . "\n";
			print_r($var);
			echo "\n\n";
			$output = ob_get_clean();
			fprintf($f,"%s",$output);
			fclose($f);
		}
		else {
			echo "<pre>" .
				date("Y/m/d H:i:s") . " (" . gettype($var) . ") " . $more_info .
				"</pre>";
			echo "<pre>";print_r($var);echo "</pre>";
		}
	}
	
	public function createPage($title = null, $page_name = null) {
		if (!isset($title)) {
			$this->title = "Integria IMS ".__('Mobile');
		}
		else {
			$this->title = $title;
		}
		
		if (!isset($page_name)) {
			$this->page_name = 'main_page';
		}
		else {
			$this->page_name = $page_name;
		}
		
		$this->html = '';
		$this->endHeader = false;
		$this->header = array();
		$this->endContent = false;
		$this->content = array();
		$this->noFooter = false;
		$this->endFooter = false;
		$this->footer = array();
		$this->form = array();
		$this->grid = array();
		$this->collapsible = array();
		$this->endForm = true;
		$this->endGrid = true;
		$this->endCollapsible = true;
		$this->dialog = '';
		$this->dialogs = array();
	}
	
	public function showFooter($show = true) {
		$this->noFooter = !$show;
	}
	
	public function beginHeader() {
		$this->header = array();
		$this->header['button_left'] = '';
		$this->header['button_right'] = '';
		$this->header['title'] = "Integria IMS ".__('Mobile');
		$this->endHeader = false;
	}
	
	public function endHeader() {
		$this->endHeader = true;
	}
	
	public function createHeader($title = null, $buttonLeft = null, $buttonRight = null, $type = "text") {
		$this->beginHeader();
		
		$this->headerTitle($title, $type);
		$this->headerAddButtonLeft($buttonLeft);
		$this->headerAddButtonRight($buttonRight);
		
		$this->endHeader();
	}
	
	public function headerTitle($title = null, $type = "text") {
		if (isset($title)) {
			if ($type == "logo") {
				$this->header['title'] = $title;
			} else {
				$this->header['title'] = "<h1>$title</h1>";
			}
		}
	}
	
	public function headerAddButtonLeft($button = null) {
		if (isset($button)) {
			$this->header['button_left'] = $button;
		}
	}
	
	public function headerAddButtonRight($button = null) {
		if (isset($button)) {
			$this->header['button_right'] = $button;
		}
	}
	
	public function createHeaderButton($options) {
		return $this->createButton($options);
	}
	
	public function createDefaultHeader($title = false, $left_button = false, $type = "text") {
		if ($title === false) {
			$title = "Integria IMS ".__('Mobile');
		}
		
		if ($left_button === false) {
			$left_button = $this->createHeaderButton(
				array('icon' => 'back',
					'pos' => 'left',
					'text' => __('Exit'),
					'href' => 'index.php?action=logout'));
		}
		
		$this->createHeader(
			$title,
			$left_button,
			$this->createHeaderButton(
				array('icon' => 'home',
					'pos' => 'right',
					'text' => __('Home'),
					'href' => 'index.php?page=home')),
			$type);
	}
	
	public function createButton($options) {
		$return = '<a data-role="button" ';
		
		if (isset($options['id'])) {
			$return .= 'id="' . $options['id'] . '" ';
		}
		
		if (isset($options['icon'])) {
			$return .= 'data-icon="' . $options['icon'] . '" ';
		}
		
		if (isset($options['icon_pos'])) {
			$return .= 'data-iconpos="' . $options['pos'] . '" ';
		}
		
		if (isset($options['href'])) {
			$return .= 'href="' . $options['href'] . '" ';
		}
		else {
			$return .= 'href="#" ';
		}
		
		if (isset($options['data-ajax'])) {
			$return .= ' data-ajax="' . $options['data-ajax'] . '" ';
		} else {
			$return .= ' data-ajax="false" ';
		}
		$return .= '>';
		
		if (isset($options['text'])) {
			$return .= $options['text'];
		}
		
		$return .= '</a>';
		
		return $return;
	}
	
	public function beginFooter($options = null) {
		$this->footer = array();
		if (isset($options)) {
			$this->footer['options'] = $options;
		}
		$this->endFooter = false;
	}
	
	public function endFooter() {
		$this->endFooter = true;
	}
	
	public function createFooter($text = "") {
		$this->footerText($text);
	}
	
	public function footerText($text = null) {
		if (!isset($text)) {
			$this->footer['text'] = '';
		}
		else {
			$this->footer['text'] = $text;
		}
		
		$this->endFooter();
	}
	
	public function defaultFooter() {
		$system = System::getInstance();
		
		if ($system->getConfig('enteprise') == 1)
		$enterprise = "Enterprise Edition";
	else
		$enterprise = "OpenSource Edition";
		
		if (isset($_SERVER['REQUEST_TIME'])) {
			$time = $_SERVER['REQUEST_TIME'];
		}
		else {
			$time = get_system_time ();
		}
		
		return "<div id='footer' style='font-size: 12px; text-align: center;'>\n"
			. "Integria IMS $enterprise ". "<br />\n"
			.$system->getConfig('version')
			. " - Build ".$system->getConfig('build_version'). "<br />\n"
			. "</div>";
	}
	
	public function beginContent() {
		$this->content = array();
		$this->endContent = false;
	}
	
	public function endContent() {
		$this->endContent = true;
	}
	
	public function contentAddHtml($html) {
		$this->content[] = $html;
	}
	
	public function contentBeginGrid($mode = 'responsive') {
		$this->endGrid = false;
		
		$this->grid = array();
		$this->grid['mode'] = $mode;
		$this->grid['cells'] = array();
	}
	
	public function contentGridAddCell($html, $key = false) {
		$k = uniqid('cell_');
		if ($key !== false) {
			$k = $key;
		}
		
		$this->grid['cells'][$k] = $html;
	}
	
	public function getContentEndGrid($theme = "d") {
		$this->endGrid = true;
		
		//TODO Make others modes, only responsible mode
		$convert_columns_jquery_grid = array(
			2 => 'a', 3 => 'b', 4 => 'c', 5 => 'd');
		$convert_cells_jquery_grid = array('a', 'b', 'c', 'd', 'e');
		
		$html = "<div class='ui-grid-" .
			$convert_columns_jquery_grid[count($this->grid['cells'])] .
			" ui-responsive'>\n";
		
		reset($convert_cells_jquery_grid);
		foreach ($this->grid['cells'] as $key => $cell) {
			switch ($this->grid['mode']) {
				default:
				case 'responsive':
					$html .= "<div class='ui-block-" .
						current($convert_cells_jquery_grid) . "'>\n";
					break;
			}
			next($convert_cells_jquery_grid);
			$html .= "<div id='" . $key . "' style='padding-left: 2px; padding-right: 2px;'>\n";
			//$html .= "<div id='" . $key . "' class='ui-body ui-body-$theme'>\n";
			$html .= $cell;
			$html .= "</div>\n";
			
			$html .= "</div>\n";
		}
		
		$html .= "</div>\n";
		
		$this->grid = array();
		
		return $html;
	}
	
	public function addContentEndGrid() {
		$this->contentAddHtml($this->getContentEndGrid());
	}
	
	public function contentBeginCollapsible($title = "&nbsp;") {
		$this->endCollapsible = false;
		$this->collapsible = array();
		$this->collapsible['items'] = array();
		$this->collapsible['title'] = $title;
	}
	
	public function contentCollapsibleAddItem($html) {
		$this->collapsible['items'][] = $html;
	}
	
	public function getEndCollapsible($class = "", $data_theme = "a", $data_content_theme = "c", $collapsed = true) {
		$this->endCollapsible = true;
		
		$html = "<div class='$class' data-role='collapsible' " .
			" data-collapsed='$collapsed' " .
			" data-collapsed-icon='arrow-d' " .
			" data-expanded-icon='arrow-u' data-mini='true' ".
			" data-theme='$data_theme' data-content-theme='$data_content_theme'>\n";
		$html .= "<h4>" . $this->collapsible['title'] . "</h4>\n";
		
		$html .= "<ul data-role='listview' data-theme='$data_content_theme'>\n";
		foreach ($this->collapsible['items'] as $item) {
			$html .= "<li class='collapsible-non-list'>" . $item . "</li>";
		}
		$html .= "</ul>\n";
		
		$html .= "</div>\n";
		
		$this->collapsible = array();
		
		return $html;
	}
	
	public function contentEndCollapsible($class = "", $data_theme = "a") {
		$this->contentAddHtml($this->getEndCollapsible($class, $data_theme));
	}
	
	public function beginForm($options = null) {
		$this->form = array();
		$this->endForm = false;
		
		if (isset($options)) {
			$this->form['options'] = $options;
		}
	}
	
	public function endForm() {
		$this->contentAddHtml($this->getEndForm());
	}
	
	public function getEndForm() {
		$this->endForm = true;
		
		if (isset($this->form['options'])) {
			$html = "<form";
			foreach ($this->form['options'] as $label => $value) {
				$html .= " $label=\"$value\"";
			}
			$html .= ">\n";
		} else {
			$html = "<form data-ajax=\"false\" action=\"".$_SERVER['PHP_SELF']."\" method=\"POST\">\n";
		}
		
		foreach ($this->form['fields'] as $field) {
			$html .= $field . "\n";
		}
		$html .= "</form>\n";
		
		$this->form = array();
		
		return $html;
	}
	
	public function formAddHtml($html) {
		$this->form['fields'][] = $html;
	}
	
	public function formAddInput($options) {
		$this->formAddHtml($this->getInput($options));
	}
	
	public function getInput($options) {
		if (empty($options['name'])) {
			$options['name'] = uniqid('input');
		}
		
		if (empty($options['id'])) {
			$options['id'] = 'text-' . $options['name'];
		}
		
		$html = "<div>\n";
		$html .= "<fieldset data-role='controlgroup'>\n";
		if (!empty($options['label'])) {
			$html .= "<label for='" . $options['id'] . "'>" . $options['label'] . "</label>\n";
		}
		
		//Erase other options and only for the input
		unset($options['label']);
		
		$html .= "<input "; 
		foreach ($options as $option => $value) {
			$html .= " " . $option  . "='" . $value . "' ";
		}
		$html .= ">\n";
		
		$html .= "</fieldset>\n";
		$html .= "</div>\n";
		
		return $html;
	}
	
	public function getTextarea($options) {
		if (empty($options['name'])) {
			$options['name'] = uniqid('input');
		}
		
		if (empty($options['id'])) {
			$options['id'] = 'text-' . $options['name'];
		}
		
		$html = "<div>\n";
		$html .= "<fieldset data-role='controlgroup'>\n";
		if (!empty($options['label'])) {
			$html .= "<label for='" . $options['id'] . "'>" . $options['label'] . "</label>\n";
		}
		$text = "";
		if (!empty($options['value'])) {
			$text = $options['value'];
		}
		
		//Erase other options and only for the input
		unset($options['label']);
		unset($options['value']);
		
		$html .= "<textarea "; 
		foreach ($options as $option => $value) {
			$html .= " " . $option  . "='" . $value . "' ";
		}
		$html .= ">$text</textarea>\n";
		
		$html .= "</fieldset>\n";
		$html .= "</div>\n";
		
		return $html;
	}
	
	public function formAddInputPassword($options) {
		$options['type'] = 'password';
		
		$this->formAddInput($options);
	}
	
	public function formAddInputText($options) {
		$options['type'] = 'text';
		
		$this->formAddInput($options);
	}
	
	public function formAddInputSearch($options) {
		$options['type'] = 'search';
		
		$this->formAddInput($options);
	}
	
	public function formAddInputDate($options) {
		$options['type'] = 'date';
		$options['data-clear-btn'] = "false";
		
		$this->formAddInput($options);
	}
	
	public function formAddCheckbox($options) {
		$options['type'] = 'checkbox';
		
		if (isset($options['checked'])) {
			if ($options['checked']) {
				$options['checked'] = 'checked';
			}
			else {
				unset($options['checked']);
			}
		}
		$this->formAddInput($options);
	}
	
	public function formAddSubmitButton($options) {
		$options['type'] = 'submit';
		
		if (isset($options['icon'])) {
			$options['data-icon'] = $options['icon'];
			unset($options['icon']);
		}
		
		if (isset($options['icon_pos'])) {
			$options['data-iconpos'] = $options['icon_pos'];
			unset($options['icon_pos']);
		}
		
		if (isset($options['text'])) {
			$options['value'] = $options['text'];
			unset($options['text']);
		}
		
		$this->formAddInput($options);
	}
	
	public function formAddSelectBox($options) {
		$html = '';
		
		if (empty($options['name'])) {
			$options['name'] = uniqid('input');
		}
		
		if (empty($options['id'])) {
			$options['id'] = 'select-' . $options['name'];
		}
		
		$html = "<div data-role='fieldcontain'>\n";
		$html .= "<fieldset>\n";
		if (!empty($options['label'])) {
			$html .= "<label for='" . $options['id'] . "'>" . $options['label'] . "</label>\n";
		}
		
		$html .= "<select name='" . $options['name'] . "' " .
			"id='" . $options['id'] . "' data-native-menu='false'>\n";
		
		//Hack of jquery mobile
		$html .= "<option>" . $options['title'] . "</option>\n";
		if (empty($options['items']))
			$options['items'] = array();
		foreach ($options['items'] as $id => $item) {
			if(is_array($item)){
				if(!isset($lastopttype) || ($item['optgroup'] != $lastopttype)) {
					if(isset($lastopttype) && ($lastopttype != '')) {
						$html .= '</optgroup>';
					}
					$html .= '<optgroup label="'.$item['optgroup'].'">';
					$lastopttype = $item['optgroup'];
				}				
				$item = $item['name'];
			}
			
			if (!empty($options['item_id'])) {
				$item_id = $item[$options['item_id']];
			}
			else {
				$item_id = $id;
			}
			
			if (!empty($options['item_value'])) {
				$item_value = $item[$options['item_value']];
			}
			else {
				$item_value = $item;
			}
			
			$selected = '';
			if (isset($options['selected'])) {
				if (is_numeric($options['selected'])) {
					if (floatval($options['selected']) === floatval($item_id)) {
						$selected = "selected = 'selected'";
					}
				}
				else {
					if ($options['selected'] === $item_id) {
						$selected = "selected = 'selected'";
					}
				}
			}
			
			$html .= "<option " . $selected . " value='" . $item_id . "'>" . $item_value . "</option>\n";
		}
		$html .= "</select>\n";
		
		$html .= "</fieldset>\n";
		$html .= "</div>\n";
		
		$this->formAddHtml($html);
	}
	
	public function formAddSlider($options) {
		$options['type'] = 'range';
		
		$this->formAddInput($options);
	}
	
	public function addDialog($options) {
		
		$type = 'hidden';
		
		$dialog_id = uniqid('dialog_');
		$dialog_class = '';
		
		$title_close_button = false;
		$title_text = '';
		
		$content_id = uniqid('content_');
		$content_class = '';
		$content_text = '';
		
		$button_close = true;
		$button_text = __('Close');
		
		if (is_array($options)) {
			if (isset($options['type']))
				$type = $options['type'];
			if (isset($options['dialog_id']))
				$dialog_id = $options['dialog_id'];
			if (isset($options['dialog_class']))
				$dialog_class = $options['dialog_class'];
			if (isset($options['title_close_button']))
				$title_close_button = $options['title_close_button'];
			if (isset($options['title_text']))
				$title_text = $options['title_text'];
			if (isset($options['content_id']))
				$content_id = $options['content_id'];
			if (isset($options['content_class']))
				$content_class = $options['content_class'];
			if (isset($options['content_text']))
				$content_text = $options['content_text'];
			if (isset($options['button_close']))
				$button_close = $options['button_close'];
			if (isset($options['button_text']))
				$button_text = $options['button_text'];
		}
		
		$html_title_close_button = "";
		if ($title_close_button) {
			$html_title_close_button = "data-close-btn='yes'";
		}
		
		$dialogHtml = "<div id='" . $dialog_id . "' class='" . $dialog_class . "' data-role='dialog' " . $html_title_close_button . ">\n";
		$dialogHtml .= "<div data-role='header'>\n";
		$dialogHtml .= "<h1 class='dialog_title'>" . $title_text . "</h1>\n";
		$dialogHtml .= "</div>\n";
		$dialogHtml .= "<div id='" . $content_id . "' class='" . $content_class . "' data-role='content'>\n";
		$dialogHtml .= $content_text;
		if ($button_close) {
			$dialogHtml .= "<a data-role='button' href='#main_page'>";
			if (empty($button_text)) {
				$dialogHtml .= __('Close');
			}
			else {
				$dialogHtml .= $button_text;
			}
			$dialogHtml .= "</a></p>\n";
		}
		$dialogHtml .= "</div>\n";
		$dialogHtml .= "</div>\n";
		
		$this->dialogs[$type][] = $dialogHtml;
	}
	
	public function getPopupHTML ($options) {
		
		$popup_id = uniqid('popup_');
		$popup_class = '';
		$popup_content = '';
		
		$is_custom = false;
		
		if (is_array($options)) {
			if (isset($options['popup_id']))
				$popup_id = $options['popup_id'];
			if (isset($options['popup_class']))
				$popup_class = $options['popup_class'];
			if (isset($options['popup_content']))
				$popup_content = $options['popup_content'];
			if (! isset($options['data-transition']))
				$options['data-transition'] = 'slidedown';
			if (isset($options['popup_custom']))
				$is_custom = (bool) $options['popup_custom'];
			
			unset($options['popup_id']);
			unset($options['popup_class']);
			unset($options['popup_content']);
			unset($options['popup_custom']);
		}
		
		$popupHtml = '';
		
		if ($is_custom) $popupHtml .= "<div class=\"popup-back\">\n";
		
		$popupHtml .= "<div ";
		$popupHtml .= "id=\"$popup_id\" ";
		$popupHtml .= "class=\"$popup_class " . (($is_custom) ? " custom-popup" : "") . "\" ";
		if (!$is_custom) $popupHtml .= "data-role=\"popup\" ";
		foreach ($options as $option => $value) {
			$popupHtml .= " " . $option  . "='" . $value . "' ";
		}
		$popupHtml .= ">\n";
		$popupHtml .= $popup_content;
		$popupHtml .= "</div>\n";
		
		if ($is_custom) $popupHtml .= "</div>\n";
		
		return $popupHtml;
	}
	
	public function addPopup ($options) {
		$this->contentAddHtml($this->getPopupHTML($options));
	}
	
	public function getDeletePopupHTML ($options) {
		
		if (isset($options['dialog_title'])) {
			$title = $options['dialog_title'];
		} else {
			$title = __('Delete item');
		}
		if (isset($options['dialog_content'])) {
			$content = $options['dialog_content'];
		} else {
			$content = "<h3>".__('Are you sure you want to delete this item?')."</h3>";
			$content .= "<p>".__('This action cannot be undone.')."</p>";
		}
		if (isset($options['delete_href'])) {
			$delete_href = $options['delete_href'];
			unset($options['delete_href']);
		} else {
			$delete_href = "#";
		}
		$options['data-position-to'] = 'window';
		$options['data-transition'] = 'pop';
		
		$options['popup_content'] = "<div data-role=\"header\" data-theme=\"a\">\n
										<h1>$title</h1>\n
									</div>\n
									<div data-role=\"content\" data-theme=\"d\">\n
										$content
										<a href=\"#\" data-role=\"button\" data-inline=\"true\" data-rel=\"back\" data-theme=\"c\" data-corners=\"true\" data-shadow=\"true\" data-iconshadow=\"true\" data-wrapperels=\"span\">".__('Cancel')."</a>
										<a href=\"$delete_href\" data-role=\"button\" data-inline=\"true\" data-rel=\"back\" data-transition=\"flow\" data-theme=\"b\" data-corners=\"true\" data-shadow=\"true\" data-iconshadow=\"true\" data-wrapperels=\"span\">".__('Delete')."</a>
									</div>\n";
		
		return $this->getPopupHTML($options);
	}
	
	public function addDeletePopup ($options) {
		$this->contentAddHtml($this->getDeletePopupHTML($options));
	}
	
	public function getWarningPopupHTML ($options) {
		
		if (isset($options['dialog_title'])) {
			$title = $options['dialog_title'];
		} else {
			$title = __('You don\'t have access to this page');
		}
		if (isset($options['dialog_content'])) {
			$content = $options['dialog_content'];
		} else {
			$content = "<h2>".__('You don\'t have access to this page')."</h2>";
			$content .= "<p>".__('Access to this page is restricted to authorized users only,
								please contact to system administrator if you need assistance.
								<br><br>Please know that all attempts to access this page are
								recorded in security logs of Integria System Database')."</p>";
		}
		
		$options['data-position-to'] = 'window';
		$options['data-transition'] = 'pop';
		
		$options['popup_content'] = "
									<div style=\"text-align:center;\" data-role=\"content\" data-theme=\"d\">\n
										$content
										<a href=\"#\" data-role=\"button\" data-inline=\"true\" data-rel=\"back\" data-transition=\"flow\" data-theme=\"b\" data-corners=\"true\" data-shadow=\"true\" data-iconshadow=\"true\" data-wrapperels=\"span\">".__('OK')."</a>
									</div>\n";
		
		return $this->getPopupHTML($options);
	}
	
	public function addWarningPopup ($options) {
		$this->contentAddHtml($this->getWarningPopupHTML($options));
	}
	
	public function getPriorityFlagImage ($priority) {
		
		$output .= '<img class="icon-priority ui-li-icon" ';
		switch ($priority) {
		case 0:
			// Informative
			$output .= 'src="../images/pixel_gray.png" title="'.__('Informative').'" ';
			break;
		case 1:
			// Low
			$output .= 'src="../images/pixel_green.png" title="'.__('Low').'" ';
			break;
		case 2:
			// Medium
			$output .= 'src="../images/pixel_yellow.png" title="'.__('Medium').'" ';
			break;
		case 3:
			// Serious
			$output .= 'src="../images/pixel_orange.png" title="'.__('Serious').'" ';
			break;
		case 4:
			// Very serious
			$output .= 'src="../images/pixel_red.png" title="'.__('Very serious').'" ';
			break;
		case 10:
			// Maintance
			$output .= 'src="../images/pixel_blue.png" title="'.__('Maintance').'" ';
			break;
		default:
			// Default
			$output .= 'src="../images/pixel_gray.png" title="'.__('Unknown').'" ';
		}
		
		$output .= ' />';
		
		return $output;
	}
	
	public function bindMobileAutocomplete ($idInput, $idListview, $idProject = false, $callbackF = "") {
		
		if ($idProject) {
			$ajaxUrl = "index.php?action=ajax&page=user&method=search_users_role&id_project=$idProject";
		} else {
			$ajaxUrl = "index.php?action=ajax&page=user&method=search_users";
		}
		
		$html = "<script type=\"text/javascript\" src=\"include/javascript/jqm.autoComplete-1.5.2.js\"></script>";
		$html .= "<script type=\"text/javascript\">
					$(document).on(\"pageshow\", function() {
						$(\"$idInput\").autocomplete({
							method: \"GET\",
							target: $(\"$idListview\"),
							source: \"$ajaxUrl\",
							minLength: 2,
							callback: function(e) {
								var a = $(e.currentTarget);
								$(\"$idInput\").val(a.data(\"autocomplete\").value);
								$(\"$idListview\").html(\"\");

								$callbackF
							}
						});
						$(\"$idInput\").bind(\"targetUpdated.autocomplete\", function(e) {
							$.mobile.silentScroll($(e.currentTarget).offset().top);
						});
						$(\"$idInput\").focus(function() {
							if ($(\"$idListview\").html().length > 0) {
								$(\"$idListview\").slideDown();
							}
						});
						$(\"$idInput\").blur(function() {
							if ($(\"$idListview\").html().length > 0) {
								$(\"$idListview\").slideUp();
							}
						});
					});
				</script>";
		
		$this->contentAddHtml($html);
	}
	
	public function getPaginationControgroup ($page, $offset = 1, $numPages = 1) {
		
		if ($offset <= 1) {
			$button_first = "<a class='ui-disabled' data-role='button' data-ajax='false'
							data-icon='back' data-theme='b' data-iconpos='notext'>".__('First')."</a>\n";
			$button_back = "<a class='ui-disabled' data-role='button' data-ajax='false'
							data-icon='arrow-l' data-theme='b' data-iconpos='notext'>".__('Back')."</a>\n";
		} else {
			$button_first = "<a href='index.php?page=$page&offset=1' data-role='button' data-ajax='false'
							data-icon='back' data-theme='b' data-iconpos='notext'>".__('First')."</a>\n";
			$button_back = "<a href='index.php?page=$page&offset=".($offset -1)."' data-role='button'
							data-icon='arrow-l' data-theme='b' data-iconpos='notext'>".__('Back')."</a>\n";
		}
		if ($offset >= $numPages) {
			$button_last = "<a class='ui-disabled' data-role='button' data-ajax='false'
							data-icon='forward' data-theme='b' data-iconpos='notext'>".__('Last')."</a>\n";
			$button_forward = "<a class='ui-disabled' data-role='button' data-ajax='false'
								data-icon='arrow-r' data-theme='b' data-iconpos='notext'>".__('Forward')."</a>\n";
		} else {
			$button_last = "<a href='index.php?page=$page&offset=".$numPages."' data-role='button' data-ajax='false'
							data-icon='forward' data-theme='b' data-iconpos='notext'>".__('Last')."</a>\n";
			$button_forward = "<a href='index.php?page=$page&offset=".($offset +1)."' data-role='button' data-ajax='false'
								data-icon='arrow-r' data-theme='b' data-iconpos='notext'>".__('Forward')."</a>\n";
		}
		
		return "<div style='float:right; padding-right:25px;' data-type='horizontal' data-role='controlgroup'>
					$button_first
					$button_back
					$button_forward
					$button_last
				</div>";
	}
	
	public function addNavBar ($buttons) {
		$options = array (
			'class' => 'ui-bar',
			'data-position' => 'fixed',
			'role' => 'contentinfo',
			'data-id' => 'incident-tabs',
			'style' => 'padding: 0px;'
			);
		$this->beginFooter($options);
		$html = "<div data-role='navbar'>\n
					<ul>\n";
		foreach ($buttons as $button) {
			$html .= "	<li>$button</li>\n";
		}
		$html .= "	</ul>
				 </div>\n";
		$this->createFooter($html);
		$this->endFooter();
		$this->showFooter();
	}
	
	public function showError($msg) {
		echo $msg;
	}
	
	public function showPage() {
		
		if (!$this->endHeader) {
			$this->showError(__('Header not found.'));
		}
		else if (!$this->endContent) {
			$this->showError(__('Content not found.'));
		}
		else if ((!$this->endFooter) && (!$this->noFooter)) {
			//$this->showError(__('Footer not found.'));
		}
		else if (!$this->endForm) {
			$this->showError(__('Incorrect form.'));
		}
		else if (!$this->endGrid) {
			$this->showError(__('Incorrect grid.'));
		}
		else if (!$this->endCollapsible) {
			$this->showError(__('Incorrect collapsible.'));
		}
		
		ob_start ();
		echo "<!DOCTYPE html>\n";
		echo "<html>\n";
		echo "	<head>\n";
		echo "		<title>" . $this->title . "</title>\n";
		echo "		<meta charset='UTF-8' />\n";
		echo "		<meta name='viewport' content='width=device-width, initial-scale=1'>\n";
		echo "		<link rel='icon' href='../images/integria_mini_logo.png' type='image/png' />\n";
		echo "		<link rel='stylesheet' href='include/style/main.css' />\n";
		echo "		<link rel='stylesheet' href='include/style/calendar.css' />\n";
		echo "		<link rel='stylesheet' href='include/style/jquery.mobile-1.3.2.css' />\n";
		echo "		<script src='include/javascript/jquery.js'></script>\n";
		echo "		<script src='include/javascript/jquery.mobile-1.3.2.js'></script>\n";
		
		echo "	</head>\n";
		echo "	<body>\n";
		echo "		<div class='ui-loader-background'> </div>";
		if (!empty($this->dialogs)) {
			if (!empty($this->dialogs['onStart'])) {
				foreach ($this->dialogs['onStart'] as $dialog) {
					echo "		" . $dialog . "\n";
				}
			}
		}
		echo "		<div data-dom-cache='false' data-role='page' id='" . $this->page_name . "'>\n";
		echo "			<div data-role='header' data-position='fixed' >\n";
		echo "				" . $this->header['title'] . "\n";
		echo "				" . $this->header['button_left'] . "\n";
		echo "				" . $this->header['button_right'] . "\n";
		echo "			</div>\n";
		echo "			<div data-role='content'>\n";
		foreach ($this->content as $content) {
			echo "				" . $content . "\n";
		}
		echo "			</div>\n";
		if (!$this->noFooter) {
			if (isset($this->footer['options'])) {
				echo "		<div data-role='footer'";
				foreach ($this->footer['options'] as $key => $value) {
					echo " $key='$value'";
				}
				echo "		>\n";
			} else {
				echo "		<div data-role='footer' class='ui-bar' data-position='fixed' role='contentinfo'>\n";
			}
			if (!empty($this->footer['text'])) {
				echo "				" . $this->footer['text'] . "\n";
			}
			else {
				echo "				" . $this->defaultFooter() . "\n";
			}
		}
		echo "			</div>\n";
		echo "		</div>\n";
		if (!empty($this->dialogs)) {
			if (!empty($this->dialogs['hidden'])) {
				foreach ($this->dialogs['hidden'] as $dialog) {
					echo "		" . $dialog . "\n";
				}
			}
		}
		echo "<script type='text/javascript'>
			$(document).bind('mobileinit', function(){
				//Disable ajax link
				$('.disable-ajax').click(function(event){
					$.mobile.ajaxFormsEnabled = false;
				});
			});
			</script>";
		echo "<script type='text/javascript'>
				$(document).bind('mobileinit', function () {
					$.mobile.ajaxEnabled = false;
					$.mobile.linkBindingEnabled = false;
					$.mobile.hashListeningEnabled = false;
					$.mobile.pushStateEnabled = false;
				});
			</script>";
		echo "	</body>\n";
		echo "</html>";
		ob_end_flush();
	}
	
}


class Table {
	private $head = array();
	private $rows = array();
	public $id = '';
	private $rowClass = array();
	private $class_table = '';
	private $row_heads = array();
	public $row_keys_as_head_row = false;
	
	public function __construct() {
		$this->init();
	}
	
	public function init() {
		$this->id = uniqid();
		$this->head = array();
		$this->rows = array();
		$this->rowClass = array();
		$this->class_table = '';
		$this->row_heads = array();
		$this->row_keys_as_head_row = false;
	}
	
	public function addHeader($head) {
		$this->head = $head;
	}
	
	public function addRowHead($key, $head_text) {
		$this->row_heads[$key] = $head_text;
	}
	
	public function addRow($row = array(), $key = false) {
		if ($key !== false) {
			$this->rows[$key] = $row;
		}
		else {
			$this->rows[] = $row;
		}
	}
	
	public function importFromHash($data) {
		foreach ($data as $id => $row) {
			$table_row = array();
			foreach ($row as $key => $value) {
				if (!in_array($key, $this->head)) {
					$this->head[] = $key;
				}
				
				$cell_key = array_search($key, $this->head);
				
				$table_row[$cell_key] = $value;
			}
			
			$this->rows[$id] = $table_row;
		}
	}
	
	public function setClass($class = '') {
		$this->class_table = $class;
	}
	
	public function setId($id = false) {
		if (empty($id)) {
			$this->id = uniqid();
		}
		else {
			$this->id = $id;
		}
	}
	
	public function setRowClass($class = '', $pos = false) {
		if (is_array($class)) {
			$this->rowClass = $class;
		}
		else {
			if ($pos !== false) {
				$this->rowClass[$pos] = $class;
			}
			else {
				$this->rowClass = array_fill(0, count($this->rows), $class);
			}
		}
	}
	
	public function getHTML() {
		$html = '';
		
		$html = "<table data-role='table' id='" . $this->id . "' " .
			"data-mode='reflow' class='" . $this->class_table . " ui-responsive table-stroke'>\n";
		
		
		$html .= "<thead>\n";
		$html .= "<tr>\n";
		//Empty head for white space between rows in the responsive vertical layout
		$html .= "<th></th>\n";
		foreach ($this->head as $head) {
			$html .= "<th class='head_horizontal'>" . $head . "</th>\n";
		}
		$html .= "</tr>\n";
		$html .= "</thead>\n";
		
		$html .= "<tbody>\n";
		foreach ($this->rows as $key => $row) {
			$class = '';
			if (isset($this->rowClass[$key])) {
				$class = $this->rowClass[$key];
			}
			
			$html .= "<tr class='" . $class . "'>\n";
			//Empty head for white space between rows in the responsive vertical layout
			if (isset($this->row_heads[$key])) {
				$html .= "<th class='head_vertical'>" . $this->row_heads[$key] . "</th>\n";
			}
			elseif ($this->row_keys_as_head_row) {
				$html .= "<th class='head_vertical'>" . $key . "</th>\n";
			}
			else {
				$html .= "<th class='head_vertical'></th>\n";
			}
			foreach ($row as $key_cell => $cell) {
				$html .= "<td class='cell_" . $key_cell . "'>" . $cell . "</td>\n";
			}
			$html .= "</tr>\n";
		}
		
		$html .= "</tbody>\n";
		$html .= "</table>\n";
		
		return $html;
	}
}

?>
