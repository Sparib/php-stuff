<?php

// TODO: Rewrite this to work off of a list in the director file to load files from a preset list.
// TODO: Also, preferably handle non existent files and entries with non-breaking errors.

namespace app\Handlers;
use app\Internal\Director;

class FileHandler {
    public readonly array $director;

    public function create_director() {
        include_once __BASE_URL__ . "/app/Internal/Director.php";
        $this->director = new Director();
    }

    public function include_files() {
        # code...
    }
}

?>