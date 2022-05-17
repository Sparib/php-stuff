<?php

// TODO: Rewrite this to work off of a list in the director file to load files from a preset list.
// TODO: Also, preferably handle non existent files and entries with non-breaking errors.

namespace app\Handlers;
use app\Internal\Director;

class FileHandler {
    public readonly Director $director;

    public function create_director() {
        include_once __BASE_URL__ . "/app/Internal/Director.php";
        $this->director = new Director();
    }

    public function include_files() {
        $has_error = false;
        foreach ($this->director->loads as $path) {
            $origin = $path;
            if (!str_starts_with($path, "/")) $path = "/${path}";
            if (!str_starts_with($path, "/app")) $path = "/app${path}";
            $path = __BASE_URL__ . $path . ".php";
            if (!is_file($path)) {
                \Sentry\addBreadcrumb(
                    new \Sentry\Breadcrumb(
                        \Sentry\Breadcrumb::LEVEL_WARNING,
                        \Sentry\Breadcrumb::TYPE_DEFAULT,
                        'loads',
                        "Load path does not point to file.",
                        ['original' => $origin, 'full' => $path]
                    )
                );
                $has_error = true;
            }
        }

        if ($has_error) ErrorHandler::nonbreaking("One or more nonbreaking errors encountered during file loading.", \Sentry\Severity::warning());
    }
}

?>