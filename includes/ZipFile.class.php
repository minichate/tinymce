<?php
/**
 * ...
 *
 */
class ZipFile {
	var $_entries;
	var $_path;
	var $_fp;
	var $_offset;
	var $_size;
	var $_header;
	var $_entryLookup;
	var $_compressionLevel;

	function ZipFile($path) {
		$header = array();
		$header['disk'] = 0;
		$header['disks'] = 0;
		$header['comment'] = 0;
		$header['comment_len'] = 0;

		$this->_path = $path;
		$this->_header = $header;
		$this->_entryLookup = array();
		$this->_compressionLevel = 5;
	}

	function setCompressionLevel($level) {
		$this->_compressionLevel = $level;
	}

	function setComment($comment) {
		$this->_header['comment'] = $comment;
		$this->_header['comment_len'] = strlen($comment);
	}

	function addData($zip_path, $data, $comment = "") {
		$zip_path = preg_replace('/^\//', '', $zip_path);

		$this->deleteFile($zip_path);

		$entry =& new ZipEntry($this);

		$entry->setPath($zip_path);
		$entry->setData($data);
		$entry->setComment($comment);

		$this->buildPath($entry->getPath());
		$this->addEntry($entry);
	}

	function addFile($zip_path, $path = "", $comment = "") {
		$zip_path = preg_replace('/^\//', '', $zip_path);

		$this->deleteFile($zip_path);

		$entry =& new ZipEntry($this);

		if ($path != '')
			$entry->setLocalPath($path);

		$entry->setPath($zip_path);
		$entry->setComment($comment);

		$this->buildPath($entry->getPath());
		$this->addEntry($entry);
	}

	function addDirectory($zip_path, $path = "", $comment = "", $recursive = true) {
		$zip_path = preg_replace('/^\//', '', $zip_path);

		$zip_path = $this->addTrailingSlash($zip_path);

		$this->deleteDir($zip_path);

		if ($path) {
			$path = realpath($path);
			$files = $this->_listTree($path, $recursive);

			foreach ($files as $file) {
				$entry =& new ZipEntry($this);

				$entry->setPath($zip_path . substr($file, strlen($path) + 1));
				$entry->setLocalPath($file);
				$entry->setComment($comment);
				$entry->setType(is_dir($file));

				$this->addEntry($entry);
				$this->buildPath($entry->getPath(), is_dir($file));
			}

			$this->buildPath($zip_path . substr($file, strlen($path) + 1), is_dir($file));
		} else {
			$entry =& new ZipEntry($this);

			$entry->setPath($zip_path);
			$entry->setComment($comment);
			$entry->setType(1);

			$this->addEntry($entry);
			$this->buildPath($entry->getPath());
		}
	}

	function moveEntry($from_path, $to_path) {
		$from_path = preg_replace('/^\//', '', $from_path);
		$to_path = preg_replace('/^\//', '', $to_path);

		$entry =& $this->getEntryByPath($from_path);

		if ($entry->isFile()) {
			unset($this->_entryLookup[$entry->getPath()]);
			$entry->setPath($to_path);
			$this->_entryLookup[$to_path] =& $entry;
			$this->buildPath($to_path);
		} else {
			$from_path = preg_replace('/\/$/', '', $from_path) . '/';
			$to_path = preg_replace('/\/$/', '', $to_path) . '/';

			$newEntries = array();

			for ($i=0; $i<count($this->_entries); $i++) {
				$entry =& $this->_entries[$i];

				// Move all entries
				if (strpos($entry->getPath(), $from_path) === 0) {
					unset($this->_entryLookup[$entry->getPath()]);
					$entry->setPath($to_path . substr($entry->getPath(), strlen($from_path)));
					$this->_entryLookup[$entry->getPath()] =& $entry;
				}
			}

			$this->buildPath($to_path);
		}
	}

	function addEntry(&$entry) {
		if ($entry) {
			$this->_entries[] =& $entry;
			$this->_entryLookup[$entry->getPath()] =& $entry;
		}
	}

