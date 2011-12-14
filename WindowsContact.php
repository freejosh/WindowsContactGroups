<?php
/**
 * Parses a Windows Contact (.contact) file.
 *
 * For now has explicit methods only to get basic information, but the default
 * get method returns the contents of the requested node by using it as an
 * XPath selector (prepending the initial 'c:' namespace). See
 * http://en.wikipedia.org/wiki/Windows_Contacts for the XML structure.
 */
class WindowsContact {
	private $fileData, $xml;

	function __construct($file) {
		if (!file_exists($file)) throw new Exception("File '$file' does not exist.");
		ob_start();
		if (!@readfile($file)) throw new Exception("Could not read file '$file'");
		$this->fileData = ob_get_clean();
		$this->xml = new SimpleXMLElement($this->fileData);
	}

	function __get($var) {
		$xpath = $this->xml->xpath("//c:$var");
		$arr = array();
		while(list( , $node) = each($xpath)) $arr[] =  (string)$node;
		return $arr;
	}

	function getName() {
		$v = $this->FormattedName;
		return $v ? $v[0] : null;
	}

	function getEmail() {
		$v = $this->Address;
		return $v ? $v[0] : null;
	}
}
?>