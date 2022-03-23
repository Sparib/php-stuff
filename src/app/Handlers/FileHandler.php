<?php

namespace app\Handlers;

class FileHandler {
    /**
     * Example:
     * [filename w/o extention] => [
     *      'priority' => [num],
     *      'dependson' => [list of files]
     * ]
     */

    function __construct() {
        $includes = $this->get_includes(__BASE_URL__ . "/app");
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
}

?>