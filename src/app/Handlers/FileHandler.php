<?php

namespace app\Handlers;

use app\Internal\Director;
    /**
     * Example:
     * 
     * Will load with priorities and (optional) dependencies  
     * ```
     * [filename w/o extention] => [
     *      'priority' => [num],
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
        $loaded = 0;
        $load = count($includes, COUNT_RECURSIVE) - count($includes);

        while ($loaded < $load) {
            $cur_priority = null;
            $name = null;

            foreach ($includes as $dir) {
                $directory = __BASE_URL__ . "/app/$dir";
                foreach ($includes[$dir] as $file) {
                    if ($file == false) continue;
                    // TODO: Create nonbreaking error handling, handle nonrepresented file
                }
            }
        }
    }
}

?>