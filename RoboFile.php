<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
  use \Boedah\Robo\Task\Drush\loadTasks;

  /**
   * The location of our Drush executable.
   */
  const DRUSH_BIN = "bin/drush";

  /**
   * The location of our Codeception executable.
   */
  const CODECEPT_BIN = "bin/codecept";

  /**
   * Build the site into the document root, ensuring our custom code and settings
   * are able to be loaded by Drupal in either our local environment or on
   * Pantheon in production.
   *
   * @param string $env
   *
   * @return $this
   */
  public function build()
  {
    $this->taskMirrorDir([
        'src/modules' => 'docroot/modules/custom',
        'src/themes' => 'docroot/themes/custom',
    ])->run();
    $this->_copy('src/settings.php', 'docroot/sites/default/settings.php');
    $this->_touch(".built");
    return $this;
  }

  /**
   * Deploy site to Pantheon.
   */
  public function deploy()
  {
    // Get a copy of Pantheon's repository and clean it out.
    $this->taskGitStack()
         ->dir('../')
         ->cloneRepo(getenv('PANTHEON_REPO'), 'pantheon')
         ->run();
    $this->_cleanDir('../pantheon');
    // Put all our code in the repo.
    $this->_mirrorDir((__DIR__), '../pantheon');
    // Get rid of dev dependencies
    $this->taskComposerInstall()
         ->dir('../pantheon')
         ->preferDist()
         ->noDev()
         ->option('no-scripts')
         ->run();
    // Force commit and push the code.
    $sha = getenv('CIRCLE_SHA1');
    $username = getenv('CIRCLE_PROJECT_USERNAME');
    $this->taskGitStack()
         ->dir('../pantheon')
         ->add('-Af')
         ->commit("Successful verified merge of {$username} {$sha}.")
         ->exec('push origin master -f')
         ->run();
  }

  /**
   * Install Drupal using some assumed defaults.
   */
  public function install($env = "dev")
  {
    if (!file_exists(".built")) {
      $this->build($env);
    }
    $this->buildDrushTask()
         ->siteName('Rock Solid')
         ->siteMail('dustin@pantheon.io')
         ->locale('en')
         ->accountMail('dustin@pantheon.io')
         ->accountName('dustin')
         ->siteInstall('standard')
         ->run();
  }

  /**
   * Runs Codeception Test Suite.
   */
  public function test()
  {
    $this->taskCodecept(self::CODECEPT_BIN)->run();
  }

  /**
   * Set all of our defaults for Drush tasks so we don't have to repeat boilerplate.
   *
   * @return $this
   */
  private function buildDrushTask()
  {
    return $this->taskDrushStack($this::DRUSH_BIN)
                ->drupalRootDirectory((__DIR__) . '/docroot');
  }
}