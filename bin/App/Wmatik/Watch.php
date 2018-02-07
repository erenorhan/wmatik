<?php
namespace Wmatik;

class Watch
{
    /**
     * render edilecek uzantılar..
     * @var array
     */
    public $ext = ['twig', 'yml', 'yaml'];

    /**
     * exlude directory..
     *
     * @var array
     */
    private $exludeDir = ['_site', 'static'];

    public function __construct($app)
    {
        $this->app = $app;
    }
    /**
     * Dizindeki değişiklikleri izle..
     *
     * @return void
     */
    public function watch()
    {
        $files = new \Illuminate\Filesystem\Filesystem;
        $tracker = new \JasonLewis\ResourceWatcher\Tracker;
        $watcher = new \JasonLewis\ResourceWatcher\Watcher($tracker, $files);

        $listener = $watcher->watch($this->app['dir']);

        $listener->onModify(function ($resource, $path) {

            $nPage = str_replace(realpath($this->app['dir']), '', $path);

            $dirInfo = pathinfo($nPage);

            $dirArr = explode('\\', $dirInfo['dirname']);
            $lastDir = end($dirArr);

            if ($dirArr[1]=='_site'){
               
                echo shell_exec('php bin/index.php build');
                echo "\n";
               
            }
                     
            if (!in_array($dirArr[1], $this->exludeDir) && $lastDir=='_src') {
            
                if (in_array($dirInfo['extension'], $this->ext)) {
                    $nPage = $dirInfo['dirname'];

                    $nPage = str_replace('_src', '', $nPage);
                    $nPage = str_replace('\\', '/', $nPage);
                    $nPage = trim($nPage, '/');

                    echo exec('php bin/index.php build ' . $nPage);
                    echo "\n";

                    //Bunun çalışması lazım ama olmuyor :((
                        /*$cname = new \Wmatik\Index($this->app);
                        $cname->index($nPage);
                     */

                }
            }

        });

        $watcher->start();

    }

}
