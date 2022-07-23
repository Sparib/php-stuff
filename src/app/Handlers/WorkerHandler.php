<?php

namespace app\Handlers;

use app\Internal\Director;
use app\Setup;
use app\Internal\Handlers\Command;
use app\Internal\Handlers\FileHandler;
use Attribute;

class WorkerHandler {
    private static $workers = [];

    #[Setup]
    public static function setup() {
        include_once Director::dir("routes") . "/workers.php";
    }

    public static function registerWorker(string $name, string $workerClass, string ...$workerConfigs) {
        array_push(WorkerHandler::$workers, new InternalWorker($name, $workerClass, $workerConfigs));
    }

    #[Command("work")]
    public static function work() {
        foreach (WorkerHandler::$workers as $worker) {
            $worker->run();
        }
    }

    #[Command("list")]
    public static function list() {
        foreach (WorkerHandler::$workers as $worker) {
            printf("%-12s %s\n", $worker->name, $worker->class);
        }
    }
}

#[Attribute(Attribute::TARGET_METHOD)]
class Worker {
    public int $frequency;

    public function __construct(int $frequency) {
        $this->frequency = $frequency;
    }
}

class InternalWorker {
    public readonly string $name;
    public readonly string $class;
    private array $configs;

    public function __construct(string $name, string $class, array $configs) {
        $this->name = $name;
        $this->class = $class;
        $this->configs = $configs;
    }

    public function run() {
        include_once FileHandler::path_from_class($this->class);

        foreach ($this->configs as $config) {
            include_once FileHandler::path_from_class($config);
        }

        $reflec = new \ReflectionClass($this->class);
        foreach ($reflec->getMethods(17) as $method) {
            if (count(($attr = $method->getAttributes(Worker::class))) == 0) continue;

            $freq = $attr[0]->newInstance()->frequency;
            $name = hash("sha256", $method->getName() . $reflec->getName());

            $client = new \Predis\Client();
            $lastRun = $client->get($name) ?? null;

            if ($lastRun == null || time() >= $lastRun + $freq) {
                $method->invoke(null);
                $client->set($name, time());
            }
        }
    }
}