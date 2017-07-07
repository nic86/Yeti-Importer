<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/reportimporter.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

//Yetiforce folder installation 
chdir('/var/www/yetiforce/');

include_once 'include/main/WebUI.php';

class CsvImporter
{
	const INPUT_DIR     = __DIR__ ."/upload/";
	const COMPLETE_DIR  = __DIR__ ."/system/csv/complete/";
	const ERROR_DIR     = __DIR__ ."/system/csv/error/";
	const LOG_DIR       = __DIR__ ."/system/csv/log/";
	
	const SEPARATOR_NAME = "__";
	
	const COMPLETE_MAXDATE = "31"; //massimo 7 giorni
	const ERROR_MAXDATE    = "31"; //massimo 7 giorni
	const LOG_MAXDATE      = "31"; //massimo 7 giorni
	
	const EXTENSION_PERMIT = ['csv','del','txt','var'];
	
	private function inputPath()
	{
		return self::INPUT_DIR.$this->id."/".$this->profile."/";
	}
	
	private function completePath()
	{
		return self::COMPLETE_DIR.$this->id."/".$this->profile."/";
	}
	
	private function errorPath()
	{
		return self::ERROR_DIR.$this->id."/".$this->profile."/";
	}
	
	private function logPath()
	{
		return self::LOG_DIR.$this->id."/".$this->profile."/";
	}
	
	public function __construct($attributes  =  Array()) 
	{
		$this->scriptDate = date('YmdHis');
		// Apply provided attribute values
		foreach($attributes as $field=>$value){
			$this->$field = $value;
		}
		
		if(!isset($this->profile)) {
			throw new Exception("Nessun nome del profilo impostato");
		}
		if(!isset($this->id)) {
			throw new Exception("Nessun ID impostato");
		}
		if (!is_dir($this->completePath)) {
			mkdir($this->completePath, 0775, true);
		}
		if (!is_dir($this->errorPath)) {
			mkdir($this->errorPath, 0775, true);
		}
		if (!is_dir($this->logPath)) {
			mkdir($this->logPath, 0775, true);
		}
		
		$this->deleteFilesOlderthan($this->completePath, self::COMPLETE_MAXDATE);
		$this->deleteFilesOlderthan($this->errorPath, self::ERROR_MAXDATE);
		$this->deleteFilesOlderthan($this->logPath, self::LOG_MAXDATE);
		
		if ($this->debug) {
			$date       = new DateTime();
			$date       = $date->format("YmdHis");

			$logname    = $this->scriptDate.self::SEPARATOR_NAME.$this->id.self::SEPARATOR_NAME.$this->profile.".log";

			$dateFormat = "d/m/Y, H:i:s";
			// the default output format is "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
			$output = "%datetime% > %level_name% > %message% %context% %extra%\n";
			// finally, create a formatter
			$formatter = new LineFormatter($output, $dateFormat);
				
			// Create a handler
			$stream = new StreamHandler($this->logPath.$logname, Logger::DEBUG);
			$stream->setFormatter($formatter);
				
			$this->log = new Logger($logname);
			$this->log->pushHandler($stream);
		}
		
		$this->parseCsv = new parseCSV();
	}
	
	private function trim_value(&$value)
	{
		if (is_string($value)) {
			$value = trim($value);
		}
	}
	
	function __set($name,$value) {
		if (is_array($value)) {
			$func = create_function('&$val', 'if(is_string($val)){$val = trim($val);}');
			array_walk_recursive($value,$func);
		} elseif(is_string($value)) {
			$value = trim($value);
		}
		if (method_exists($this, $name)) {
			$this->$name($value);
		} else {
			// Getter/Setter not defined so set as property of object
			$this->$name = $value;
		}
	}
	
	function __get($name){
		if (method_exists($this, $name)) {
			return $this->$name();
		} elseif(property_exists($this,$name)) {
			// Getter/Setter not defined so return property if it exists
			return $this->$name;
		}
		return null;
	}

