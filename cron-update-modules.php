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
    public function scan_modules($dir)
    {
        $folders = scandir($dir);
        foreach ($folders as $folder) {
            if($this->check_exist_folder($dir, $folder))
            {
                if($this->check_exist_settings($dir, $folder))
                {
                    $json_local  = $this->read_json_file($dir, $folder);
                    $json_remote = $this->read_json_file($this->_remote_server, $this->_remote_checkout_modules.$folder);
                    if($json_local['version'] === $json_remote['version'])
                        return false;
                    else 
                    {
                        if($this->get_http_response_file($this->_remote_server, $this->_remote_checkout_modules.$folder) === '200')
                        {
                            if($this->download_new_module($this->_remote_server, $this->_remote_checkout_modules, $folder, 'module.zip', $dir) == TRUE)
                            {
                                if($this->unzip_module($dir, $folder) == TRUE)
                                {
                                    $this->save_update_log('Ok');
                                    $this->delete_zip_file($dir, $folder);
                                }
                            }
                        }
                        else 
                            $this->save_update_log('File not found.');
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

    public function save_update_log($log)
    {

    }
}

$modules = new ISC_UPGRADE_MODULES();
$modules->scan_modules($modules->_checkout_modules);