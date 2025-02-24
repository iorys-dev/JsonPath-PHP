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
    public function testFilterInArray()
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
    public function testFilterInArrayWithMultipleValues()
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
}
