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

defined('_JEXEC') or die;

/** FilesystemDirEntry - Represents a directory entry.  This class
   represents a single file or directory entry.  It holds information
   about the entry and is able to generate a URL pointing to the
   entry, and for files, to output their content.  The class expects
   that path strings pointing to directories NOT include a trailing /.
 **/
class FilesystemDirEntry {

    /** The module id of the module used to access the directory **/
    private $moduleId;

    /** The full filesystem path to the file or directory **/
    private $fullPath;

    /** The path of the file or directory, relative to the module path parameter **/
    private $subpath;

    /** The name of the file or directory **/
    private $name;

    /** A description of the file or directory **/
    private $description;

    /** Set to true if the named entry is a directory **/
    private $isDir;

    /**
       @param $moduleId - The id of the module used to access the directory

       @param $path - The path parameter associated with the module

       @param $subpath - The path of the file or directory relative to the module path parameter

       @param $name - The name of the file or directory

       @param $description - A description of the item
     **/
    public function __construct(string $moduleId, string $path, string $subpath, string $name, string $description) {
    	$this->moduleId=$moduleId;
	if ($subpath!='') {
	    $this->fullPath=$path.DIRECTORY_SEPARATOR.$subpath.DIRECTORY_SEPARATOR.$name;
	} else {
	    $this->fullPath=$path.DIRECTORY_SEPARATOR.$name;
	}
	$this->subpath=$subpath;
	$this->name=$name;
	$this->description=$description;
	$this->isDir=is_dir($this->fullPath);
    }

    public function getName(): string {
	return $this->name;
    }

    public function getDescription(): string {
	return $this->description;
    }

    public function isDir(): bool {
	return $this->isDir;
    }

    public function getURL(): string {
	$subpathVar=FilesystemDirLister::getSubpathVar($this->moduleId);
	$sharedUri=Uri::getInstance();
	$currentPage=new Uri($sharedUri->toString());
	if ($this->isDir) {
	    if ($this->subpath=='') {
		$subpath=$this->name;
	    } else {
		$subpath=$this->subpath.DIRECTORY_SEPARATOR.$this->name;
	    }
	    $currentPage->setVar($subpathVar, $subpath);
	    return $currentPage->toString();
	} else {
	    $currentPage->delVar($subpathVar);
	    $currentPage->setVar('module', $this->moduleId);
	    if ($this->subpath!='') {
		$currentPage->setVar('subpath', $this->subpath);
	    }
	    $currentPage->setVar('fsdownload', $this->name);
	    return $currentPage->toString();
	}
    }

    /** Returns a modified file name that is suitable for insertion
       into a Content-Disposition header **/
    private static function getHttpFriendlyName(string $name) {
	return str_replace(array('"','\r','\n'),'_',$name);
    }
    
    /** Generates and outputs an HTTP response containing the file
       data, returning true on success and false on failure.  The
       function sets the Content-Type and Content-Disposition HTTP
       headers.

       The Content-Disposition header specifies 'inline' for pdf
       files so that they are rendered by the web browser.
       'attachment' is specified for all other file types. 

       @param $app - The Joomla application object
     **/
    public function outputFileContent($app): bool {
	if ($this->isDir) {
	    return false;
	}
	/* Don't allow paths containing :; file_get_contents may
	   support URL paths, so : could denote the end of the URL
	   scheme */
	if (strpos($this->fullPath, ':')===false) {
	    log::add("fsDirectory - File content of {$this->fullPath} being sent", Log::INFO);
	    $content=file_get_contents($this->fullPath);
	    if ($content===false) {
		return false;
	    }
	    $contentType=mime_content_type($this->fullPath);
	    if ($contentType==false) {
		$contentType='application/octet-stream';
	    }
	    $app->setHeader('Content-Type', $contentType, true);
	    if ($contentType=='application/pdf') {
		$deliveryType='inline';
	    } else {
		$deliveryType='attachment';
	    }
	    $app->setHeader('Content-Disposition', $deliveryType.'; filename="'.self::getHttpFriendlyName($this->name).'"', true);
	    /* Call sendHeaders(); otherwise our Content-Type seems to be overridden. */
	    $app->sendHeaders();
	    echo $content;
	    $app->setBody('');
	    $app->close();
	    return true;
	}
	return false;
    }
}
