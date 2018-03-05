<?php
class ISC_UPGRADE_MODULES {

    public $_checkout_modules = '../modules/checkout/';
    public $_shipping_modules = '../modules/shipping/';

    public $_remote_server           = 'https://www.server.com';
    public $_remote_checkout_modules = '/modules/checkout/';
    public $_remote_shipping_modules = '/modules/shipping/';

    /**
     * Scan all Checkout Modules
     */
    public function scan_modules($dir, $dir_remote)
    {
        $folders = scandir($dir);
        foreach ($folders as $folder) {
            if($this->check_exist_folder($dir, $folder))
            {
                if($this->check_exist_settings($dir, $folder))
                {
                    $json_local  = $this->read_json_file($dir, $folder);
                    $json_remote = $this->read_json_file($this->_remote_server, $dir_remote.$folder);
                    if($json_local['version'] === $json_remote['version'])
                        $this->save_update_log("Data:".date('d/m/Y')." Módulo -> {$dir}{$folder} - Está na versão mais recente -> {$json_remote['version']}");
                    else 
                    {
                        if($this->get_http_response_file($this->_remote_server, $dir_remote.$folder) === '200')
                        {
                            if($this->download_new_module($this->_remote_server, $dir_remote, $folder, 'module.zip', $dir) == TRUE)
                            {
                                if($this->unzip_module($dir, $folder) == TRUE)
                                {
                                    $this->save_update_log("Data:".date('d/m/Y')." Módulo -> {$dir}{$folder} - Atualizado para a versão -> {$json_remote['version']} com Sucesso!");
                                    $this->delete_zip_file($dir, $folder);
                                }
                            }
                        }
                        else 
                            $this->save_update_log("Data:".date('d/m/Y')." Não foi encontrado o arquivo -> module.zip no servidor remoto.");
                    }
                }
            }
        }
    }
    
    private function check_exist_folder($dir, $folder)
    {
        return is_dir($dir . $folder);
    }

    private function check_exist_settings($dir, $folder)
    {
        return file_exists($dir.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.'settings.json');
    }

    private function read_json_file($dir, $folder, $json_file='settings.json')
    {
        $string = file_get_contents($dir.'/'.$folder.'/'.$json_file);
        $json_a = json_decode($string, true);
        return $json_a;
    }

    private function get_http_response_file($remote_local, $folder_module, $file = 'module.zip')
    {
        $headers = get_headers($remote_local.'/'.$folder_module.'/'.$file);
        return substr($headers[0], 9, 3);
    }

    private function download_new_module($remote_local, $remote_folder, $folder_module, $file = 'module.zip', $save_local)
    {
        $remote_url  = $remote_local.'/'.$remote_folder.'/'.$folder_module.'/'.$file;
        $destination = $save_local.'/'.$folder_module.'/'.$file;
        
        if ($fp_remote = fopen($remote_url, 'rb')) {
            if ($fp_local = fopen($destination, 'wb')) {
                while ($buffer = fread($fp_remote, 8192)) {
                    fwrite($fp_local, $buffer);
                }
                fclose($fp_local);
            }
            else
            {
                fclose($fp_remote);
                return false;    
            }
            
            fclose($fp_remote);
            return true;
        }
        else
            return false;
    }

    private function unzip_module($dir, $folder, $file = 'module.zip')
    {
        $zip = new ZipArchive;
        if ($zip->open($dir.'/'.$folder.'/'.$file) === TRUE) {
            $zip->extractTo($dir.'/'.$folder.'/');
            $zip->close();
            return TRUE;
        }
    }

    private function delete_zip_file($dir, $folder, $file = 'module.zip')
    {
        return unlink($dir.'/'.$folder.'/'.$file);
    }

    public function create_table_logs()
	{
		$query = $GLOBALS['ISC_CLASS_DB']->Query("SHOW TABLES LIKE 'kreativos_cron_update_modules'");
		$count = $GLOBALS['ISC_CLASS_DB']->CountResult($query);
		if($count == 0)
		{
			$create_table = "CREATE TABLE kreativos_cron_update_modules 
            (
				id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
				log TEXT NOT NULL
            ) 
            DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci
            ";
			$query = $GLOBALS['ISC_CLASS_DB']->Query($create_table);
			if($query)
			{
				return true;
			}
		}
	}

    public function save_update_log($log)
    {
        $this->create_table_logs();
        $query = $GLOBALS['ISC_CLASS_DB']->Query("INSERT INTO kreativos_cron_update_modules (log) VALUES ('{$log}')");
    }
}

$modules = new ISC_UPGRADE_MODULES();
$modules->scan_modules($modules->_checkout_modules, $modules->_remote_checkout_modules);
$modules->scan_modules($modules->_shipping_modules, $modules->_remote_shipping_modules);