	public function disableWorkflows($arg = null)
	{
	
		if (isset($arg)) {
			$this->disableWorkflows = $arg;
		} else {
			return $this->disableWorkflows;
		}
	}

	public function disableHandlers($arg = null)
	{
	
		if (isset($arg)) {
			$this->disableHandlers = $arg;
		} else {
			return $this->disableHandlers;
		}
	}

	public function id($arg = null)
	{
	
		if (isset($arg)) {
			$arg = preg_replace("/[^A-Za-z0-9]/","",$arg);
			$this->id = $arg;
		} else {
			return $this->id;
		}
	}
	
	public function delimiter($arg = null)
	{
		if (isset($arg)) {
			$this->delimiter = $arg;
		} else {
			return $this->delimiter;
		}
	}
	
	public function profile($arg = null)
	{
		if (isset($arg)) {
			$arg = preg_replace("/[^A-Za-z0-9]/","",$arg);
			$this->profile = $arg;
		} else {
			return $this->profile;
		}
	}
	
	public function getLogPath()
	{
		if (isset($this->log)) {
			return $this->logPath.$this->scriptDate.self::SEPARATOR_NAME.$this->id.self::SEPARATOR_NAME.$this->profile.".log";
		}
	}
	
	public function logError($arg = null)
	{
		if (isset($this->log) && isset($arg)) {
			$this->log->error($arg);
		} else {
			return $this->log;
		}
	}
	
	public function logInfo($arg = null)
	{
		if (isset($this->log)  && isset($arg)) {
			$this->log->info($arg);
		} else {
			return $this->log;
		}
	}
	
	public function report($arg = null)
	{
		if (isset($arg)) {
			$this->report = new ReportImporter($arg);
		} else {
			return $this->report;
		}
	}
	
	public function debug($arg = null)
	{
		if (isset($arg)) {
			$this->debug = $arg;
		} else {
			return $this->debug;
		}
	}
	
	public function maxFileNumber($arg = null)
	{
		if (isset($arg)) {
			if (is_numeric($arg)) {
				$this->maxFileNumber = $arg;
			} else {
				throw new Exception("Errore nell'impostazione del massimo numero dei file");
			}
		} else {
			return $this->maxFileNumber;
		}
	}
	
	public function offset($arg = null)
	{
		if (isset($arg)) {
			if (is_numeric($arg)) {
				$this->offset = $arg;
			} else {
				throw new Exception("Errore nell'impostazione del parametro di offset");
			}
		} else {
			return $this->offset;
		}
	}
	
	public function maxLineNumber($arg = null)
	{
		if (isset($arg)) {
			if (is_numeric($arg)) {
				$this->maxLineNumber = $arg;
			} else {
				throw new Exception("Errore nell'impostazione del massimo numero di righe");
			}
		} else {
			return $this->maxLineNumber;
		}
	}
	
	public function searchFields($arg = null)
	{
		if (isset($arg) && is_array($arg)) {
			$this->searchFields = $arg;
		}
		return $this->searchFields;
	}
	
	public function defaultFields($arg = null)
	{
		if (isset($arg) && is_array($arg)) {
			$this->defaultFields = $arg;
		}
		return $this->defaultFields;
	}
	
	public function conditionFields($arg = null)
	{
		if (isset($arg) && is_array($arg)) {
			$this->conditionFields = $arg;
		}
		return $this->conditionFields;
	}
	
	public function sanitizedFields($arg = null)
	{
		if (isset($arg) && is_array($arg)) {
			$this->sanitizedFields = $arg;
		}
		return $this->sanitizedFields;
	}
	
	public function relatedModules($arg = null)
	{
		if (isset($arg)) {
			$this->relatedModules = $arg;
		}
		return $this->relatedModules;
	}
	
	public function mappingFields($arg = null)
	{
		if (isset($arg)) {
			$this->mappingFields = $arg;
		}
		return $this->mappingFields;
	}
	
