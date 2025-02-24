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

namespace Galbar\JsonPath;

use JsonPath\InvalidJsonException;
use JsonPath\JsonObject;
use PHPUnit\Framework\TestCase;

/**
 * Class JsonObjectTest
 * @author Alessio Linares
 */
class JsonObjectContainsTest extends TestCase
{
    private $json = '
{
    "store": {
        "base": "USD",
        "models": [
          {
            "id": 105,
            "currencies": [
              "EUR"
            ]
          },
          {
            "id": 106,
            "currencies": [
              "GBP",
              "USD",
              "EUR"
            ]
          },
          {
            "id": 107,
            "currencies": [
              "USD"
            ]
          },
          {
            "id": 108,
            "currencies": [
              "GBP"
            ]
          },
          {
            "id": 109,
            "currencies": [
              "EUR",
              "PHP"
            ]
          }
        ]
    }
}
';

    /**
     * @throws InvalidJsonException
     */
    public function testFilterContains()
    {
        $jsonObject = new JsonObject(($this->json));
        $result = $jsonObject->get('$.store.models[?(@.currencies contains "USD")]');
        $this->assertEquals(
            [
                [
                    'id' => 106,
                    'currencies' => ['GBP', 'USD', 'EUR']
                ],
                [
                    'id' => 107,
                    'currencies' => ['USD']
                ],
            ],
            $result
        );
    }

    /**
     * @throws InvalidJsonException
     */
    public function testFilterContainsWithMultipleValues()
    {
        $jsonObject = new JsonObject(($this->json));
        $result = $jsonObject->get('$.store.models[?(@.currencies contains [$.store.base, "GBP"])]');
        $this->assertEquals(
            [
                [
                    'id' => 106,
                    'currencies' => ['GBP', 'USD', 'EUR']
                ],
                [
                    'id' => 107,
                    'currencies' => ['USD']
                ],
                [
                    'id' => 108,
                    'currencies' => ['GBP']
                ],
            ],
            $result
        );
    }

    public function testFilterContainsWithSingleValueFromArray()
    {
        $jsonObject = new JsonObject('{
            "store": [
                { "test": {"values": ["a", "b"]} },
                { "test": {"values": ["cav", "b"]} },
                { "test": {"values": ["c", "b"]} },
                { "test": {"values": ["d", "b"]} }
            ]
        }');
        $result = $jsonObject->get('$.store[?(@.test.values contains ["a"])]');
        $this->assertEquals(
            [
                [
                    'test' => ['values' => ['a', 'b']]
                ],
            ],
            $result
        );
    }

    public function testFilterContainsWithMultipleValuesFromArray()
    {
        $jsonObject = new JsonObject('{
            "store": [
                { "test": {"values": ["a", "b"]} },
                { "test": {"values": ["cav", "b"]} },
                { "test": {"values": ["c", "b"]} },
                { "test": {"values": ["d", "b"]} }
            ]
        }');
        $result = $jsonObject->get('$.store[?(@.test.values contains ["c", "d"])]');
        $this->assertEquals(
            [
                [
                    'test' => ['values' => ['c', 'b']]
                ],
                [
                    'test' => ['values' => ['d', 'b']]
                ],
            ],
            $result
        );
    }

    public function testFilterContainsScalar()
    {
        $jsonObject = new JsonObject('[{"test": [1,2,3,123,234,345]}, {"test": ["a","b","c"]}]');
        $result = $jsonObject->get('$[?(@.test contains 123)]');
        $this->assertEquals(
            [
                ['test' => [1, 2, 3, 123, 234, 345]],
            ],
            $result
        );
    }

    public function testFilterContainsFromArraySingle()
    {
        $jsonObject = new JsonObject('[{"test": [1,2,3,123,234,345]}, {"test": ["a","b","c"]}]');
        $result = $jsonObject->get('$[?(@.test contains [123])]');
        $this->assertEquals(
            [
                ['test' => [1, 2, 3, 123, 234, 345]],
            ],
            $result
        );
    }

    public function testFilterContainsFromArrayMultiple()
    {
        $jsonObject = new JsonObject('[{"test": [1,2,3,123,234,345]}, {"test": ["a","b","c"]}, {"test": ["a","b","c","d"]}]');
        $result = $jsonObject->get('$[?(@.test contains [123, "d"])]');
        $this->assertEquals(
            [
                ['test' => [1, 2, 3, 123, 234, 345]],
                ['test' => ['a', 'b', 'c', 'd']],
            ],
            $result
        );
    }

    public function testFilterContainsRegex(){
        $jsonObject = new JsonObject('{
            "store": [
                { "test": {"values": ["a", "b"]} },
                { "test": {"values": ["cav", "b"]} },
                { "test": {"values": ["c", "b"]} },
                { "test": {"values": ["d", "b"]} }
            ]
        }');
        $result = $jsonObject->get('$.store[?(@.test.values contains /a/)]');
        $this->assertEquals(
            [
                [
                    'test' => ['values' => ['a', 'b']]
                ],
                [
                    'test' => ['values' => ['cav', 'b']]
                ],
            ],
            $result
        );
    }
}
