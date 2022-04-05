<?php

namespace app\Handlers;

use app\Internal\Director;
use Error;

    /**
     * Example:
     * 
     * Will load with priorities and (optional) dependencies  
     * ```
     * "filename w/o extention" => new File(priority {0-99999}[, optional dependencies])
     * ```
     * 
     * OR
     * 
     * Will not be loaded
     * `[filename w/o extention] => false`
     */

     // TODO: Cry

class FileHandler {

    public readonly Director $director;
    private $includes = null;
    private $file_list = [];

    public function __construct() {
        $this->includes = [
            "Internal" => [
                "Director" => new File(10),
                "Router" => new File(0, ["Director"])
            ]
        ];
    }

    public function initialize() {
        $this->file_list = $this->flatten_includes($this->includes);
        // print_r($this->file_list);

        print_r($this->load_includes());

        // $director = new Director;
    }

    private function flatten_includes($array): array {
        $return = [];

        foreach ($array as $k => $v) {
            if ($v instanceof File) array_push($return, $v);
            elseif (is_array($v)) $return = array_merge($return, $this->flatten_includes($v));
            else throw new Error("Includes list contains a value with key {$k} that is not an array, nor File class.");
        }

        return $return;
    }

    private function load_includes(): array {
        $loaded = [];
        $load = count($this->includes, COUNT_RECURSIVE) - count($this->includes);

        while (count($loaded) < $load) {
            [$file, $priority] = array_merge($loaded, $this->loop_dir($this->includes, null));
            echo print_r($file, true), "<br><br>";
            array_push($loaded, $file);
        }
        
        return $loaded;
    }

    private function loop_dir(array &$table, int|null $priority, string $directory = __BASE_URL__ . "/app"): array {
        $file = null;
        $cur_priority = $priority;
        
        \Sentry\addBreadcrumb(
            new \Sentry\Breadcrumb(
                \Sentry\Breadcrumb::LEVEL_DEBUG,
                \Sentry\Breadcrumb::TYPE_DEFAULT,
                'filehandler',                // category
                'Looping new dir',  // message (optional)
                ['table' => print_r($table, true), 'directory' => $directory] // data (optional)
            )
        );
        
        foreach ($table as $key => &$item) {
            \Sentry\addBreadcrumb(
                new \Sentry\Breadcrumb(
                    \Sentry\Breadcrumb::LEVEL_DEBUG,
                    \Sentry\Breadcrumb::TYPE_DEFAULT,
                    'filehandler',                // category
                    'Handling item',  // message (optional)
                    ['key' => $key, 'value' => $item] // data (optional)
                )
            );
            if ($item instanceof File) {
                \Sentry\addBreadcrumb(
                    new \Sentry\Breadcrumb(
                        \Sentry\Breadcrumb::LEVEL_DEBUG,
                        \Sentry\Breadcrumb::TYPE_DEFAULT,
                        'filehandler',                // category
                        'Item is file',  // message (optional)
                    )
                );
                throw new Error("Hi");

                if (!file_exists("{$directory}/{$key}.php"))
                    ErrorHandler::nonbreaking("File entry for {$key}, but it does not exist.", \Sentry\Severity::warning());

                if ($item === false) continue;

                if (array_key_exists("dependson", $item)) { // FIXME: Create functions for file class instead of array_key_exists
                    // TODO: Replace with handle_dependencies
                    $include_dpend = [];
                    foreach ($item["dependson"] as $depend) {
                        if (!array_key_exists($depend, $this->includes))
                            throw new Error("File entry for {$key} contains a non-existent dependency \"{$depend}\".");

                        $depend_path = $this->get_path($depend);
                        if ($depend_path == null)
                            throw new Error("Error retrieving the path for {$depend}.");

                        if ($this->include($depend_path))
                            $this->check_file($depend);
                        else
                            throw new Error("File path for dependency \"{$depend}\" under file entry \"{$key}\" does not exist.");
                    }
                }

                if ($cur_priority > $item["priority"]) {
                    $cur_priority = $item["priority"];
                    $cur_file = $item;
                }
            }
        }

        return [$item, $cur_priority];
    }

    private function include(string $filePath): bool {
        if (!file_exists($filePath))
            return false;
        else {
            include_once $filePath;
            return true;
        }
    }

    private function check_file(string $filename): void {
        // FIXME: Rewrite this to handle new format
        // foreach ($this->includes as $dir) {
        //     foreach ($this->includes[$dir] as $file) {
        //         if ($filename === $file) {
        //             if (!$this->includes[$dir][$])
        //                 array_push($this->includes[$dir][$filename], "checked");
        //             break;
        //         }
        //     }
        // }
    }

    private function handle_dependencies(array $depends, array $prev = []) {
        // TODO: Handle list of files and load by priority
        foreach ($depends as $depend) {
            if (in_array($depend, $prev))
                throw new Error("Recursive dependency encountered.");
        }
    }

    private function get_path($file) {
        // FIXME: Rewrite this to handle new format
        $return = null;
        foreach ($this->includes as $dir) {
            foreach ($this->includes[$dir] as $filename) {
                if ($file === $filename) {
                    $return = __BASE_URL__ . "/app/{$dir}/{$filename}.php";
                    break;
                }
            }
        }

        return $return;
    }
}

class File {
    public readonly int $priority;
    public readonly array $dependencies;
    public bool $checked = false;

    public function __construct(int $priority, array $dependencies = []) {
        if (0 > $priority || $priority > 99999) {
            ErrorHandler::nonbreaking("File initialized with a priority that is out of bounds. It will be clamped to the nearest value.", \Sentry\Severity::warning());
            $priority = max(0, min($priority, 99999));
        }

        $this->priority = $priority;
        $this->dependencies = $dependencies;
    }
}

                    // if (!file_exists("{$directory}/{$filename}.php"))
                    //     ErrorHandler::nonbreaking("File entry for {$filename}, but it does not exist.", \Sentry\Severity::warning());
                    
                    // $file = &$this->includes[$dir][$filename];

                    // if ($file === false) continue;
                    // if (!in_array("priority", $file)) array_push($file, ["priority" => 99999]);
                    // elseif (0 > $file["priority"] || $file["priority"] > 99999) {
                    //     ErrorHandler::nonbreaking("File entry for {$filename} has a priority that is out of bounds. It will be clamped to the nearest value.", \Sentry\Severity::warning());
                    //     $file["priority"] = max(0, min($file["priority"], 99999));
                    // }

                    // if (array_key_exists("dependson", $file)) {
                    //     $include_dpend = [];
                    //     foreach ($file["dependson"] as $depend) {
                    //         if (!array_key_exists($depend, $this->includes))
                    //             throw new Error("File entry for {$filename} contains a non-existent dependency \"{$depend}\".");

                    //         $depend_path = $this->get_path($depend);
                    //         if ($depend_path == null)
                    //             throw new Error("Error retrieving the path for {$depend}.");

                    //         if ($this->include($depend_path))
                    //             $this->check_file($depend);
                    //         else
                    //             throw new Error("File path for dependency \"{$depend}\" under file entry \"{$filename}\" does not exist.");
                    //     }
                    // }

                    // if ($cur_priority > $file["priority"]) {
                    //     $cur_priority = $file["priority"];
                    //     $cur_file = $file;
                    // }

?>