<?php
/**
 * @package FSDirectory
 *
 * @author    Serge Mister <cf744@ncf.ca>
 * @license   GNU General Public License Version 3 or any later version; see LICENSE file 
 * @copyright 2022
 **/

defined('_JEXEC') or die;

$displayName=$dirInfo->getDisplayName();
if ($displayName!='') {
    echo htmlspecialchars($displayName);
}

$htmlEscapedTarget=htmlspecialchars($target);

echo '<ul>';

$parentURL=$dirInfo->getParentURL();
if (!is_null($parentURL)) {
    $escapedURL=htmlspecialchars($parentURL);
    echo "<li><span class=\"icon-folder\"> </span> <a href=\"$escapedURL\">../</a></li>";
}
foreach ($dirInfo->getDirEntryList() as $entry) {
    $escapedName=htmlspecialchars($entry->getName());
    $escapedURL=htmlspecialchars($entry->getURL());
    if ($entry->isDir()) {
	echo "<li><span class=\"icon-folder\"> </span> <a href=\"$escapedURL\">$escapedName/</a></li>";
    } else {
	echo "<li><span class=\"icon-file\"> </span> <a href=\"$escapedURL\" target=\"$htmlEscapedTarget\">$escapedName</a></li>";
    }
}
echo '</ul>';
