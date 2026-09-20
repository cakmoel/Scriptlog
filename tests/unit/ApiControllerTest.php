<?php

/**
 * ApiController Unit Tests
 *
 * Tests for base API controller methods:
 * - getSorting() validation, whitelist enforcement, backtick quoting
 */

use Scriptlog\Controller\ApiController;

class ApiControllerTest extends \PHPUnit\Framework\TestCase
{
    public function getSortingDataProvider()
    {
        return [
            'valid sort_by with ASC' => [
                ['sort_by' => 'post_date', 'sort_order' => 'ASC'],
                ['ID', 'post_date', 'post_title'],
                '`post_date`',
                'ASC'
            ],
            'valid sort_by with DESC' => [
                ['sort_by' => 'ID', 'sort_order' => 'DESC'],
                ['ID', 'post_date'],
                '`ID`',
                'DESC'
            ],
            'invalid sort_by falls back to ID' => [
                ['sort_by' => 'password', 'sort_order' => 'ASC'],
                ['ID', 'post_date'],
                '`ID`',
                'ASC'
            ],
            'SQL injection attempt falls back to ID' => [
                ['sort_by' => 'ID; DROP TABLE tbl_posts;--', 'sort_order' => 'DESC'],
                ['ID', 'post_date'],
                '`ID`',
                'DESC'
            ],
            'invalid sort_order falls back to DESC' => [
                ['sort_by' => 'post_title', 'sort_order' => 'INVALID'],
                ['ID', 'post_title'],
                '`post_title`',
                'DESC'
            ],
            'empty allowedFields uses default sort_by but respects sort_order' => [
                ['sort_by' => 'post_date', 'sort_order' => 'ASC'],
                [],
                '`ID`',
                'ASC'
            ],
            'no sort params uses defaults' => [
                [],
                ['ID', 'post_date'],
                '`ID`',
                'DESC'
            ],
            'sort_order lowercase asc' => [
                ['sort_by' => 'ID', 'sort_order' => 'asc'],
                ['ID'],
                '`ID`',
                'ASC'
            ],
            'sort_by column with backtick in name falls back to ID' => [
                ['sort_by' => '`evil`', 'sort_order' => 'DESC'],
                ['ID'],
                '`ID`',
                'DESC'
            ],
        ];
    }

    /**
     * @dataProvider getSortingDataProvider
     */
    public function testGetSorting($params, $allowedFields, $expectedSortBy, $expectedSortOrder)
    {
        $controller = new class extends ApiController {
            public function __construct() {}

            public function callGetSorting($params, $allowedFields = [])
            {
                return $this->getSorting($params, $allowedFields);
            }
        };

        $result = $controller->callGetSorting($params, $allowedFields);

        $this->assertSame($expectedSortBy, $result['sort_by']);
        $this->assertSame($expectedSortOrder, $result['sort_order']);
    }

    public function testValidateContentTypeSkipsOnGetMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $controller = new class extends ApiController {
            public function __construct() {}
            public function callValidateContentType()
            {
                $this->validateContentType();
            }
        };
        $controller->callValidateContentType();
        $this->assertTrue(true);
    }

    public function testValidateContentTypeAcceptsJsonForPost()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $controller = new class extends ApiController {
            public function __construct() {}
            public function callValidateContentType()
            {
                $this->validateContentType();
            }
        };
        $controller->callValidateContentType();
        $this->assertTrue(true);
    }

    public function testValidateContentTypeAcceptsFormUrlencodedForPost()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        $controller = new class extends ApiController {
            public function __construct() {}
            public function callValidateContentType()
            {
                $this->validateContentType();
            }
        };
        $controller->callValidateContentType();
        $this->assertTrue(true);
    }

    public function testValidateContentTypeAcceptsEmptyContentType()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        unset($_SERVER['CONTENT_TYPE']);
        $controller = new class extends ApiController {
            public function __construct() {}
            public function callValidateContentType()
            {
                $this->validateContentType();
            }
        };
        $controller->callValidateContentType();
        $this->assertTrue(true);
    }
}
