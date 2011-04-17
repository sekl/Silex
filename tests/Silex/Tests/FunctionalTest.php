<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests;

use Silex\Application;
use Silex\LazyApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class FunctionalTest extends \PHPUnit_Framework_TestCase
{
    public function testBind()
    {
        $app = new Application();

        $app->get('/', function() {
            return 'hello';
        })
        ->bind('homepage');

        $app->get('/foo', function() {
            return 'foo';
        })
        ->bind('foo_abc');

        $app->flush();
        $routes = $app['routes'];
        $this->assertInstanceOf('Symfony\Component\Routing\Route', $routes->get('homepage'));
        $this->assertInstanceOf('Symfony\Component\Routing\Route', $routes->get('foo_abc'));
    }

    public function testMount()
    {
        $mounted = new Application();
        $mounted->get('/{name}', function ($name) { return new Response($name); });

        $app = new Application();
        $app->mount('/hello', $mounted);

        $response = $app->handle(Request::create('/hello/Silex'));
        $this->assertEquals('Silex', $response->getContent());
    }

    public function testLazyMount()
    {
        $mounted = function (Request $request, $prefix) {
            $mounted = new Application();
            $mounted->get('/{name}', function ($name) { return new Response($name); });

            return $mounted($request, $prefix);
        };

        $app = new Application();
        $app->mount('/hello', $mounted);

        $response = $app->handle(Request::create('/hello/Silex'));
        $this->assertEquals('Silex', $response->getContent());
    }

    public function testLazyMountWithAnExternalFile()
    {
        $tmp = sys_get_temp_dir().'/SilexLazyApp.php';
        file_put_contents($tmp, <<<'EOF'
<?php

$app = new Silex\Application();
$app->get('/{name}', function ($name) { return new Symfony\Component\HttpFoundation\Response($name); });

return $app;
EOF
        );

        $mounted = new LazyApplication($tmp);

        $app = new Application();
        $app->mount('/hello', $mounted);

        $response = $app->handle(Request::create('/hello/Silex'));
        $this->assertEquals('Silex', $response->getContent());

        unlink($tmp);
    }
}
