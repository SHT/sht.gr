<?php

// Trait that handles blueprint-based page rendering
trait Renderer {

    // Protected title-related datamembers
    protected $app;
    protected $separator;
    protected $title;
    // Private page rendering datamembers
    protected $name;
    protected $blueprint;
    protected $template;

    protected $system_dirs;
    protected $asset_dirs;

    /**
     * Returns the page title
     *
     * @return string The page title
     */
    function getTitle() {
        return escape($this->title);
    }

    /**
     * Returns the current page's name
     *
     * @return string Current page's name
     */
    function getPage() {
        return $this->name;
    }

    /**
     * Formats the current page's title
     */
    function formatTitle() {
        $this->title = $this->app . " " . $this->separator . " " . $this->name;
    }

    /**
     * Overrides the current page path
     *
     * @param string $page The page path to force
     */
    function setCurrentPage($url) {
        // echo $url;
        // echo " set";
        $page = $this->pages[$url];
        $this->current_page = $url;
        $this->name = $page->name;
        $this->template = $page->template;
        $this->blueprint = $page->blueprint;
        $this->pages[$url]->current = true;
        // Re-format the title since the page data was changed
        $this->formatTitle();
    }

    function throwError($code) {
        http_response_code($code);
        $this->setCurrentPage("/error/$code");
    }

    function findCurrentPage() {
        $folder = $this->getProjectFolder();
        $current_page = $this->getCurrentPage();
        // Loop all pages

        foreach ($this->pages as $url => $page) {
            // echo "$url <br>";
            if (endsWith($url, "/*")) {
                $url = substr($url, 0, -2);
                if (startsWith($current_page, $url)) {
                    $this->setCurrentPage($page->url);
                    return;
                }
            }
            else {
                if ($current_page == $page->url) {
                    $this->setCurrentPage($page->url);
                    return;
                }
                if ($page->children) {
                    foreach ($page->children as $child) {
                        if ($current_page === $child->url) {
                            $this->setCurrentPage($child->url);
                            return;
                        }
                    }
                }
            }
        }
    }



    function isEndpoint($actual_page) {
        $parameters = explode("/", $actual_page);
        // var_dump($parameters);
        array_shift($parameters);
        if ($parameters[0] == "api") {
            return true;
        }
        return false;
    }

    function serveEndpoint($location) {
        $path = $this->getRoot() . $location . ".php";
        if (file_exists($path)) {
            global $core;
            require $path;
        }
        else {
            $this->serveErrorPage(403, $location);
        }
    }

    function isPage($location) {
        // if ($location != "/") {
        //     // Get rid of trailing slashes when checking
        //     $location = rtrim($location, "/");
        // }
        // $found = false;
        // foreach ($this->pages as $url => $page) {
        //     echo "$url $location<br>";
        //
        //     if (startsWith($location, $url)) {
        //         return true;
        //     }
        // }
        foreach ($this->pages as $url => $page) {
            if (endsWith($url, "/*")) {
                // echo "$location $url<br>";
                $url = substr($url, 0, -2);
                if (startsWith($location, $url)) {
                    return true;
                }
            }
            else {
                if ($location == $url) {
                    return true;
                }
            }

        }
        return false;
        // die();
        // if (array_key_exists($location, $this->pages)) {
        //     return true;
        // }
        // else {
        //     return false;
        // }
    }

    function servePage($location) {
        $current_page = $this->getCurrentPage();
        $query = $_SERVER['QUERY_STRING'];
        // var_dump($this->blueprint);
        // if (!$query && (endsWith($this->getCurrentPage(), "//") || !endsWith($this->getCurrentPage(), "/"))) {
        //     header("Expires: " . gmdate("D, d M Y H:i:s", time() + 86400) . " GMT");
        //     header("Pragma: cache");
        //     header("Cache-Control: max-age=86400");
        //     $this->redirect(rtrim($location, "/") . "/", 301);
        // }
        // else if ($query && endsWith($this->getCurrentPage(), "/")) {
        //     $location = rtrim($location, "/");
        //     $location = $location . "?" . $query;
        //     $this->redirect($location);
        // }

        $path = $this->getRoot() . "/includes/blueprints/" . $this->blueprint . ".php";
        $template = $this->getRoot() . "/includes/pages/" . $this->template . ".php";

        if (!file_exists($path)) {
            // echo $path;
            $this->log("RENDERER", "Error while serving " . $current_page . ": Blueprint $this->blueprint does not exist, falling back to default blueprint.");
            $this->serveErrorPage(501, $location);
        }
        else if (!file_exists($template)) {
            $this->log("RENDERER", "Error while serving " . $current_page . ": Template $this->template does not exist.");
            $this->serveErrorPage(501, $location);
        }
        else {
            global $core;
            require_once $path;
        }
    }