	public function heading($arg = null)
	{
		if (isset($arg)) {
			$this->heading = $arg;
		}
		return $this->heading;
	}
	
	public function encoding($arg = null)
	{
		if (isset($arg)) {
			$this->encoding = $arg;
		}
		return $this->encoding;
	}
	
	public function conditions($arg = null)
	{
		if (isset($arg)) {
			$this->conditions = $arg;
		}
		return $this->conditions;
	}
	
	public function user($arg = null)
	{
		if (isset($arg)) {
			$this->user = $this->getUserId($arg);
			return true;
		}
		return $this->user;
	}
	
	public function module($module = null)
	{
		if (isset($module)) {
			$this->module = $module;
		}
		return $this->module;
	}
	
	public function importProfile()
	{
		$this->logInfo = "---------------------------";
		$this->logInfo = "Inizio importazione profilo";

		App\User::setCurrentUserId(Users::getActiveAdminId());
		$currentuser = Users::getActiveAdminUser();
		vglobal('current_user', $currentuser);

		$this->checkImportConfig();
		
		$files = new FilesystemIterator($this->inputPath, FilesystemIterator::SKIP_DOTS);
		
		if (iterator_count($files) == 0) {
			$this->logInfo  = "Nessuna file nella cartella: ".$this->inputPath;
		}
		
		$countFile  = 0;
		foreach($files as $file) {
			$fileextension = strtolower($file->getExtension());
			if(!in_array($fileextension,self::EXTENSION_PERMIT)) {
				continue;
			}
			$countFile++;
			if ($countFile>$this->maxFileNumber) {
				$this->logInfo = "Superato il numero massimo di file nella cartella: ".$this->inputPath;
				break;
			}
			
			switch ($fileextension) {
				case "del":
					$action = "delete";
					break;
				case "txt":
					$action = "create";
					break;
				case "var":
					$action = "edit";
					break;
				default:
					$action = "create";
					break;
			}
			
			$pathname = $file->getPathname();
			$filename = $file->getFilename();
			try{
				if (isset($this->delimiter)) {
					$this->parseCsv->delimiter = $this->delimiter;
				}
				if (isset($this->heading)) {
					$this->parseCsv->heading = $this->heading;
				}
				if (isset($this->maxLineNumber)) {
					$this->parseCsv->limit = $this->maxLineNumber;
				}
				if (isset($this->conditions)) {
					$this->parseCsv->conditions = $this->conditions;
				}
				if (isset($this->offset)) {
					$this->parseCsv->offset = $this->offset;
				}
				if (isset($this->encoding)) {
					$this->parseCsv->encoding($this->encoding,'UTF-8');
				}

				$this->parseCsv->parse($pathname);
				$lines = $this->parseCsv->data;
			} catch(Exception $e) {
				$this->logError = $e->getMessage();
				try {
					$this->moveFileToError($file);
				} catch(Exception $e) {
					$this->logError = $e->getMessage();
				}
				continue;
			}
			
			if (!isset($lines)) {
				$this->logError = "Non è stato possibile fare il parsing del file: ".$pathname;
				try {
					$this->moveFileToError($file);
				} catch(Exception $e) {
					$this->logError = $e->getMessage();
				}
				continue;
			}
			
			if (count($lines) === 0) {
				$this->logError = "Non è stato possibile fare il parsing del file: ".$pathname;
				try {
					$this->moveFileToError($file);
				} catch(Exception $e) {
					$this->logError = $e->getMessage();
				}
				continue;
			}
						
			if (isset($this->mappingFields)) {
				try {
					$lines = $this->setMapping($lines);
				} catch(Exception $e) {
					$this->logError = $e->getMessage();
					try{
						$this->moveFileToError($file);
					} catch(Exception $e) {
						$this->logError = $e->getMessage();
					}
					continue;
				}
			}

			$linesError = [];
			$countLineCorrect = 0;
			foreach ($lines as $line) {
				if (!empty($this->defaultFields) && $action!=="delete") {
					foreach ($this->defaultFields as $key => $value) {
						if (array_key_exists($key,$line) && !empty($value)) {
							if (strpos($value, 'formatdate') !== false) {
								$formatdate = explode('!',$value);
								$myDateTime = DateTime::createFromFormat($formatdate[1],$line[$key]);
								$newDateString = $myDateTime->format($formatdate[2]);
								$value = $newDateString;
							}
							$line[$key] = $value;
						}
					}
				}

				if(!empty($this->sanitizedFields) && $action!=="delete") {
					foreach ($this->sanitizedFields as $key => $value) {
						if (array_key_exists($key,$line) && !empty($value)) {
							if (!empty($line[$key])) {
								$regex = '/'.$value.'/';
								$match = preg_replace($regex, "", $line[$key]);
								if(!empty($match)) {
									$line[$key] = $match;
								}
							}
						}
					}
				}
				
				$keyAlert = [];
				if(!empty($this->conditionFields) && $action!=="delete") {
					foreach ($this->conditionFields as $key => $value) {
						if(array_key_exists($key,$line) && !empty($value)) {
							if(!empty($line[$key])) {
								$matches = [];
								$regex = '/'.$value.'/';
								preg_match($regex, $line[$key], $matches);
								if(empty($matches)) {
									$keyAlert[] = $key;
								}
							}
						}
					}
				}

				if(!empty($keyAlert)) {
					$this->report->RowsAlert($line);
					$this->report->KeysAlert($keyAlert);
					foreach ($keyAlert as $key) {
						unset($line[$key]);
					}
				}

				$locksOverwrite = [];
				$relatedIds = [];
				if(!empty($this->relatedModules) && $action!=="delete") {
					foreach ($this->relatedModules as $relatedModule) {
						try{
							$this->relateRecord($line,$relatedModule,$locksOverwrite,$relatedIds);
						} catch(Exception $e) {
							$this->logError = "Errore record relazionato - relatedModule: ".print_r($relatedModule,true)." - Line:".print_r($line,true)." - Messaggio: ".$e->getMessage();
						}
					}
				}
				$recordId = false;
				if(isset($this->searchFields)) {
					try{
						$recordId = $this->searchRecord($line);
					} catch(Exception $e) {
						$this->logError = "Errore record di ricerca - SearchModule: ".print_r($this->searchFields,true)." - Line:".print_r($line,true)." - Messaggio: ".$e->getMessage();
					}
				}
				
				if($action==="delete") {
					if($recordId) {
						try{
							$this->entityDelete($this->module,$recordId);
						} catch(Exception $e) {
							$this->logError = "Errore eliminazione record - Line:".print_r($line,true)." - Messaggio: ".$e->getMessage();
						}
						$countLineCorrect++;
					}
					continue;
				}

				if($recordId) {
					try{
						$recordId = $this->entitySave($this->module,$line,$recordId);
						if(!$recordId) {
							$linesError[] = $line;
							$this->logError = "Errore nell'aggiornamento del record: ".print_r($line,true);
							continue;
						}
					} catch(Exception $e) {
						$linesError[] = $line;
						$this->logError = "Errore nell'aggiornamento del record: ".print_r($line,true)." - Messaggio: ".$e->getMessage();
						continue;
					}
					$this->logInfo = "Record aggiornato: ".print_r($line,true);
				} else {
					try{
						$recordId = $this->entitySave($this->module,$line);
						if(!$recordId) {
							$linesError[] = $line;
							$this->logError = "Errore nella creazione del record: ".print_r($line,true);
							continue;
						}
					} catch(Exception $e) {
						$linesError[] = $line;
						$this->logError = "Errore nella creazione del record: ".print_r($line,true)." - Messaggio: ".$e->getMessage();
						continue;
					}
					$this->logInfo = "Record creato: ".print_r($line,true);
				}

				if (count($relatedIds)>0 && $recordId) {
					foreach ($relatedIds as $relatedId => $relatedModule) {
						$this->entityRelate($recordId,$relatedId,$relatedModule);
					}
				}

				$countLineCorrect++;
			}

			$countLineError = count($linesError);
			if ($countLineError>0) {
				try {
					$csverr = new parseCSV();
					$filename = $this->scriptDate.SELF::SEPARATOR_NAME.$filename;
					$csverr->save($this->errorPath.$filename, $linesError, true);
					$result = unlink($file);
					$this->report->Row(["value"=>[$filename,$countLineError],"result"=>false]);
					$this->report->Attachment($this->errorPath.$filename);
				} catch(Exception $e) {
					$this->logError = "Errore nella creazione del csv delle linee errate - linesError: ".print_r($linesError,true)." - Messaggio: ".$e->getMessage();
				}
			}
			
			if($countLineCorrect>0) {
				try {
					$this->moveFileToComplete($file,$countLineCorrect);
					if($action==="delete") {
						$this->logInfo = "Importazione Completata: ".$filename." - Record eliminati: ".$countLineCorrect;
					} else {
						$this->logInfo = "Importazione Completata: ".$filename." - Record creati: ".$countLineCorrect;
					}
				} catch(Exception $e) {
					$this->logError = $e->getMessage();
				}
			}
		}

		$this->logInfo = "Fine importazione profilo";
		$this->logInfo = "---------------------------";
		
		if(isset($this->report)) {
			$this->report->ColumnsAlert($this->mappingFields);
			$this->report->Profile = $this->profile;
			$this->report->sendReport();
		}
	}
	
