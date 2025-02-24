<?php
/**
 * Copyright 2018 Alessio Linares
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Tests;

use JsonPath\InvalidJsonException;
use JsonPath\InvalidJsonPathException;
use JsonPath\JsonObject;
use PHPUnit\Framework\TestCase;

/**
 * Class JsonObjectTest
 * @author Alessio Linares
 */
class JsonObjectTest extends TestCase
{
    private $json = '
{ "store": {
    "book": [
      { "category": "reference",
        "author": "Nigel Rees",
        "title": "Sayings of the Century",
        "price": 8.95,
        "available": true
      },
      { "category": "fiction",
        "author": "Evelyn Waugh",
        "title": "Sword of Honour",
        "price": 12.99,
        "available": false
      },
      { "category": "fiction",
        "author": "Herman Melville",
        "title": "Moby Dick",
        "isbn": "0-553-21311-3",
        "price": 8.99,
        "available": true
      },
      { "category": "fiction",
        "author": "J. R. R. Tolkien",
        "title": "The Lord of the Rings",
        "isbn": "0-395-19395-8",
        "price": 22.99,
        "available": false
      }
    ],
    "bicycle": {
      "color": "red",
      "price": 19.95,
      "available": true,
      "model": null,
      "sku-number": "BCCLE-0001-RD"
    }
  },
  "authors": [
    "Nigel Rees",
    "Evelyn Waugh",
    "Herman Melville",
    "J. R. R. Tolkien"
  ],
  "Bike models": [
    1,
    2,
    3
  ]
}
';

    /**
     * testAdd
     *
     * @param  bool  $smartGet  smartGet
     *
     * @return void
     * @dataProvider testWithSmartGet
     */
    public function testAdd($smartGet)
    {
        $jsonObject = new JsonObject($this->json, $smartGet);
        $jsonObject->add('$.authors', 'Trudi Canavan');
        $this->assertEquals(["Nigel Rees", "Evelyn Waugh", "Herman Melville", "J. R. R. Tolkien", "Trudi Canavan"], $jsonObject->get('$.authors[*]'));

        $jsonObject->add('$.store.bicycle', 'BMX', 'type');
        $expected = [
            [
                'color' => 'red',
                'price' => 19.95,
                'type' => 'BMX',
                'available' => true,
                'model' => null,
                "sku-number" => "BCCLE-0001-RD"
            ]
        ];
        $expected = $smartGet ? $expected[0] : $expected;
        $this->assertEquals(
            $expected,
            $jsonObject->get('$.store.bicycle')
        );
    }

    /**
     * testConstructErrors
     *
     * @param  string  $jsonPath  jsonPath
     *
     * @return void
     * @dataProvider testConstructErrorsProvider
     */
    public function testConstructErrors($json, $message)
    {
        $exception = null;
        try {
            $jsonObject = new JsonObject($json);
        } catch (InvalidJsonException $e) {
            $exception = $e;
        }
        $this->assertEquals($exception->getMessage(), $message);
    }

    public function testConstructErrorsProvider()
    {
        return [
            [5, 'value does not encode a JSON object.'],
            ['{"invalid": json}', 'string does not contain a valid JSON object.']
        ];
    }

    /**
     * testGet
     *
     * @param  array  $expected  expected
     * @param  array  $jsonObject  jsonObject
     * @param  string  $jsonPath  jsonPath
     *
     * @return void
     *
     * @dataProvider testGetProvider
     */
    public function testGet($expected, $jsonPath, $testReference = true)
    {
        $jsonObject = new JsonObject($this->json);
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals($expected, $result);

        if ($result !== false && $testReference) {
            // Test that all elements in the result are
            // references to the contents in the object
            foreach ($result as &$element) {
                $element = 'NaN';
            }

            $result2 = $jsonObject->get($jsonPath);
            foreach ($result2 as &$element) {
                $this->assertEquals('NaN', $element);
            }
        }
    }

    public function testGetJson()
    {
        $jsonObject = new JsonObject();
        $jsonObject
            ->add('$', 41, 'size')
            ->add('$', 'black', 'color')
            ->add('$', [], 'meta')
            ->add('$.meta', 0xfe34, 'code');
        $this->assertEquals('{"size":41,"color":"black","meta":{"code":65076}}', $jsonObject->getJson());
    }

