<?php
/**
 * @package FSDirectory
 *
 * @author    Serge Mister <cf744@ncf.ca>
 * @license   GNU General Public License Version 3 or any later version; see LICENSE file 
 * @copyright 2022
 *
 * Generates a directory listing with links for subdirectories and
 * download links for the files.  The links to subdirectories and
 * download links point back to the URL containing the module, but add
 * module-specific parameters:
 *
 * For directories:
 *    - subpath<moduleId>=subpath
 *
 * For files:
 *    - module=module id
 *    - subpath=subpath
 *    - fsdownload=file name
 *
 * The subpath parameter name for directories includes the module id
 * in its name so that multiple FSDirectory modules can coexist on a
 * page.  All instances of the same module on a page will display the
 * same subdirectory.
 **/

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Module\FSDirectory\Site\Helper\FilesystemDirLister;

/* Most of the module's parameters are handled by FilesystemDirLister */
$param_target=trim($params->get('target','','STRING'));

/* The name of the subpath parameter specific to the module */
$subpathVar=FilesystemDirLister::getSubpathVar($module->id);

$input_subpath=$app->input->get($subpathVar,'','PATH');

/* Below are the variables that will be accessed by the template */
$dirInfo=new FilesystemDirLister($module, $input_subpath, true);
$target=$param_target;

require ModuleHelper::getLayoutPath('mod_fsdirectory', $params->get('layout', 'default'));
