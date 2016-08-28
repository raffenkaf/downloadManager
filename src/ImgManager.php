<?php
namespace Samson\DownloadUtil;

class ImgManager 
{
	public $ftp;
	public $username;
	public $password; 
	
	public function __construct($ftp=null, $username=null, $password=null) {
		$this->ftp = $ftp;
		$this->username = $username;
		$this->password = $password;
	}
	
	public function setFtp($ftp) {
		$this->ftp = $ftp;
	}
	
	public function setUsernameAndPassword($username, $password) {
		$this->username = $username;
		$this->password = $password;
	}
		
	public function downloadImg($pathToCopy, $externalFileUrl, $fullUrl=false, $maxFileSize = null) 
        {		
		$fullExternalUrl = null;
		if ($fullUrl) {
			$fullExternalUrl = $externalFileUrl;
		} elseif (is_null($this->username) || is_null($this->password)) {
			$fullExternalUrl='ftp://'.$ftp.$externalFileUrl;
		} else {
			$fullExternalUrl='ftp://'.$this->username.':'.$this->password.'@ftp.'.$ftp.$externalFileUrl;
		}
		
		$extension = strtolower(pathinfo($fullUrl, PATHINFO_EXTENSION));
		if ($extension == "") { 
			$extension = "jpg";			
		} elseif (
			$extension != "jpg" && 
			$extension != "jpeg" && 
			$extension != "png" && 
			$extension != "gif"
		) {
			
			throw new \Exception('Неверное расширение файла источника("'.$extension.'").
					                      Допустимо только jpg, png, gif.');
			
		}
		
	    if (!@getimagesize($fullExternalUrl)) {
	        throw new \Exception('Путь источника указан неверно("'.$fullExternalUrl.'").');
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_URL, $fullExternalUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1); 
		$data = curl_exec($ch);
		curl_close($ch);
		if(imagecreatefromstring($data)){
			$image = imagecreatefromstring($data);
		} else {
			throw new \Exception('Источник не изображение "'.$pathToCopy.'".');
		}
		
		switch ($extension) {
			case 'jpeg':
			case 'jpg':
				$pathToCopy.='.jpg';
				if (!imagejpeg($image, $pathToCopy, 100)) {
				    throw new \Exception('Нет возможности создать jpg изображение для "'.$pathToCopy.'".');
				}
				break;
			case 'png':
				$pathToCopy.='.png';
				if (!imagepng($image, $pathToCopy, 100)) {
					throw new \Exception('Нет возможности создать png изображение для "'.$pathToCopy.'".');
				}
				break;
			case 'gif':
				$pathToCopy.='.gif';
				if (!imagegif($image, $pathToCopy, 100)) {
					throw new \Exception('Нет возможности создать gif изображение для "'.$pathToCopy.'".');
				}
			break;
		}
		
		if (!is_null($maxFileSize) && (filesize($pathToCopy) > $maxFileSize)) {
			if (!unlink($pathToCopy)) {
				throw new \Exception(
					'Файл слишком велик но возникла ошибка при попытке его удаления, 
					path = "'.$pathToCopy.'".'
				);
			}
		}
	}
}