    public function testGetJsonObjects()
    {
        $jsonObject = new JsonObject($this->json);
        $childs = $jsonObject->getJsonObjects('$.store.book[*]');
        foreach ($childs as $key => $book) {
            $book->set('$.price', $key);
            $this->assertEquals($jsonObject->{'$.store.book[' . $key . ']'}[0], $book->getValue());
        }
        $this->assertEquals(4, count($childs));

        $jsonObject = new JsonObject($this->json, true);
        $bike = $jsonObject->getJsonObjects('$.store.bicycle');
        $bike->set('$.price', 412);
        $this->assertEquals($jsonObject->{'$.store.bicycle'}, $bike->getValue());

        $this->assertEquals(false, $jsonObject->getJsonObjects('$.abc'));
        $this->assertEquals([], $jsonObject->getJsonObjects('$[abc, 234f]'));
    }

    public function testGetJsonWithOptionsBitmask()
    {
        $jsonObject = new JsonObject();
        $jsonObject
            ->add('$', 'Ö Kent C. Dodds', 'author')
            ->add('$', 'À First Timers Only', 'title')
            ->add('$', [], 'volunteers')
            ->add('$.volunteers[0]', 'Fayçal', 'name');
        $expectedJson = <<<EOF
{
    "author": "Ö Kent C. Dodds",
    "title": "À First Timers Only",
    "volunteers": [
        {
            "name": "Fayçal"
        }
    ]
}
EOF;
        $this->assertEquals($expectedJson, $jsonObject->getJson(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public function testGetJsonWithoutOptionsBitmask()
    {
        $jsonObject = new JsonObject();
        $jsonObject
            ->add('$', 'Ö Kent C. Dodds', 'author')
            ->add('$', 'À First Timers Only', 'title')
            ->add('$', [], 'volunteers')
            ->add('$.volunteers[0]', 'Fayçal', 'name');
        $expectedJson = '{"author":"\u00d6 Kent C. Dodds","title":"\u00c0 First Timers Only","volunteers":[{"name":"Fay\u00e7al"}]}';
        $this->assertEquals($expectedJson, $jsonObject->getJson());
    }

    public function testGetProvider()
    {
        return [
            [
                json_decode(
                    '[
                    {"category": "reference",
                    "author": "Nigel Rees",
                    "title": "Sayings of the Century",
                    "price": 8.95,
                    "available": true
                    },
                    {"category": "fiction",
                    "author": "Herman Melville",
                    "title": "Moby Dick",
                    "isbn": "0-553-21311-3",
                    "price": 8.99,
                    "available": true},
                    {"category": "fiction",
                    "author": "J. R. R. Tolkien",
                    "title": "The Lord of the Rings",
                    "isbn": "0-395-19395-8",
                    "price": 22.99,
                    "available": false}
                ]',
                    true
                ),
                "$.store.book[-4, -2, -1]"
            ],
            [
                [19.95],
                "$.store.bicycle.price"
            ],
            [
                ["BCCLE-0001-RD"],
                "$.store.bicycle.sku-number"
            ],
            [
                [
                    [
                        "color" => "red",
                        "price" => 19.95,
                        "available" => true,
                        "model" => null,
                        "sku-number" => "BCCLE-0001-RD"
                    ]
                ],
                "$.store.bicycle"
            ],
            [
                [],
                "$.store.bicycl"
            ],
            [
                [
                    8.95,
                    12.99,
                    8.99,
                    22.99
                ],
                "$.store.book[*].price"
            ],
            [
                [],
                "$.store.book[7]"
            ],
            [
                [
                    12.99,
                    8.99
                ],
                "$.store.book[1, 2].price"
            ],
            [
                [
                    'reference',
                    'Nigel Rees',
                    'fiction',
                    'Evelyn Waugh',
                    'fiction',
                    'Herman Melville',
                    'fiction',
                    'J. R. R. Tolkien'
                ],
                "$.store.book[*][category, author]"
            ],
            [
                [
                    'reference',
                    'Nigel Rees',
                    'fiction',
                    'Evelyn Waugh',
                    'fiction',
                    'Herman Melville',
                    'fiction',
                    'J. R. R. Tolkien'
                ],
                "$.store.book[*]['category', \"author\"]"
            ],
            [
                [
                    8.95,
                    8.99
                ],
                "$.store.book[0:3:2].price"
            ],
            [
                [
                    8.95,
                    12.99
                ],
                "$.store.book[:2].price"
            ],
            [
                [],
                "$.store.bicycle.price[2]"
            ],
            [
                [],
                "$.store.bicycle.price.*"
            ],
            [
                [
                    "red",
                    19.95,
                    true,
                    null,
                    "BCCLE-0001-RD"
                ],
                "$.store.bicycle.*"
            ],
            [
                [
                    19.95,
                    8.95,
                    12.99,
                    8.99,
                    22.99
                ],
                "$..*.price"
            ],
            [
                [
                    12.99,
                    8.99,
                    22.99
                ],
                "$.store.book[?(@.category == 'fiction')].price"
            ],
            [
                [
                    19.95,
                    8.95,
                    8.99
                ],
                "$..*[?(@.available == true)].price"
            ],
            [
                [
                    12.99,
                    22.99
                ],
                "$..*[?(@.available == false)].price"
            ],
            [
                [
                    "Sayings of the Century",
                    "Moby Dick"
                ],
                "$..*[?(@.price < 10)].title"
            ],
            [
                [
                    "Sayings of the Century",
                    "Moby Dick"
                ],
                "$..*[?(@.price < 10.0)].title"
            ],
            [
                [
                    "Sword of Honour",
                    "The Lord of the Rings"
                ],
                "$.store.book[?(@.price > 10)].title"
            ],
            [
                [
                    "The Lord of the Rings"
                ],
                "$..*[?(@.author =~ /.*Tolkien/)].title"
            ],
            [
                [
                    "The Lord of the Rings"
                ],
                "$..*[?(@.author =~ /.*tolkien/i)].title"
            ],
            [
                [
                    "The Lord of the Rings"
                ],
                "$..*[?(@.author =~ /  J.\ R.\ R.\ Tolkien  /x)].title"
            ],
            [
                [
                    "red"
                ],
                "$..*[?(@.length <= 5)].color"
            ],
            [
                [
                    "red"
                ],
                "$..*[?(@.length <= 5.0)].color"
            ],
            [
                [
                    "The Lord of the Rings"
                ],
                "$.store.book[?(@.author == $.authors[3])].title"
            ],
            [
                [
                    "red",
                    "J. R. R. Tolkien"
                ],
                "$..*[?(@.price >= 19.95)][author, color]"
            ],
            [
                [
                    19.95,
                    8.99
                ],
                "$..*[?(@.category == 'fiction' and @.price < 10 or @.color == \"red\")].price"
            ],
            [
                [
                    19.95,
                    8.99
                ],
                "$..*[?(@.category == 'fiction' && @.price < 10 || @.color == \"red\")].price"
            ],
            [
                [
                    8.95
                ],
                "$.store.book[?(not @.category == 'fiction')].price"
            ],
            [
                [
                    8.95
                ],
                "$.store.book[?(! @.category == 'fiction')].price"
            ],
            [
                [
                    8.95
                ],
                "$.store.book[?(@.category != 'fiction')].price"
            ],
            [
                [
                    "red"
                ],
                "$..*[?(@.color)].color"
            ],
            [
                [
                    true
                ],
                "$.store[?(not @..price or @..color == 'red')].available"
            ],
            [
                [
                    true
                ],
                "$.store[?(! @..price or @..color == 'red')].available"
            ],
            [
                [
                    true
                ],
                "$.store[?(not @..price || @..color == 'red')].available"
            ],
            [
                [
                    true
                ],
                "$.store[?(! @..price || @..color == 'red')].available"
            ],
            [
                [],
                "$.store[?(@.price.length == 3)]"
            ],
            [
                [
                    19.95
                ],
                "$.store[?(@.color.length == 3)].price"
            ],
            [
                [],
                "$.store[?(@.color.length == 5)].price"
            ],
            [
                [
                    [
                        "color" => "red",
                        "price" => 19.95,
                        "available" => true,
                        "model" => null,
                        "sku-number" => "BCCLE-0001-RD"
                    ]
                ],
                "$.store[?(@.*.length == 3)]",
                false
            ],
            [
                [
                    "red"
                ],
                "$.store..[?(@..model == null)].color"
            ],
            [
                [
                    [1, 2, 3]
                ],
                "$['Bike models']"
            ],
            [
                [
                    [1, 2, 3]
                ],
                '$["Bike models"]'
            ]
        ];
    }

    public function testGetValue()
    {
        $array = json_decode($this->json, true);
        $jsObject = new JsonObject($array);
        $this->assertEquals($array, $jsObject->getValue());
        $jsObject = new JsonObject($this->json);
        $this->assertEquals($array, $jsObject->getValue());
        $object = json_decode($this->json);
        $jsObject = new JsonObject($object);
        $this->assertEquals($array, $jsObject->getValue());
    }

    public function testMagickMethods()
    {
        $jsonObject = new JsonObject($this->json);
        $this->assertEquals(['0-553-21311-3', '0-395-19395-8'], $jsonObject->{'$..*.isbn'});
        $jsonObject->{'$.store.bicycle.color'} = 'green';
        $this->assertEquals(['green'], $jsonObject->{'$.store.bicycle.color'});
        $jsonObject = new JsonObject();
        $jsonObject
            ->add('$', 41, 'size')
            ->add('$', 'black', 'color')
            ->add('$', [], 'meta')
            ->add('$.meta', 0xfe34, 'code');
        $this->assertEquals('{"size":41,"color":"black","meta":{"code":65076}}', (string) $jsonObject);
    }

    public function testNegativeIndexOnEmptyArray()
    {
        $object = new JsonObject('{"data": []}');
        $this->assertEquals([], $object->get('$.data[-1]'));

        $object = new JsonObject('{"data": [{"id": 1},{"id": 2}]}');
        $this->assertEquals([], $object->get('$.data[-5].id'));

        $object = new JsonObject('{"data": [{"id": 1}]}');
        $this->assertEquals($object->get('$.data[-1].id'), [1]);

        $object = new JsonObject('{"data": [{"id": 1},{"id": 2}]}');
        $this->assertEquals($object->get('$.data[-1].id'), [2]);

        $object = new JsonObject('{"data": []}');
        $this->assertEquals([], $object->get('$.data[1].id'));

        $object = new JsonObject('{"data": [{"id": 1},{"id": 2}]}');
        $this->assertEquals([], $object->get('$.data[3].id'));
    }

    /**
     * testParsingErrors
     *
     * @param  string  $jsonPath  jsonPath
     *
     * @return void
     * @dataProvider testParsingErrorsProvider
     */
    public function testParsingErrors($jsonPath, $token)
    {
        $jsonObject = new JsonObject($this->json);
        $exception = null;
        try {
            $jsonObject->get($jsonPath);
        } catch (InvalidJsonPathException $e) {
            $exception = $e;
        }
        $this->assertEquals($exception->getMessage(), "Error in JSONPath near '" . $token . "'");
    }

    public function testParsingErrorsProvider()
    {
        return [
            ['$[store', '[store'],
            ['$[{fail}]', '{fail}'],
            ['a.bc', 'a.bc'],
            ["$.store.book[?(@.title in ['foo']])]", "[?(@.title in ['foo']])]"],
            ["$.store.book[?(@.title in [['foo'])]", "[?(@.title in [['foo'])]"],
            ["$.store.book[?(@.title in ['foo')]", "[?(@.title in ['foo')]"],
            ["$.store.book[?(@.title in 'foo'])]", "[?(@.title in 'foo'])]"],
            ["$.store.book[?(@.title in 'foo')]", " in 'foo'"],
            ["$.store.book[?(@.title ['foo'])]", " ['foo']"]
        ];
    }

    /**
     * testRemove
     *
     * @param  bool  $smartGet  smartGet
     *
     * @return void
     * @dataProvider testWithSmartGet
     */
    public function testRemove($smartGet)
    {
        $jsonObject = new JsonObject($this->json, $smartGet);
        $jsonObject->remove('$..*[?(@.price)]', 'price')->remove('$..*', 'available');
        $jsonObject->remove('$', 'Bike models');
        $this->assertEquals(
            json_decode(
                '{ "store": {
    "book": [
      { "category": "reference",
        "author": "Nigel Rees",
        "title": "Sayings of the Century"
      },
      { "category": "fiction",
        "author": "Evelyn Waugh",
        "title": "Sword of Honour"
      },
      { "category": "fiction",
        "author": "Herman Melville",
        "title": "Moby Dick",
        "isbn": "0-553-21311-3"
      },
      { "category": "fiction",
        "author": "J. R. R. Tolkien",
        "title": "The Lord of the Rings",
        "isbn": "0-395-19395-8"
      }
    ],
    "bicycle": {
      "color": "red",
      "model": null,
      "sku-number": "BCCLE-0001-RD"
    }
  },
  "authors": [
    "Nigel Rees",
    "Evelyn Waugh",
    "Herman Melville",
    "J. R. R. Tolkien"
  ]
}'
                ,
                true
            )
            ,
            $jsonObject->getValue()
        );
    }

