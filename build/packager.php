<?php
/**
 * Packaging script that generates omnipay.phar and omnipay.zip using Burgomaster
 */
require_once(__DIR__.'/artifacts/Burgomaster.php');

/* Do we want just Omnipay or also dependencies in the .phar? */
$includeGuzzle = true;
$includeSymfony = true;

$staging = __DIR__.'/artifacts/staging';
$root    = __DIR__.'/../';
$output  = getcwd();
$packager = new Burgomaster($staging, $root);

/* Copy Omnipay Common to staging */
$packager->recursiveCopy('vendor/omnipay/common/src/Omnipay', 'Omnipay', ['php']);

/* GatewayFactory::getSupportedGateways() uses information from composer.json
   Copy that file as gatewayinfo.json and fix-up hard coded path - pending a better solution */
$packager->deepCopy('vendor/omnipay/common/composer.json', 'gatewayinfo.json');
$d = file_get_contents($staging.'/Omnipay/Common/GatewayFactory.php');
$d = str_replace('/../../../composer.json', '/../../gatewayinfo.json', $d);
file_put_contents($staging.'/Omnipay/Common/GatewayFactory.php', $d);

/* Copy Omnipay modules to PSR-0 style folder structure */
$dh = opendir('vendor/omnipay');
while ($entry = readdir($dh)) {
    if ($entry == '.' || $entry == '..' || $entry == 'common')
        continue;

    /* Consult composer.json for namespace names */
    $composer_json = @file_get_contents('vendor/omnipay/'.$entry.'/composer.json');

    if ($composer_json) {
        $j = json_decode($composer_json);
        $psr4 = $j->autoload->{'psr-4'};
        if ($psr4) {
            foreach ($psr4 as $ns => $path) {
                $packager->recursiveCopy('vendor/omnipay/'.$entry.'/'.$path,
                                         str_replace("\\", "/", $ns), ['php']);
            }
        }
        else {
            die("Error processing '$entry', no psr-4 autoload entry in composer.json");
        }
    }
}
closedir($dh);

if ($includeGuzzle) {
    $packager->recursiveCopy('vendor/guzzle/guzzle/src/Guzzle',
                             'Guzzle', ['php', 'pem']);
}

if ($includeSymfony) {
    $packager->recursiveCopy('vendor/symfony/http-foundation',
                             'Symfony/Component/HttpFoundation', ['php']);
    $packager->recursiveCopy('vendor/symfony/event-dispatcher',
                             'Symfony/Component/EventDispatcher', ['php']);
}

$packager->createAutoloader();
$packager->createPhar($output.'/omnipay.phar');
$packager->createZip($output.'/omnipay-psr0.zip');
