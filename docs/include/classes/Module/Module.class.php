<?php
/************************************************************************/
/* ATutor																*/
/************************************************************************/
/* Copyright (c) 2002-2004 by Greg Gay, Joel Kronenberg & Heidi Hazelton*/
/* Adaptive Technology Resource Centre / University of Toronto			*/
/* http://atutor.ca														*/
/*																		*/
/* This program is free software. You can redistribute it and/or		*/
/* modify it under the terms of the GNU General Public License			*/
/* as published by the Free Software Foundation.						*/
/************************************************************************/
// $Id$

/**
* ModuleFactory
* 
* @access	public
* @author	Joel Kronenberg
* @package	Module
*/
class ModuleFactory {
	// private
	var $_enabled_modules       = array();
	var $_disabled_modules      = array();
	var $_installed_modules     = array();
	var $_not_installed_modules = array();
	var $_all_modules           = array();

	var $_db;

	function ModuleFactory($auto_load = FALSE) {
		global $db;

		$this->db =& $db;

		// initialise enabled modules
		$sql	= "SELECT dir_name, privilege FROM ". TABLE_PREFIX . "modules WHERE status=".AT_MOD_ENABLED;
		$result = mysql_query($sql, $this->db);
		while($row = mysql_fetch_assoc($result)) {
			$module =& new ModuleProxy($row['dir_name'], TRUE);
			$this->_enabled_modules[$row['dir_name']]   =& $module;

			if ($auto_load == TRUE) {
				$module->load();
			}
		}
		$this->_all_modules       = array_merge($this->_enabled_modules);
		$this->_installed_modules = array_merge($this->_enabled_modules);
	}

	function & getModule($module_dir) {
		if (!isset($this->_all_modules[$module_dir])) {
			$module =& new ModuleProxy($module_dir);
			if ($module->isEnabled()) {
				$this->_enabled_modules[$module_dir]   =& $module;
				$this->_installed_modules[$module_dir] =& $module;
			}

			$this->_all_modules[$module_dir] =& $module;
		}
		return $this->_all_modules[$module_dir];
	}

	function & getEnabledModules() {
		return $this->_enabled_modules;
	}

	function & getInstalledModules() {
		// already have enabled modules, so need to get list of disabled modules
		$this->initDisabledModules();

		return $this->_installed_modules;
	}

	function & getUnInstalledModules() {
		static $initialised;
		if (!$initialised) {
			$this->initUnInstalledModules();
		}
		$initialised = TRUE;
	
		return $this->_not_installed_modules;
	}

	// private
	function initUnInstalledModules() {
		$this->initInstalledModules();

		// has to scan the dir
		$dir = opendir(AT_INCLUDE_PATH.'../mods/');
		while (false !== ($dir_name = readdir($dir))) {
			if (($dir_name == '.') || ($dir_name == '..') || ($dir_name == '.svn')) {
				continue;
			}

			if (is_dir(AT_INCLUDE_PATH.'../mods/' . $dir_name) && !isset($this->_installed_modules[$dir_name])) {
				$module =& new ModuleProxy($dir_name, FALSE);
				$this->_not_installed_modules[$dir_name]  =& $module;
				$this->_all_modules[$dir_name]            =& $module;
			}
		}
		closedir($dir);
	}

	// private
	function initDisabledModules() {
		static $initialised;
		if ($initialised) {
			return;
		}
		$initialised = TRUE;
		$sql	= "SELECT dir_name, privilege FROM ". TABLE_PREFIX . "modules WHERE status=".AT_MOD_DISABLED;
		$result = mysql_query($sql, $this->db);
		while($row = mysql_fetch_assoc($result)) {
			$module =& new ModuleProxy($row['dir_name'], FALSE);
			$this->_disabled_modules[$row['dir_name']]  =& $module;
			$this->_all_modules[$row['dir_name']]       =& $module;
			$this->_installed_modules[$row['dir_name']] =& $module;

		}
	}

	// private
	function initInstalledModules() {
		// installed modules are Enabled (always given) + Disabled
		$this->initDisabledModules();
	}
}