	function buildPath($path, $is_dir = false) {
		$pos = strrpos($path, '/');

		if ($pos === false)
			return;

		$path = substr($path, 0, $pos);

		// Check for dir
		$entry =& $this->getEntryByPath($path);
		if ($entry)
			return;

		// Dir not found create all parents
		$items = explode('/', $path);
		$path = "";

		if ($is_dir)
			array_pop($items);

		foreach ($items as $item) {
			$path .= $item . '/';

			// Look for entry
			$entry =& $this->getEntryByPath($path);

			if (!$entry) {
				$entry =& new ZipEntry($this);

				$entry->setPath($path);
				$this->addEntry($entry);
			}
		}
	}

	function &getEntryByPath($path) {
		$path = preg_replace('/^\//', '', $path);

		if (isset($this->_entryLookup[$path]))
			return $this->_entryLookup[$path];

		for ($i=0; $i<count($this->_entries); $i++) {
			$entry =& $this->_entries[$i];

			if ($entry->getPath() == $path || $entry->getPath() == $path . '/') {
				$this->_entryLookup[$path] =& $entry;
				return $entry;
			}
		}

		$obj = null; // Stupid PHP 5 notices

		return $obj;
	}

	function deleteFile($path) {
		$path = preg_replace('/^\//', '', $path);
		$newEntries = array();
		$deleted = false;

		// No dir, no file = no need
		if (!isset($this->_entryLookup[$path]) && !isset($this->_entryLookup[$path . '/']))
			return;

		for ($i=0; $i<count($this->_entries); $i++) {
			$entry =& $this->_entries[$i];

			if ($entry->getPath() != $path)
				$newEntries[] =& $entry;
			else {
				unset($this->_entryLookup[$path]);
				$deleted = true;
			}
		}

		$this->_entries =& $newEntries;

		return $deleted;
	}

	function deleteDir($path, $deep = true) {
		$path = preg_replace('/^\//', '', $path);
		$path = $this->addTrailingSlash($path);
		$deleted = false;

		// No dir, no file = no need
		if (!isset($this->_entryLookup[$path]) && !isset($this->_entryLookup[$path . '/']))
			return;

		if (!$deep) {
			for ($i=0; $i<count($this->_entries); $i++) {
				$entry =& $this->_entries[$i];

				if (strpos($entry->getPath(), $path) !== 0 && strlen($entry->getPath()) > strlen($path))
					return false;
			}
		}

		$newEntries = array();

		for ($i=0; $i<count($this->_entries); $i++) {
			$entry =& $this->_entries[$i];

			if (strpos($entry->getPath(), $path) !== 0)
				$newEntries[] =& $entry;
			else {
				$deleted = true;
				unset($this->_entryLookup[$path]);
			}
		}

		$this->_entries =& $newEntries;

		return $deleted;
	}

	function setHeader($header) {
		$this->_header;
	}

	function getHeader() {
		return $this->_header;
	}

	function commit($path = '') {
		$paths = array();

		// Get entry paths
		for ($i=0; $i<count($this->_entries); $i++)
			$paths[] = $this->_entries[$i]->getPath();

		// Sort entry paths
		sort($paths);

		$tmpFile = false;

		if (!$path)
			$path = $this->_path;

		$this->_size = 0;
		$this->_offset = 0;

		// If zip is the same and open use tmp file
		if ($this->_fp && $path == $this->_path) {
			$tmpFile = true;
			$path = $path . ".tmp";
		}

		// Write output file
		if (file_exists($path))
			@unlink($path);

		$fp = @fopen($path, 'wb');
		if ($fp) {
			for ($i=0; $i<count($paths); $i++) {
				$entry =& $this->getEntryByPath($paths[$i]);
				$this->writeLocalFileHeader($fp, $entry);
			}

			for ($i=0; $i<count($paths); $i++) {
				$entry =& $this->getEntryByPath($paths[$i]);
				$this->writeCentralDirHeader($fp, $entry);
			}

			$this->writeCentralDirEnd($fp);

			@fclose($fp);
		}

		// If zip is the same and open use tmp file
		if ($tmpFile) {
			$this->close();

			if (file_exists($this->_path))
				@unlink($this->_path);

			@rename($this->toOSPath($path), $this->toOSPath($this->_path));
		}
	}