	private function searchRecord($line)
	{
		foreach ($this->searchFields as $searchField) {
			$param = [];
			foreach ($searchField as $fields) {
				if(array_key_exists($fields,$line)) {
					$param[$fields]=$line[$fields];
				}
			}
			
			$idSearchEntity = false;
			if(!empty($param)) {
				$idSearchEntity = $this->entityGetId($this->module,$param);
				if($idSearchEntity) {
					return $idSearchEntity;
				}
			}
		}
	}
	
	private function relateRecord(&$line,$relatedModule,&$locksOverwrite,&$relatedIds)
	{
		if(isset($relatedModule["module"])) {
			$module = $relatedModule["module"];
		} else {
			throw new Exception("Variabile modules non assegnata nel modulo relazionato: ".print_r($relatedModule,true));	
		}
		
		if(isset($relatedModule["search"])) {
			$searchFields = $relatedModule["search"];
			$paramSearch = [];
			foreach($searchFields as $campoCsv =>  $campoRel) {
				if(array_key_exists($campoCsv,$line)) {
					if(!empty($line[$campoCsv])) {
						$paramSearch[$campoRel]=$line[$campoCsv];
					}
				}
			}
		}
		
		if(isset($relatedModule["mapping"])) {
			$mappingFields = $relatedModule["mapping"];
			$paramMapping = [];
			foreach($mappingFields as $campoCsv =>  $campoRel) {
				if(array_key_exists($campoCsv,$line)) {
					if (!empty($line[$campoCsv])) {
						$paramMapping[$campoRel] = $line[$campoCsv];
					}
				}
			}
			
			if(isset($relatedModule["conditions"])) {
				$conditionFields = $relatedModule["conditions"];
				foreach($conditionFields as $key =>  $value) {
					if(array_key_exists($key,$paramMapping) && !empty($value)) {
						$matches = [];
						$regex = '/'.$value.'/';
						preg_match($regex, $paramMapping[$key], $matches);
						if(!empty($matches[1])) {
							$paramMapping[$key] = $matches[1];
						} else {
							unset($paramMapping[$key]);
						}
					}
				}
			}
		}
		
		if (isset($relatedModule["overwrite"])) {
			$overwrite = $relatedModule["overwrite"];
			if (in_array($overwrite, $locksOverwrite)) {
				return;
			}
		}
			
				
		if(!empty($paramSearch)) {
			$idRelatedEntity = $this->entityGetId($module,$paramSearch);
		}
		if(!empty($paramMapping)) {
			foreach($paramMapping as $key=>$value) {
				$entity[$key] = $value;
			}

			if(!empty($idRelatedEntity)) {
				$idRelatedEntity = $this->entitySave($module,$entity,$idRelatedEntity);
			} else {
				$idRelatedEntity = $this->entitySave($module,$entity);
			}
		}

		if (isset($overwrite)) {
			if(!empty($idRelatedEntity)) {
				$line[$overwrite] = $idRelatedEntity;
				$locksOverwrite[] = $overwrite;
			} else {
				unset($line[$overwrite]);
			}
		} else {
			$relatedIds[$idRelatedEntity] = $module;
		}
	}

