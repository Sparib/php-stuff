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

    function initialize() {
        $includes = $this->get_includes(__BASE_URL__ . "/app");
        $this->load_includes($includes);

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

    private function load_includes(array $includes) {
        $loaded = [];
        $load = count($includes, COUNT_RECURSIVE) - count($includes);

        while (count($loaded) < $load) {
            $cur_priority = null;
            $name = null;

            foreach ($includes as $dir) {
                $directory = __BASE_URL__ . "/app/$dir";
                foreach ($includes[$dir] as $filename) {
                    if (!file_exists($directory . "/$filename.php"))
                        ErrorHandler::nonbreaking("File entry for $filename, but it does not exist.", \Sentry\Severity::warning());
                    $file = &$includes[$dir][$filename];
                    if ($file === false) continue;
                    if (!in_array("priority", $file)) array_push($file, ["priority" => 99999]);
                    elseif (0 > $file["priority"] || $file["priority"] > 99999) {
                        ErrorHandler::nonbreaking("File entry for $filename has a priority that is out of bounds. It will be clamped to the nearest value", \Sentry\Severity::warning());
                        $file["priority"] = max(0, min($file["priority"], 99999));
                    }
                }
            }
        }
    }
}

?>