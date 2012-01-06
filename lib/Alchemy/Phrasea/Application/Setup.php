<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use Alchemy\Phrasea\Controller\Setup as Controller;
use Alchemy\Phrasea\Controller\Utils as ControllerUtils;

require_once __DIR__ . '/../../../bootstrap.php';
/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
return call_user_func(function()
                {
                  $app = new Silex\Application();

                  $app['Core'] = bootstrap::getCore();

                  $app['install'] = false;
                  $app['upgrade'] = false;

                  $app->before(function($a) use ($app)
                          {
                            if (setup::is_installed())
                            {
                              $appbox = appbox::get_instance();

                              if (!$appbox->need_major_upgrade())
                              {
                                throw new Exception_Setup_PhraseaAlreadyInstalled();
                              }

                              $app['upgrade'] = true;
                            }
                            elseif (\setup::needUpgradeConfigurationFile())
                            {
                              //copy sample
                              $file = __DIR__ . "/../../../../config/config.sample.yml";
                              $file1 = __DIR__ . "/../../../../config/config.yml";
                              if (!copy($file, $file1))
                              {
                                throw new \Exception(sprintf("Unable to copy %s", $file1));
                              }

                              //get connexion credentials
                              $conn = \connection::getPDOConnection();
                              $credentials = $conn->get_credentials();

                              //get configuration object
                              $app = new \Alchemy\Phrasea\Core\Configuration\Application();
                              $parser = new \Alchemy\Phrasea\Core\Configuration\Parser\Yaml();
                              $handler = new \Alchemy\Phrasea\Core\Configuration\Handler($app, $parser);
                              $configuration = new \Alchemy\Phrasea\Core\Configuration($handler);

                              //refactor credentials
                              $connexionINI = array();
                              foreach ($credentials as $key => $value)
                              {
                                $key = $key == 'hostname' ? 'host' : $key;
                                $connexionINI[$key] = (string) $value;
                              }
                              //write credentials to config file
                              $configuration->setAllDatabaseConnexion($connexionINI);
                              $request = $app["request"];
                              //write servername
                              $serverName = $request->getScheme() . '://' . $request->getHttpHost() . '/';
                              $configuration->setServerName($serverName);

                              $app->redirect("/");
                            }
                            else
                            {
                              $app['install'] = true;
                            }

                            return;
                          });


                  $app->get('/', function() use ($app)
                          {
                            if ($app['install'] === true)
                              return $app->redirect('/setup/installer/');
                            if ($app['upgrade'] === true)
                              return $app->redirect('/setup/upgrader/');
                          });


                  $app->mount('/installer/', new Controller\Installer());
                  $app->mount('/upgrader/', new Controller\Upgrader());
                  $app->mount('/test', new ControllerUtils\PathFileTest());
                  $app->mount('/connection_test', new ControllerUtils\ConnectionTest());

                  $app->error(function($e) use ($app)
                          {
                            if ($e instanceof Exception_Setup_PhraseaAlreadyInstalled)
                              return $app->redirect('/login');

                            return new Response(
                                            sprintf(
                                                    'Error %s @%s:%s'
                                                    , $e->getFile()
                                                    , $e->getLine()
                                                    , $e->getMessage()
                                            )
                                            , 500
                            );
                          });

                  return $app;
                });