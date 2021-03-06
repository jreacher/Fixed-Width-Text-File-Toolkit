<?php
	/**
 	 * Fixed Width Text File Toolkit
	 *
	 * PHP class to help extract information from fixed width text files as easily as possible.
	 *
	 * https://github.com/mrandrewmills/Fixed-Width-Text-File-Toolkit
	 *
	 *
	 */	
	
	class FixedWidthFile {
	
		// Our properties
	
		private $filename;

		private $headers;

		private $lineLength = 4096; // default value, override with setFileLength() as needed

		private $fileData;

		private $hasHeaderRow = true;
	
		
		// Our constructor uses class name instead of __constructor to work with older versions of PHP
		
		function FixedWidthFile($filename, $lineLength, $hasHeaderRow) {
			
			// if we received a filename
			if ($filename) { 
			
				// pass it on to the setter function 
				$this->setFilename($filename); 

			}
			
			// if we received lineLength override
			if ($lineLength) {

				// pass it on to our setter function
				$this->setLineLength($lineLength);

			}

			// if we received hasHeaderRow override
			if ($hasHeaderRow) {

				// pass it on to our setter function
				$this->setHasHeaderRow($hasHeaderRow);

			}
		}
	
		
		// Our getters, no surprises here
		
		public function getFilename(){

			return $this->filename;

		}
		
		public function getHeaders(){

			return $this->headers;

		}
		
		public function getLineLength(){

			return $this->lineLength;

		}

		public function getfileData(){

			return $this->fileData;

		}
		
		public function getNumRows(){

			$numRows = 0;
			
			if ($this->fileData) {

				$numRows = count($this->fileData);

			}
			
			return $numRows;

		}
		
		public function getHasHeaderRow(){

			return $this->hasHeaderRow;

		}
		
		
		// Our setters, but some of our properties are not meant to be accessible (i.e. internal use only)

		public function setFilename($filename){
		
			// verify file exists 
			if (file_exists($filename)) {

				// set the filename property accordingly
				$this->filename = $filename;
							
				// attempt to read the header row
				$this->readHeaderRow();
				
				// attempt to read rest of data file
				$this->readData();
			
				}
				
			else {

				$errMsg = "$filename not found. Please verify path and filename.";

				throw new Exception($errMsg);

			}
		}
		
		public function setLineLength($lineLength) {
		
			// give developer means to override default fileLength value
			
			if ($lineLength) {

				$this->lineLength = $lineLength;

			}
			
		}
		
		public function setHasHeaderRow($hasHeaderRow){

			// give developer means to override assumption of a header row

			if ($hasHeaderRow) {

				if (is_bool($hasHeaderRow)) {

					$this->hasHeaderRow = $hasHeaderRow;

				}

				else {

					$errMsg = "method setHasHeaderRow requires argument of true or false.";

					throw new Exception($errMsg);

				}
			}
		}
		

		// Our "utility functions", for internal use only (thus private)
		
		private function readHeaderRow(){

			/* check to see if file exists */
			if (file_exists($this->filename)) {

				/* if file exists, let's open it! Using @ to suppress error warnings */
				$handle = @fopen($this->filename, "r");

				if ($handle) {

					// read only the first line
				    	$firstRow = fgets($handle, $this->lineLength);
	
					// if we believe our file has a header row, then . . .
					if ($this->hasHeaderRow) {

						// parse first row for field names, using two or more consecutive spaces
						$this->headers = preg_split('/(?:\s\s+|\n|\t)/', $firstRow, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);
					}

					else { // if we believe file does NOT have a header row

						$this->headers = preg_split('/(?:\s\s+|\n|\t)/', $firstRow, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);

						// add generic Column names (Column1, Column2, etc.
						$numFields = count($this->headers);

						for ($x = 0; $x < $numFields; $x++) {

							$this->headers[$x][0] = "Column" . ($x + 1) ;

						}
					}
				
					// close file when we're done
					fclose($handle);
				}

				else {

					// file exists, but we cannot open it for some reason
					$errMsg = "Unable to open $filename. Please verify file permissions.";

					throw new Exception($errMsg);

				}
			}

			else {

				// error handling for bad file path, typo in filename, etc.
				$errMsg = "$filename not found. Please verify path and filename.";

				throw new Exception($errMsg);
			}
		}
		
		private function readData(){
					
			// verify file exists, no typos, etc.
			if (file_exists($this->filename)) {
				
				// open the file, suppressing any error messages with @
				$handle = fopen($this->filename, "r");
				
				// if we were successful in opening the file
				if ($handle) {
					
					// does this file have a header row? If so, bypass it.
					if ($this->hasHeaderRow) {

						$firstRow = fgets($handle, $this->lineLength);

						}
					
					// and process the remaining rows of the file
					while (($buffer = fgets($handle, $this->lineLength)) !== false) {
        					
        					$numFields = count($this->headers);
												
						// find out how long one line is
						$fieldLength = strlen($buffer);
						
						$rowData = Array();

						// working our way BACKWARDS through the array
						for ($x = $numFields - 1; $x >= 0; $x--) {
							
							$fieldLength = $fieldLength - $this->headers[$x][1];
							
							$rowData[$this->headers[$x][0]] = rtrim(substr($buffer, $this->headers[$x][1], $fieldLength));
							
							$fieldLength = $this->headers[$x][1];
							}

						// when we've got the current row sorted, add it to the more permanent pile							
						$this->fileData[] = $rowData;
    				}
    				
				// if file handle is lost/broken before we reach end of file
    				if (!feof($handle)) {

					$errMsg = "Error while reading the file.";
					throw new Exception($errMsg);					

    				}
    				
    				fclose($handle);
				}
			}
			
		}
		
		// Our "Bare Bones JSON Conversion" function

		public function toJSON(){
		
			$JSONresult = "";
			
			$JSONresult = json_encode($this->fileData);

			// if our result is not false (i.e. no error)
			if ($JSONresult != false) {

				return $JSONresult;

			}
			
			else { // if there was a problem

				$errMsg = json_last_error();
				throw new Exception($errMsg);

			}
		}

	}
?>