	function open() {
		if (!$this->_fp) {
			// Load zip
			if (!file_exists($this->_path))
				return;

			$this->_fp = @fopen($this->_path, "rb");

			if ($this->_fp) {
				// Parse local file headers
				while ($header = $this->readLocalFileHeader()) {
					/*echo "Local file header:\n";
					var_dump($header);*/

					$entry =& new ZipEntry($this, $header);
					$this->_entries[] =& $entry;
					$this->_entryLookup[$entry->getPath()] =& $entry;
				}

				// Parse central dir headers
				while ($header = $this->readCentralDirHeader()) {
					/*echo "Central dir header:\n";
					var_dump($header);*/

					// Append to existing headers
					for ($i=0; $i<count($this->_entries); $i++) {
						$entry =& $this->_entries[$i];

						if ($entry->getPath() == $header['filename'])
							$entry->addHeader($header);
					}
				}

				// Parse central dir end header
				if ($header = $this->readCentralDirEnd()) {
					/*echo "Central dir end:\n";
					var_dump($header);*/

					$this->setHeader($header);
				}
			}
		}
	}

	function close() {
		if ($this->_fp) {
			@fclose($this->_fp);
			$this->_fp = null;
		}
	}

	function extract($zip_path, $path, $is_target = false) {
		$path = $this->toUnixPath($path);

		// Extract single file
		$entry =& $this->getEntryByPath($zip_path);
		if ($entry && $entry->isFile()) {
			if ($is_target)
				$this->_extractEntry($entry, $path);
			else
				$this->_extractEntry($entry, $path . "/" . $entry->getName());

			return;
		}

		// Extract files
		$entries =& $this->listEntries($zip_path, true);
		for ($i=0; $i<count($entries); $i++) {
			$entry =& $entries[$i];

			if ($is_target)
				$outPath = $this->addTrailingSlash($path) . preg_replace('/^([^\/]+\/)/', '', $entries[$i]->getPath());
			else
				$outPath = $this->addTrailingSlash($path) . $entries[$i]->getPath();

			if ($entry->isDirectory())
				$this->_mkdirs($this->toOSPath($outPath));
			else
				$this->_extractEntry($entry, $outPath);
		}
	}

	function _extractEntry(&$entry, $path) {
		// Make parent dir
		$ar = explode('/', $path);
		array_pop($ar);	
		$this->_mkdirs(implode('/', $ar));

		// Extract file contents
		$fp = @fopen($path, "wb");
		if ($fp) {
			fwrite($fp, $entry->getData());
			fclose($fp);
		}
	}

	function getEntries() {
		$this->open();

		return $this->_entries;
	}

	function &listEntries($path = '/', $deep = false) {
		$path = preg_replace('/^\//', '', $path);
		$path = $this->addTrailingSlash($path);

		if ($path == '')
			$path = '/';

		$slashCount = substr_count($path, '/');
		$entries = $this->getEntries();
		$output = array();

		for ($i=0; $i<count($this->_entries); $i++) {
			$entry =& $this->_entries[$i];
			$entryPath = $entry->getPath();
			$entryPath = preg_replace('/\/$/', '', $entryPath);

			if (!$deep) {
				if ($path == '/' && substr_count($entryPath, '/') == 0)
					$output[] =& $entry;
				else if (strpos($entryPath, $path) === 0 && substr_count($entryPath, '/') == $slashCount)
					$output[] =& $entry;
			} else {
				if ($path == '/' || strpos($entryPath, $path) === 0)
					$output[] =& $entry;
			}
		}

		return $output;
	}

