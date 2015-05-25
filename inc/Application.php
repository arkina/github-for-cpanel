<?php
/**
 * Application Class File
 * PHP Version 5
 * @author   NeXt I.T. - Mikel Bitson <me@mbitson.com>
 * @license  http://opensource.org/licenses/MIT	MIT License
 * @link     http://github-for-cpanel.mbitson.com
 */
namespace GHCP;

/**
 * Application Class
 * Date: 5/19/2015
 * @author   NeXt I.T. - Mikel Bitson <me@mbitson.com>
 * @license  http://opensource.org/licenses/MIT	MIT License
 * @link     http://github-for-cpanel.mbitson.com
 */
class Application
{
    /**
     * @var string Tthe key that identifies this application.
     */
    public $key;

    /**
     * @var string The directory to install this repo to.
     */
    public $directory;

    /**
     * @var string The branch to checkout.
     */
    public $branch;

    /**
     * @var string The repo name to install (user/repo)
     */
    public $repo;

    /**
     * @var bool Rather or not to install composer dependencies.
     */
    public $composer;

    /**
     * @var string The directory applications are installed to.
     */
    private $_application_dir;

    /**
     * Function to set default applications directory
     */
    public function __construct()
    {
        // Set default template directory
        $this->_application_dir = GHCP_PLUGIN_PATH . 'applications/';
    }

    /**
     * Function to load a list of applications and present them to the view.
     * @return array Data array for view that contains app list
     */
    public function apps()
    {
        // Get global userdata
        global $userdata;

        // Load list of applications for this particular user.
        $apps = glob( $this->_application_dir . $userdata['user'] . '-*.json' );

        // If we found apps
        if(!empty($apps))
        {
            // Cleanup on each app
            foreach($apps as &$app)
            {
                // Remove the path from string
                $app = file_get_contents($app);

                // Remove the .json from string
                $app = json_decode($app);
            }

            // Return for view
            return array('apps'=>$apps);
        }

        // If we found no apps...
        else
        {
            // Alert the user that they must create an appliction
            $this->alert('No applications found for your cPanel user. Please create one below.');

            // Get router for routing to the create page
            $router = new \GHCP\Router();

            // Route to the create page
            $router->route('application-create');

            // Return false to prevent view on current router instance
            return false;
        }
    }

    public function load($key)
    {
        // Load an application by application key (domain-directory)
        // Create a new application object
        $app = new \GHCP\Application();

        // Load data from json file
        $data = file_get_contents($this->_application_dir . $key);

        // Json decode string into array
        $data = json_decode($data);

        // Set data onto app
        $app->setData($data);

        // Return loaded application
        return $app;
    }

    /**
     * Attempts to fill all public properties with values from passed array.
     * @param array $data Array of data to set on this object.
     */
    public function setData(array $data)
    {
        // Get public (fillable) properties
        $properties = array_keys(get_object_vars($this));

        // For each of the properties...
        foreach($properties as $property)
        {
            // If the passed data has this property...
            if(in_array($property, array_keys($data)))
            {
                // Set this value
                $this->{$property} = $data[$property];
            }
        }
    }

    public function create()
    {
        // Return the data for the form view
        return array('test'=>'test');
    }

    /**
     * Function that will set passed data and post data to object and then store.
     * @param null $data Array of data to set on new object
     * @return false
     */
    public function save($data = NULL)
    {
        // Set post data onto object
        if(!empty($_POST)){
            $this->setData($_POST);
        }

        // Set passed data onto object
        if(!is_null($data) && !empty($data)){
            $this->setData($data);
        }

        // Get the username
        global $userdata;

        // Set this key
        $this->key = str_replace('/', '-', $userdata['user'].'-'.$this->repo);

        // Save properties to json file
        $this->store();

        // Setup this instance!
        $this->setup();

        // Alert the user saved correctly
        $this->alert('Your application has been created successfully!');

        // Route to list page.
        $router = new \GHCP\Router();
        $router->route('application-list', 'GET');

        // Return false to prevent this router from rendering
        return FALSE;
    }

    /**
     * Function to save this model as a json file based on key.
     */
    public function store()
    {
        // Open an application file based on key
        $applicationFile = fopen( $this->_application_dir . $this->key . '.json', "w")
            or die("Unable to open file! ".$this->key.'.json');

        // Write our json into
        fwrite($applicationFile, json_encode($this));

        // Close write con
        fclose($applicationFile);
    }

    public function delete()
    {
        // Attempt to get a key from query string
        if(isset($_GET['key']) && is_numeric((int)$_GET['key']))
        {
            // Store the key
            $key = $_GET['key'];
        }

        // Else throw an error!
        else
        {
            // Display not allowed, return false to prevent view
            echo "<h2>Not Allowed.</h2>";
            return false;
        }

        // Delete the file for this key
        if(unlink($this->_application_dir . $key . '.json'))
        {
            // Display the success
            $this->alert('Application Deleted Successfully! You will need to manually remove or replace the files the application installed.');
        }

        // If the file couldn't be deleted...
        else
        {
            // Alert the user with a warning, couldn't delete
            $this->warning( 'Your application file could not be deleted. Please delete it manually with one of the following commands: <br/>
                <strong>rm -f ' . $this->_application_dir . $this->key . '.json' . '</strong><br />
                <strong>sudo rm -f ' . $this->_application_dir . $this->key . '.json' . '</strong>' );
        }

        // Reroute to list page
        $router = new \GHCP\Router();
        $router->route('application-list');

        // Return false to prevent this view from being rendered
        return false;
    }


    public function setup()
    {
        // Make userdata accessable
        global $userdata;

        // Get deployment path
        $deploymentPath = $userdata['homedir'].'/'.$this->directory;

        // Delete current contents of dir
        $this->deleteDirectory( $deploymentPath );

        mkdir($deploymentPath);

        echo shell_exec('/usr/bin/git clone https://github.com/'.$this->repo.'.git '.$deploymentPath.' 2>&1');
        echo shell_exec('cd '.$deploymentPath);
        echo shell_exec('/usr/bin/git pull development');
        exit;

        // Checkout Repo
        // Checkout specific branch
        // fix permissions
        // install composer dependencies if checked
        // later - install the deploy script
        // later - setup github hook to point to deploy script

        // Return success
    }

    public function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }

        }

        return rmdir($dir);
    }

    /**
     * A function to display a message to the user
     * @param $message The message to alert to the user
     */
    public function alert($message)
    {
        echo "
        <div class=\"alert alert-success alert-dismissable\">
            <button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>
            <span class=\"glyphicon glyphicon-ok-sign\"></span>
            <div class=\"alert-message\">
                <strong>Success:</strong>
                $message
            </div>
        </div>
        ";
    }

    /**
     * A function to display a warning to the user
     * @param $message The message to warn the user about
     */
    public function warning($message)
    {
        echo "
        <div class=\"alert alert-warning alert-dismissable\">
            <button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>
            <span class=\"glyphicon glyphicon-ok-sign\"></span>
            <div class=\"alert-message\">
                <strong>Warning:</strong>
                $message
            </div>
        </div>
        ";
    }
}
