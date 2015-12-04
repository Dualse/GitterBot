<?php
namespace Unit;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase;

/**
 * Class AbstractTestCase
 * @package Unit
 */
abstract class AbstractTestCase extends TestCase
{
    /**
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * @param $data
     * @param array $map
     */
    protected function assertArrayFormat(array $data, array $map)
    {
        $parsed = [];
        $input = array_keys((array)$data);

        $item = (array)$data;
        foreach ($map as $key => $val) {
            if (is_array($val)) {
                $parsed[] = $key;
                $this->assertArrayHasKey($key, $item, print_r($data, 1));
                $this->assertArrayFormat($item[$key], $val);
            } else {
                $parsed[] = $val;
                $this->assertArrayHasKey($val, $item);
            }
        }


        sort($input);
        sort($parsed);

        $this->assertEquals($input, $parsed);
    }
}