	function writeLocalFileHeader($fp, &$entry) {
		$header = $entry->getHeader();
		$data = '';

		// Compress data and set some headers
		$header['filename_len'] = strlen($entry->getPath());

		// Convert unix time to dos time
		$date = getdate($header['unixtime']);
		$header['mtime'] = ($date['hours'] << 11) + ($date['minutes'] << 5) + $date['seconds'] / 2;
		$header['mdate'] = (($date['year'] - 1980) << 9) + ($date['mon'] << 5) + $date['mday'];

		// Total commander has strange issues
		if ($header['size'] == 0)
			$header['compressed_size'] = 0;

		// Data to compress
		if ($header['size'] > 0 || ($entry->_isDirty && $entry->isFile())) {
			if ($entry->_isDirty) {
				$data = $entry->getData();
				$header['size'] = strlen($data);
				$header['crc'] = crc32($data);
				$header['compression'] = 0x0008; // deflate
				$header['flag'] = 0x0002;

				// Compress
				$data = @gzdeflate($data, $this->_compressionLevel);
				$header['compressed_size'] = strlen($data);
			} else
				$data = $entry->getRawData();
		}

		// Pack and write header
		fwrite($fp, pack("VvvvvvVVVvv",
			0x04034b50, // Signature
			$header['version'],
			$header['flag'],
			$header['compression'],
			$header['mtime'],
			$header['mdate'],
			$header['crc'],
			$header['compressed_size'],
			$header['size'],
			$header['filename_len'],
			$header['extra_len']
		), 30);

		// Write filename and compressed data
		fwrite($fp, $entry->getPath(), $header['filename_len']);

		if (isset($header['extra']))
			fwrite($fp, $header['extra'], $header['extra_len']);

		fwrite($fp, $data, $header['compressed_size']);

		$header['offset'] = $this->_offset;
		$this->_offset += 30 + $header['filename_len'] + $header['extra_len'] + $header['compressed_size'];

		$entry->setHeader($header);
	}

	function writeCentralDirHeader($fp, &$entry) {
		$header = $entry->getHeader();

		// Set extra parameters
		$header['version'] = 0x0014;
		$header['version_extracted'] = $header['compression'] == 8 ? 0x0014 : 0x000A;
		$header['disk'] = 0x0000;
		$header['iattr'] = 0x0001;
		$header['eattr'] = $entry->isDirectory() ? 0x00000010 : 0x00000020;

		// Write central directory record
		fwrite($fp, pack("VvvvvvvVVVvvvvvVV",
			0x02014b50, // Signature
			$header['version'],
			$header['version_extracted'],
			$header['flag'],
			$header['compression'],
			$header['mtime'],
			$header['mdate'],
			$header['crc'],
			$header['compressed_size'],
			$header['size'],
			$header['filename_len'],
			$header['extra_len'],
			$header['comment_len'],
			$header['disk'],
			$header['iattr'],
			$header['eattr'],
			$header['offset']
		), 46);

		// Write filename
		fwrite($fp, $entry->getPath(), $header['filename_len']);

		if (isset($header['extra']))
			fwrite($fp, $header['extra'], $header['extra_len']);

		if (isset($header['comment']))
			fwrite($fp, $header['comment'], $header['comment_len']);

		$this->_size += 46 + $header['filename_len'] + $header['extra_len'] + $header['comment_len'];
	}

	function writeCentralDirEnd($fp) {
		$header = $this->_header;

		$header['start'] = count($this->_entries);
		$header['entries'] = count($this->_entries);
		$header['size'] = $this->_size;
		$header['offset'] = $this->_offset;

		// Write end of central directory record
		fwrite($fp, pack("VvvvvVVv",
			0x06054b50, // Signature
			$header['disk'],
			$header['disks'],
			$header['start'],
			$header['entries'],
			$header['size'],
			$header['offset'],
			$header['comment_len']
		), 22);

		fwrite($fp, $header['comment'], $header['comment_len']);
	}

	function readLocalFileHeader() {
		$header = array();

		// Read signature
		$oldPos = ftell($this->_fp);
		$buff = @fread($this->_fp, 4);
		$data = unpack('Vsignature', $buff);

		// Is not local file header
		if ($data['signature'] != 0x04034b50) {
			fseek($this->_fp, $oldPos, SEEK_SET);
			return null;
		}

		// Read header
		$buff = fread($this->_fp, 26);
		$data = unpack('vversion/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len', $buff);
		$header = array_merge($data, $header);

		// Read filename
		if ($header['filename_len'] > 0)
			$header['filename'] = fread($this->_fp, $header['filename_len']);

		// Read extra
		if ($header['extra_len'] != 0)
			$header['extra'] = fread($this->_fp, $header['extra_len']);

		// Convert DOS date/time to UNIX Timestamp
		if ($header['mdate'] && $header['mtime']) {
			$header['unixtime'] = gmmktime(($header['mtime'] & 0xF800) >> 11, ($header['mtime'] & 0x07E0) >> 5, ($header['mtime'] & 0x001F) * 2,
											($header['mdate'] & 0x01E0) >> 5, $header['mdate'] & 0x001F, (($header['mdate'] & 0xFE00) >> 9) + 1980);
		}

		// Store away data offset and jump behind data
		$header['data_offset'] = ftell($this->_fp);
		fseek($this->_fp, $header['compressed_size'], SEEK_CUR);

		return $header;
	}

