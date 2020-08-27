<?php
/**
 *
 * Activates miresize plugin on install
 *
 * @package		Magic image resize
 * @copyright	Copyright 2020 (C) computer.daten.netze::feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.txt
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 * @version    0.9
 *
 **/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class PlgcontentmiresizeInstallerScript
{
	
		/**
	 * Constructor
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 */
	public function __construct($adapter) {}

	/**
	 * Called before any type of action
	 *
	 * @param   string  $route  Which action is happening (install|uninstall|discover_install|update)
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function preflight($route, $adapter) {}

	/**
	 * Called after any type of action
	 *
	 * @param   string  $route  Which action is happening (install|uninstall|discover_install|update)
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function postflight($route, $adapter) {}

	/**
	 * Called on installation
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function install($adapter) {
				// Enable plugin
		$db  = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->update('#__extensions');
		$query->set($db->quoteName('enabled') . ' = 1');
		$query->where($db->quoteName('element') . ' = ' . $db->quote('miresize'));
		$query->where($db->quoteName('type') . ' = ' . $db->quote('plugin'));
		$db->setQuery($query);
		$db->execute();
		echo "<p>Activated</p>";
	}

	/**
	 * Called on update
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function update($adapter) {}

	/**
	 * Called on uninstallation
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 */
	public function uninstall($adapter) {}
	
}