	private function getUserId($username)
	{
		$adb    = PearDatabase::getInstance();
		$query  = 'select * from vtiger_users where deleted = 0 AND user_name = ?';
		$result = $adb->pquery($query, [$username]);
		$userId = $adb->query_result($result, 0, 'id');
		$userId = $userId ? $userId : 1 ;
		return $userId;
	}

	private function moveFileToError($file,$countline = 0)
	{
		$orifilename = $file->getFilename();
		$filename    = $this->scriptDate.SELF::SEPARATOR_NAME.$orifilename;
		$source      = $file->getPathname();
		$dest        = $this->errorPath.$filename;
		if(file_exists($dest)) {
			return true;
		}
			
		chmod($source,0775);
	
		$result = copy($source,$dest);
	
		if (!$result) {
			throw new Exception("Impossibile spostare il file da ".$source." a ".$dest);
		}
		//non si può eliminare perchè è aperto l'oggetto che punta ai file
		$result = unlink($file);
	
		if (!$result) {
			throw new Exception("Impossibile eliminare il file: ".$source);
		}
			
		$this->report->Row(["value"=>[$orifilename,$countline],"result"=>false]);
			
		return true;
	}
	
	private function moveFileToComplete($file,$countLine = 0)
	{
		$orifilename = $file->getFilename();
		$filename    = $this->scriptDate.SELF::SEPARATOR_NAME.$orifilename;

		$source      = $file->getPathname();
		$dest        = $this->completePath.$filename;
	
		if(file_exists($dest)) {
			return true;
		}

		chmod($source,0775);
	
		$result = copy($source,$dest);
	
		if(!$result) {
			throw new Exception("Impossibile spostare il file da ".$source." a ".$dest);
		}
			
		$result = unlink($file);
	
		if(!$result) {
			throw new Exception("Impossibile eliminare il file: ".$source);
		}
			
		$this->report->Row(["value"=>[$orifilename,$countLine],"result"=>true]);
			
		return true;
	}
	
