<?php
/**
 * Shell Class
 *
 * The Shell extends the Core and is the class that initializes any
 * project-specific datamembers along with defining the rendering logic of
 * each page. An object of the shell class allows for ease-of-access
 * of core functions or module-related functions.
 *
 */
class Shell extends Core {
    // Include required components
    use AssetPushing;
    use Date;
    use Encryption;
    use FormHandling;
    use Github;
    use Logging;
    use SHT;
    /**
     * Shell constructor method
     */
    function __construct($shell = null) {
        parent::__construct();
        $this->shell = $shell;
        $this->name = "SHT";
        $this->title_separator = "//";
        $this->patterns = array();
        $this->data_paths = array(
            "/data/",
            "/data/logs/"
        );
        $this->pages = array(
            "/" => ["Home", "home", "default"],
            "/projects" => ["Projects", "projects", "default"],
            "/ardent" => ["Ardent Radio", "ardent", "default"],
            "#dropdown" => ["Dropdown", "", "", array(
                "/item1" => ["Item 1", "community/members", "default"],
                "/item2" => ["Item 2", "community/shop", "default"],
                "/item3" => ["Item 3", "community/administration", "default"]
            )],
            "/login" => ["Login", "login", "default"],
            "#dropdown2" => ["Another Dropdown", "", "", array(
                "/item4" => ["Item 4", "community/members", "default"],
                "/item5" => ["Item 5", "community/shop", "default"],
                "/item6" => ["Item 6", "community/administration", "default"]
            )],
            "/register" => ["Register", "login", "default"]
        );
        $this->errors = array(
            "/error/404" => ["404 Not Found", "error/404", "error"],
            "/error/503" => ["503 Service Unavailable", "error/503", "error"]
        );
        $this->folders = array(
            "api",
            "css",
            "js",
            "data"
        );
        $this->assets = array(
            "/css/core.css" => "style"
        );
        $this->pushAssets();
        $this->createDataPaths();
    }
    /**
     * Formats the title
     */
    function formatTitle() {
        $this->title = $this->page . " $this->title_separator " . $this->name;
    }
}
// Set the shell object name (for accessing in page segments and APIs)
$shell = "sht";
// Initialize the Shell object using a variable variable
$$shell = new Shell($shell);
// Initialize the connection to the database (optional) ------- |
$db = new Database($$shell, 'localhost', 'root', $shell); //    |  OPTIONAL DB
// Link the shell object with the database for easy accessing   |  CONNECTION
$$shell->linkDB($db); // -------------------------------------- |
// Render the page
$$shell->renderPage();
