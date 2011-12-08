<?php
require_once "WindowsContact.php";

/**
 * Parses a Windows Contact Group (.group) file to return contact information.
 *
 * Original source for data structure details from C4FDevKit:
 * http://www.java2s.com/Open-Source/CSharp/Windows/C4FDevKit/C4F/DevKit/Contacts/MapiGroupView.cs.htm
 */
class WindowsContactGroup {

	private $fileData, $xml;

	function __construct($file) {
		if (!file_exists($file)) throw new Exception("File '$file' does not exist.");
		ob_start();
		if (!@readfile($file)) throw new Exception("Could not read file '$file'.");
		$this->fileData = ob_get_clean();
		$this->xml = new SimpleXMLElement($this->fileData);
	}

	/**
	 * Returns array of all contacts contained or referenced in this group.
	 * Each element is an array containing the contact's information.
	 */
	function getContacts() {
		return array_merge($this->getEmbeddedContacts(), $this->getLinkedContacts());
	}

	/**
	 * Returns array of contacts listed in this group.
	 * Each element in the array is an array of fields containing the contact's information.
	 */
	function getEmbeddedContacts() {
		$data = $this->xml->xpath('//MSWABMAPI:PropTag0x80091102');
		if (!$data) return array();

		$data = $data[0];
		$data = base64_decode($data);
		$numContacts = unpack("V", $data);// first 32 bits define number of contacts in data
		$numContacts = $numContacts[1];
		$data = substr($data, 4);// shift past those 32 bits

		$junkPre = 24;// 24 bytes of junk are prepended to each contact
		$junkPost = 2;// 2 bytes of junk are appended to each contact

		$contacts = array();// to return
		for ($i = 0; $i < $numContacts; $i++) {
			$strlen = unpack("V", $data);// 32-bit int defines number of 16-bit characters in string
			$strlen = ($strlen[1] - $junkPre - $junkPost) / 2;// number of 16-bit ints to parse
			$data = substr($data, 4 + $junkPre);// skip over 32-bit int plus junk bytes
			$arr = unpack("v{$strlen}", $data);
			$data = substr($data, $strlen * 2 +  $junkPost);// skip over 16-bit ints plus junk bytes
			$str = '';
			foreach($arr as $c) $str .= chr($c);// assemble ints as chars into string
			$contact = explode("\0", $str);// fields are delimited by NULL chars - explode when done assembling
			$contacts[] = array(
				'name' => $contact[0],
				'email' => $contact[2]
			);
		}
		return $contacts;
	}

	/**
	 * Returns array of filenames which point to contacts listed in this group.
	 */
	function getLinkedContactFiles() {
		$data = $this->xml->xpath('//MSWABMAPI:PropTag0x66001102');
		if (!$data) return array();

		$data = $data[0];
		$data = base64_decode($data);
		$numContacts = unpack("V", $data);// first 32 bits define number of contacts in data
		$numContacts = $numContacts[1];
		$data = substr($data, 4);// skip over 32-bit int

		$junkPost = 2;// 2 bytes of junk are appended to each contact

		$contacts = array();
		for ($i = 0; $i < $numContacts; $i++) {
			$arr = unpack("V", $data);// 32-bit int defines number of 16-bit characters in string
			$strlen = ($arr[1] - $junkPost) / 2;// number of 16-bit ints to parse
			$data = substr($data, 4);// skip over 32-bit int
			$arr = unpack("v{$strlen}", $data);
			$data = substr($data, $strlen * 2 + $junkPost);// skip over 16-bit ints plus junk bytes
			$str = '';
			foreach($arr as $c) $str .= chr($c);// assemble ints as chars into string
			if (preg_match("/\/PATH:\"([^\"]+)\"/", $str, $filename)) $contacts[] = $filename[1];
		}
		return $contacts;
	}

	/**
	 * Returns array of minimal information from linked contact files.
	 */
	function getLinkedContacts() {
		$contacts = array();
		foreach($this->getLinkedContactFiles() as $filename) {
			$contact = new WindowsContact($filename);
			$contacts[] = array(
				'name' => $contact->getName(),
				'email' => $contact->getEmail()
			);
		}
		return $contacts;
	}
}
?>