	private function checkWSConfig()
	{
		if (!isset($this->_url)) {
			throw new Exception("Host Organizzo non impostato");
		}
		if (!isset($this->user)) {
			throw new Exception("Utente Organizzo non impostato");
		}
		if (!isset($this->_password)) {
			throw new Exception("Password Organizzo non impostata");
		}
	}
	
	private function checkImportConfig()
	{
		clearstatcache();
		
		if (PHP_SAPI !== 'cli') {
			throw new Exception("Accesso negato.");
		}

		if (!file_exists($this->inputPath)) {
			throw new Exception("La cartella di input non esiste: ".$this->inputPath);
		}

		if (!isset($this->profile)) {
			throw new Exception("Nessun nome del profilo impostato");
		}
			
		if (!isset($this->id)) {
			throw new Exception("Nessun ID impostato");
		}
				
		if (!isset($this->module)) {
			throw new Exception("Nessun modulo impostato");
		}

		if (!isset($this->parseCsv)) {
			throw new Exception("Nessuna oggetto per fare il parse csv");
		}
			
		if (!$this->existModule($this->module)) {
			throw new Exception("Non è attivo il modulo: ".$this->module);
		}

		if (isset($this->relatedModules)) {
			foreach ($this->relatedModules as $relatedModule) {
				if (isset($relatedModule['module'])) {
					if (!$this->existModule($relatedModule['module'])) {
						throw new Exception("Il modulo relazionato non è attivo: {$relatedModule['module']}");
					}
				}
			}
		}

		
	}

