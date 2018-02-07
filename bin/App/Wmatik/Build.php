<?php
namespace Wmatik;

use Symfony\Component\Yaml\Yaml;

class Build
{

    public function __construct($app)
    {

        $this->app = $app;

    }

    //build twig file..
    public function index($currentWorkDir = '')
    {

        //$this->app['workDir']=$currentWorkDir;
        echo " -> Derleniyor : " . $currentWorkDir . "\n";
        echo "xxxxxx";
        $dataLocal = '';

        $dataGlobal = '';

        //site dir + mevcut dir..
        $workDir = $this->app['dir'] . $currentWorkDir;

        $data = [];

        //local data file yükle
        if (file_exists($workDir . '/_src/data.yml')) {
            $dataLocal = Yaml::parseFile($workDir . '/_src/data.yml');
            if (!is_array($dataLocal)) {
                echo "\n[" . $currentWorkDir . "/_src/data.yml] not valid data file. \n";
            }

        }
        $data = is_array($dataLocal) ? $dataLocal : array();
        $data['global'] = [];

        //gobal data dosyasını oku. Twig : global.var
        $files = glob($this->app['dir'] . '_site/data/*.{yml,yaml}', GLOB_BRACE);
        foreach ($files as $file) {
            $f = basename($file);
            $f = pathinfo($f);
            $f = $f['filename'];

            $data['global'][$f] = Yaml::parseFile($file);
            if (!is_array($data['global'][$f])) {
                echo "\n[/_site/data/data.yml] not valid data file. \n";
            }

        }

        //$data['global'] = is_array($dataGlobal) ? $dataGlobal : array();

        //render
        if (file_exists($workDir . '/_src/index.twig')) {

            //ilgili dizindeki twig dosyasını render et..
            $render = $this->app['twig']->render($currentWorkDir . '/_src/index.twig', $data);

            //render edilmiş dosyaya header ve footer bilgisi eklemek için tekrar ayarlar
            $twig_HeaderFooter = new \Twig_Environment(new \Twig_Loader_String);
            $lexer = new \Twig_Lexer($twig_HeaderFooter, array(
                'autoescape' => false,
                'tag_variable' => array('[[', ']]'),
                'tag_block' => array('[%', '%]'),

            ));
            $twig_HeaderFooter->setLexer($lexer);

            $moduller = [];

            foreach ($_SESSION['modules'] as $key => $value) {
                array_push($moduller, $value);
            }

            $headerStr = '';
            $footerStr = '';

            $moduller = array_unique($moduller);

            foreach ($moduller as $k => $v) {

                if (file_exists($this->app['dir'] . '_site/modules/' . $v . '/header/include.twig')) {
                    $headerStr .= $this->app['twig']->render('_site/modules/' . $v . '/header/include.twig', $data);
                }
                if (file_exists($this->app['dir'] . '_site/modules/' . $v . '/header/files/')) {
                    $this->copyDir($this->app['dir'] . '_site/modules/' . $v . '/header/files/', $this->app['dir'] . 'static/build/');

                }
            }

            foreach ($moduller as $k => $v) {

                if (file_exists($this->app['dir'] . '_site/modules/' . $v . '/footer/include.twig')) {
                    $footerStr .= $this->app['twig']->render('_site/modules/' . $v . '/footer/include.twig', $data);
                }
                if (file_exists($this->app['dir'] . '_site/modules/' . $v . '/footer/files/')) {
                    $this->copyDir($this->app['dir'] . '_site/modules/' . $v . '/footer/files/', $this->app['dir'] . 'static/build/');

                }

            }

            $ndata['footer'] = $footerStr;
            $ndata['header'] = $headerStr;

            //ikinci render heade ve footer
            $render2 = $twig_HeaderFooter->render($render, $ndata);

            //bir üst dizine output file yaz..
            file_put_contents($workDir . '/index.html', $render2);
        } else {

            echo "\n[" . $currentWorkDir . "/_src/index.twig] file not found. \n";

        }

    }

    /**
     * Url duzenle..
     *
     * @param string $input
     * @return string
     */
    public function url($input)
    {

        /* $tr = array('ş','Ş','ı','I','İ','ğ','Ğ','ü','Ü','ö','Ö','Ç','ç');
        $eng = array('s','s','i','i','i','g','g','u','u','o','o','c','c');
        $input = str_replace($tr,$eng,$input);
         */
        $input = str_replace("&nbsp;", " ", $input);
        $input = str_replace(array("'", "-"), "", $input);
        $input = mb_convert_case($input, MB_CASE_LOWER, "UTF-8");
        $input = preg_replace("#[^a-zA-Z/]+#", "-", $input);
        $input = preg_replace("#(-){2,}#", "$1", $input);
        $input = trim($input, "-");
        return $input;
    }

    /**
     * Yeni sayfa oluştur
     *
     * @param strings $page
     * @return void
     */
    public function newPage($page)
    {

        $page = $this->url($page);

        if (file_exists($this->app['dir'] . $page)) {
            echo "[HATA] : Sayfa Zaten Var \n";
        } else {

            @mkdir($this->app['dir'] . $page);
            @mkdir($this->app['dir'] . $page . '/_src');
            @mkdir($this->app['dir'] . $page . '/_src/images');

            $content = "{# Page : /" . $page . "  #}

{% extends \"_site/layout/base.twig\" %}
{% block content %}
<div>
<h1>{{title}}</h1>
<p>{{description}}</p>
<p>{{date}}</p>
</div>
{% endblock %}";

            $yamlArr = ['title' => 'Page Title',
                'description' => 'Page Desc.',
                'date' => date('Y-m-d'),
            ];

            $yaml = Yaml::dump($yamlArr);

            file_put_contents($this->app['dir'] . $page . '/_src/data.yml', $yaml);

            $fContent = fopen($this->app['dir'] . $page . '/_src/index.twig', "wb");
            fwrite($fContent, $content);
            fclose($fContent);
            echo (string) "[" . $page . "] Ok";

        }
    }

/**
 * Copy Dir.
 *
 * @param string $src
 * @param string $dst
 * @return void
 */
    public function copyDir($source, $target)
    {
        if (is_dir($source)) {
            @mkdir($target);
            $d = dir($source);
            while (false !== ($entry = $d->read())) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                $Entry = $source . '/' . $entry;
                if (is_dir($Entry)) {
                    $this->copyDir($Entry, $target . '/' . $entry);
                    continue;
                }
                copy($Entry, $target . '/' . $entry);
            }

            $d->close();
        } else {
            copy($source, $target);
        }
    }
}
