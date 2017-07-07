<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/orgascanner.php';
require_once __DIR__ . '/reportimporter.php';
use mikehaertl\pdftk\Pdf;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

//yetiforce folder installation
chdir('/var/www/yetiforce/');

include_once 'include/main/WebUI.php';

class PdfImporter
{
    const INPUT_DIR     = __DIR__ ."/upload/";
    const COMPLETE_DIR  = __DIR__ ."/system/pdf/complete/";
    const ERROR_DIR     = __DIR__ ."/system/pdf/error/";
    const PROGRESS_DIR  = __DIR__ ."/system/pdf/progress/";
    const LOG_DIR       = __DIR__ ."/system/pdf/log/";
    
    const SEPARATOR_NAME = "__";
    
    const PROGRESS_MAXSIZE = "5000000000";  //massimo 1gb
    const COMPLETE_MAXSIZE = "20000000000"; //massimo 20gb
    const ERROR_MAXSIZE    = "1000000000"; //massimo 1gb
    
    const PROGRESS_MAXDATE = "7"; //massimo 7 giorni
    const COMPLETE_MAXDATE = "7"; //massimo 7 giorni
    const ERROR_MAXDATE    = "7"; //massimo 7 giorni
    const LOG_MAXDATE      = "7"; //massimo 7 giorni
    
    private function inputPath()
    {
        return self::INPUT_DIR.$this->id."/".$this->profile."/";
    }
    
    private function progressPath()
    {
        return self::PROGRESS_DIR.$this->id."/".$this->profile."/";
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
    
    
    public function __construct($attributes = Array())
    {
        $this->scriptDate=date('YmdHis');
        // Apply provided attribute values
        foreach ($attributes as $field=>$value) {
            $this->$field = $value;
        }
        
        if (!isset($this->profile))
            throw new Exception("Nessun nome del profilo impostato");
            
        if (!isset($this->id))
            throw new Exception("Nessun ID impostato");
            
        if (!is_dir($this->completePath)) {
            mkdir($this->completePath, 0775, true);
        }
        if (!is_dir($this->progressPath)) {
            mkdir($this->progressPath, 0775, true);
        }
        if (!is_dir($this->errorPath)) {
            mkdir($this->errorPath, 0775, true);
        }
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0775, true);
        }
        
        $this->deleteFilesOlderthan($this->progressPath, self::PROGRESS_MAXDATE);
        $this->deleteFilesOlderthan($this->completePath, self::COMPLETE_MAXDATE);
        $this->deleteFilesOlderthan($this->errorPath, self::ERROR_MAXDATE);
        $this->deleteFilesOlderthan($this->logPath, self::LOG_MAXDATE);
            