	private function entityDelete($moduleName,$recordId)
	{
		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
		if ($this->disableHandlers) {
			$recordModel->setHandlerExceptions(['disableHandlers' => true]);
		}
		if ($this->disableWorkflows) {
			$recordModel->setHandlerExceptions(['disableWorkflow' => true]);
		}
		$recordModel->delete();
	}

	private function entityRelate($idRecord,$idRelated,$moduleRelated)
    {
        try {
			if ($this->module === 'Documents') {
				$sourceModuleModel = Vtiger_Module_Model::getInstance($moduleRelated);
				$relatedModuleModel = Vtiger_Module_Model::getInstance($this->module);
				$relationModel = Vtiger_Relation_Model::getInstance($sourceModuleModel, $relatedModuleModel);
				if ($relationModel) {
					$relationModel->addRelation($idRelated, $idRecord);
				}
			} else {
				$sourceModuleModel = Vtiger_Module_Model::getInstance($this->module);
				$relatedModuleModel = Vtiger_Module_Model::getInstance($moduleRelated);
				$relationModel = Vtiger_Relation_Model::getInstance($sourceModuleModel, $relatedModuleModel);
				if ($relationModel) {
					$relationModel->addRelation($idRecord, $idRelated);
				}
			}
        } catch(Exception $e) {
            throw new Exception("Non è stato possibile relazionare il documento id ".$idRecord." al record con id ".$idRelated." del modulo ".$this->relatedModule);
        }

        return true;
    }


	private function entitySave($moduleName,$entity,$recordId = null)
	{
		if (!empty($recordId)) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);

