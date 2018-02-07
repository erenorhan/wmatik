<?php
namespace Wmatik;

use Symfony\Component\Yaml\Yaml;

class Get
{

    public function __construct($app)
    {

        $this->app = $app;

    }
    /**
     * Render module..
     *
     * @param string $module
     * @param string $dataFile
     * @return void twig template
     */
    public function module($module = '', $dataFile = '')
    {

        $first_char = mb_substr($dataFile, 0, 1);
        $ymlData = [];

        if ($first_char === '/') {
            
            if (file_exists($this->app['dir'] . $dataFile . '.yml')) {
                
                $ymlData = Yaml::parseFile($this->app['dir'] . $dataFile . '.yml');
            }

        } else {
            if (file_exists($this->app['dir'] . $this->app['workDir'] . '/_src/' . $dataFile . '.yml')) {
                $ymlData = Yaml::parseFile($this->app['dir'] . $this->app['workDir'] . '/_src/' . $dataFile . '.yml');
            }
        }
        

        $ymlData['global']=[];

        //gobal data dosyasını oku. Twig : global.var
        $files = glob($this->app['dir'].'_site/data/*.{yml,yaml}', GLOB_BRACE);
        foreach ($files as $file) {
                $f = basename($file);
                $f = pathinfo($f);
                $f = $f['filename'];

                $ymlData['global'][$f] = Yaml::parseFile($file);
                if (!is_array($ymlData['global'][$f])) {
                    echo "\n[/_site/data/data.yml] not valid data file. \n";
                }
              
        }

        
        //Load module..
        //local dir. module
        if (file_exists($this->app['dir'] . $this->app['workDir'] . '/_src/modules/' . $module . '/index.twig')) {

            $render = $this->app['twig']->render($this->app['workDir'] . '/_src/modules/' . $module . '/index.twig', $ymlData);

        } else if (file_exists($this->app['dir'] . '_site/modules/' . $module . '/index.twig')) {


            /*
            //header files..
            $hFiles = array_diff(scandir($this->app['dir'] . '_site/modules/' . $module . '/headerFiles'), array('.', '..'));
            foreach ($hFiles as $k => $v) {
               copy($this->app['dir'] . '_site/modules/' . $module . '/headerFiles/' . $v, $this->app['dir'] . '/static/build/'. $module .'/'. $v);
            }

            //footer files
            $fFiles = array_diff(scandir($this->app['dir'] . '_site/modules/' . $module . '/footerFiles'), array('.', '..'));
            foreach ($fFiles as $k => $v) {
             copy($this->app['dir'] . '_site/modules/' . $module . '/footerFiles/' . $v, $this->app['dir'] . '/static/build/' . $v);
            }
            $_SESSION['headerFiles'][] = $hFiles;
            $_SESSION['footerFiles'][] = $fFiles;
            */
            $_SESSION['modules'][]=$module;

          
            $render = $this->app['twig']->render('_site/modules/' . $module . '/index.twig', $ymlData);

        } else {
            $render = "Hata";
        }

        return $render;
    }

    /**
     * Render image
     *
     * @param [type] $img
     * @return string image path
     */
    public function image($img)
    {

        //Global ayarlardan imgSize değerini oku
        if (file_exists($this->app['dir'] . '_site/data.yml')) {
            $data = Yaml::parseFile($this->app['dir'] . '_site/data.yml');
        }

        $imgSize = isset($data['imgSize']) ? $data['imgSize'] : 2000;

        //src file..
        $srcFile = $this->app['dir'] . $this->app['workDir'] . '/_src/images/' . $img;
        if (!file_exists($srcFile)) {
            return;
        }

        $targetFile = $this->app['dir'] . 'static/images/build/' . $this->app['workDir'] . '/' . $imgSize . '_' . $img;

        $targetPath = 'static/images/build/' . $this->app['workDir'] . '/' . $imgSize . '_' . $img;

        //Hedefte dizin yoksa oluştur
        if (!file_exists($this->app['dir'] . 'static/images/build/' . $this->app['workDir'])) {
            mkdir($this->app['dir'] . 'static/images/build/' . $this->app['workDir'], 0777, true);
        }

        //Dosya varsa geç
        if (file_exists($targetFile)) {
            return $targetPath;
        }

        $image = new \Gumlet\ImageResize($srcFile);

        $image->resizeToShortSide($imgSize);

        $image->save($targetFile);

        return $targetPath;

    }
    /**
     * Render thumbnail
     *
     * @param [type] $img string
     * @param [type] $resize string
     * @return string img thumb path
     */
    public function thumb($img, $resize)
    {

        $resize = empty($resize) ? '320x240' : $resize;

        $resize = strtolower($resize);

        $resize = explode('x', $resize);

        (int) $width = isset($resize[0]) ? $resize[0] : 0;
        (int) $height = isset($resize[1]) ? $resize[1] : 0;

        $pre = $width . 'x' . $height . '_';

        $thumbDir = $this->app['dir'] . 'static/images/build/' . $this->app['workDir'] . '/thumbs/';

        $thumbPath = 'static/images/build/' . $this->app['workDir'] . '/thumbs/';

        if (file_exists($thumbDir . $pre . $img)) {
            return $thumbPath . $pre . $img;
        }

        if (!file_exists($thumbDir)) {
            mkdir($thumbDir, 0777, true);
        }

        $srcFile = $this->app['dir'] . $this->app['workDir'] . '/_src/images/' . $img;

        if (!file_exists($srcFile)) {
            return;
        }

        $image = new \Gumlet\ImageResize($srcFile);

        if ($width == 0 && $height == 0) {
            $width = 320;
        }

        if ($height == 0) {
            $image->resizeToWidth($width);
        }
        if ($width == 0) {
            $image->resizeToHeight($height);
        }
        if ($width != 0 && $height != 0) {
            $image->crop($width, $height);
        }

        $thumbFile = $thumbDir . $pre . $img;

        $image->save($thumbFile);

        return 'static/images/build/' . $this->app['workDir'] . '/thumbs/' . $pre . $img;

    }
}
