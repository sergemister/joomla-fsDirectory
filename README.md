# fsDirectory Joomla Extension

## Overview

The `fsDirectory` Joomla extension consists of a Joomla module and
Joomla plugin that together enable the listing and download of files
from a specified directory in the filesystem.  Unlike many Joomla
extensions allowing file download, `fsDirectory` models only a
directory in Joomla, not the individual files in the directory.  Files
can be added or removed from the directory (e.g. with a file
synchronization tool) and the changes are immediately visible on the
site.

THIS EXTENSION IS STILL UNDER DEVELOPMENT/TESTING.  IT IS NOT YET
READY FOR GENERAL USE.

## Features

`fsDirectory` is designed for simple usecases such as document
libraries; it does not support pre-download or post-download messages,
hit counts, or billing features.  With `fsDirectory` one can:

* Specify a filesystem directory whose content should be made
  available for download.

* Display a directory listing within an article or in a Joomla
  template position.

* Optionally allow content from subdirectories of the specified
  directory to be listed and downloaded too.

* Limit access to the files based on Joomla access control rules.

* Limit the directory listing and download to files with specified
  file extensions.

This package is not yet available in the Joomla Extensions Directory,
and does not yet use the Joomla update system.

## License

This software is licensed under the GNU General Public License Version
3 or any later version; see the LICENSE file.

## Installation

1. Build the installation package:
    1. Clone this Github repository onto a Linux system.
    2. Make sure that `make` and `zip` are available.
    3. Execute `make`; the package will be available in the file
       `output/pkg_fsDirectory.zip`.

2. Install the package using the Joomla backend.  In the extensions manager there will be three items added:
    - The `fsDirectory` package
    - The `fsDirectory` module
    - The `fsDirectory` plugin

3. Enable the `fsDirectory` plugin.

## Configuration

To make a filesystem directory accessible:

1. Create a new site module, selecting `fsDirectory` as its type (when
   the package is installed, an unpublished module is automatically
   created; this one could be used).
   
2. In the module configuration, configure:
    - `Display name` - The name that should be displayed to users as
      the name of the directory.
	
    - `Directory path` - The absolute filesystem path of the directory
      whose files should be made available.  This directory should NOT
      be located in the web content directory, as that would allow the
      files to be accessed directly.

	- `Allowed extensions` - A comma-separated list of file extensions
      that may be downloaded.  The special value `all` indicates that
      all files in the directory may be downloaded.
	
	- `Allow subdirectory access` - Enable this to allow
      subdirectories of the specified directory to be accessed as
      well.
	  
	- `Link target` - The [target
      attribute](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/a#attr-target)
      to specify for file links in the directory listing.  Specify
      `_blank` to cause files to display in a new tab or window.

3. Configure the access level and other settings as you would for any
   other module.  Insert the module into the site either by specifying
   a position or by embedding the module in an article.

## Security Notes

* Installing this extension grants Joomla administrators able to
  configure the `fsDirectory` module the ability to retrieve any file
  on the system accessible to Joomla.  For example, they could specify
  /etc/ as the path when configuring the module.

* The `fsDirectory` plugin implements the download capability and
  responds to specific query string parameters, regardless of the URL
  path.  A front end proxy must not attempt to limit access to files
  exposed by `fsDirectory` based on URL paths.  The download URLs
  generated by the `fsDirectory` module set the path to that of the
  page containing the module.  This has the benefit that, if the file
  requested is not found, the user will be taken to the page showing
  the current file list.

## Details

* Files and directories starting with "." (hidden files in Unix) are
  not shown in the generated listing and cannot be downloaded.
  
* Files with an absolute path containing `:` will be included in the
  listing but download is blocked by the `fsDirectory` plugin.  This
  is because PHP's file handling functions may support URLs, and : may
  introduce a URL scheme.
  
* The implementation is designed to handle small collections of files.
  The performance for directories containing a large number of files
  has not been tested.

* Multiple `fsDirectory` modules, and multiple instances of the same
  module, can be displayed on the same page.
  
* It may be desirable to define multiple `fsDirectory` modules in
  which the configured directory of one is a subdirectory of that
  configured for another.  Bear in mind that a file will be accessible
  if any published `fsDirectory` module makes it accessible.

## Removal

To uninstall the `fsDirectory` extension, simply uninstall the
`fsDirectory` package in the Joomla backend.  The module and plugin
will be uninstalled automatically.

## Testing

The Makefile `Makefile-test` can be used to create a directory structure for testing the extension.  This Makefile depends on:

* convert (from ImageMagick)
* groff
* ps2pdf

Generate the test files by running

    make -f Makefile-test

The test directory structure can then be found in `output/testDocuments.zip`.
