<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Elasticsearch\Test\Unit\SearchAdapter\Query\Builder;

use Magento\Elasticsearch\SearchAdapter\Query\Builder\Match as MatchQueryBuilder;
use Magento\Framework\Search\Request\Query\Match as MatchRequestQuery;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Elasticsearch\Model\Adapter\FieldMapperInterface;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class MatchTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Tests that method constructs a correct select query.
     * @see MatchQueryBuilder::build
     *
     * @dataProvider queryValuesInvariantsProvider
     *
     * @param string $rawQueryValue
     * @param string $errorMessage
     */
    public function testBuild($rawQueryValue, $errorMessage)
    {
        /** @var MatchQueryBuilder $matchQueryBuilder */
        $matchQueryBuilder = (new ObjectManager($this))->getObject(
            MatchQueryBuilder::class,
            [
                'fieldMapper' => $this->getFieldMapper(),
                'preprocessorContainer' => [],
            ]
        );

        $this->assertSelectQuery(
            $matchQueryBuilder->build([], $this->getMatchRequestQuery($rawQueryValue), 'not'),
            $errorMessage
        );
    }

    /**
     * @link https://dev.mysql.com/doc/refman/5.7/en/fulltext-boolean.html Fulltext-boolean search docs.
     *
     * @return array
     */
    public function queryValuesInvariantsProvider()
    {
        return [
            ['query_value', 'Select query field must match simple raw query value.'],
            ['query_value+', 'Specifying a trailing plus sign causes InnoDB to report a syntax error.'],
            ['query_value-', 'Specifying a trailing minus sign causes InnoDB to report a syntax error.'],
            ['query_@value', 'The @ symbol is reserved for use by the @distance proximity search operator.'],
            ['query_value+@', 'The @ symbol is reserved for use by the @distance proximity search operator.'],
        ];
    }

    /**
     * @param array $selectQuery
     * @param string $errorMessage
     */
    private function assertSelectQuery($selectQuery, $errorMessage)
    {
        $expectedSelectQuery = [
            'bool' => [
                'must_not' => [
                    [
                        'match' => [
                            'some_field' => [
                                'query' => 'query_value',
                                'boost' => 43,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedSelectQuery, $selectQuery, $errorMessage);
    }

    /**
     * Gets fieldMapper mock object.
     *
     * @return FieldMapperInterface|MockObject
     */
    private function getFieldMapper()
    {
        $fieldMapper = $this->getMockBuilder(FieldMapperInterface::class)
            ->getMockForAbstractClass();

        $fieldMapper->method('getFieldName')
            ->with('some_field', ['type' => FieldMapperInterface::TYPE_QUERY])
            ->willReturnArgument(0);

        return $fieldMapper;
    }

    /**
     * Gets RequestQuery mock object.
     *
     * @param string $rawQueryValue
     * @return MatchRequestQuery|MockObject
     */
    private function getMatchRequestQuery($rawQueryValue)
    {
        $matchRequestQuery = $this->getMockBuilder(MatchRequestQuery::class)
            ->disableOriginalConstructor()
            ->getMock();

        $matchRequestQuery->method('getValue')
            ->willReturn($rawQueryValue);
        $matchRequestQuery->method('getMatches')
            ->willReturn([['field' => 'some_field', 'boost' => 42]]);

        return $matchRequestQuery;
    }
}
