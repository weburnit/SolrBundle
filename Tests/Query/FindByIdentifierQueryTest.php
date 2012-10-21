<?php

namespace FS\SolrBundle\Tests\Query;

use FS\SolrBundle\Query\FindByIdentifierQuery;

/**
 *  @group query
 */
class FindByIdentifierQueryTest extends \PHPUnit_Framework_TestCase {

	public function testGetQuery_SearchInAllFields() {
		$document = new \SolrInputDocument();
		$document->addField('id', '1');
		$document->addField('document_name_s', 'validtestentity');
	
		$expectedQuery = 'id:1';
		$query = new FindByIdentifierQuery($document);
	
		$filterQueries = $query->getSolrQuery()->getFilterQueries();
		
		$queryString = $query->getQueryString();
		
		$this->assertEquals($expectedQuery, $queryString);
		$this->assertEquals(1, count($filterQueries));
		$actualFilterQuery = array_pop($filterQueries);
		$this->assertEquals('document_name_s:validtestentity', $actualFilterQuery);
	}
	
}