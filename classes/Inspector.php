<?php

require_once 'Tools.php';


/**
 * Class Inspector
 */
class Inspector {

    private $config;


    /**
     * Inspector constructor.
     * @param string $config_file
     * @throws Exception
     */
    public function __construct($config_file) {

        if ( ! file_exists($config_file)) {
            throw new Exception("Not found configuration file '{$config_file}'");
        }

        $this->config = Tools::getConfig($config_file);

        if (empty($this->config['warning_strings'])) {
            throw new Exception("Empty parameter 'warning_strings' in configuration file '{$config_file}'");
        }

        $this->config['warning_strings'] = explode(',', $this->config['warning_strings']);
        foreach ($this->config['warning_strings'] as $k => $warning_string) {
            if (trim($warning_string) == '') {
                unset($this->config['warning_strings'][$k]);
            }
        }

        if (empty($this->config['warning_strings'])) {
            throw new Exception("Incorrect parameter 'warning_strings' in configuration file '{$config_file}'");
        }
    }


    /**
     * @param string $file
     * @return string
     */
    public function getFileWarning($file) {

        $file_content = file_get_contents($file);
        $warning      = '';

        if ( ! empty($file_content)) {
            foreach ($this->config['warning_strings'] as $warning_string) {
                $pos_warning = mb_strpos($file_content, $warning_string, 0, 'utf-8');
                if ($pos_warning !== false) {

                    $str1 = mb_substr($file_content, $pos_warning - 25, 25, 'utf-8');
                    $str1 = end(explode("\n", $str1));

                    $str2 = mb_substr($file_content, $pos_warning, 25, 'utf-8');
                    $str2 = current(explode("\n", $str2));

                    $warning = trim($str1 . $str2);
                    break;
                }
            }
        }

        return $warning;
    }


    /**
     * @param string $dir
     * @param string $filename
     * @param string $mtime
     * @return array
     */
    public function fetchFiles($dir, $filename, $mtime) {

        $files    = array();
        $cmd      = sprintf("find %s -iname '%s' -mtime -%d -type f", $dir, $filename, $mtime);
        exec($cmd, $files);

        return $files;
    }


    /**
     * @param array $files
     * @return array
     */
    public function filterWarningFiles(array $files) {

        $file_warnings = array();

        foreach ($files as $file) {
            if ( ! empty($this->config['exclude_files']) && is_array($this->config['exclude_files'])) {
                if (array_search($file, $this->config['exclude_files'])) {
                    continue;
                }
            }
            if ( ! empty($this->config['exclude_dirs']) && is_array($this->config['exclude_dirs'])) {
                foreach ($this->config['exclude_dirs'] as $exclude_dir) {
                    if ( ! empty($exclude_dir) && is_string($exclude_dir) && strpos($file, $exclude_dir) === 0) {
                        continue 2;
                    }
                }
            }


            $warning = $this->getFileWarning($file);
            if ( ! empty($warning)) {
                $file_warnings[$file] = $warning;
            }
        }

        return $file_warnings;
    }


    /**
     * @return array
     */
    public function getConfig() {

        return $this->config;
    }
}