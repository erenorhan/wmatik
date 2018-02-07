<?php
$time_start = microtime(true);
use \Wmatik as W;

require_once __DIR__ . '/vendor/autoload.php';

$app['dir'] = __DIR__ . '/../www/';

//twig settins
$loader = new \Twig_Loader_Filesystem($app['dir']);
$twig = new \Twig_Environment($loader, array(
    'autoescape' => false,
    'debug' => true,
    'auto_reload' => true,
    'cache' => false,
));
$app['twig'] = $twig;
$app['workDir'] = !empty($argv[2]) ? $argv[2] : '';

$global = new \Wmatik\Get($app);

$twig->addGlobal('get', $global);


//argv1
switch ($argv[1]) {
    case 'build':
        //build options
        if (isset($argv[2])) {

           

            $cname = new \Wmatik\Build($app);

            $cname->index($app['workDir']);
        } else {
            //Build all dir.
            $allDirs = new \Wmatik\Tools($app);
            foreach ($allDirs->getDir() as $value) {

                $app['workDir'] = $value;
                $global = new \Wmatik\Get($app);
                $twig->addGlobal('get', $global);
                $cname = new \Wmatik\Build($app);
                $cname->index($value);

            }

        }
        break;
    case 'new':
        $cname = new \Wmatik\Build($app);
        $cname->newPage($app['workDir']);
        break;
    case 'watch':
        $cname = new \Wmatik\Watch($app);
        $cname->watch();

        break;

    default:
        echo "Wmatik v4 \n \n";
        break;
}
//echo "[" . (microtime(true) - $time_start) . "]";
