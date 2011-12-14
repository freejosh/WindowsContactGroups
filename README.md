# PHP Windows Contact Group Parser

These classes read and parse .group and .contact files used by Windows Contacts. I wrote these when I needed to port a large number of contact groups into an online mailing list and couldn't find any other tools to read and/or convert the .group files.

There are two types of contacts contained in .group files - embedded and linked. The embedded contacts have the name and email address directly accessible in the file data, but linked contacts point to a .contact file elsewhere on the user's computer. When running this script locally on the user's machine the class will automatically read those files when requested, but in an online environment, where only the .group file was uploaded, the list of linked files could be used to request that the user upload the proper .contact file.

## Example

Below is an example of how the classes could be used when .group and .contact files are moved from their default locations, such as uploaded to an online service.

It prints a CSV of contacts defined and referenced in all .group files contained in a directory named 'groups'. All .contact files have been moved from their default locations to a directory named 'contacts'. As such, the filenames returned by getLinkedContactFiles() must be intercepted and redirected to the designated folder.

```php
<?php
require_once "WindowsContactGroup.php";

header('Content-type: text/csv');
echo 'Name,Email',PHP_EOL;
foreach(scandir('groups') as $filename) {
	if ($filename == '.' || $filename == '..' || pathinfo($filename, PATHINFO_EXTENSION) != 'group') continue;

	$group = new WindowsContactGroup('groups'.DIRECTORY_SEPARATOR.$filename);
	$embeddedContacts = $group->getEmbeddedContacts();
	foreach($embeddedContacts as $contact) echo $contact['name'],',',$contact['email'],PHP_EOL;

	$files = $group->getLinkedContactFiles();
	foreach($files as $file) {
		if (pathinfo($file, PATHINFO_EXTENSION) != 'contact') continue;

		$file = substr($file, strrpos($file, '\\') + 1);// isolate filename - always going to be Windows format
		$contact = new WindowsContact('contacts'.DIRECTORY_SEPARATOR.$file);
		echo $contact->getName(),',',$contact->getEmail(),PHP_EOL;
	}
}
?>
```