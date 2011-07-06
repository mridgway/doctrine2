<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\CMS\CmsEmployee;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1256
 */
class DDC1256Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1256Flag'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1256FlagAwesome'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1256Balloon'),
            ));
        } catch(\PDOException $e) {

        }
    }

    public function testWithClauseIsOnCorrectJoin()
    {
        $balloon1 = new DDC1256Balloon(new DDC1256FlagAwesome(true));
        $balloon2 = new DDC1256Balloon(new DDC1256FlagAwesome(false));
        $this->_em->persist($balloon1);
        $this->_em->persist($balloon2);
        $this->_em->flush();

        $query = $this->_em->createQuery('SELECT b FROM ' . __NAMESPACE__ . '\\DDC1256Balloon b LEFT JOIN b.flag f WITH f.isTrue = 1');
        $sql = $query->getSQL();
        var_dump($sql);

        // join to flag should come before isTrue condition
        $this->assertLessThan(
            strpos($sql, 'LEFT JOIN DDC1256Flag'),
            strpos($sql, 'isTrue = 1'),
            'WITH condition is on wrong join'
        );

    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorMap({
 *  "Awesome" = "DDC1256FlagAwesome"
 * })
 */
class DDC1256Flag
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    public $id;

    /**
     * @var int
     * @Column(type="boolean")
     */
    public $isTrue = false;

    public function __construct($isTrue = true)
    {
        $this->isTrue = $isTrue;
    }
}

/**
 * @Entity
 */
class DDC1256FlagAwesome extends DDC1256Flag
{
    /**
     * @var int
     * @Column(type="integer")
     */
    public $awesomeLevel = 10;

    public function __construct($isTrue = true, $level = 10)
    {
        parent::__construct($isTrue);
        $this->awesomeLevel = $level;
    }
}

/**
 * @Entity
 */
class DDC1256Balloon
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    public $id;

    /**
     * @var DDC1256Flag
     * @OneToOne(targetEntity="DDC1256FlagAwesome", cascade={"persist"})
     */
    protected $flag;

    public function __construct(DDC1256Flag $flag)
    {
        $this->flag = $flag;
    }
}