<?php

/**
 * This file is part of GitterBot package.
 *
 * @author Serafim <nesk@xakep.ru>
 * @date 24.09.2015 15:27
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Interfaces\Console\Commands;


use Carbon\Carbon;
use Domains\Room;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Interfaces\Gitter\Client;

/**
 * Class StartGitterBot
 */
class StartGitterBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gitter:listen {room}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start gitter bot thread for target room.';

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var string
     */
    protected $pid;

    /**
     * Execute the console command.
     *
     * @param Repository $config
     * @param Container $container
     *
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \LogicException
     * @throws \Exception
     */
    public function handle(Repository $config, Container $container)
    {
        $roomId = $this->argument('room');
        if (! is_null($env = \Config::get('gitter.envs.'.Room::getId($roomId)))) {
            \Config::set('gitter.env', $env);
        }

        $client = Client::make($config->get('gitter.token'), $roomId);
        $stream = $container->make(Room::class)->listen();

        $this->info(sprintf('KarmaBot %s started at %s', Client::VERSION, Carbon::now()));

        if (is_array($group = \Config::get('gitter.env'))) {
            $group = implode(', ', $group);
        }

        if (! empty($group)) {
            $this->info(sprintf('Current group: %s', $group));
        }

        $this->makePidFile();
        $client->run();
        $this->removePidFile();
    }

    /**
     * Create pid file
     */
    protected function makePidFile()
    {
        $this->pid = storage_path('pids/' . date('Y_m_d_tis_') . microtime(1) . '.pid');
        file_put_contents($this->pid, getmypid());
    }

    /**
     * Delete pid file
     */
    protected function removePidFile()
    {
        if (is_file($this->pid)) {
            unlink($this->pid);
        }
    }
}