			$isNew = $recordModel->isNew();
			if ($isNew) {
				$recordModel->isNew = false;
			}

		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
		}
		$fieldModelList = $recordModel->getModule()->getFields();
		foreach ($fieldModelList as $fieldName => &$fieldModel) {
			if (!$fieldModel->isWritable()) {
				continue;
			}
			if ($fieldName=='assigned_user_id') {
				$recordModel->set($fieldName, $fieldModel->getUITypeModel()->getDBValue($this->user, $recordModel));
			} elseif (!empty($entity[$fieldName]) && ($fieldModel->get('uitype') === 302)) {
				$value = $this->getTreeValue($entity[$fieldName],$fieldModel);
				$recordModel->set($fieldName, $value);
			} elseif (!empty($entity[$fieldName]) && ($fieldModel->get('uitype') === 309)) {
				$value = $this->getMultiTreeValue($entity[$fieldName],$fieldModel);
				$recordModel->set($fieldName, $value);
			} elseif (!empty($entity[$fieldName])) {
				$recordModel->set($fieldName, $fieldModel->getUITypeModel()->getDBValue($entity[$fieldName], $recordModel));
			} elseif ($recordModel->isNew()) {
				$defaultValue = $fieldModel->getDefaultFieldValue();
				if ($defaultValue !== '') {
					$recordModel->set($fieldName, $defaultValue);
				}
			}
		}
		if (!empty($recordId)) {
                        $recordModel->setId($recordId);
        }
		if ($this->disableHandlers) {
			$recordModel->setHandlerExceptions(['disableHandlers' => true]);
		}
		if ($this->disableWorkflows) {
			$recordModel->setHandlerExceptions(['disableWorkflow' => true]);
		}

		$recordModel->save();
		$recordModel->clearPrivilegesCache();

		$recordId = $recordModel->getId();
		if ($recordId) {
			\App\Record::updateLabel($moduleName, $recordId);
		}
		return $recordId;
	}

	private function getMultiTreeValue ($importValue, $fieldModel)
	{
		$template = $fieldModel->getFieldParams();
		$module = $fieldModel->getModuleName();

		if (empty($importValue)) {
			return '';
		}
		$names = [];
		$importValues = explode(',', $importValue);
		if (\App\Cache::has('TreeData', $template)) {
			$treeData = \App\Cache::get('TreeData', $template);
		} else {
			$treeData = (new \App\Db\Query())
				->select(['tree', 'name', 'parenttrre', 'depth', 'label'])
				->from('vtiger_trees_templates_data')
				->where(['templateid' => $template])
				->createCommand()
				->queryAll();
			\App\Cache::save('TreeData', $template, $treeData, \App\Cache::LONG);
		}

		foreach ($importValues as $value) {
			foreach ($treeData as $treeValue) {
				if (isset($treeValue['label'])) {
					if($treeValue['label'] === $value) {
						$names[] = $treeValue['tree'];
						break;
					}
				}
			}
		}
		return implode(', ', $names);
	}

	private function getTreeValue ($value, $fieldModel)
	{
		$template = $fieldModel->getFieldParams();
		$name = Vtiger_Cache::get('TreeData' . $template, $value);
		if ($name) {
			return $name;
		}

		$row = (new App\Db\Query())
			->from('vtiger_trees_templates_data')
			->where(['templateid' => $template, 'label' => $value])
			->one();

		$module = $fieldModel->getModuleName();
		$name = false;
		if ($row !== false) {
			$name = $row['tree'];
		}
		Vtiger_Cache::set('TreeData' . $template, $tree, $name);
		return $name;
	}


	private function entityGetId($moduleName,$search)
	{
		$moduleModel = \Vtiger_Module_Model::getInstance($moduleName);
		$returnId = false;
		$where = '';
		foreach($search as $key => $val) {
			$fieldModel  = $moduleModel->getFieldByName($key);
			if(!empty($fieldModel)) {
				$fieldTable  = $fieldModel->getTableName();
				$fieldColumn = $fieldModel->getColumnName();
				$val = mysql_escape_string($val);
				$where .=  " AND {$fieldTable}.{$fieldColumn} LIKE '{$val}'";
			}
		}
		if (!empty($where)) {
			$where   .= ' LIMIT 1';
			$query    = getListQuery($moduleName, $where);
			$query    = explode('FROM',$query);
			$query    = "SELECT vtiger_crmentity.crmid FROM{$query[1]}";
			$adb      = PearDatabase::getInstance();
			$result   = $adb->query($query);
			$returnId = $adb->getSingleValue($result);
		}

		return $returnId;
	}

	private function existModule($moduleName)
	{
		$modules = \vtlib\Functions::getAllModules();
		foreach ($modules as $module) {
			if($module['name']===$moduleName) {
				if (\App\Module::isModuleActive($moduleName)) {
					return true;
				}
			}
		}
		return false;
	}

	private function setMapping($lines)
	{
		$newLines = [];
		foreach ($lines as $line) {
			$i = 0;
			$newLine = [];
			foreach ($this->mappingFields as $key => $value) {
				if (isset($line[$i])) {
					$newLine[$value] = $line[$i];
				}
				$i++;
			}
			$newLines[] = $newLine;
		}
		return $newLines;
	}

	private function folderSize ($dir)
	{
		$size = 0;
		foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
			$size += is_file($each) ? filesize($each) : folderSize($each);
		}
		return $size;
	}

	private function deleteFilesOlderthan($folder,$days)
	{
		$now   = time();
		$files = new FilesystemIterator($folder, FilesystemIterator::SKIP_DOTS);
		foreach ($files as $file) {
			if (($now - $file->getMTime()) >= (60 * 60 * 24 * $days)) {
				unlink($file->getPathname());
			}
		}
	}
}
?>
