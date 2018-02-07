<?php
namespace Wmatik;

class Tools
{

    public function __construct($app)
    {
        $this->app = $app;
    }


  /**
   * Get pages dir.
   *
   * @return array
   **/
    public function getDir()
    {

        $dir = $this->app['dir'];
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));

        $files = array();
        foreach ($rii as $file) {
            if ($file->isDir()) {
                $src = strpos($file, '_src');
                if ($src == true) {
                    $rp = realpath($file);
                    $rp = explode('\\', $rp);
                    $lrp = end($rp);

                    if ($lrp == '_src') {
                        $files[] = realpath($file);
                    }

                }
            }
        }

        $pages = array_unique($files);

        foreach ($pages as $k => $v) {
            $nPage = str_replace(realpath($this->app['dir']), '', $v);
            $nPage = str_replace('_src', '', $nPage);
            $nPage = str_replace('\\', '/', $nPage);
            $nPage = trim($nPage, '/');
            $sonuc[] = $nPage;
        }
        return $sonuc;

    }
   
}
