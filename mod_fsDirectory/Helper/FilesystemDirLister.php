<?php
/**
 * @package FSDirectory
 *
 * @author    Serge Mister <cf744@ncf.ca>
 * @license   GNU General Public License Version 3 or any later version; see LICENSE file 
 * @copyright 2022
 **/

namespace Joomla\Module\FSDirectory\Site\Helper;

use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/** FilesystemDirLister - Generates a directory listing from a trusted
   base path (the path parameter).  If configured to allow recursion,
   a subpath can be specified, allowing access to subdirectories.  A
   set of allowed file extensions can be provided as well.

   When navigating the subpath, the implementation takes the cautious
   approach of enumerating the content of the parent path, then
   looking for a match between the subpath and the directories
   detected.  This allows safe support of arbitrary directory names
   while avoiding risks associated with treating the subpath directly
   as a filesystem path string (e.g. avoiding Windows special file
   names that wouldn't appear in a directory listing). */
class FilesystemDirLister {
    /* Module parameter values */
    private $param_displayName;
    private $param_path;
    private $param_recurse;
    private $param_allowedExtensions;

    /* The module id associated with this listing (used to generate
       links) */
    private $moduleId;

    /* true if the $param_path is a directory */
    private $isDir;

    /* The subpath user input, vetted against the directory content on the host */
    private $subpath;

    /* The subpath of the parent directory of the path+subpath; null
       means we're at the top */
    private $parentSubpath;

    /* The array of FilesystemDirEntry objects representing the content */
    private $dirEntries;

    /** Constructs a FilesystemDirLister object, enumerating the
       directory content.

       @param $module - The FSDirectory module associated with the
       request.

       @param $input_subpath - The subpath to be listed; this
       parameter is as yet unvalidated user input.

       @param $loadDescriptions - Set to true to request that file
       descriptions be loaded from the file system as well.
       This is not yet implemented. */
    public function __construct($module, string $input_subpath, bool $loadDescriptions) {
	// TODO Implement loadDescriptions

	if (JDEBUG) {
	    log::add("fsDirectory - Directory Lister; subpath '$input_subpath'", Log::DEBUG);
	}
	
	// Gets set to false if the input values are not valid
	$params_ok=true;
	
    	$this->moduleId=$module->id;

	// Load the parameters
	$paramsRegistry=new Registry($module->params);
	$this->param_displayName=trim($paramsRegistry->get('displayName', ''));
	$this->param_path=rtrim(trim($paramsRegistry->get('path', '')), ' \t'.DIRECTORY_SEPARATOR);
	$this->param_recurse=filter_var($paramsRegistry->get('recurse', 'false'), FILTER_VALIDATE_BOOLEAN);
	$this->param_allowedExtensions=trim($paramsRegistry->get('allowedExtensions', ''));

	$this->isDir=is_dir($this->param_path);
	/* $this->subpath and $this->parentSubpath will be built up as
	   the input subpath is validated */
	$this->subpath='';
	$this->parentSubpath=null;
	$this->dirEntries=array();

	/* The three member variables above will be populated when the
    	   processing is complete.  They are computed using the local
    	   variables below. */
	
	// The subpath of the entries
	$outputSubpath='';

	// The subpath of the parent directory; null means we're at the top
	$parentSubpath=null;

	// The array of file entries associated with param_path/subpath
	$dirEntries=array();

	$input_subpathArray=array();

	if ($input_subpath!='') {
	    if ($this->param_recurse) {
		$input_subpathArray=explode(DIRECTORY_SEPARATOR, $input_subpath);
	    } else {
		$params_ok=false;
	    }
	}

	/* allowedExtensionsArray==null means all extensions are allowed */
	$allowedExtensionsArray=null;
	if ($this->param_allowedExtensions!='' && $this->param_allowedExtensions!='all') {
	    $allowedExtensionsArray=explode(',', $this->param_allowedExtensions);
	}

	if ($this->param_path=='') {
	    $params_ok=false;
	    log::add('fsDirectory - Module path parameter cannot be empty', Log::ERROR);
	}
	
	if ($params_ok && $this->isDir) {
	    $path=$this->param_path;
	    do {
		$repeat=0;
		foreach (scandir($path) as $dirEntry) {
		    /* Skip ., .., and hidden files and directories */
		    if ($dirEntry[0]=='.') {
			continue;
		    }
		    $entryIsDir=is_dir($path.DIRECTORY_SEPARATOR.$dirEntry);
		    if (empty($input_subpathArray)) {
			/* Collect all the allowed file and directory entries */
			if (($entryIsDir && $this->param_recurse) || (!$entryIsDir && self::extensionAllowed($allowedExtensionsArray, $dirEntry))) {
			    $dirEntries[]=new FilesystemDirEntry($this->moduleId, $this->param_path, $outputSubpath, $dirEntry, '');
			}
		    } else if ($dirEntry==$input_subpathArray[0] && $entryIsDir) {
			/* Go into the subdirectory */
			array_shift($input_subpathArray);
			$path=$path.DIRECTORY_SEPARATOR.$dirEntry;
			$parentSubpath=$outputSubpath;
			if ($outputSubpath=='') {
			    $outputSubpath=$dirEntry;
			} else {
			    $outputSubpath=$outputSubpath.DIRECTORY_SEPARATOR.$dirEntry;
			}
			$repeat=1;
			break;
		    }
		}
	    } while ($repeat==1);
	    /* If $input_subpathArray is not empty, the requested path was not found */
	    if (empty($input_subpathArray)) {
		$this->subpath=$outputSubpath;
		$this->parentSubpath=$parentSubpath;
		$this->dirEntries=$dirEntries;
	    }
	} else {
	    log::add('fsDirectory - Module path parameter is not a directory or error in parameters', Log::ERROR);
	}
    }

    /** Returns true if $fileName has a file extension allowed by
       $allowedExtensions.

       @param $allowedExtensions - An array of extensions that are
       allowed (without a leading .), or null if all extensions are
       allowed.

       @param $fileName - The file name to be checked. */
    private static function extensionAllowed($allowedExtensions, string $fileName): bool {
	if ($allowedExtensions===null) {
	    return true;
	}
	$lastDot=strrpos($fileName, '.');
	if ($lastDot===false) {
	    return false;
	}
	$extension=substr($fileName, $lastDot+1);
	return in_array($extension, $allowedExtensions);
    }
    
    public function getDirEntryList(): array {
    	return $this->dirEntries;
    }

    /** Returns the URL of the parent directory, or null if the
       current directory is the root */
    public function getParentURL() {
    	$subpathVar=self::getSubpathVar($this->moduleId);
	if (is_null($this->parentSubpath)) {
	    return null;
	}
	$sharedUri=Uri::getInstance();
	$currentPage=new Uri($sharedUri->toString());
	if ($this->parentSubpath=='') {
	    $currentPage->delVar($subpathVar);
	} else {
	    $currentPage->setVar($subpathVar,urlencode($this->parentSubpath));
	}
	return $currentPage->toString();
    }

    /** Returns the display name of the directory or subdirectory */
    public function getDisplayName(): string {
	if ($this->subpath=='') {
	    return $this->param_displayName;
	} else {
	    return $this->param_displayName.DIRECTORY_SEPARATOR.$this->subpath;
	}
    }

    /** Returns the name of the subpath query string variable for the
       given module id */
    public static function getSubpathVar(int $moduleId): string {
	return "subpath$moduleId";
    }
}
