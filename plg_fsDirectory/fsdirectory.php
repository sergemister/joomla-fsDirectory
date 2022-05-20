<?php
/**
 * @package FSDirectory
 *
 * @author    Serge Mister <cf744@ncf.ca>
 * @license   GNU General Public License Version 3 or any later version; see LICENSE file 
 * @copyright 2022
 *
 * Handles the file download functionality of the package.  Access to
 * the requested file is dictated by the ACL on the module.  The
 * plugin fetches the module by id.  If the module is accessible, then
 * access is granted.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\SubscriberInterface;
use Joomla\Module\FSDirectory\Site\Helper\FilesystemDirLister;

class PlgSystemFSDirectory extends CMSPlugin implements SubscriberInterface {
    protected $app;
    protected $autoloadLanguage=true;
    
    public static function getSubscribedEvents(): array {
	return [
	    'onAfterRoute' => 'onAfterRoute'
	];
    }

    public function onAfterRoute() {
    	if (!$this->app->isClient('site') || $this->app->get('offline','0')) {
	    return true;
	}
	
	$input_fileName=$this->app->input->get('fsdownload','','STRING');
	if ($input_fileName=='') {
	    return true;
	}
	
	$input_moduleId=$this->app->input->get('module',0,'INTEGER');
	if ($input_moduleId==0) {
	    return true;
	}

	/* If we get here, our plugin has the query parameters that
	   suggest the plugin was deliberately invoked */

	$input_subpath=$this->app->input->get('subpath','','PATH');

	/* getModuleById only returns modules that are published and
	   accessible according to the ACL */
	$module=ModuleHelper::getModuleById("$input_moduleId");
	if ($module->id==0) {
	    /* Module not found; this may be because the user needs to
	       login */
	    return true;
	}

	if ($module->module!='mod_fsdirectory') {
	    return true;
	}

	/* Delete the parameters so that the module produces clean URLs */
	$sharedUri=Uri::getInstance();
	$sharedUri->delVar('fsdownload');
	$sharedUri->delVar('module');
	$sharedUri->delVar('subpath');
	
	$dirInfo=new FilesystemDirLister($module, $input_subpath, false);
	$fileEntry=null;
	foreach ($dirInfo->getDirEntryList() as $entry) {
	    if ($entry->getName()==$input_fileName) {
		$fileEntry=$entry;
		break;
	    }
	}
	// TODO The messages below need to be in the language .ini
	if ($fileEntry==null) {
	    $this->app->enqueueMessage('Requested file is not available','error');
	} else {
	    if (!$entry->outputFileContent($this->app)) {
		$this->app->enqueueMessage('File retrieval failed','error');
	    }
	}
	return true;
    }
}