    function isAsset($location) {
        $flag = false;
        foreach ($this->asset_dirs as $path) {
            if (substr($location, 0, strlen($path)) === $path) {
                $flag = true;
            }
        }
        if ($flag) {
            return true;
        }
        else {
            return false;
        }
    }

    function detect_mime_type($filename) {
        $result = new finfo();

        if (is_resource($result) === true) {
            return $result->file($filename, FILEINFO_MIME_TYPE);
        }

        return false;
    }

    function serveAsset($location) {
        $path = $this->getRoot() . $location;
        if (file_exists($path)) {
            $mime_type = getMimeType($path);
            header("Expires: " . gmdate("D, d M Y H:i:s", time() + 86400) . " GMT");
            header("Pragma: cache");
            header("Cache-Control: max-age=86400");
            header ('X-Sendfile: ' . ltrim($location, '/'));
            header("Content-Type: $mime_type");
            readfile($path);
        }
        else {
            $this->serveErrorPage(404, $location);
        }
    }

    function serveErrorPage($code, $location) {
        $this->throwError($code);
        $this->servePage($location);
    }



    /**
     * Renders a page based on its blueprint's format
     */
    function renderPage() {


        $this->findCurrentPage();


        // Acquire the first segment of the requested path
        //$dir = $this->getProjectFolder();
        //$url = substr($this->getCurrentPage(), strlen($dir));

        // $location = strtok($url, '?');

        $location = $this->getCurrentPage();

        if (endsWith($location, "/") && $location != "/") {
            $this->redirect(rtrim($location, "/"));
            die();
        }


        if ($location === false) {
            $this->redirect("/");
        }
        //var_dump($path);
        //die();
        //$location = rtrim($location, '/');
        //var_dump($location);

        $accessible = $this->isAccessible($location);

        if (!$accessible or $location === "/api/") {
            //echo "not allowed, serving 403<br>";
            $this->serveErrorPage(403, $location);
        }
        else if ($this->isEndpoint($location)) {
            //echo "serving endpoint<br>";
            $this->serveEndpoint($location);
        }
        else if ($this->isAsset($location)) {
            //echo "serving asset<br>";
            $this->serveAsset($location);
        }
        else if ($this->isPage($location)) {

            //echo "serving page<br>";
            $this->servePage($location);
        }
        else {
            //echo "serving 404<br>";
            $this->serveErrorPage(404, $location);
        }

    }

    /**
     * Loads a component on the page's content
     *
     * @param string $component The component to load
     */
    function loadComponent($component) {
        $core = $this;
        require($this->getRoot() . "/includes/components/$component.php");
    }

    /**
     * Inserts the main content into the page
     */
    function loadContent() {
        // Create a variable variable reference to the shell object
        // in order to be able to access the shell object by its name and not
        // $this when in page context
        $core = $this;
        $path = $this->getRoot() . "/includes/pages/" . $this->template . ".php";
        if (file_exists($path)) {
            require_once $path;
        }
        else {
            $this->log("RENDERER", "Page template file $this->template.php doesn't exist.");
        }
    }

    /**
     * Echoes a formatted style include
     *
     * @param string $style The style filename
     */
    function loadStyle($style) {
        $style = escape($style);
        $project_dir = escape($this->getProjectFolder());
        $commit_hash = escape($this->getCommitHash());
        echo "<link href=\"$project_dir/css/$style?v=$commit_hash\" type=\"text/css\" rel=\"stylesheet\" media=\"screen\"/>\n";
    }

    /**
     * Echoes a formatted script tag
     *
     * @param string $script The script filename
     */
    function loadScript($script) {
        $script = escape($script);
        $project_dir = escape($this->getProjectFolder());
        $commit_hash = escape($this->getCommitHash());
        echo "<script src=\"$project_dir/js/$script?v=$commit_hash\"></script>\n";
    }

    /**
     * Queues a script to be included after the scripts component is loaded
     *
     * @param string $script The script filename
     */
    function queueScript($script) {
        $this->script_queue[] = $script;
    }

    /**
     * Echoes all the queued scripts as formatted script tags
     */
    function appendScripts() {
        foreach ($this->script_queue as $script) {
            $this->loadScript($script);
        }
    }

}

?>
