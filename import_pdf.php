<?php
	require_once __DIR__ .'/pdfimporter.php';
	require_once __DIR__ .'/vendor/phpmailer/phpmailer/PHPMailerAutoload.php';
	use Monolog\Logger;
	use Monolog\Handler\StreamHandler;
	use Monolog\Formatter\LineFormatter;
	
	ini_set('memory_limit','1024M');
	set_time_limit(12000);
	
	const NOMESCRIPT	 = "Yetiforce importer PDF";
	const LOCK_FILE    = __DIR__ ."/importerpdf.lock";   //File di lock per verificare se lo script è già in esecuzione
	const PROFILE_DIR  = __DIR__ ."/profile/pdf/";			      //Cartella che contiene i profili di esecuzione dello script
	const ADMINLOG_DIR = __DIR__ ."/log/pdf/";                     //Cartella che contiene i log di amministrazione dello script   
	const ADMIN_EMAIL  = "adminmail";
	const SCRIPTHOSTNAME = "hostname";
	
	$debug= false; //Definisce il debug e invio report all'amministratore
	
	if (!tryLock())
		die("Already running.\n");
	
	# remove the lock on exit (Control+C doesn't count as 'exit'?)
	register_shutdown_function('unlink', LOCK_FILE);
	
	if (!is_dir(PROFILE_DIR)) {
		mkdir(PROFILE_DIR, 0775, true);
	}
	if (!is_dir(ADMINLOG_DIR)) {
		mkdir(ADMINLOG_DIR, 0775, true);
	}
	
	if($debug)
	{
		try{
			$logname=date('YmdHis')."__".SCRIPTHOSTNAME.".log";
			$log=adminLog($logname);
			$log->info("------------------------------");
			$log->info("Inizio ".NOMESCRIPT);
			$log->info("Server Name: ".SCRIPTHOSTNAME);
			$log->info("Email Admin: ".ADMIN_EMAIL);
			$log->info("Folder Profili: ".PROFILE_DIR);
			$listLogProfile=[];
			$listLogProfile[]=ADMINLOG_DIR.$logname;
		}catch(Exception $e)
		{
			$debug=false;
		}
	}
	
	try{
		$files_json = new FilesystemIterator(PROFILE_DIR, FilesystemIterator::SKIP_DOTS);
		$file_paths =[];
        	foreach($files_json as $f)
        	{
                	$file_paths[$f->getFilename()] = $f;
        	}
        	ksort($file_paths);
	}catch(Exception $e)
	{
		if($debug)
		{
			$log->error("Errore nella lettura cartella dei profili");
		}
	}
	
	$profiles=[];
	foreach( $file_paths as $file_json)
	{
		$extension = $file_json->getExtension();
		$pathname = $file_json->getPathname();
		if($extension === "json")
		{
			try{
				$encode_profile = file_get_contents($pathname);
				$decode_profile = json_decode($encode_profile,TRUE);
				switch (json_last_error()) {
					case JSON_ERROR_NONE:
						$profiles[]=$decode_profile;
						break;
					case JSON_ERROR_DEPTH:
						throw new Exception('Maximum stack depth exceeded');
						break;
					case JSON_ERROR_STATE_MISMATCH:
						throw new Exception('Underflow or the modes mismatch');
						break;
					case JSON_ERROR_CTRL_CHAR:
						throw new Exception('Unexpected control character found');
						break;
					case JSON_ERROR_SYNTAX:
						throw new Exception('Syntax error, malformed JSON - Controllare le virgole alla fine delle ultime righe');
						break;
					case JSON_ERROR_UTF8:
						throw new Exception('Malformed UTF-8 characters, possibly incorrectly encoded');
						break;
					default:
						throw new Exception('Unknown error');
						break;
				}
			}catch(Exception $e)
			{
				if($debug)
				{
					$log->error($e->getMessage());
					$checkError=true;
				}
			}
		}
	}
	unset($files_json);
	
	foreach( $profiles as $profile)
	{
		if($debug)
		{
			$log->info("++++++++++++++++++++++++++++");
			$log->info("Inizio importazione profilo");
			$log->info("Nome: ".$profile["Profile"]);
			$log->info("ID:".$profile["ID"]);
			$log->info("Host: ".$profile["Url"]);
		}
		try{
			$importer= new PdfImporter($profile);
			$importer->importProfile();
		}catch(Exception $e)
		{
			if($debug)
			{
				$log->error($e->getMessage());
				$log->error("Host websevice: ".$profile["Url"]);;
			}
		}
		if($debug)
		{
			if($importer->debug)
			{
				$listLogProfile[]=$importer->getLogPath;
			}
			$log->info("Fine importazione profilo");
			$log->info("++++++++++++++++++++++++++++");
		}
	}
	unset($importer);
	
	if($debug)
	{
		$log->info("Fine ".NOMESCRIPT);
		$log->info("------------------------------");
		adminReport($listLogProfile);
	}
	
	exit(0);
	
	function adminReport($listLogProfile=null)
	{
		try
		{
			$mail = new PHPMailer;
			$mail->isSMTP();
			$mail->SMTPDebug = 0;
			$mail->Host = "smtphostname";
			$mail->Port = 465;
			$mail->SMTPAuth = true;
			$mail->Username = "username";
			$mail->Password = "password";
			$mail->setFrom('mailfrom', NOMESCRIPT);
			$mail->addAddress(ADMIN_EMAIL);
			$mail->Subject = NOMESCRIPT." - Host: ".SCRIPTHOSTNAME;
			$mail->CharSet = 'UTF-8';
			$mail->isHTML(true);
			$body="<h2>Informazioni Importazione</h2><ul>";
			$body.="<li>Hostname: ".SCRIPTHOSTNAME."</li>";
			$body.="<li>Data: ".date('d/m/Y H:i:s')."</li>";
			$body.="</ul><p>Sono stati allegati i file di log.</p>";
			$mail->Body=$body;
			if(isset($listLogProfile))
			{
				foreach($listLogProfile as $logProfile)
				{
					$mail->addAttachment($logProfile);
				}
			}
			//send the message, check for errors
			if (!$mail->send()) {
				throw new Exception("Errore nell' invio del report: " . $mail->ErrorInfo);
			}
		}
		catch(Exception $e)
		{
			throw new Exception("Errore nell' invio del report: " . $e->getMessage());
		}
	}
	
	function adminLog($logname)
	{
		$date = new DateTime();
		$date = $date->format("YmdHis");
	
		$dateFormat = "d/m/Y, H:i:s";
		// the default output format is "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
		$output = "%datetime% > %level_name% > %message% %context% %extra%\n";
		// finally, create a formatter
		$formatter = new LineFormatter($output, $dateFormat);
	
		// Create a handler
		$stream = new StreamHandler(ADMINLOG_DIR.$logname, Logger::DEBUG);
		$stream->setFormatter($formatter);
	
		$log = new Logger($logname);
		$log->pushHandler($stream);
	
		return $log;
	}
	
	function tryLock()
	{
		# If lock file exists, check if stale.  If exists and is not stale, return TRUE
		# Else, create lock file and return FALSE.
	
		if (@symlink("/proc/" . getmypid(), LOCK_FILE) !== FALSE) # the @ in front of 'symlink' is to suppress the NOTICE you get if the LOCK_FILE exists
			return true;
	
			# link already exists
			# check if it's stale
			if (is_link(LOCK_FILE) && !is_dir(LOCK_FILE))
			{
				unlink(LOCK_FILE);
				# try to lock again
				return tryLock();
			}
	
			return false;
	}
	?>
