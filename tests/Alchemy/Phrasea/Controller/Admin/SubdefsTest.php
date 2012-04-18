<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class ControllerSubdefsTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{

  /**
   * As controllers use WebTestCase, it requires a client
   */
  protected $client;

  /**
   * If the controller tests require some records, specify it her
   *
   * For example, this will loacd 2 records
   * (self::$record_1 and self::$record_2) :
   *
   * $need_records = 2;
   *
   */
  protected static $need_records = false;

  /**
   * The application loader
   */
  public function createApplication()
  {
    return require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Admin.php';
  }

  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();
  }

  /**
   * Default route test
   */
  public function testRouteGetSubdef()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());
    $databox = array_shift($appbox->get_databoxes());
    $this->client->request("GET", "/subdefs/" . $databox->get_sbas_id() . "/");
    $this->assertTrue($this->client->getResponse()->isOk());
  }

  public function testPostRouteAddSubdef()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());
    $databox = array_shift($appbox->get_databoxes());
    $this->client->request("POST", "/subdefs/" . $databox->get_sbas_id() . "/", array('add_subdef' => array(
            'class' => 'thumbnail',
            'name' => 'aname',
            'group' => 'image'
            )));
    $this->assertTrue($this->client->getResponse()->isRedirect());
    $subdefs = $databox->get_subdef_structure();
    $subdefs->get_subdef("image", "aname");
    $subdefs->delete_subdef('image', 'aname');
  }

  public function testPostRouteDeleteSubdef()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());
    $databox = array_shift($appbox->get_databoxes());
    $subdefs = $databox->get_subdef_structure();
    $subdefs->add_subdef("image", "name", "class");
    $this->client->request("POST", "/subdefs/" . $databox->get_sbas_id() . "/", array('delete_subdef' => 'group_name'));
    $this->assertTrue($this->client->getResponse()->isRedirect());
    try
    {
      $subdefs->get_subdef("image", "name");
      $this->fail("should raise an exception");
    }
    catch (\Exception $e)
    {

    }
  }

  public function testPostRouteAddSubdefWithNoParams()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());
    $databox = array_shift($appbox->get_databoxes());
    $subdefs = $databox->get_subdef_structure();
    $subdefs->add_subdef("image", "name", "class");
    $this->client->request("POST", "/subdefs/" . $databox->get_sbas_id() . "/", array('subdefs' => array(
            'image_name'
        )
        , 'image_name_class' => 'class'
        , 'image_name_downloadable' => 0
        , 'image_name_mediatype' => 'image'
        , 'image_name_image' => array(
            'size' => 400
            , 'resolution' => 83
            , 'strip' => 0
            , 'quality' => 90
            ))
    );
    $this->assertTrue($this->client->getResponse()->isRedirect());
    $subdef = $subdefs->get_subdef("image", "name");
    /* @var $subdef \databox_subdefAbstract */
    $this->assertFalse($subdef->is_downloadable());
    $options = $subdef->get_options();
    $this->assertEquals(400, $options["size"]);
    $this->assertEquals(83, $options["resolution"]);
    $this->assertEquals(90, $options["quality"]);
    $this->assertFalse($options["strip"]);
    $subdefs->delete_subdef("image", "name");
  }

}