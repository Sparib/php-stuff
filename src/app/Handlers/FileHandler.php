<?php

namespace app\Handlers;

use app\Internal\Director;
    /**
     * Example:
     * 
     * Will load with priorities and (optional) dependencies  
     * ```
     * [filename w/o extention] => [
     *      'priority' => [0 - 99999],
     *      'dependson' => [list of files]
     * ]
     * ```
     * 
     * OR
     * 
     * Will not be loaded
     * `[filename w/o extention] => false`
     */

class FileHandler {

    public readonly Director $director;
    private $includes = [];
    private $file_list = [];

    function initialize() {
        $this->includes = $this->get_includes(__BASE_URL__ . "/app");

        $this->file_list = $this->flatten_includes();

        $this->load_includes();

        $director = new Director;
    }

    private function get_includes(string $dir, bool $recurse=True) {
        $return = [];
        foreach (new \FilesystemIterator($dir) as $t) {
            if (preg_match("/\w*\.disabled\.\w*/", $t->getFilename())) continue;
            if ($t->getType() === "file") {
                if (!preg_match("/\w*.php$/", $t->getFilename())) continue;
                $dirName = str_replace(__BASE_URL__, "", $dir);
                $dirName = explode("/", $dirName);
                $dirName = end($dirName);
                if (!array_key_exists($dirName, $return)) $return[$dirName] = [];
                array_push($return[$dirName], $t);
            } elseif ($t->getType() === "dir" && $recurse) {
                if ($t->getFilename() === "vendor") continue;
                $return = array_merge($return, $this->get_includes($t->getPathname()));
            }
        }
        return $return;
    }

    private function flatten_includes(): array {
        $return = [];

        foreach ($this->includes as $dir) foreach ($this->includes[$dir] as $file) array_push($return, $file);

        return $return;
    }

    private function load_includes(): array {
        $loaded = [];
        $load = count($this->includes, COUNT_RECURSIVE) - count($this->includes);

        while (count($loaded) < $load) {
            $cur_priority = null;
            $cur_file = null;

            foreach ($this->includes as $dir) {
                $directory = __BASE_URL__ . "/app/$dir";
                foreach ($this->includes[$dir] as $filename) {
                    if (!file_exists($directory . "/$filename.php"))
                        ErrorHandler::nonbreaking("File entry for $filename, but it does not exist.", \Sentry\Severity::warning());
                    
                    $file = &$this->includes[$dir][$filename];

                    if ($file === false) continue;
                    if (!in_array("priority", $file)) array_push($file, ["priority" => 99999]);
                    elseif (0 > $file["priority"] || $file["priority"] > 99999) {
                        ErrorHandler::nonbreaking("File entry for $filename has a priority that is out of bounds. It will be clamped to the nearest value", \Sentry\Severity::warning());
                        $file["priority"] = max(0, min($file["priority"], 99999));
                    }

                    if (array_key_exists("dependson", $file)) {
                        $include_dpend = [];
                        foreach ($file["dependson"] as $depend) {
                            if (!array_key_exists($depend, $this->includes));
                            # Finish this idek
                        }
                    }

                    if ($cur_priority > $file["priority"]) {
                        $cur_priority = $file["priority"];
                        $cur_file = $file;
                    }
                }
            }
        }
        
        return $loaded;
    }
}

?>