	function readCentralDirHeader() {
		$header = array();

		// Read signature
		$oldPos = ftell($this->_fp);
		$buff = @fread($this->_fp, 4);
		$data = unpack('Vsignature', $buff);

		// Is not central dir header
		if ($data['signature'] != 0x02014B50) {
			fseek($this->_fp, $oldPos);
			return null;
		}

		// Read header
		$buff = fread($this->_fp, 42);
		$data = unpack('vversion/vversion_extracted/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len/vcomment_len/vdisk/viattr/Veattr/Voffset', $buff);
		$header = array_merge($data, $header);

		// Read filename
		if ($header['filename_len'] != 0)
			$header['filename'] = fread($this->_fp, $header['filename_len']);

		// Read extra
		if ($header['extra_len'] != 0)
			$header['extra'] = fread($this->_fp, $header['extra_len']);

		// Read comment
		if ($header['comment_len'] != 0)
			$header['comment'] = fread($this->_fp, $header['comment_len']);

		// Convert DOS date/time to UNIX Timestamp
		if ($header['mdate'] && $header['mtime']) {
			$header['unixtime'] = gmmktime(($header['mtime'] & 0xF800) >> 11, ($header['mtime'] & 0x07E0) >> 5, ($header['mtime'] & 0x001F) * 2,
											($header['mdate'] & 0x01E0) >> 5, $header['mdate'] & 0x001F, (($header['mdate'] & 0xFE00) >> 9) + 1980);
		}

		return $header;
	}

	function readCentralDirEnd() {
		$header = array();

		// Read signature
		$oldPos = ftell($this->_fp);
		$buff = @fread($this->_fp, 4);
		$data = unpack('Vsignature', $buff);

		// Is not central dir header
		if ($data['signature'] != 0x06054b50) {
			fseek($this->_fp, $oldPos);
			return null;
		}

		// Read header
		$buff = fread($this->_fp, 22);
		$data = unpack('vdisk/vdisks/vstart/ventries/Vsize/Voffset/vcomment_len', $buff);
		$header = array_merge($data, $header);

		// Read comment
		if ($header['comment_len'] > 0)
			$header['comment'] = fread($this->_fp, $header['comment_len']);

		return $header;
	}

	function getFileData($header, $uncompres = true) {
		$data = "";

		if ($this->_fp) {
			$oldPos = ftell($this->_fp);

			fseek($this->_fp, $header['data_offset']);

			$buff = @fread($this->_fp, $header['compressed_size']);
			if ($uncompres && $header['compression'] == 8 && $header['compressed_size'] > 0)
				$data = @gzinflate($buff);
			else
				$data = $buff;

			fseek($this->_fp, $oldPos);
		}

		return $data;
	}

	/**
	 * Adds a trailing slash to a path.
	 *
	 * @param String path Path to add trailing slash on.
	 * @return String New path with trailing slash.
	 */
	function addTrailingSlash($path) {
		if (strlen($path) > 0 && $path[strlen($path)-1] != '/')
			$path .= '/';

		return $path;
	}

	/**
	 * Removes the trailing slash from a path.
	 *
	 * @param String path Path to remove trailing slash from.
	 * @return String New path without trailing slash.
	 */
	function removeTrailingSlash($path) {
		// Is root
		if ($path == "/")
			return $path;

		if ($path == "")
			return $path;

		if ($path[strlen($path)-1] == '/')
			$path = substr($path, 0, strlen($path)-1);

		return $path;
	}

	/**
	 * Converts a OS specific path to Unix path.
	 *
	 * @param String $path OS path to convert to Unix style.
	 */
	function toUnixPath($path) {
		return str_replace(DIRECTORY_SEPARATOR, "/", $path);
	}

	/**
	 * Converts a Unix path to OS specific path.
	 *
	 * @param String $path Unix path to convert.
	 */
	function toOSPath($path) {
		return str_replace("/", DIRECTORY_SEPARATOR, $path);
	}