    /**
     * testSet
     *
     * @param  bool  $smartGet  smartGet
     *
     * @return void
     * @dataProvider testWithSmartGet
     */
    public function testSet($smartGet)
    {
        $jsonObject = new JsonObject($this->json, $smartGet);
        $jsonObject->set('$.authors', ["Patrick Rothfuss", "Trudi Canavan"]);
        $this->assertEquals(["Patrick Rothfuss", "Trudi Canavan"], $jsonObject->get('$.authors[*]'));
        $jsonObject->set('$.store.car[0,1].type', 'sport');
        $jsonObject->set('$.store[pen, pencil].price', 0.99);
        $this->assertEquals(
            [
                0.99,
                0.99
            ],
            $jsonObject->get('$.store[pen, pencil].price')
        );
        if ($smartGet) {
            $this->assertEquals(
                [
                    [
                        'type' => 'sport'
                    ],
                    [
                        'type' => 'sport'
                    ]
                ],
                $jsonObject->get('$.store.car')
            );
        } else {
            $this->assertEquals(
                [
                    [
                        [
                            'type' => 'sport'
                        ],
                        [
                            'type' => 'sport'
                        ]
                    ]
                ],
                $jsonObject->get('$.store.car')
            );
        }
    }

    /**
     * testSmartGet
     *
     * @param  array  $expected  expected
     * @param  string  $jsonPath  jsonPath
     *
     * @return void
     *
     * @dataProvider testSmartGetProvider
     */
    public function testSmartGet($expected, $jsonPath)
    {
        $jsonObject = new JsonObject($this->json, true);
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals($expected, $result);
    }

