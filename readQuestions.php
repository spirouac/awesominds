<?php
	//TODO: Open connection to DB.
	Require('../../conn.php');

	//$target_file determined by upload script.
 	//create temporary file for use
	$filename = $target_file;
	$temp_file = tempnam(sys_get_temp_dir(), 'qs');
	
	//takes the uploaded doc file and converts it to tmp file
	//parameters: .doc file to be uploaded, path and name of tmpFile.
	function read_doc($file, $tmp) {
		
		$fileHandle = fopen($file, "r");
		//stores whole contents on a single line.
		$line = @fread($fileHandle, filesize($file));   
		//create array of strings, each index is new line
		$lines = explode(chr(0x0D),$line);
		$outtext = "";
		foreach($lines as $thisline){
			//find the position of the null characters in the string
			$pos = strpos($thisline, chr(0x00));
			//if not null character or string is empty continue
			if (($pos !== FALSE)||(strlen($thisline)==0)){
			} 
			//otherwise add newline to string
			else{
				$outtext .= $thisline."\n";
			}
		}
		fclose($fileHandle); 		
		//create name for new .txt file and write the newly created string to it.
		$tempFile = fopen($tmp,"r+");
		//matche all characters and add to new string 
		$outtext = preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/","",$outtext);
		fputs($tempFile, $outtext);
		//$newfile = "questionFile.txt";
		//file_put_contents($newfile, $outtext);
		unlink($file);
		rewind($tempFile);
	}
	
	
	
	//could separate this into it's own php file.
	//probably need to pass db conn as param.
	//takes the tmp file and parses through it line by line looking for key words
	//loads into JSON then stores in db
	//parameters: file to be parsed <might want to add courseid too, dpeneding on how that's being entered>
	function tmpToDB($temp_file, $dbcon){
		$courseid = 150;
		$questionBank = array();
		$index = 0;		
		$questionFile = fopen($temp_file, "r") or die("file not found");
		//iterate over question document, check if it is a question or an answer. add to appropriate array.	
		while(!feof($questionFile)){	
			$line = fgets($questionFile);

			//find chapter number
			if(strpos($line,"Chapter ")!==FALSE){
				$lineArray =  explode(" ", $line);
				$insertChapter = $lineArray[1];
			}
			
			//check if it's a question
			if(is_numeric(substr($line,0,1))){
				$question = substr($line,3);
				$question = trim($question, ' \0\t\n\x0b\r');
				$questionBank["question"] = $question;
			}
			//check if it's a possible answer operates under the assumption a b c and d are the only choices.
			//can probably spruce this up a little nicer. cascading switch statement maybe?
			if(strtoupper(substr($line,0,2)) == 'A)' | strtoupper(substr($line,0,2)) == 'B)' | strtoupper(substr($line,0,2)) == 'C)' | strtoupper(substr($line,0,2)) == 'D)'){
				$choice = strtoupper(substr($line,0,1));
				$choice = trim($choice, ' \0\t\n\x0b\r');
				$choices[$choice] = substr($line,3);
				$questionBank["choices"] = $choices;			
			}
			//assign questions, choices and answers to question bank.
			if(strtoupper(substr($line,0,6)) == "ANSWER"){
				$answer= substr($line,7,8);
				$answer = trim($answer, ' \0\t\n\x0b\r');
				//php seems to require this before it recognizes A as A.
				$a = ord($answer);
				$a = chr($a);
				//remove $index when loading into db.
				$questionBank["question"] = $question;
				$questionBank["choices"] = $choices;
				$questionBank["answer"] = $answer;
			
				//TODO database tom foolery
				//insert statements to questiontable.
				
				//bound params
				//$stmt = $dbcon->prepare("INSERT INTO db.table (question, chapter, courseid)) VALUES(?,?)");
				//$stmt->bind_param("data_types",$insertQuestion, $insertChaper);
				
				//PDO prefer to use this
				$insertQuestion = json_encode($questionBank);
				$stmt = $dbcon->prepare("INSERT INTO question (questionid, question, chapter, courseid) VALUES (null, :question, :chapter, :courseid)");
				$stmt->bindParam(':question', $insertQuestion);
				$stmt->bindParam(':chapter', $insertChapter);
				$stmt->bindParam(':courseid', $courseid);
				if($stmt->execute()){
					$index+=1;
				}else{
					echo 'error <br>';
					$arr = $stmt->errorInfo();
					print_r($arr);
				}
			}
		}
		//close db connection
		$conn=null;
		//close file
		fclose($questionFile);
		echo $index . " questions uploaded for chapter " . $insertChapter . " on course: " . $courseid;
		//this is only for debug purposes, uncomment PDO statements when ready.
		//$qbjson = json_encode($questionBank);
		//echo $qbjson;

	}



?>
