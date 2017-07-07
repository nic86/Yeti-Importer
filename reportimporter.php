<?php
require_once __DIR__ .'/vendor/phpmailer/phpmailer/PHPMailerAutoload.php';

class ReportImporter
{
	const MAX_ATTACHMENTS = 50;
	
	private $_profile;
	private $_to;
	private $_from;
	private $_cc;
	private $_bcc;
	private $_message;
	private $_columnsError=[];
	private $_attachments=[];
	private $_rowsError=[];
	private $_columnsComplete=[];
	private $_rowsComplete=[];
	private $_columnsAlert=[];
	private $_rowsAlert=[];
	private $_keysAlert=[];
	
	public function __construct($attributes = Array()){
		// Apply provided attribute values
		foreach($attributes as $field=>$value){
			$this->$field = $value;
		}
	}
	
	function __set($name,$value){
		if(method_exists($this, $name)){
			$this->$name($value);
		}
		else{
			// Getter/Setter not defined so set as property of object
			$this->$name = $value;
		}
	}
	
	function __get($name){
		if(method_exists($this, $name)){
			return $this->$name();
		}
		elseif(property_exists($this,$name)){
			// Getter/Setter not defined so return property if it exists
			return $this->$name;
		}
		return null;
	}
	
	
	public function Profile($profile)
	{
		if(isset($profile))
		{
			$profile=preg_replace("/[^A-Za-z0-9]/","",$profile);
			$this->_profile=$profile;
		}
		else
		{
			return $this->_profile;
		}
	}
	