        if ($this->debug) {
            $date       = new DateTime();
            $date       = $date->format("YmdHis");

            $logname    = $this->scriptDate.self::SEPARATOR_NAME.$this->id.self::SEPARATOR_NAME.$this->profile.".log";
            $dateFormat = "d/m/Y, H:i:s";
            $output     = "%datetime% > %level_name% > %message% %context% %extra%\n";
            $formatter  = new LineFormatter($output, $dateFormat);
                
            // Create a handler
            $stream = new StreamHandler($this->logPath.$logname, Logger::DEBUG);
            $stream->setFormatter($formatter);
                
            $this->log = new Logger($logname);
            $this->log->pushHandler($stream);
        }
    }

    function __set($name,$value)
    {
        if (method_exists($this, $name)) {
            $this->$name($value);
        } else {
            // Getter/Setter not defined so set as property of object
            $this->$name = $value;
        }
    }
    
    function __get($name) 
    {
        if (method_exists($this, $name)) {
            return $this->$name();
        } elseif (property_exists($this, $name)) {
            // Getter/Setter not defined so return property if it exists
            return $this->$name;
        }
        return null;
    }
    
    public function id($arg = null)
    {
        if (isset($arg)) {
            $arg = preg_replace("/[^A-Za-z0-9]/", "", $arg);
            $this->id = $arg;
        } else {
            return $this->id;
        }
	}
	
	public function searchField($arg = null)
    {
        if (isset($arg)) {
            $this->searchField = $arg;
        } else {
            return $this->searchField;
        }
    }

	public function sanitizedField($arg = null)
	{
		if (isset($arg)) {
			$this->sanitizedField = $arg;
		}
		return $this->sanitizedField;
	}

    public function getLogPath()
    {
        if (isset($this->log)) {
            return $this->logPath.$this->scriptDate.self::SEPARATOR_NAME.$this->id.self::SEPARATOR_NAME.$this->profile.".log";
        }
    }
    
    public function logError($error=null)
    {
        if (isset($this->log) && isset($error)) {
            $this->log->error($error);
        } else {
            return $this->log;
        }
    }
    
    public function logInfo($info=null)
    {
        if (isset($this->log)  && isset($info)) {
            $this->log->info($info);
        } else {
            return $this->log;
        }
    }
    
    public function report($report=null)
    {
        if (isset($report)) {
            $this->report = new ReportImporter($report);
        } else {
            return $this->report;
        }
    }
    
    public function debug($active=null)
    {
            
        if (isset($active)) {
            $this->debug=$active;
        } else {
            return $this->debug;
        }
    }
    
    public function maxFileSize($arg = null)
    {
        if (isset($arg)) {
            if (is_numeric($arg)) {
                $this->maxFileSize=$arg;
            } else {
                throw new Exception("Errore nell'impostazione della massima dimensione del file");
            }
        } else {
            return $this->maxFileSize;
        }
    }
    
    public function maxFileNumber($arg = null)
    {
        if (isset($arg)) {
            if (is_numeric($arg)) {
                $this->maxFileNumber=$arg;
            } else {
                throw new Exception("Errore nell'impostazione del massimo numero dei file");
            }
        } else {
            return $this->maxFileNumber;
        }
    }
    
    public function profile($arg = null)
    {
        if (isset($arg)) {
            $arg=preg_replace("/[^A-Za-z0-9]/", "", $arg);
            $this->profile=$arg;
        } else {
            return $this->profile;
        }
    }
    
    public function type($arg = null)
    {
        if (isset($arg)) {
            $this->type=$arg;
            if ($arg==="barcode") {
                try {
                    $this->scanner= new OrgaScanner();
                } catch(Exception $e) {
                    throw new Exception("Impossibile creare l'oggetto Scanner: ".$e->getMessage());
                }
            } else {
                unset($this->scanner);
            }
        } else {
            return $this->type;
        }
    }
    
    public function folder($arg = null)
    {
        if (isset($arg)) {
            $this->folder=$arg;
        } else {
            return $this->folder;
        }
    }
    
    public function user($arg = null)
    {
        if (isset($arg)) {
            $this->user=$this->getUserId($arg);
        } else {
            return $this->user;
        }
    }

    public function prefix($arg = null)
    {
        if (isset($arg)) {
            $this->prefix=$arg;
        } else {
            return $this->prefix;
        }
	}
	
	public function postfix($arg = null)
    {
        if (isset($arg)) {
            $this->postfix=$arg;
        } else {
            return $this->postfix;
        }
	}


	public function allowExtensions($arg = null)
    {
        if (isset($arg)) {
            $this->allowExtensions=$arg;
        } else {
            return $this->allowExtensions;
        }
	}

	public function createIfNotExist($arg = null)
    {
        if (isset($arg)) {
            $this->createIfNotExist=$arg;
        } else {
            return $this->createIfNotExist;
        }
    }

    public function relatedField($arg = null)
    {
        if (isset($arg)) {
            $this->relatedField=$arg;
        } else {
            return $this->relatedField;
        }
    }
    
    public function relatedModule($arg = null)
    {
        if (isset($arg)) {
            $this->relatedModule=$arg;
        } else {
            return $this->relatedModule;
        }
    }
    
    public function importProfile()
    {
        $this->logInfo = "---------------------------";
        $this->logInfo = "Inizio importazione profilo";

        App\User::setCurrentUserId(Users::getActiveAdminId());
        $currentuser = Users::getActiveAdminUser();
        vglobal('current_user', $currentuser);

        $this->checkImportConfig();
        
        $inputdirsize    = $this->folderSize($this->inputPath);
        $progressdirsize = $this->folderSize($this->progressPath);
        $completedirsize = $this->folderSize($this->completePath);
        $errordirsize    = $this->folderSize($this->errorPath);
        
        if (($inputdirsize + $progressdirsize) > self::PROGRESS_MAXSIZE) {
            throw new Exception("Raggiunto il limite massimo di ".self::PROGRESS_MAXSIZE. " nella cartella ".$this->progressPath);
        }

        if ($progressdirsize > 0) {
            if (($progressdirsize + $completedirsize) > self::COMPLETE_MAXSIZE) {
                throw new Exception("Raggiunto il limite massimo di ".self::COMPLETE_MAXSIZE. " nella cartella ".$this->completePath);
            }
            if (($progressdirsize + $errordirsize) > self::ERROR_MAXSIZE) {
                throw new Exception("Raggiunto il limite massimo di ".self::ERROR_MAXSIZE. " nella cartella ".$this->errorPath);
            }
            $this->stepProgress();
        } else {
            $this->stepInput();
        }
        
        if (($progressdirsize + $completedirsize) > self::COMPLETE_MAXSIZE) {
            throw new Exception("Raggiunto il limite massimo di ".self::COMPLETE_MAXSIZE. " nella cartella ".$this->completePath);           }

        if (($progressdirsize + $errordirsize) > self::ERROR_MAXSIZE) {
            throw new Exception("Raggiunto il limite massimo di ".self::ERROR_MAXSIZE. " nella cartella ".$this->errorPath);
        }

        $this->stepProgress();
        
        $this->logInfo = "Fine importazione profilo";
        $this->logInfo = "---------------------------";
        
        if (isset($this->report)) {
            $this->report->Profile = $this->profile;
            $this->report->sendReport();
        }
    }
    
    private function stepInput()
    {
        $files = new FilesystemIterator($this->inputPath, FilesystemIterator::SKIP_DOTS);
            
        if (iterator_count($files) === 0) {
            $this->logInfo="Nessuna file pdf nella cartella:". $this->inputPath;
            return;
        }
    
        $fileCount = 0;
        foreach ($files as $file) {
            $fileCount++;
            if ($fileCount>$this->maxFileNumber) {
                $this->logInfo="Raggiunto il numero massimo di file lavorabili: ".$this->maxFileNumber;
                break;
            }

			if (!in_array(strtolower($file->getExtension()),$this->allowExtensions)) {
                continue;
            }
            $inputfilename = $file->getFilename();
            $inputpathname = $file->getPathname();
                
            if (filesize($file)>$this->maxFileSize) {
                $this->logInfo = "Il file ".$inputpathname." è di dimensioni troppo elevate. Max size: ". $this->formatSizeUnits($this->maxFileSize);
                continue;
            }
			try {
            	switch($this->type) {
					case "barcode":
						$barcodes = $this->Scan($file);
						$this->moveFileToProgress($file, $barcodes);
						break;
					case "filename":
						$this->moveFileToProgress($file);
						break;
					default:
						$this->moveFileToProgress($file);
						break;
				}
			} catch(Exception $e) {
				$this->logError = $e->getMessage();
				try {
					$this->moveFileToError($file);
				} catch(Exception $e) {
					$this->logError=$e->getMessage();
				}
				continue;
			}
        }
    }
    
    private function stepProgress()
    {
        $files = new FilesystemIterator($this->progressPath, FilesystemIterator::SKIP_DOTS);
            
        if (iterator_count($files) === 0) {
            $this->logInfo = "Nessun file pdf nella cartella:". $this->progressPath;
            return;
        }

        foreach ($files as $file) {
            if (!in_array(strtolower($file->getExtension()), $this->allowExtensions)) {
                continue;
            }
                
            $progressfilename = $file->getFilename();
            $progresspathname = $file->getPathname();
            
            if ((strpos($progressfilename, SELF::SEPARATOR_NAME) === false)) {
                $this->logError="Nome del file non compatibile: ".$progresspathname;
                try {
                    $this->moveFileToError($file);
                } catch(Exception $e) {
                    $this->logError=$e->getMessage();
                }
                continue;
            }

            $explodeFilename = explode(SELF::SEPARATOR_NAME, $progressfilename);
            $countSeparator  = count($explodeFilename);

            if ($countSeparator>2) {
                $barcode     = explode(SELF::SEPARATOR_NAME, $progressfilename)[1];
                $orifilename = explode(SELF::SEPARATOR_NAME, $progressfilename)[2];
            } else {
                $orifilename = explode(SELF::SEPARATOR_NAME, $progressfilename)[1];
            }

			if(empty($orifilename) || empty($progresspathname)) {
				$this->logError="Nome del file non compatibile: ".$progresspathname;

				try {
                    $this->moveFileToError($file);
                } catch(Exception $e) {
                    $this->logError=$e->getMessage();
                }
			}
			switch($this->type) {
				case "barcode":
					$searchValue = $barcode;
					break;
				case "filename":
					$searchValue = pathinfo($orifilename, PATHINFO_FILENAME);
					break;
				default:
					$searchValue = pathinfo($orifilename, PATHINFO_FILENAME);
					break;
			}

			if(!empty($this->sanitizedField)) {
				if (!empty($searchValue)) {
					$regex = '/'.$this->sanitizedField.'/';
					$match = preg_replace($regex, "", $searchValue);
					if(!empty($match)) {
						$searchValue = $match;
					}
				}
			}

			if (!empty($this->prefix)) {
				$searchValue = $this->prefix . $searchValue;
			}
			if (!empty($this->postfix)) {
				$searchValue = $searchValue . $this->postfix;
			}

			try {
				if (!empty($this->searchField)) {
					$recordId = $this->entityGetId("Documents", [$this->searchField => $searchValue]);
				}
				
				if (!empty($recordId)) {
					$recordId  = $this->createDocument($orifilename, $progresspathname, $searchValue, $recordId);
				} else {
					$recordId  = $this->createDocument($orifilename, $progresspathname, $searchValue);
				}

				if (!empty($this->relatedField) && !empty($this->relatedModule)) {
					$relatedId = $this->searchRelation($searchValue);
					$this->relateDocument($recordId, $relatedId);
				}

			    $this->moveFileToComplete($file);
            } catch(Exception $e) {
                $this->logError=$e->getMessage();
                try {
                    $this->moveFileToError($file);
                } catch(Exception $e) {
                    $this->logError=$e->getMessage();
                }
            }
            $this->logInfo = "Lavorazione file completata: {$progresspathname}";
        }
    }

    private function createDocument($filename, $pathname, $searchVal = null,$recordId = null)
    {
		if (!empty($searchVal)) {
			$filename = $searchVal;
		}

		$originalFile = [
            'name'     => $filename,
            'type'     => mime_content_type($pathname),
            'tmp_name' => $pathname,
            'error'    => 0,
            'size'     => filesize($pathname),
        ];
        if (!empty($recordId)) {
            $recordModel = Vtiger_Record_Model::getInstanceById($recordId, 'Documents');
			$isNew = $recordModel->isNew();
			if ($isNew) {
				$recordModel->isNew = false;
			}

        } else {
            $recordModel = Vtiger_Record_Model::getCleanInstance('Documents');
        }

		$recordModel->set('notes_title', $filename);
        $recordModel->set('assigned_user_id', 1);
        $recordModel->file = $originalFile;
        $recordModel->fileImported = true;
        $recordModel->set('filelocationtype', 'I');
        $recordModel->set('filestatus', true);

        $recordModel->set('folderid', $this->folder);
		if ($this->disableHandlers) {
			$recordModel->setHandlerExceptions(['disableHandlers' => true]);
		}
		if ($this->disableWorkflows) {
			$recordModel->setHandlerExceptions(['disableWorkflow' => true]);
		}

		$recordModel->save();

		$recordId = $recordModel->getId();
		if ($recordId) {
			\App\Record::updateLabel('Documents', $recordId);
		} else {
			throw new Exception("Non è stato possibile creare il documento {$filename}");
		}

        return $recordId;
    }

    private function searchRelation($barcode)
    {
        $retriveId = false;
        $param[$this->relatedField] = $barcode;
        try {
            $retriveId = $this->entityGetId($this->relatedModule,$param);

            if (empty($retriveId) && $this->createIfNotExist) {
                $retriveId = $this->entitySave($this->relatedModule,$param);
            }

        } catch(Exception $e) {
            throw new Exception("Non è stato possibile trovare il campo {$this->relatedField} del modulo {$this->relatedModule} con valore {$barcode}");
		}

		if (empty($retriveId)) {
            throw new Exception("Non è stato possibile trovare il campo {$this->relatedField} del modulo {$this->relatedModule} con valore {$barcode}");
        }

        return $retriveId;
    }

    private function relateDocument($idDoc, $idRelated)
    {
        try {
            $for_module  = $this->relatedModule;
            $for_crmid   = $idRelated;
            $with_module = "Documents";
            $with_crmid  = $idDoc;
            $on_focus    = CRMEntity::getInstance($for_module);
            if ($for_module && $for_crmid && $with_module && $with_crmid) {
                relateEntities($on_focus, $for_module, $for_crmid, $with_module, $with_crmid);
            }
        } catch(Exception $e) {
            throw new Exception("Non è stato possibile relazionare il documento id ".$idDoc." al record con id ".$idRelated." del modulo ".$this->relatedModule);
        }

        return true;
    }

    private function entitySave($moduleName, $entity, $recordId = null)
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
            if ($fieldName == 'assigned_user_id') {
                $recordModel->set($fieldName, $fieldModel->getUITypeModel()->getDBValue($this->user, $recordModel));
            }
            elseif (isset($entity[$fieldName])) {
                $recordModel->set($fieldName, $fieldModel->getUITypeModel()->getDBValue($entity[$fieldName], $recordModel));
            } elseif ($recordModel->isNew()) {
                $defaultValue = $fieldModel->getDefaultFieldValue();
                if ($defaultValue !== '') {
                    $recordModel->set($fieldName, $defaultValue);
                }
            }
        }
		if ($this->disableHandlers) {
			$recordModel->setHandlerExceptions(['disableHandlers' => true]);
		}
		if ($this->disableWorkflows) {
			$recordModel->setHandlerExceptions(['disableWorkflow' => true]);
		}

        $recordModel->save();
        $recordId = $recordModel->getId();
		if ($recordId) {
			\App\Record::updateLabel($moduleName, $recordId);
		}

		return $recordId;
    }

    private function entityGetId($moduleName, $search)
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
			if ($moduleName === 'Documents') {
				$where .=  " AND vtiger_notes.filename = ''";
			}
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

    private function getUserId($username)
    {
        $adb = PearDatabase::getInstance();
        $query = 'select * from vtiger_users where deleted=0 AND user_name = ?';
        $result = $adb->pquery($query, [$username]);
        $userId = $adb->query_result($result, 0, 'id');
        $userId = $userId ? $userId : 1 ;
        return $userId;
    }

    private function Scan($image)
    {
        $arr = $this->scanner->scan($image);
        $this->scanner->clear();
		if (!isset($arr)) {
			throw new Exception("Errore riconoscimento codice a barre");
		}

		if (count($arr)<1) {
			throw new Exception("Errore riconoscimento codice a barre");
		}

        return $arr;
    }
    
    private function moveFileToProgress($file,$barcodes=null)
    {
        $filename=$file->getFilename();
        $source=$file->getPathname();
        
        if (isset($barcodes)) {
            $listSplitPage=$this->splitBarcodeFile($barcodes);
            
            if (count($listSplitPage) == 0) {
                throw new Exception("Nessun codice a barre trovato: ".$inputpathname);
            }
            
            $counterSplit=0;
            foreach ($listSplitPage as $SplitPage) {
                $splitfilename=$this->scriptDate.$counterSplit.SELF::SEPARATOR_NAME.$SplitPage["barcode"].SELF::SEPARATOR_NAME.$filename;
                
                if (file_exists($this->progressPath.$splitfilename)) {
                    return true;
				}

                try {
                    $pdf = new Pdf($source);
                    $result = $pdf->cat($SplitPage["start"], $SplitPage["end"])->saveAs($this->progressPath.$splitfilename);
                    if (!$result) {
                        unset($pdf);
                        throw new Exception("Errore divisione ".$inputfilename." dalla pagina ".$SplitPage["start"]. " alla pagina ".$SplitPage["end"]);
                    }
                } catch(Exception $e) {
                    unset($pdf);
                    throw new Exception("Errore divisione ".$inputfilename." dalla pagina ".$SplitPage["start"]. " alla pagina ".$SplitPage["end"]);
                }
                $counterSplit++;
            }
            unset($pdf);
        }
        else {
            $dest = $this->progressPath.$this->scriptDate.SELF::SEPARATOR_NAME.$filename;
            
            if (file_exists($dest)) {
                return true;
			}

            chmod($source,0775);
            
            $result = copy($source,$dest);
            
            if (!$result) {
                throw new Exception("Impossibile spostare il file da ".$source." a ".$dest);
            }
        }
        
        $result = unlink($source);
        
        if (!$result) {
            throw new Exception("Impossibile eliminare il file: ".$source);
        }
    }
    
    
    private function moveFileToError($file)
    {
        $filename = $file->getFilename();
        if ((strpos($filename, SELF::SEPARATOR_NAME) === false)) {
            $filename = $this->scriptDate.SELF::SEPARATOR_NAME.$filename;
        }
        
        $source = $file->getPathname();
        $dest   = $this->errorPath.$filename;
        
        if (file_exists($dest)) {
            return true;
		}
        chmod($source,0775);
        $result = copy($source,$dest);
        if (!$result) {
            throw new Exception("Impossibile spostare il file da ".$source." a ".$dest);
        }

        $result=unlink($file);
            
        if (!$result) {
            throw new Exception("Impossibile eliminare il file: ".$source);
        }
        
        $this->report->Row(["value"=>[$filename], "result"=>false]);
        
        return true;
    }
    
    private function moveFileToComplete($file)
    {
        $filename=$file->getFilename();
        if ((strpos($filename, SELF::SEPARATOR_NAME) !== false)) {
            $barcode     = explode(SELF::SEPARATOR_NAME, $filename)[1];
            $orifilename = explode(SELF::SEPARATOR_NAME, $filename)[2];
        }
        if (!isset($barcode)) {
            $barcode="";
		}
        $source = $file->getPathname();
        $dest   = $this->completePath.$filename;

            
        if (file_exists($dest)) {
            return true;
		}
        
        chmod($source,0775);
            
        $result = copy($source,$dest);
            
        if (!$result) {
            throw new Exception("Impossibile spostare il file da ".$source." a ".$dest);
        }
        
        $result = unlink($file);
            
        if (!$result) {
            throw new Exception("Impossibile eliminare il file: ".$source);
        }
        
        $this->report->Row(["value" => [$orifilename,$filename,$barcode],"result" => true]);
        
        return true;
    }
    
    
    private function splitBarcodeFile($barcodes)
    {
        $startSplitPage   = 1;
        $endSplitPage     = 1;
        $barcodeSplitPage = "";
        $listSplitPage    = [];
        foreach ($barcodes as $barcode) {
            if (!empty($barcode)) {
                if (!empty($barcodeSplitPage)) {
                    $listSplitPage[] = ["start" => $startSplitPage,"end" => $endSplitPage-1,"barcode" => $barcodeSplitPage];
                    $startSplitPage = $endSplitPage;
                } else {
                    $startSplitPage = $endSplitPage;
                }
                $barcodeSplitPage = $barcode[0]["data"];
            }
            if ((count($barcodes)) == $endSplitPage && !empty($barcodeSplitPage)) {
                $listSplitPage[] = ["start" => $startSplitPage,"end" => $endSplitPage,"barcode" => $barcodeSplitPage];
                break;
            }
            $endSplitPage++;
        }
        return $listSplitPage;
    }
    
    private function checkImportConfig()
    {
        clearstatcache();
        
        if (!file_exists($this->inputPath)) {
            throw new Exception("La cartella di input non esiste: ".$this->inputPath);
		}
        
        if (!isset($this->profile)) {
            throw new Exception("Nessun nome del profilo impostato");
		}
        
        if (!isset($this->id)) {
            throw new Exception("Nessun ID impostato");
		}
    }
    
    private function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' kB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }
    
        return $bytes;
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