/**
* ModuleProxy
* 
* @access	public
* @author	Joel Kronenberg
* @package	Module
*/
class ModuleProxy {
	// private
	var $_moduleObj;

	var $_directoryName;

	var $_enabled; // enabled|disabled

	var $_privilege; // priv bit | 0

	function ModuleProxy($dir, $enabled = FALSE) {
		$this->_directoryName = $dir;
		$this->_enabled       = $enabled;
	}

	function isEnabled() {
		return $this->_enabled;
	}

	function isCore() {
		if (!isset($this->_moduleObj)) {
			$this->_moduleObj =& new Module($this->_directoryName);
		}
		return $this->_moduleObj->isCore();
	}

	function getProperties($properties_list) {
		// this requires a real module object
		if (!isset($this->_moduleObj)) {
			$this->_moduleObj =& new Module($this->_directoryName);
		}
		return $this->_moduleObj->getProperties($properties_list);
	}

	function getProperty($property) {
		// this requires a real module object
		if (!isset($this->_moduleObj)) {
			$this->_moduleObj =& new Module($this->_directoryName);
		}
		return $this->_moduleObj->getProperty($property);
	}

	function getVersion() {
		// this requires a real module object
		if (!isset($this->_moduleObj)) {
			$this->_moduleObj =& new Module($this->_directoryName);
		}
		return $this->_moduleObj->getVersion();
	}


	function getName($lang) {
		// this requires a real module object
		if (!isset($this->_moduleObj)) {
			$this->_moduleObj =& new Module($this->_directoryName);
		}
		return $this->_moduleObj->getName($lang);
	}

	function getDescription($lang) {
		// this requires a real module object
		if (!isset($this->_moduleObj)) {
			$this->_moduleObj =& new Module($this->_directoryName);
		}
		return $this->_moduleObj->getDescription($lang);
	}

	function load() {
		if (is_file(AT_INCLUDE_PATH.'../mods/'.$this->_directoryName.'/module.php')) {
			global $_modules, $_pages;

			//$mod_priv = intval($row['privilege']);
			require(AT_INCLUDE_PATH.'../mods/'.$this->_directoryName.'/module.php');
		}
	}


	function getPrivilege() {
		return 0;
	}

	function backup($course_id) {

	}

	function restore($course_id) {

	}

	function delete($course_id) {

	}

	function enable() {

	}

	function disable() {

	}

	function install() {

	}
}

// ----------------- in a diff file. only required when .. required.
/**
* Module
* 
* @access	protected
* @author	Joel Kronenberg
* @package	Module
*/
class Module {
	// all private
	var $_directory_name;
	var $_properties; // array from xml

	function Module($dir_name) {
		require_once(dirname(__FILE__) . '/ModuleParser.class.php');
		$moduleParser   =& new ModuleParser();

		$moduleParser->parse(file_get_contents(AT_INCLUDE_PATH . '../mods/'.$dir_name.'/module.xml'));
		$this->_properties = $moduleParser->rows[0];
	}

	function getVersion() {
		return $this->_properties['version'];
	}

	function getName($lang = 'en') {
		// this may have to connect to the DB to get the name.
		// such that, it returns _AT($this->_directory_name) instead.

		return (isset($this->_properties['name'][$lang]) ? $this->_properties['name'][$lang] : current($this->_properties['name']));
	}

	function getDescription($lang = 'en') {
		// this may have to connect to the DB to get the name.
		// such that, it returns _AT($this->_directory_name) instead.

		return (isset($this->_properties['description'][$lang]) ? $this->_properties['description'][$lang] : current($this->_properties['description']));
	}

	function getProperties($properties_list) {

		$properties_list = array_flip($properties_list);
		foreach ($properties_list as $property => $garbage) {
			$properties_list[$property] = $this->_properties[$property];
		}
		return $properties_list;
	}

	
	function getProperty($property) {
		return $this->_properties[$property];
	}


	function isCore() {
		if (strcasecmp($this->_properties['core'], 'true') == 0) {
			return TRUE;
		}
		return FALSE;
	}

	function backup($course_id) {

	}

	function restore($course_id) {

	}

	function delete($course_id) {

	}

	function enable() {

	}

	function disable() {

	}

	function install() {

	}
}

?>