	public function From($from)
	{
	
		if(isset($from))
		{
			if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
				throw new Exception("Indirizzo email(from) non valido:".$from);
			}
			$this->_from=$from;
		}
		else{
			return $this->_from;
		}
	}
	
	public function To($to)
	{
	
		if(isset($to))
		{
			if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
				throw new Exception("Indirizzo email(to) non valido:".$to);
			}
			$this->_to=$to;
		}
		else{
			return $this->_to;
		}
	}
	
	public function Cc($cc)
	{
	
		if(isset($cc))
		{
			if (!filter_var($cc, FILTER_VALIDATE_EMAIL)) {
				throw new Exception("Indirizzo email(cc) non valido:".$cc);
			}
			$this->_cc=$cc;
		}
		else{
			return $this->_cc;
		}
	}
	
	public function Bcc($bcc)
	{
	
		if(isset($bcc))
		{
			if (!filter_var($bcc, FILTER_VALIDATE_EMAIL)) {
				throw new Exception("Indirizzo email(bcc) non valido:".$bcc);
			}
			$this->_bcc=$bcc;
		}
		else{
			return $this->_bcc;
		}
	}
	
	public function ColumnsAlert($columns)
	{
		if(isset($columns))
		{
			$this->_columnsAlert=$columns;
		}
		else {
			return false;
		}
	}
	
	
	public function RowsAlert($rows)
	{
		if(isset($rows))
		{
			$this->_rowsAlert[]=$rows;
		}
		else {
			return false;
		}
	}
	
	public function KeysAlert($keys)
	{
		if(isset($keys))
		{
			$this->_keysAlert[]=$keys;
		}
		else {
			return false;
		}
	}
	
	public function Attachment($file)
	{
		if(isset($file))
		{
			$this->_attachments[]=$file;
		}
		else {
			return false;
		}
	}
	
	public function ColumnsComplete($columns)
	{
		if(isset($columns))
		{
			$this->_columnsComplete=$columns;
		}
		else {
			return false;
		}
	
	}
	
	public function ColumnsError($columns)
	{
		if(isset($columns))
		{
			$this->_columnsError=$columns;
		}
		else {
			return false;
		}
	}
	
	public function Row($row)
	{
		if(isset($row))
		{
			if($row['result']===true)
			{
				$this->_rowsComplete[]=$row["value"];
			}
			else
			{
				$this->_rowsError[]=$row["value"];
			}
		}
		else {
			return false;
		}
	
	}
	
	public function Message($message)
	{
	
		if(isset($message))
		{
			$this->_message=$message;
		}
		else{
			return $this->_message;
		}
	}
	
	private function _subject()
	{
		if(isset($this->_profile))
		{
			$subject="Report importazioni organizzo - Profilo: ".$this->_profile;
			return $subject;
		}
		else {
			return "Report importazioni organizzo";
		}
	
	}
	
	private function _bodyHTML()
	{
		$body='<html>';
	    $body.='<head>';
	    $body.='<title>Yetiforce Report Importer</title>';
	    $body.='<style type="text/css">';
		$body.='.tg  {border-collapse:collapse;border-spacing:0;border-color:#aabcfe;}';
		$body.='.tg td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#aabcfe;color:#669;background-color:#e8edff;}';
		$body.='.tg th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#aabcfe;color:#039;background-color:#b9c9fe;}';
		$body.='.tg .tg-baqh{text-align:center;vertical-align:top}';
		$body.='.tg .tg-yw4l{background-color:#D2E4FC;text-left;vertical-align:top}';
		$body.='.tg .tg-mb3i{background-color:#D2E4FC;text-left;vertical-align:top}';
		$body.='@media screen and (max-width: 767px) {.tg {width: auto !important;}.tg col {width: auto !important;}.tg-wrap {overflow-x: auto;-webkit-overflow-scrolling: touch;}}';
		$body.='</style>';
		$body.='</head>';
	    $body.='<body>';
		
	    $body.='<h2>Report importazioni organizzo - '.date("d/m/Y").'</h2>';
	    
		if(!empty($this->_rowsComplete))
		{
			$body.='<div class="tg-wrap">';
			$body.='<h3>Importazioni Completate - Tot. '.count($this->_rowsComplete).'</h3>';
			$body.='<table class="tg">';
			$body.='<tr>';
			$body.='<th class="tg-baqh">No</th>';
			foreach($this->_columnsComplete as $column)
			{
			    $body.='<th class="tg-baqh">'.$column.'</th>';
			}
			$body.='</tr>';
				
			$count=1;
			foreach($this->_rowsComplete as $rows)
			{
				$class= ( $count & 1 ) ? 'tg-yw4l' : 'tg-mb3i';
				$body.='<tr>';
				$body.='<td class="'.$class.'">'.$count.'</td>';
				foreach($rows as $row)
				{
					$body.='<td class="'.$class.'">'.$row.'</td>';
				}
				$body.='</tr>';
				$count++;
			}
			$body.='</table></div>';
		}
		
		if(!empty($this->_rowsError))
		{
			$body.='<br>';
			$body.='<div class="tg-wrap">';
			$body.='<h3>Importazioni Errate - Tot. '.count($this->_rowsError).'</h3>';
			$body.='<table class="tg">';
			$body.='<tr>';
			$body.='<th class="tg-baqh">No</th>';
			foreach($this->_columnsError as $column)
			{
				$body.='<th class="tg-baqh">'.$column.'</th>';
			}
			$body.='</tr>';
		
			$count=1;
			foreach($this->_rowsError as $rows)
			{
				$class= ( $count & 1 ) ? 'tg-yw4l' : 'tg-mb3i';
				$body.='<tr>';
				$body.='<td class="'.$class.'">'.$count.'</td>';
				foreach($rows as $row)
				{
					$body.='<td class="'.$class.'">'.$row.'</td>';
				}
				$body.='</tr>';
				$count++;
			}
			$body.='</table></div>';
		}
		
		if(!empty($this->_rowsAlert))
		{
			$body.='<br>';
			$body.='<div class="tg-wrap">';
			$body.='<h3>Record da verificare</h3>';
			$body.='<p>I record presenti nella tabella sono stati creati. I valori in rosso sono stati saltati.</p>';
			$body.='<table class="tg">';
			$body.='<tr>';
			$body.='<th class="tg-baqh">No</th>';
			foreach($this->_columnsAlert as $key => $value)
			{
				$body.='<th class="tg-baqh">'.$key.'</th>';
			}
			$body.='</tr>';
			
			$count=1;
			foreach($this->_rowsAlert as $rows)
			{
				$class= ( $count & 1 ) ? 'tg-yw4l' : 'tg-mb3i';
				$body.='<tr>';
				$body.='<td class="'.$class.'">'.$count.'</td>';
				foreach($rows as $key => $value)
				{
					if(in_array($key, $this->_keysAlert[$count-1]))
					{
						$body.='<td class="'.$class.'" style="color: red;">'.$value.'</td>';
					}
					else 
					{
						$body.='<td class="'.$class.'">'.$value.'</td>';
					}
				}
				$body.='</tr>';
				$count++;
			}
			$body.='</table></div>';
		}
			
		if(isset($this->_message))
		{
			$body.=$this->_message;
		}
			
		$body.='</body></html>';
		
		return $body;
	}
	
	private function _bodyPLAIN()
	{
		$body=' -* Yetiforce Report Importer *- \n\n\n';
	
		if(!empty($this->_rowsComplete))
		{
			
			$body.='Importazioni Completate:\n\n';
			$body.='No';
			foreach($this->_columnsComplete as $column)
			{
				$body.=' - '.$column;
			}
			$body.='\n';
			$count=1;
			foreach($this->_rowsComplete as $rows)
			{
				$body.=$count;
				foreach($rows as $row)
				{
					$body.=' - '.$row;
				}
				$body.='\n';
				$count++;
			}
			$body.='\n\n';
		}
	
	if(!empty($this->_rowsError))
		{
			
			$body.='Importazioni Completate:\n\n';
			$body.='No';
			foreach($this->_columnsError as $column)
			{
				$body.=' - '.$column;
			}
			$body.='\n';
			$count=1;
			foreach($this->_rowsError as $rows)
			{
				$body.=$count;
				foreach($rows as $row)
				{
					$body.=' - '.$row;
				}
				$body.='\n';
				$count++;
			}
			$body.='\n\n';
		}
	
		return $body;
	}
	
	public function SendReport()
	{
		if(!empty($this->_rowsComplete) || !empty($this->_rowsError))
		{
			try
			{
				//Create a new PHPMailer instance
				$mail = new PHPMailer;
				//Tell PHPMailer to use SMTP
				$mail->isSMTP();
				//Enable SMTP debugging
				// 0 = off (for production use)
				// 1 = client messages
				// 2 = client and server messages
				$mail->SMTPDebug = 0;
				//Ask for HTML-friendly debug output
				//$mail->Debugoutput = 'html';
				//Set the hostname of the mail server
				$mail->Host = "smtphostname";
				//Set the SMTP port number - likely to be 25, 465 or 587
				$mail->Port = 465;
				//Whether to use SMTP authentication
				$mail->SMTPAuth = true;
				//Username to use for SMTP authentication
				$mail->Username = "username";
				//Password to use for SMTP authentication
				$mail->Password = "password";
				//Set who the message is to be sent from
				$mail->setFrom('emailfrom', 'Importazione Organizzo');
				//Set an alternative reply-to address
				//$mail->addReplyTo('replyto@example.com', 'First Last');
				//Set who the message is to be sent to
				$mail->addAddress($this->_to);
				
				if(isset($this->_cc))
					$mail->addCC($this->_cc);
				
				if(isset($this->_bcc))
					$mail->addBCC($this->_bcc);
				
				//Set the subject line
				$mail->Subject = $this->_subject;
				$mail->CharSet = 'UTF-8';
				//Read an HTML message body from an external file, convert referenced images to embedded,
				//convert HTML into a basic plain-text alternative body
				$mail->isHTML(true);
				$mail->Body = $this->_bodyHTML();
				//$mail->msgHTML(file_get_contents('contents.html'), dirname(__FILE__));
				//Replace the plain text body with one created manually
				$mail->AltBody = $this->_bodyPLAIN();
				
				if(!empty($this->_attachments))
				{
					$count_attachment=0;
					foreach($this->_attachments as $attachment)
					{
						$mail->addAttachment($attachment);
						$count_attachment++;
						if($count_attachment>self::MAX_ATTACHMENTS)
							break;
					}
				}
				//Attach an image file
				//$mail->addAttachment('images/phpmailer_mini.png');
				//send the message, check for errors
					if (!$mail->send()) {
						throw new Exception("Errore nell'invio del report: " . $mail->ErrorInfo);
					}
			}
			catch(Exception $e)
			{
				throw new Exception("Errore nell'invio del report: " . $e->getMessage());
			}
		}	
	}
}
