<?php

namespace app\Internal\Handlers;

use app\Setup;
use Attribute;
use InvalidArgumentException;

class CommandHandler {
    private static $commands = [];

    public static function registerCommandHandler(string $command, string $handlerClass, string ...$handlerConfigs) {
        if (preg_match("/^[a-z]+$/", $command) !== 1) {
            ErrorHandler::nonbreaking("Api with name '$command' contains an illegal charater. Command names can only contain a-z. This command cannot be registered!", \Sentry\Severity::error());
            throw new \Exception("Api with name '$command' contains a slash. This command cannot be registered!");
            return;
        }

        if (!file_exists(FileHandler::path_from_class($handlerClass))) {
            ErrorHandler::nonbreaking("Api class '$handlerClass' does not exist. The '$command' api cannot be registered!", \Sentry\Severity::error());
            throw new \Exception("Api class '$handlerClass' does not exist. The '$command' api cannot be registered!");
            return;
        }

        foreach ($handlerConfigs as $handlerConfig) {
            if (!file_exists(FileHandler::path_from_class($handlerConfig))) {
                ErrorHandler::nonbreaking("Api config '$handlerConfig' does not exist. The '$command' command will not be registered!", \Sentry\Severity::error());
                throw new \Exception("Api config '$handlerConfig' does not exist. The '$command' command will not be registered!");
                return;
            }
        }

        CommandHandler::$commands[$command] = [$handlerClass, $handlerConfigs];
    }

    public static function runCommand($argv) {
        $command = $argv[1] ?? null;
        $subcommand = $argv[2] ?? null;
        $arguments = array_slice($argv, 3) ?? null;

        if ($command == null) { echo "You must specify a command\n"; return; }

        if (!array_key_exists($command, CommandHandler::$commands)) { echo "Unknown command\n"; return; }

        if ($subcommand == null) $subcommand = "default";

        include_once FileHandler::path_from_class(CommandHandler::$commands[$command][0]);

        $class = new \ReflectionClass(CommandHandler::$commands[$command][0]);

        $matches = array_filter($class->getMethods(17), function($method) use ($subcommand) {
            if (count(($attr = $method->getAttributes(Command::class))) == 0) return false;
            $inst = $attr[0]->newInstance();

            $name = $inst->command;

            return $subcommand == $name;
        });

        if (count($matches) == 0) {
            if ($subcommand == "default") {
                echo "You must specify a subcommand\n";
                return;
            } else {
                echo "Unknown subcommand\n"; return; 
            }
        }

        $method = array_values($matches)[0];

        foreach (array_filter($class->getMethods(17), function($meth) { return count($meth->getAttributes(Setup::class)) != 0; }) as $setup) {
            $setup->invoke(null);
        }

        if (count($method->getParameters()) == 0) $method->invoke(null);
        else $method->invoke(null, $arguments);
}
}

#[Attribute(Attribute::TARGET_METHOD)]
class Command {
    public string $command;

    /**
     * @param string $command The name of the command. Must only contain characters a-z.
     */
    public function __construct(string $command) {
        if (preg_match("/^[a-z]+$/", $command) !== 1) throw new InvalidArgumentException("Command name '$command' must not contain characters other than a-z!");
        $this->command = $command;
    }
}