    public function testSmartGetProvider()
    {
        return [
            [
                19.95,
                "$.store.bicycle.price"
            ],
            [
                [
                    "color" => "red",
                    "price" => 19.95,
                    "available" => true,
                    "model" => null,
                    "sku-number" => "BCCLE-0001-RD"
                ],
                "$.store.bicycle"
            ],
            [
                false,
                "$.store.bicycl"
            ],
            [
                [
                    8.95,
                    12.99,
                    8.99,
                    22.99
                ],
                "$.store.book[*].price"
            ],
            [
                false,
                "$.store.book[7]"
            ],
            [
                [],
                "$.store.book[7, 9]"
            ],
            [
                [
                    12.99,
                    8.99
                ],
                "$.store.book[1, 2].price"
            ],
            [
                [
                    'reference',
                    'Nigel Rees',
                    'fiction',
                    'Evelyn Waugh',
                    'fiction',
                    'Herman Melville',
                    'fiction',
                    'J. R. R. Tolkien'
                ],
                "$.store.book[*][category, author]"
            ],
            [
                [
                    'reference',
                    'Nigel Rees',
                    'fiction',
                    'Evelyn Waugh',
                    'fiction',
                    'Herman Melville',
                    'fiction',
                    'J. R. R. Tolkien'
                ],
                "$.store.book[*]['category', \"author\"]"
            ],
            [
                [
                    8.95,
                    8.99
                ],
                "$.store.book[0:3:2].price"
            ],
            [
                false,
                "$.store.bicycle.price[2]"
            ],
            [
                [],
                "$.store.bicycle.price.*"
            ],
            [
                [
                    "red",
                    19.95,
                    true,
                    null,
                    "BCCLE-0001-RD"
                ],
                "$.store.bicycle.*"
            ],
            [
                [
                    19.95,
                    8.95,
                    12.99,
                    8.99,
                    22.99
                ],
                "$..*.price"
            ],
            [
                [
                    12.99,
                    8.99,
                    22.99
                ],
                "$.store.book[?(@.category == 'fiction')].price"
            ],
            [
                [
                    19.95,
                    8.95,
                    8.99
                ],
                "$..*[?(@.available == true)].price"
            ],
            [
                [
                    12.99,
                    22.99
                ],
                "$..*[?(@.available == false)].price"
            ],
            [
                [
                    "Sayings of the Century",
                    "Moby Dick"
                ],
                "$..*[?(@.price < 10)].title"
            ],
            [
                [
                    "Sayings of the Century",
                    "Moby Dick"
                ],
                "$..*[?(@.price < 10.0)].title"
            ],
            [
                [
                    "Sword of Honour",
                    "The Lord of the Rings"
                ],
                "$.store.book[?(@.price > 10)].title"
            ],
            [
                [
                    "The Lord of the Rings"
                ],
                "$..*[?(@.author =~ /.*Tolkien/)].title"
            ],
            [
                [
                    "red"
                ],
                "$..*[?(@.length <= 5)].color"
            ],
            [
                [
                    "red"
                ],
                "$..*[?(@.length <= 5.0)].color"
            ],
            [
                [
                    "The Lord of the Rings"
                ],
                "$.store.book[?(@.author == $.authors[3])].title"
            ],
            [
                [
                    "red",
                    "J. R. R. Tolkien"
                ],
                "$..*[?(@.price >= 19.95)][author, color]"
            ],
            [
                [
                    19.95,
                    8.99
                ],
                "$..*[?(@.category == 'fiction' and @.price < 10 or @.color == \"red\")].price"
            ],
            [
                [
                    19.95,
                    8.99
                ],
                "$..*[?(@.category == 'fiction' && @.price < 10 || @.color == \"red\")].price"
            ],
            [
                [
                    8.95
                ],
                "$.store.book[?(not @.category == 'fiction')].price"
            ],
            [
                [
                    8.95
                ],
                "$.store.book[?(! @.category == 'fiction')].price"
            ],
            [
                [
                    8.95
                ],
                "$.store.book[?(@.category != 'fiction')].price"
            ],
            [
                [
                    "red"
                ],
                "$..*[?(@.color)].color"
            ],
            [
                [
                    true
                ],
                "$.store[?(not @..price or @..color == 'red')].available"
            ],
            [
                [
                    true
                ],
                "$.store[?(! @..price or @..color == 'red')].available"
            ],
            [
                [
                    true
                ],
                "$.store[?(not @..price || @..color == 'red')].available"
            ],
            [
                [
                    true
                ],
                "$.store[?(! @..price || @..color == 'red')].available"
            ],
            [
                [],
                "$.store[?(@.price.length == 3)]"
            ],
            [
                [
                    19.95
                ],
                "$.store[?(@.color.length == 3)].price"
            ],
            [
                [],
                "$.store[?(@.color.length == 5)].price"
            ],
            [
                [
                    [
                        "color" => "red",
                        "price" => 19.95,
                        "available" => true,
                        "model" => null,
                        "sku-number" => "BCCLE-0001-RD"
                    ]
                ],
                "$.store[?(@.*.length == 3)]",
                false
            ],
            [
                [
                    "red"
                ],
                "$.store..[?(@..model == null)].color"
            ],
            [
                [1, 2, 3],
                "$['Bike models']"
            ],
            [
                [1, 2, 3],
                '$["Bike models"]'
            ],
            [
                [
                    [
                        "category" => "fiction",
                        "author" => "Evelyn Waugh",
                        "title" => "Sword of Honour",
                        "price" => 12.99,
                        "available" => false
                    ],
                    [
                        "category" => "fiction",
                        "author" => "J. R. R. Tolkien",
                        "isbn" => "0-395-19395-8",
                        "title" => "The Lord of the Rings",
                        "price" => 22.99,
                        "available" => false
                    ]
                ],
                "$.store.book[?(@.title in ['Sword of Honour', 'The Lord of the Rings'])]",
            ],
            [
                [
                    [
                        "category" => "reference",
                        "author" => "Nigel Rees",
                        "title" => "Sayings of the Century",
                        "price" => 8.95,
                        "available" => true,
                    ],
                    [
                        "category" => "fiction",
                        "author" => "Evelyn Waugh",
                        "title" => "Sword of Honour",
                        "price" => 12.99,
                        "available" => false,
                    ],
                    [
                        "category" => "fiction",
                        "author" => "J. R. R. Tolkien",
                        "title" => "The Lord of the Rings",
                        "isbn" => "0-395-19395-8",
                        "price" => 22.99,
                        "available" => false,
                    ],
                ],
                "$.store.book[?(@.author == 'Nigel Rees' or @.title in ['Sword of Honour', 'The Lord of the Rings'])]",
            ],
            [
                [
                    [
                        "category" => "fiction",
                        "author" => "Herman Melville",
                        "title" => "Moby Dick",
                        "isbn" => "0-553-21311-3",
                        "price" => 8.99,
                        "available" => true,
                    ]
                ],
                "$.store.book[?(@.isbn in ['0-553-21311-3', '0-395-19395-8'] and @.available == true)]"
            ],
            [
                [
                    [
                        "category" => "reference",
                        "author" => "Nigel Rees",
                        "title" => "Sayings of the Century",
                        "price" => 8.95,
                        "available" => true,
                    ],
                    [
                        "category" => "fiction",
                        "author" => "Herman Melville",
                        "title" => "Moby Dick",
                        "isbn" => "0-553-21311-3",
                        "price" => 8.99,
                        "available" => true,
                    ]
                ],
                "$.store.book[?(not @.title in ['Sword of Honour', 'The Lord of the Rings'])]"
            ],
            [
                [
                    [
                        "category" => "fiction",
                        "author" => "Evelyn Waugh",
                        "title" => "Sword of Honour",
                        "price" => 12.99,
                        "available" => false,
                    ]
                ],
                "$.store.book[?(not @.isbn in ['0-553-21311-3', '0-395-19395-8'] and @.available == false)]"
            ],
            [
                [
                    [
                        "category" => "reference",
                        "author" => "Nigel Rees",
                        "title" => "Sayings of the Century",
                        "price" => 8.95,
                        "available" => true,
                    ],
                    [
                        "category" => "fiction",
                        "author" => "Evelyn Waugh",
                        "title" => "Sword of Honour",
                        "price" => 12.99,
                        "available" => false,
                    ]
                ],
                "$.store.book[?(@.price in [12.99, 8.95])]"
            ],
            [
                [
                    [
                        "category" => "reference",
                        "author" => "Nigel Rees",
                        "title" => "Sayings of the Century",
                        "price" => 8.95,
                        "available" => true,
                    ],
                    [
                        "category" => "fiction",
                        "author" => "Herman Melville",
                        "title" => "Moby Dick",
                        "isbn" => "0-553-21311-3",
                        "price" => 8.99,
                        "available" => true,
                    ]
                ],
                "$.store.book[?(@.available in [ true ])]"
            ],
            [
                [
                    [
                        "category" => "reference",
                        "author" => "Nigel Rees",
                        "title" => "Sayings of the Century",
                        "price" => 8.95,
                        "available" => true,
                    ],
                    [
                        "category" => "fiction",
                        "author" => "Herman Melville",
                        "title" => "Moby Dick",
                        "isbn" => "0-553-21311-3",
                        "price" => 8.99,
                        "available" => true,
                    ]
                ],
                "$..book[?(@.author in [$.authors[0], $.authors[2]])]"
            ]
        ];
    }

    // Bug when using negative index triggers DivisionByZeroError
    // https://github.com/Galbar/JsonPath-PHP/issues/60

    public function testWithSmartGet()
    {
        return [
            [true],
            [false]
        ];
    }
}
