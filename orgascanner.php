<?php
class OrgaScanner
{
	private $scanner;
	private $image;
	
	public function __construct($code_type=zbarcode::SYM_CODE128,$max_len=7,$min_len=1) {
		$this->image = new ZBarcodeImage();
		$this->scanner = new ZBarCodeScanner();
		$this->scanner->setConfig(zbarcode::CFG_ENABLE,0,zbarcode::SYM_ALL);
		$this->scanner->setConfig(zbarcode::CFG_ENABLE,1,$code_type);
		$this->scanner->setConfig(zbarcode::CFG_MAX_LEN,$max_len);
		$this->scanner->setConfig(zbarcode::CFG_MIN_LEN,$min_len);
	}
	
	public function codeType($code)
	{
		$this->scanner->setConfig(zbarcode::CFG_ENABLE,0,zbarcode::SYM_ALL);
		$this->scanner->setConfig(zbarcode::CFG_ENABLE,1,$code);
	}
	
	public function maxLen($max)
	{
		$this->scanner->setConfig(zbarcode::CFG_MAX_LEN,$max);
	}
	
	public function minLen($max)
	{
		$this->scanner->setConfig(zbarcode::CFG_MAX_LEN,$min);
	}
	public function clear()
	{
		if(isset($this->image))
		{
			$this->image->clear();
		}
	}
	public function scan($filename)
	{
		if (file_exists($filename)) {
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			if(strtolower($ext)=="pdf")
			{
				if(isset($this->image))
				{
					//$this->image->read($filename, ZBarCode::OPT_RESOLUTION | ZBarCode::OPT_ENHANCE | ZBarCode::OPT_SHARPEN);
					$this->image->read($filename,ZBarCode::OPT_ENHANCE | ZBarCode::OPT_SHARPEN);
				}
				else 
				{
					throw new Exception("L'istanza Image non è stata creata");
				}
			}
			else 
			{
				throw new Exception("Il file: ". $filename ." non è un formato valido");
			}
		}
		else
		{
			throw new Exception("Il file: ". $filename ." non esiste");
		}
		
		if(isset($this->scanner))
		{
			if($this->image->count()>0)
			{	
				return $this->scanner->scan($this->image, 0);
			}
			else {
				throw new Exception("il file ".$filename." ha zero pagine");
			}
		}
		else
		{
			throw new Exception("L'istanza Scanner non è stata creata");
		}
	}
}