	function _listTree($path, $recursive = true) {
		$files = array();

		if ($dir = opendir($path)) {
			while (false !== ($file = readdir($dir))) {
				if ($file == "." || $file == "..")
					continue;

				$file = $path . "/" . $file;
				$files[] = $file;

				if (is_dir($file) && $recursive)
					$files = array_merge($files, $this->_listTree($file, $recursive));
			}

			closedir($dir);
		}

		return $files;
	}

	function _mkdirs($path, $rights = 0777) {
		$path = preg_replace('/\/$/', '', $path);
		$dirs = array();

		// Figure out what needs to be created
		while ($path) {
			if (file_exists($path))
				break;

			$dirs[] = $path;
			$pathAr = explode("/", $path);
			array_pop($pathAr);
			$path = implode("/", $pathAr);
		}

		// Create the dirs
		$dirs = array_reverse($dirs);
		foreach ($dirs as $path) {
			if (!@is_dir($path) && strlen($path) > 0)
				mkdir($path, $rights);
		}
	}
}

class ZipEntry {
	var $_zip;
	var $_header;
	var $_data;
	var $_rawData;
	var $_isDirty;
	var $_localPath;

	function ZipEntry(&$zip, $header = false) {
		if (!$header) {
			$header = array();
			$header['version'] = 0x0014;
			$header['version_extracted'] = 0x0000;
			$header['flag'] = 0;
			$header['compression'] = 0;
			$header['mtime'] = 0;
			$header['mdate'] = 0;
			$header['crc'] = 0;
			$header['compressed_size'] = 0;
			$header['size'] = 0;
			$header['filename_len'] = 0;
			$header['extra_len'] = 0;
			$header['comment_len'] = 0;
			$header['disk'] = 0;
			$header['iattr'] = 0;
			$header['eattr'] = 0;
			$header['offset'] = 0;
			$header['filename'] = '';
			$header['extra'] = '';
			$header['comment'] = '';
			$header['unixtime'] = time();
		}

		$this->_zip = $zip;
		$this->_header = $header;
		$this->_isDirty = false;
	}

	function getRawData() {
		if (!$this->_rawData)
			$this->_rawData = $this->_zip->getFileData($this->_header, false);

		return $this->_rawData;
	}

	function getData() {
		if ($this->_localPath)
			$this->setData(file_get_contents($this->_localPath));

		if ($this->_data)
			return $this->_data;

		return $this->_zip->getFileData($this->_header);
	}

	function setData($data) {
		$this->_header['unixtime'] = time();
		$this->_header['size'] = strlen($data);
		$this->_data = $data;
		$this->_isDirty = true;
	}

	function setLocalPath($path) {
		$this->_header['unixtime'] = filemtime($path);
		$this->_localPath = $path;
		$this->_isDirty = true;
	}

	function getLocalPath() {
		return $this->_localPath;
	}

	function getHeader() {
		return $this->_header;
	}

	function addHeader($header) {
		$this->_header = array_merge($this->_header, $header);
	}

	function setHeader($header) {
		$this->_header = $header;
	}

	function getLastModified() {
		return $this->_header['unixtime'];
	}

	function setLastModified($date) {
		$this->_header['unixtime'] = $date;
	}

	function getPath() {
		return $this->_header['filename'];
	}

	function setPath($path) {
		$path = preg_replace('/^\//', '', $path);
		$path = $this->_zip->toUnixPath($path);
		$this->_header['filename'] = $path;
		$this->_header['filename_len'] = strlen($path);
	}

	function setComment($comment) {
		$this->_header['comment'] = $comment;
		$this->_header['comment_len'] = strlen($comment);
	}

	function setExtra($extra) {
		$this->_header['extra'] = $extra;
		$this->_header['extra_len'] = strlen($extra);
	}

	function getSize() {
		return $this->_header['size'];
	}

	function getName() {
		$ar = explode('/', $this->_zip->removeTrailingSlash($this->_header['filename']));

		return array_pop($ar);
	}

	function setType($type) {
		if ($type == 1)
			$this->_header['filename'] = $this->_zip->addTrailingSlash($this->_header['filename']);
	}

	function isFile() {
		return !$this->isDirectory();
	}

	function isDirectory() {
		return substr($this->getPath(), strlen($this->getPath()) - 1) == '/';
	}
}

?>